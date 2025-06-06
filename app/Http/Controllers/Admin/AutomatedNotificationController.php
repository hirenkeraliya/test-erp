<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\AutomatedNotification\AutomatedNotificationQueries;
use App\Domains\AutomatedNotification\DataObjects\AutomatedNotificationData;
use App\Domains\AutomatedNotification\Enums\AutomatedNotificationTimeframeTypes;
use App\Domains\AutomatedNotification\Enums\AutomatedNotificationTypes;
use App\Domains\AutomatedNotification\Exports\AutomatedNotificationExport;
use App\Domains\AutomatedNotification\Resources\AdminAutomatedNotificationListResource;
use App\Domains\AutomatedNotification\Resources\AdminEditAutomatedNotificationResource;
use App\Domains\AutomatedNotificationProduct\Exports\AutomatedNotificationProductExport;
use App\Domains\AutomatedNotificationStore\Exports\AutomatedNotificationStoreExport;
use App\Domains\EmailRecipient\EmailRecipientQueries;
use App\Domains\ImportRecord\DataObjects\ImportRecordData;
use App\Domains\ImportRecord\Enums\ImportTypes;
use App\Domains\ImportRecord\Enums\Status;
use App\Domains\ImportRecord\ImportRecordQueries;
use App\Domains\ImportRecord\Jobs\ImportRecordsJob;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Exceptions\RedirectBackWithErrorException;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class AutomatedNotificationController extends Controller
{
    public function __construct(
        protected AutomatedNotificationQueries $automatedNotificationQueries
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('automated_notifications/Index', [
            'exportPermission' => PermissionList::getExportPermissionName('automated_notification'),
        ]);
    }

    /**
     * @return array<string, AnonymousResourceCollection>|array<string, int>
     */
    public function fetchAutomatedNotifications(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $lengthAwarePaginator = $this->automatedNotificationQueries->listQuery(
            $filterData,
            session('admin_company_id')
        );

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => AdminAutomatedNotificationListResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function create(): Response
    {
        $companyId = session('admin_company_id');
        $typeIds = $this->automatedNotificationQueries->getAll($companyId)
            ->pluck('type_id')->toArray();
        $automatedNotificationTypes = collect(AutomatedNotificationTypes::formattedForSelection())
            ->filter(fn ($notification): bool => ! in_array($notification['id'], $typeIds, true))->values()->toArray();

        $emailRecipientQueries = resolve(EmailRecipientQueries::class);
        $automatedEmailReceipts = $emailRecipientQueries->getAutomatedEmailReceivers($companyId);

        [$stores, $warehouses] = $this->getStoresAndWarehouses($companyId);

        return Inertia::render('automated_notifications/Manage', [
            'automatedNotificationTypes' => $automatedNotificationTypes,
            'automatedEmailReceipts' => $automatedEmailReceipts,
            'automatedNotificationStaticTypes' => AutomatedNotificationTypes::getFormattedArrayForStaticUse(),
            'automatedNotificationTimeframeTypes' => AutomatedNotificationTimeframeTypes::formattedForSelection(),
            'automatedNotificationTimeframeStaticDetails' => AutomatedNotificationTimeframeTypes::getFormattedArrayForStaticUse(),
            'stores' => $stores,
            'warehouses' => $warehouses,
            'staticLocationTypes' => LocationTypes::getFormattedArrayForStaticUse(),
            'locationTypes' => LocationTypes::getList(),
        ]);
    }

    public function store(AutomatedNotificationData $automatedNotificationData, Request $request): RedirectResponse
    {
        $importRecordQueries = resolve(ImportRecordQueries::class);

        /** @var Admin $admin */
        $admin = $request->user();

        $companyId = session('admin_company_id');

        $this->checkRequestDetails($companyId, $automatedNotificationData);

        DB::beginTransaction();
        try {
            $automatedNotification = $this->automatedNotificationQueries->addNew(
                $automatedNotificationData,
                $companyId
            );
            if ($automatedNotificationData->product_locations_file instanceof UploadedFile && $automatedNotification->type_id === AutomatedNotificationTypes::LOW_STOCK_PRODUCT->value
                && (null !== $automatedNotificationData->product_location_ids && [] !== $automatedNotificationData->product_location_ids)) {
                $importRecordData = [
                    'type_id' => ImportTypes::AUTOMATED_NOTIFICATION_PRODUCTS->value,
                    'upload_file' => $automatedNotificationData->product_locations_file,
                ];
                $importRecord = $importRecordQueries->addNew(
                    new ImportRecordData(...$importRecordData),
                    $admin,
                    $companyId,
                    $automatedNotification,
                );
                ImportRecordsJob::dispatch($importRecord)->onQueue('high');
            }

            DB::commit();

            return to_route('admin.automated_notifications.index')->with(
                'success',
                'Automated Notification added successfully.'
            );
        } catch (Throwable $throwable) {
            Log::error('Automated Notification Products Import', [
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

    public function edit(int $automatedNotificationId): Response
    {
        $companyId = session('admin_company_id');
        $typeIds = $this->automatedNotificationQueries->getAll($companyId)
            ->pluck('type_id')->toArray();
        $automatedNotification = $this->automatedNotificationQueries->getByIdWithRelations(
            $automatedNotificationId,
            $companyId
        );

        if ($automatedNotification->type_id === AutomatedNotificationTypes::LOW_STOCK_COMPANY->value || $automatedNotification->type_id === AutomatedNotificationTypes::LOW_STOCK_LOCATION->value ||
            $automatedNotification->type_id === AutomatedNotificationTypes::LOW_STOCK_PRODUCT->value) {
            $importRecord = $automatedNotification->importRecord;
            if ($importRecord && $importRecord->status !== Status::COMPLETED->value) {
                throw new RedirectBackWithErrorException('You cannot update while the process is in progress.');
            }
        }

        $automatedNotificationTypes = collect(AutomatedNotificationTypes::formattedForSelection())
            ->filter(
                fn ($notification): bool => ! (in_array(
                    $notification['id'],
                    $typeIds,
                    true
                ) && $automatedNotification->type_id !== $notification['id'])
            )->values()->toArray();

        $emailRecipientQueries = resolve(EmailRecipientQueries::class);
        $automatedEmailReceipts = $emailRecipientQueries->getAutomatedEmailReceivers($companyId);

        [$stores, $warehouses] = $this->getStoresAndWarehouses($companyId);

        return Inertia::render('automated_notifications/Manage', [
            'automatedNotification' => new AdminEditAutomatedNotificationResource($automatedNotification),
            'automatedNotificationTypes' => $automatedNotificationTypes,
            'automatedEmailReceipts' => $automatedEmailReceipts,
            'automatedNotificationStaticTypes' => AutomatedNotificationTypes::getFormattedArrayForStaticUse(),
            'automatedNotificationTimeframeTypes' => AutomatedNotificationTimeframeTypes::formattedForSelection(),
            'automatedNotificationTimeframeStaticDetails' => AutomatedNotificationTimeframeTypes::getFormattedArrayForStaticUse(),
            'stores' => $stores,
            'warehouses' => $warehouses,
            'staticLocationTypes' => LocationTypes::getFormattedArrayForStaticUse(),
            'locationTypes' => LocationTypes::getList(),
        ]);
    }

    public function update(
        AutomatedNotificationData $automatedNotificationData,
        Request $request,
        int $automatedNotificationId
    ): RedirectResponse {
        $importRecordQueries = resolve(ImportRecordQueries::class);
        $companyId = session('admin_company_id');
        $automatedNotification = $this->automatedNotificationQueries->getById($automatedNotificationId, $companyId);

        $this->checkRequestDetails($companyId, $automatedNotificationData);

        if (
            $automatedNotification->type_id === AutomatedNotificationTypes::LOW_STOCK_COMPANY->value || $automatedNotification->type_id === AutomatedNotificationTypes::LOW_STOCK_LOCATION->value ||
            $automatedNotification->type_id === AutomatedNotificationTypes::LOW_STOCK_PRODUCT->value) {
            $importRecord = $automatedNotification->importRecord;
            if ($importRecord && $importRecord->status !== Status::COMPLETED->value) {
                throw new RedirectBackWithErrorException('You cannot update while the process is in progress.');
            }
        }

        /** @var Admin $admin */
        $admin = $request->user();

        DB::beginTransaction();
        try {
            $this->automatedNotificationQueries->update(
                $automatedNotificationData,
                $automatedNotification,
                $companyId
            );
            if ($automatedNotificationData->product_locations_file instanceof UploadedFile && $automatedNotificationData->type_id === AutomatedNotificationTypes::LOW_STOCK_PRODUCT->value && (null !== $automatedNotificationData->product_location_ids && [] !== $automatedNotificationData->product_location_ids)) {
                $importRecordData = [
                    'type_id' => ImportTypes::AUTOMATED_NOTIFICATION_PRODUCTS->value,
                    'upload_file' => $automatedNotificationData->product_locations_file,
                ];
                $importRecord = $importRecordQueries->addNew(
                    new ImportRecordData(...$importRecordData),
                    $admin,
                    $companyId,
                    $automatedNotification,
                );
                ImportRecordsJob::dispatch($importRecord)->onQueue('high');
            }

            DB::commit();

            return to_route('admin.automated_notifications.index')->with(
                'success',
                'Automated Notification updated successfully.'
            );
        } catch (Throwable $throwable) {
            Log::error('Automated Notification Products Import', [
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

    public function exportAutomatedNotifications(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $automatedNotifications = $this->automatedNotificationQueries->getAutomatedNotificationExport(
            $filterData,
            session('admin_company_id')
        );

        return Excel::download(new AutomatedNotificationExport($automatedNotifications), $filename);
    }

    public function removeSelectedStores(int $automatedNotificationId): void
    {
        $this->automatedNotificationQueries->removeSelectedStores(
            $automatedNotificationId,
            session('admin_company_id')
        );
    }

    public function exportAutomatedNotificationStores(int $id, string $filename, Request $request): BinaryFileResponse
    {
        $request->validate([
            'id' => ['required', 'integer'],
        ]);

        $automatedNotificationStores = $this->automatedNotificationQueries->getByIdWithAutomatedNotificationStores(
            $id,
            session('admin_company_id')
        )->automatedNotificationStores;

        return Excel::download(new AutomatedNotificationStoreExport($automatedNotificationStores), $filename);
    }

    public function removeSelectedProducts(int $automatedNotificationId): void
    {
        $this->automatedNotificationQueries->removeSelectedProducts(
            $automatedNotificationId,
            session('admin_company_id')
        );
    }

    public function exportAutomatedNotificationProducts(int $id, string $filename, Request $request): BinaryFileResponse
    {
        $request->validate([
            'id' => ['required', 'integer'],
        ]);

        $automatedNotificationProducts = $this->automatedNotificationQueries->getByIdWithAutomatedNotificationProducts(
            $id,
            session('admin_company_id')
        )->automatedNotificationProducts;

        return Excel::download(new AutomatedNotificationProductExport($automatedNotificationProducts), $filename);
    }

    private function checkRequestDetails(int $companyId, AutomatedNotificationData $automatedNotificationData): void
    {
        if ($automatedNotificationData->type_id !== AutomatedNotificationTypes::LOW_STOCK_LOCATION->value || (null === $automatedNotificationData->locations || [] === $automatedNotificationData->locations)) {
            return;
        }

        $automatedNotificationQueries = resolve(AutomatedNotificationQueries::class);
        $automatedNotification = $automatedNotificationQueries->checkExistsByLocationsAndCompany(
            $companyId,
            array_column($automatedNotificationData->locations, 'id')
        );

        if ($automatedNotification) {
            throw new RedirectBackWithErrorException('Some of the locations are already in our records.');
        }
    }

    public function getStoresAndWarehouses(int $companyId): array
    {
        $locationQueries = resolve(LocationQueries::class);

        $locations = $locationQueries->getWithBasicColumns($companyId);
        $stores = $locations->where('type_id', LocationTypes::STORE->value)->values()->all();
        $warehouses = $locations->where('type_id', LocationTypes::WAREHOUSE->value)->values()->all();

        return [$stores, $warehouses];
    }
}
