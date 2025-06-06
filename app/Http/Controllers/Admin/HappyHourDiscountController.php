<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Brand\BrandQueries;
use App\Domains\Category\CategoryQueries;
use App\Domains\Company\CompanyQueries;
use App\Domains\Department\DepartmentQueries;
use App\Domains\HappyHourDiscount\DataObjects\HappyHourDiscountData;
use App\Domains\HappyHourDiscount\Enums\ProductTypes;
use App\Domains\HappyHourDiscount\Exports\HappyHourDiscountExport;
use App\Domains\HappyHourDiscount\HappyHourDiscountQueries;
use App\Domains\HappyHourDiscount\Resources\HappyHourDiscountListResource;
use App\Domains\HappyHourDiscountTransaction\HappyHourDiscountTransactionQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\Style\StyleQueries;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\HappyHourDiscount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class HappyHourDiscountController extends Controller
{
    public function __construct(
        protected HappyHourDiscountQueries $happyHourDiscountQueries
    ) {
    }

    public function index(): Response
    {
        $companyQueries = resolve(CompanyQueries::class);
        $companyAllowHappyHourDiscount = $companyQueries->getAllowHappyHourDiscount(session('admin_company_id'));

        return Inertia::render('happy_hours/Index', [
            'exportPermission' => PermissionList::getExportPermissionName('happy_hour'),
            'companyAllowHappyHourDiscount' => $companyAllowHappyHourDiscount,
        ]);
    }

    public function fetchHappyHours(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $lengthAwarePaginator = $this->happyHourDiscountQueries->listQuery($filterData, session('admin_company_id'));

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => HappyHourDiscountListResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function store(HappyHourDiscountData $happyHourDiscountData, Request $request): RedirectResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        $companyId = session('admin_company_id');

        $companyQueries = resolve(CompanyQueries::class);
        $companyAllowHappyHourDiscount = $companyQueries->getAllowHappyHourDiscount($companyId);

        if (! $companyAllowHappyHourDiscount) {
            abort(
                417,
                "The company's guidelines do not permit the application of a Happy Hour discount. To address this issue or seek permission for any exceptions, please contact the super admin for further assistance."
            );
        }

        $this->checkRequest($companyId, $happyHourDiscountData, $admin);

        DB::beginTransaction();

        try {
            $this->happyHourDiscountQueries->addNewForAdmin($happyHourDiscountData, $admin, $companyId);
            DB::commit();

            return to_route('admin.happy_hours.index')
                ->with('success', 'Happy hour added successfully.');
        } catch (Throwable) {
            DB::rollBack();
            abort(417, 'An error occurred. Please try again.');
        }
    }

    public function create(): Response
    {
        return Inertia::render('happy_hours/Manage', $this->getCommonRecords());
    }

    public function edit(int $happyHourDiscountId): Response
    {
        $happyHour = $this->happyHourDiscountQueries->getById($happyHourDiscountId, session('admin_company_id'));

        return Inertia::render('happy_hours/Manage', [
            'happyHour' => $happyHour,
            ...$this->getCommonRecords(),
        ]);
    }

    public function update(HappyHourDiscountData $happyHourDiscountData, int $happyHourDiscountId): RedirectResponse
    {
        $companyId = session('admin_company_id');
        $companyQueries = resolve(CompanyQueries::class);
        $companyAllowHappyHourDiscount = $companyQueries->getAllowHappyHourDiscount($companyId);

        if (! $companyAllowHappyHourDiscount) {
            abort(
                417,
                "The company's guidelines do not permit the application of a Happy Hour discount. To address this issue or seek permission for any exceptions, please contact the super admin for further assistance."
            );
        }

        $happyHourDiscountValidatedData = $happyHourDiscountData->all();
        $happyHourDiscountValidatedData['company_id'] = $companyId;

        $this->happyHourDiscountQueries->update($happyHourDiscountData, $happyHourDiscountId, $companyId);

        return to_route('admin.happy_hours.index')
            ->with('success', 'Happy hour updated successfully.');
    }

    public function exportHappyHours(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $happyHourDiscounts = $this->happyHourDiscountQueries->happyHourDiscountExport(
            $filterData,
            session('admin_company_id')
        );

        return Excel::download(new HappyHourDiscountExport($happyHourDiscounts), $filename);
    }

    /**
     * @return array<string, mixed>
     */
    private function getCommonRecords(): array
    {
        $departmentQueries = resolve(DepartmentQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $styleQueries = resolve(StyleQueries::class);
        $categoryQueries = resolve(CategoryQueries::class);
        $locationQueries = resolve(LocationQueries::class);

        return [
            'departments' => $departmentQueries->getWithBasicColumns(session('admin_company_id')),
            'brands' => $companyQueries->getByIdWithBrands(session('admin_company_id'))->brands,
            'styles' => $styleQueries->getWithBasicColumns(session('admin_company_id')),
            'categories' => $categoryQueries->getMainCategoriesWithBasicColumns(session('admin_company_id')),
            'productTypes' => ProductTypes::formattedForSelection(),
            'staticProductTypes' => ProductTypes::getFormattedArrayForStaticUse(),
            'locations' => $locationQueries->getStoreWithBasicColumns(session('admin_company_id')),
        ];
    }

    private function checkRequest(int $companyId, HappyHourDiscountData $happyHourDiscountData, Admin $user): void
    {
        $happyHourDiscountValidatedData = $happyHourDiscountData->all();

        $happyHourDiscountValidatedData = $this->happyHourDiscountQueries->prepareHappyHourDiscount(
            $happyHourDiscountValidatedData,
            $companyId,
            $user
        );

        $happyHourDiscountTransactionQueries = resolve(HappyHourDiscountTransactionQueries::class);

        $happyHourDiscount = $this->happyHourDiscountQueries->checkIfExists($happyHourDiscountValidatedData);
        if ($happyHourDiscount instanceof HappyHourDiscount) {
            $happyHourDiscountTransactionQueries->addNew($happyHourDiscount->id, $happyHourDiscountValidatedData);

            abort(417, 'Similar Happy Hour discount is already available with us.');
        }

        if (
            ProductTypes::BRAND->value === $happyHourDiscountData->product_type_id
            && $happyHourDiscountData->brand_ids
        ) {
            $brandQueries = resolve(BrandQueries::class);
            $doAllBrandExist = $brandQueries->doExistsById($companyId, array_unique($happyHourDiscountData->brand_ids));

            if (! $doAllBrandExist) {
                abort(417, 'Some of the brands are not available in over records');
            }
        }

        if (
            ProductTypes::CATEGORY->value === $happyHourDiscountData->product_type_id
            && $happyHourDiscountData->category_ids
        ) {
            $categoryQueries = resolve(CategoryQueries::class);
            $doAllCategoryExist = $categoryQueries->doAllCategoriesExist(
                $companyId,
                array_unique($happyHourDiscountData->category_ids)
            );

            if (! $doAllCategoryExist) {
                abort(417, 'Some of the categories are not available in over records');
            }
        }

        if (
            ProductTypes::STYLE->value === $happyHourDiscountData->product_type_id
            && $happyHourDiscountData->style_ids
        ) {
            $styleQueries = resolve(StyleQueries::class);
            $doAllStyleExist = $styleQueries->doAllStylesExist(
                $companyId,
                array_unique($happyHourDiscountData->style_ids)
            );

            if (! $doAllStyleExist) {
                abort(417, 'Some of the styles are not available in over records');
            }
        }

        if (
            ProductTypes::DEPARTMENTS->value === $happyHourDiscountData->product_type_id
            && $happyHourDiscountData->department_ids
        ) {
            $departmentQueries = resolve(DepartmentQueries::class);
            $doAllStyleExist = $departmentQueries->doAllDepartmentExist(
                $companyId,
                array_unique($happyHourDiscountData->department_ids)
            );

            if (! $doAllStyleExist) {
                abort(417, 'Some of the departments are not available in over records');
            }
        }
    }
}
