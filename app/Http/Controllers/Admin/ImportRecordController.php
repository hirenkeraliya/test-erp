<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\CommonFunctions;
use App\Domains\Company\CompanyQueries;
use App\Domains\Company\Enums\CommissionTypes;
use App\Domains\ImportRecord\DataObjects\ImportRecordData;
use App\Domains\ImportRecord\Enums\ImportTypes;
use App\Domains\ImportRecord\Enums\Status;
use App\Domains\ImportRecord\Exports\ImportRecordExport;
use App\Domains\ImportRecord\ImportRecordQueries;
use App\Domains\ImportRecord\Jobs\ImportRecordsJob;
use App\Domains\ImportRecord\Jobs\ProductBulkMediaUploadJob;
use App\Domains\ImportRecord\Resources\AdminImportRecordListResource;
use App\Domains\ImportRecord\Services\ImportRecordService;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\Product\Enums\ProductUploadTypes;
use App\Domains\Product\Exports\ProductPriceUpdateExport;
use App\Exceptions\RedirectBackWithErrorException;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class ImportRecordController extends Controller
{
    public function __construct(
        protected ImportRecordQueries $importRecordQueries
    ) {
    }

    public function index(?int $id = null): Response
    {
        return Inertia::render('import_records/Index', [
            'importRecordId' => $id,
            'importTypes' => ImportTypes::getList(),
            'statuses' => Status::getList(),
            'staticStatuses' => Status::getStatuses(),
            'exportPermission' => PermissionList::getExportPermissionName('import_record'),
        ]);
    }

    /**
     * @return array<string, AnonymousResourceCollection>|array<string, int>
     */
    public function fetchImportRecords(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'import_record_id' => $request->get('import_record_id'),
            'status' => $request->get('status'),
            'date_range' => $request->get('date_range'),
            'import_type' => $request->get('import_type'),
        ];

        $lengthAwarePaginator = $this->importRecordQueries->listQuery($filterData, session('admin_company_id'));

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => AdminImportRecordListResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function create(): Response
    {
        $companyQueries = resolve(CompanyQueries::class);
        $promoterCommissionType = $companyQueries->getByIdWithPromoterCommissionDetails(session('admin_company_id'));

        $importTypeDetails = [
            'products' => ImportTypes::PRODUCTS->value,
            'members_bulk_update' => ImportTypes::MEMBERS_BULK_UPDATE->value,
            'members' => ImportTypes::MEMBERS->value,
            'product_price_bulk_update' => ImportTypes::PRODUCT_PRICE_BULK_UPDATE->value,
            'employees' => ImportTypes::EMPLOYEES->value,
            'counters' => ImportTypes::COUNTERS->value,
            'payment_types' => ImportTypes::PAYMENT_TYPES->value,
            'product_bulk_update' => ImportTypes::PRODUCT_BULK_UPDATE->value,
            'add_member_loyalty_points' => ImportTypes::ADD_MEMBER_LOYALTY_POINTS->value,
            'update_member_loyalty_points' => ImportTypes::UPDATE_MEMBER_LOYALTY_POINTS->value,
            'promoters' => ImportTypes::PROMOTERS->value,
            'color_groups' => ImportTypes::COLOR_GROUPS->value,
            'regions' => ImportTypes::REGIONS->value,
            'vendors' => ImportTypes::VENDORS->value,
            'size_groups' => ImportTypes::SIZE_GROUPS->value,
            'sizes' => ImportTypes::SIZES->value,
            'colors' => ImportTypes::COLORS->value,
            'cashiers' => ImportTypes::CASHIERS->value,
            'store_managers' => ImportTypes::STORE_MANAGERS->value,
            'employees_bulk_update' => ImportTypes::EMPLOYEES_BULK_UPDATE->value,
            'categories' => ImportTypes::CATEGORIES->value,
            'product_bulk_image_upload' => ImportTypes::PRODUCT_BULK_IMAGE_UPLOAD->value,
            'bulk_product_merge' => ImportTypes::BULK_PRODUCT_MERGE->value,
            'set_product_loyalty_points' => ImportTypes::SET_PRODUCT_LOYALTY_POINTS->value,
            'set_product_box_units' => ImportTypes::SET_PRODUCT_BOX_UNITS->value,
            'member_address' => ImportTypes::MEMBER_ADDRESS->value,
            'payment_type_bulk_update' => ImportTypes::PAYMENT_TYPE_BULK_UPDATE->value,
            'counter_bulk_update' => ImportTypes::COUNTER_BULK_UPDATE->value,
            'color_group_bulk_update' => ImportTypes::COLOR_GROUP_BULK_UPDATE->value,
            'promoter_bulk_update' => ImportTypes::PROMOTER_BULK_UPDATE->value,
            'vendor_bulk_update' => ImportTypes::VENDOR_BULK_UPDATE->value,
            'regions_bulk_update' => ImportTypes::REGIONS_BULK_UPDATE->value,
            'store_manager_bulk_update' => ImportTypes::STORE_MANAGER_BULK_UPDATE->value,
            'size_bulk_update' => ImportTypes::SIZE_BULK_UPDATE->value,
            'size_group_bulk_update' => ImportTypes::SIZE_GROUP_BULK_UPDATE->value,
            'color_bulk_update' => ImportTypes::COLOR_BULK_UPDATE->value,
            'category_bulk_update' => ImportTypes::CATEGORY_BULK_UPDATE->value,
            'cashier_bulk_update' => ImportTypes::CASHIER_BULK_UPDATE->value,
            'cashier_groups' => ImportTypes::CASHIER_GROUPS->value,
            'cashier_groups_bulk_update' => ImportTypes::CASHIER_GROUPS_BULK_UPDATE->value,
            'locations' => ImportTypes::LOCATIONS->value,
            'location_bulk_update' => ImportTypes::LOCATION_BULK_UPDATE->value,
        ];

        return Inertia::render('import_records/Manage', [
            'productUploadTypes' => ProductUploadTypes::getList(),
            'productUploadTypeDetails' => ProductUploadTypes::getFormattedArrayForStaticUse(),
            'importTypeDetails' => $importTypeDetails,
            'promoterCommissionType' => $promoterCommissionType->commission_type_id->value,
            'commissionType' => CommissionTypes::BY_PROMOTER->value,
            'exportPermission' => PermissionList::getExportPermissionName('import_record'),
            ...$this->preparedImportTypes(),
        ]);
    }

    public function store(ImportRecordData $importRecordData, Request $request): RedirectResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();

        $allPermissionLists = $admin->roles->pluck('permissions')->collapse()->pluck('name')->toArray();

        $importRecordService = resolve(ImportRecordService::class);
        if ($importRecordData->type_id !== ImportTypes::PRODUCT_BULK_IMAGE_UPLOAD->value) {
            $importRecordService->validateColumns(
                $importRecordData->upload_file,
                $allPermissionLists,
                session('admin_company_id'),
                $importRecordData->type_id
            );
        }

        DB::beginTransaction();

        try {
            $importRecord = $this->importRecordQueries->addNew(
                $importRecordData,
                $admin,
                session('admin_company_id'),
                null,
            );

            DB::commit();
            if ($importRecordData->type_id === ImportTypes::PRODUCT_BULK_IMAGE_UPLOAD->value) {
                ProductBulkMediaUploadJob::dispatch(
                    $importRecord,
                    (int) $importRecordData->product_upload_type_id
                )->onQueue('medium');
            } else {
                ImportRecordsJob::dispatch($importRecord)->onQueue('high');
            }

            return to_route('admin.import_records.index')
                ->with(
                    'success',
                    'File uploaded successfully. The import process will occur in the background. We will notify you by email once the import is complete.'
                );
        } catch (Throwable $throwable) {
            Log::error('Import Record', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            DB::rollBack();

            throw new RedirectBackWithErrorException('An error occurred. Please try again.');
        }
    }

    public function exportImportRecords(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'import_record_id' => $request->get('import_record_id'),
            'status' => $request->get('status'),
            'date_range' => $request->get('date_range'),
            'import_type' => $request->get('import_type'),
        ];

        $importRecords = $this->importRecordQueries->getImportRecordExport(
            $filterData,
            session('admin_company_id')
        );

        return Excel::download(new ImportRecordExport($importRecords), $filename);
    }

    public function getPendingImportRecordCount(string $moduleType): array
    {
        return [
            'pending_counts' => $this->importRecordQueries->getPendingImportRecordCount(
                $moduleType,
                session('admin_company_id')
            ),
        ];
    }

    public function exportProductPriceUpdate(Request $request, string $filename): BinaryFileResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();

        $allPermissionLists = $admin->roles->pluck('permissions')->collapse()->pluck('name')->toArray();

        return Excel::download(new ProductPriceUpdateExport($allPermissionLists), $filename);
    }

    private function preparedImportTypes(): array
    {
        $groupImportTypeDetails = [
            'products' => ImportTypes::PRODUCTS->value,
            'members' => ImportTypes::MEMBERS->value,
            'employees' => ImportTypes::EMPLOYEES->value,
            'counters' => ImportTypes::COUNTERS->value,
            'payment_types' => ImportTypes::PAYMENT_TYPES->value,
            'promoters' => ImportTypes::PROMOTERS->value,
            'color_groups' => ImportTypes::COLOR_GROUPS->value,
            'regions' => ImportTypes::REGIONS->value,
            'vendors' => ImportTypes::VENDORS->value,
            'size_groups' => ImportTypes::SIZE_GROUPS->value,
            'sizes' => ImportTypes::SIZES->value,
            'colors' => ImportTypes::COLORS->value,
            'cashiers' => ImportTypes::CASHIERS->value,
            'store_managers' => ImportTypes::STORE_MANAGERS->value,
            'categories' => ImportTypes::CATEGORIES->value,
            'cashier_groups' => ImportTypes::CASHIER_GROUPS->value,
            'locations' => ImportTypes::LOCATIONS->value,
        ];

        $productImportTypes = [
            [
                'id' => ImportTypes::PRODUCTS->value,
                'name' => 'New ' . CommonFunctions::stringTitleLowerCase(ImportTypes::PRODUCTS->name),
            ],
            [
                'id' => ImportTypes::PRODUCT_PRICE_BULK_UPDATE->value,
                'name' => CommonFunctions::stringTitleLowerCase(ImportTypes::PRODUCT_PRICE_BULK_UPDATE->name),
            ],
            [
                'id' => ImportTypes::PRODUCT_BULK_UPDATE->value,
                'name' => CommonFunctions::stringTitleLowerCase(ImportTypes::PRODUCT_BULK_UPDATE->name),
            ],
            [
                'id' => ImportTypes::PRODUCT_BULK_IMAGE_UPLOAD->value,
                'name' => CommonFunctions::stringTitleLowerCase(ImportTypes::PRODUCT_BULK_IMAGE_UPLOAD->name),
            ],
            [
                'id' => ImportTypes::BULK_PRODUCT_MERGE->value,
                'name' => CommonFunctions::stringTitleLowerCase(ImportTypes::BULK_PRODUCT_MERGE->name),
            ],
            [
                'id' => ImportTypes::SET_PRODUCT_LOYALTY_POINTS->value,
                'name' => CommonFunctions::stringTitleLowerCase(ImportTypes::SET_PRODUCT_LOYALTY_POINTS->name),
            ],
            [
                'id' => ImportTypes::SET_PRODUCT_BOX_UNITS->value,
                'name' => CommonFunctions::stringTitleLowerCase(ImportTypes::SET_PRODUCT_BOX_UNITS->name),
            ],
        ];

        $memberImportTypes = [
            [
                'id' => ImportTypes::MEMBERS->value,
                'name' => 'New ' . CommonFunctions::stringTitleLowerCase(ImportTypes::MEMBERS->name),
            ],
            [
                'id' => ImportTypes::MEMBERS_BULK_UPDATE->value,
                'name' => CommonFunctions::stringTitleLowerCase(ImportTypes::MEMBERS_BULK_UPDATE->name),
            ],
            [
                'id' => ImportTypes::ADD_MEMBER_LOYALTY_POINTS->value,
                'name' => CommonFunctions::stringTitleLowerCase(ImportTypes::ADD_MEMBER_LOYALTY_POINTS->name),
            ],
            [
                'id' => ImportTypes::UPDATE_MEMBER_LOYALTY_POINTS->value,
                'name' => CommonFunctions::stringTitleLowerCase(ImportTypes::UPDATE_MEMBER_LOYALTY_POINTS->name),
            ],
            [
                'id' => ImportTypes::MEMBER_ADDRESS->value,
                'name' => CommonFunctions::stringTitleLowerCase(ImportTypes::MEMBER_ADDRESS->name),
            ],
        ];

        $employeeImportTypes = [
            [
                'id' => ImportTypes::EMPLOYEES->value,
                'name' => 'New ' . CommonFunctions::stringTitleLowerCase(ImportTypes::EMPLOYEES->name),
            ],
            [
                'id' => ImportTypes::EMPLOYEES_BULK_UPDATE->value,
                'name' => CommonFunctions::stringTitleLowerCase(ImportTypes::EMPLOYEES_BULK_UPDATE->name),
            ],
        ];

        $counterImportTypes = [
            [
                'id' => ImportTypes::COUNTERS->value,
                'name' => 'New ' . CommonFunctions::stringTitleLowerCase(ImportTypes::COUNTERS->name),
            ],
            [
                'id' => ImportTypes::COUNTER_BULK_UPDATE->value,
                'name' => CommonFunctions::stringTitleLowerCase(ImportTypes::COUNTER_BULK_UPDATE->name),
            ],
        ];

        $paymentTypeImportTypes = [
            [
                'id' => ImportTypes::PAYMENT_TYPES->value,
                'name' => 'New ' . CommonFunctions::stringTitleLowerCase(ImportTypes::PAYMENT_TYPES->name),
            ],
            [
                'id' => ImportTypes::PAYMENT_TYPE_BULK_UPDATE->value,
                'name' => CommonFunctions::stringTitleLowerCase(ImportTypes::PAYMENT_TYPE_BULK_UPDATE->name),
            ],
        ];

        $promoterImportTypes = [
            [
                'id' => ImportTypes::PROMOTERS->value,
                'name' => 'New ' . CommonFunctions::stringTitleLowerCase(ImportTypes::PROMOTERS->name),
            ],
            [
                'id' => ImportTypes::PROMOTER_BULK_UPDATE->value,
                'name' => CommonFunctions::stringTitleLowerCase(ImportTypes::PROMOTER_BULK_UPDATE->name),
            ],
        ];

        $colorGroupImportTypes = [
            [
                'id' => ImportTypes::COLOR_GROUPS->value,
                'name' => 'New ' . CommonFunctions::stringTitleLowerCase(ImportTypes::COLOR_GROUPS->name),
            ],
            [
                'id' => ImportTypes::COLOR_GROUP_BULK_UPDATE->value,
                'name' => CommonFunctions::stringTitleLowerCase(ImportTypes::COLOR_GROUP_BULK_UPDATE->name),
            ],
        ];

        $regionImportTypes = [
            [
                'id' => ImportTypes::REGIONS->value,
                'name' => 'New ' . CommonFunctions::stringTitleLowerCase(ImportTypes::REGIONS->name),
            ],
            [
                'id' => ImportTypes::REGIONS_BULK_UPDATE->value,
                'name' => CommonFunctions::stringTitleLowerCase(ImportTypes::REGIONS_BULK_UPDATE->name),
            ],
        ];

        $vendorImportTypes = [
            [
                'id' => ImportTypes::VENDORS->value,
                'name' => 'New ' . CommonFunctions::stringTitleLowerCase(ImportTypes::VENDORS->name),
            ],
            [
                'id' => ImportTypes::VENDOR_BULK_UPDATE->value,
                'name' => CommonFunctions::stringTitleLowerCase(ImportTypes::VENDOR_BULK_UPDATE->name),
            ],
        ];

        $sizeGroupImportTypes = [
            [
                'id' => ImportTypes::SIZE_GROUPS->value,
                'name' => 'New ' . CommonFunctions::stringTitleLowerCase(ImportTypes::SIZE_GROUPS->name),
            ],
            [
                'id' => ImportTypes::SIZE_GROUP_BULK_UPDATE->value,
                'name' => CommonFunctions::stringTitleLowerCase(ImportTypes::SIZE_GROUP_BULK_UPDATE->name),
            ],
        ];

        $sizeImportTypes = [
            [
                'id' => ImportTypes::SIZES->value,
                'name' => 'New ' . CommonFunctions::stringTitleLowerCase(ImportTypes::SIZES->name),
            ],
            [
                'id' => ImportTypes::SIZE_BULK_UPDATE->value,
                'name' => CommonFunctions::stringTitleLowerCase(ImportTypes::SIZE_BULK_UPDATE->name),
            ],
        ];

        $colorImportTypes = [
            [
                'id' => ImportTypes::COLORS->value,
                'name' => 'New ' . CommonFunctions::stringTitleLowerCase(ImportTypes::COLORS->name),
            ],
            [
                'id' => ImportTypes::COLOR_BULK_UPDATE->value,
                'name' => CommonFunctions::stringTitleLowerCase(ImportTypes::COLOR_BULK_UPDATE->name),
            ],
        ];

        $cashierImportTypes = [
            [
                'id' => ImportTypes::CASHIERS->value,
                'name' => 'New ' . CommonFunctions::stringTitleLowerCase(ImportTypes::CASHIERS->name),
            ],
            [
                'id' => ImportTypes::CASHIER_BULK_UPDATE->value,
                'name' => CommonFunctions::stringTitleLowerCase(ImportTypes::CASHIER_BULK_UPDATE->name),
            ],
        ];

        $cashierGroupImportTypes = [
            [
                'id' => ImportTypes::CASHIER_GROUPS->value,
                'name' => 'New ' . CommonFunctions::stringTitleLowerCase(ImportTypes::CASHIER_GROUPS->name),
            ],
            [
                'id' => ImportTypes::CASHIER_GROUPS_BULK_UPDATE->value,
                'name' => CommonFunctions::stringTitleLowerCase(ImportTypes::CASHIER_GROUPS_BULK_UPDATE->name),
            ],
        ];

        $storeManagerImportTypes = [
            [
                'id' => ImportTypes::STORE_MANAGERS->value,
                'name' => 'New ' . CommonFunctions::stringTitleLowerCase(ImportTypes::STORE_MANAGERS->name),
            ],
            [
                'id' => ImportTypes::STORE_MANAGER_BULK_UPDATE->value,
                'name' => CommonFunctions::stringTitleLowerCase(ImportTypes::STORE_MANAGER_BULK_UPDATE->name),
            ],
        ];

        $categoryImportTypes = [
            [
                'id' => ImportTypes::CATEGORIES->value,
                'name' => 'New ' . CommonFunctions::stringTitleLowerCase(ImportTypes::CATEGORIES->name),
            ],
            [
                'id' => ImportTypes::CATEGORY_BULK_UPDATE->value,
                'name' => CommonFunctions::stringTitleLowerCase(ImportTypes::CATEGORY_BULK_UPDATE->name),
            ],
        ];

        $locationImportTypes = [
            [
                'id' => ImportTypes::LOCATIONS->value,
                'name' => 'New ' . CommonFunctions::stringTitleLowerCase(ImportTypes::LOCATIONS->name),
            ],
            [
                'id' => ImportTypes::LOCATION_BULK_UPDATE->value,
                'name' => CommonFunctions::stringTitleLowerCase(ImportTypes::LOCATION_BULK_UPDATE->name),
            ],
        ];

        $groupImportTypes = [
            [
                'id' => ImportTypes::PRODUCTS->value,
                'name' => CommonFunctions::stringTitleLowerCase(ImportTypes::PRODUCTS->name),
            ],
            [
                'id' => ImportTypes::MEMBERS->value,
                'name' => CommonFunctions::stringTitleLowerCase(ImportTypes::MEMBERS->name),
            ],
            [
                'id' => ImportTypes::EMPLOYEES->value,
                'name' => CommonFunctions::stringTitleLowerCase(ImportTypes::EMPLOYEES->name),
            ],
            [
                'id' => ImportTypes::COUNTERS->value,
                'name' => CommonFunctions::stringTitleLowerCase(ImportTypes::COUNTERS->name),
            ],
            [
                'id' => ImportTypes::PAYMENT_TYPES->value,
                'name' => CommonFunctions::stringTitleLowerCase(ImportTypes::PAYMENT_TYPES->name),
            ],
            [
                'id' => ImportTypes::PROMOTERS->value,
                'name' => CommonFunctions::stringTitleLowerCase(ImportTypes::PROMOTERS->name),
            ],
            [
                'id' => ImportTypes::COLOR_GROUPS->value,
                'name' => CommonFunctions::stringTitleLowerCase(ImportTypes::COLOR_GROUPS->name),
            ],
            [
                'id' => ImportTypes::REGIONS->value,
                'name' => CommonFunctions::stringTitleLowerCase(ImportTypes::REGIONS->name),
            ],
            [
                'id' => ImportTypes::VENDORS->value,
                'name' => CommonFunctions::stringTitleLowerCase(ImportTypes::VENDORS->name),
            ],
            [
                'id' => ImportTypes::SIZE_GROUPS->value,
                'name' => CommonFunctions::stringTitleLowerCase(ImportTypes::SIZE_GROUPS->name),
            ],
            [
                'id' => ImportTypes::SIZES->value,
                'name' => CommonFunctions::stringTitleLowerCase(ImportTypes::SIZES->name),
            ],
            [
                'id' => ImportTypes::COLORS->value,
                'name' => CommonFunctions::stringTitleLowerCase(ImportTypes::COLORS->name),
            ],
            [
                'id' => ImportTypes::CASHIERS->value,
                'name' => CommonFunctions::stringTitleLowerCase(ImportTypes::CASHIERS->name),
            ],
            [
                'id' => ImportTypes::STORE_MANAGERS->value,
                'name' => CommonFunctions::stringTitleLowerCase(ImportTypes::STORE_MANAGERS->name),
            ],
            [
                'id' => ImportTypes::CATEGORIES->value,
                'name' => CommonFunctions::stringTitleLowerCase(ImportTypes::CATEGORIES->name),
            ],
            [
                'id' => ImportTypes::CASHIER_GROUPS->value,
                'name' => CommonFunctions::stringTitleLowerCase(ImportTypes::CASHIER_GROUPS->name),
            ],
            [
                'id' => ImportTypes::LOCATIONS->value,
                'name' => CommonFunctions::stringTitleLowerCase(ImportTypes::LOCATIONS->name),
            ],
        ];

        return [
            'groupImportTypes' => $groupImportTypes,
            'groupImportTypeDetails' => $groupImportTypeDetails,
            'productImportTypes' => $productImportTypes,
            'memberImportTypes' => $memberImportTypes,
            'employeeImportTypes' => $employeeImportTypes,
            'counterImportTypes' => $counterImportTypes,
            'paymentTypeImportTypes' => $paymentTypeImportTypes,
            'promoterImportTypes' => $promoterImportTypes,
            'colorGroupImportTypes' => $colorGroupImportTypes,
            'regionImportTypes' => $regionImportTypes,
            'vendorImportTypes' => $vendorImportTypes,
            'sizeGroupImportTypes' => $sizeGroupImportTypes,
            'sizeImportTypes' => $sizeImportTypes,
            'colorImportTypes' => $colorImportTypes,
            'cashierImportTypes' => $cashierImportTypes,
            'storeManagerImportTypes' => $storeManagerImportTypes,
            'categoryImportTypes' => $categoryImportTypes,
            'cashierGroupImportTypes' => $cashierGroupImportTypes,
            'locationImportTypes' => $locationImportTypes,
        ];
    }
}
