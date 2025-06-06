<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Brand\BrandQueries;
use App\Domains\Category\CategoryQueries;
use App\Domains\DynamicMenus\DataObjects\DynamicMenuData;
use App\Domains\DynamicMenus\DataObjects\DynamicMenuListData;
use App\Domains\DynamicMenus\DynamicMenuQueries;
use App\Domains\DynamicMenus\Enums\DynamicMenuTypesEnum;
use App\Domains\ProductCollection\ProductCollectionQueries;
use App\Domains\SaleChannel\Services\SaleChannelService;
use App\Domains\SyncTransaction\Enums\SyncTypes;
use App\Exceptions\RedirectBackWithErrorException;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class DynamicMenuController extends Controller
{
    public function index(): Response
    {
        $saleChannelService = resolve(SaleChannelService::class);
        $saleChannels = $saleChannelService->getModifySaleChannels(
            SyncTypes::CATEGORY->value,
            session('admin_company_id')
        );

        return Inertia::render('dynamic_menus/Index', [
            'saleChannels' => $saleChannels,
            'staticMenuTypes' => [
                'brand' => DynamicMenuTypesEnum::BRAND->value,
                'category' => DynamicMenuTypesEnum::CATEGORIES->value,
                'product_collection' => DynamicMenuTypesEnum::PRODUCT_COLLECTION->value,
                'static_page' => DynamicMenuTypesEnum::STATIC_PAGE->value,
            ],
        ]);
    }

    public function fetchDynamicMenus(Request $request): array
    {
        $dynamicMenuQueries = resolve(DynamicMenuQueries::class);
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $collection = $dynamicMenuQueries->listQuery($filterData, session('admin_company_id'));

        return [
            'data' => DynamicMenuListData::collection($collection)->all(),
        ];
    }

    public function create(?int $parentId = null): Response|RedirectResponse
    {
        if ($parentId) {
            $dynamicMenuQueries = resolve(DynamicMenuQueries::class);
            $parentMenu = $dynamicMenuQueries->getById($parentId);

            if ($parentMenu->type === DynamicMenuTypesEnum::STATIC_PAGE->value) {
                return to_route('admin.dynamic_menus.index')->with('error', 'Can not add child menu in static page.');
            }
        }

        $propsData = $this->preparePropsData();
        $propsData['parentId'] = $parentId;

        return Inertia::render('dynamic_menus/Manage', $propsData);
    }

    public function store(DynamicMenuData $dynamicMenuData): RedirectResponse
    {
        try {
            $dynamicMenuQueries = resolve(DynamicMenuQueries::class);
            $dynamicMenuQueries->addNew($dynamicMenuData, session('admin_company_id'));

            return to_route('admin.dynamic_menus.index')->with('success', 'Dynamic Menu added successfully.');
        } catch (Throwable $throwable) {
            Log::error('Add Dynamic Menu', [
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

    public function edit(int $dynamicMenuId): Response
    {
        $dynamicMenuQueries = resolve(DynamicMenuQueries::class);

        $propsData = $this->preparePropsData();
        $propsData['dynamicMenu'] = $dynamicMenuQueries->getById($dynamicMenuId);

        return Inertia::render('dynamic_menus/Manage', $propsData);
    }

    public function update(DynamicMenuData $dynamicMenuData, int $dynamicMenuId): RedirectResponse
    {
        $dynamicMenuQueries = resolve(DynamicMenuQueries::class);

        if ($dynamicMenuData->type === DynamicMenuTypesEnum::STATIC_PAGE->value &&
            $dynamicMenuQueries->getChildCount($dynamicMenuId) > 0
        ) {
            return to_route('admin.dynamic_menus.index')
                ->with('error', 'You can not set the static page due to child menu are available.');
        }

        try {
            $dynamicMenuQueries->update($dynamicMenuData, $dynamicMenuId, session('admin_company_id'));

            return to_route('admin.dynamic_menus.index')->with('success', 'Dynamic Menu updated successfully.');
        } catch (Throwable $throwable) {
            Log::error('Add Dynamic Menu', [
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

    private function preparePropsData(): array
    {
        $brandQueries = resolve(BrandQueries::class);
        $categoryQueries = resolve(CategoryQueries::class);
        $productCollectionQueries = resolve(ProductCollectionQueries::class);

        return [
            'menuTypes' => DynamicMenuTypesEnum::formattedForSelection(),
            'staticMenuTypes' => [
                'brand' => DynamicMenuTypesEnum::BRAND->value,
                'category' => DynamicMenuTypesEnum::CATEGORIES->value,
                'product_collection' => DynamicMenuTypesEnum::PRODUCT_COLLECTION->value,
                'static_page' => DynamicMenuTypesEnum::STATIC_PAGE->value,
            ],
            'brands' => $brandQueries->getWithBasicColumns(),
            'categories' => $categoryQueries->getECommerceCategories(),
            'productCollection' => $productCollectionQueries->getProductCollectionsForECommerce(
                session('admin_company_id')
            ),
        ];
    }
}
