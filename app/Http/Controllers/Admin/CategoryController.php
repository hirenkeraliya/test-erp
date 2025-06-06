<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Category\CategoryQueries;
use App\Domains\Category\DataObjects\CategoryData;
use App\Domains\Category\DataObjects\CategoryListData;
use App\Domains\Category\Exports\CategoryBulkUpdateExport;
use App\Domains\Category\Exports\CategoryExport;
use App\Domains\Category\Jobs\CategorySyncMainJob;
use App\Domains\Category\Services\CategoryExportService;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\SaleChannel\Services\SaleChannelService;
use App\Domains\SyncTransaction\Enums\SyncTypes;
use App\Domains\SyncTransaction\SyncTransactionQueries;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CategoryController extends Controller
{
    public function __construct(
        protected CategoryQueries $categoryQueries
    ) {
    }

    public function index(): Response
    {
        $saleChannelService = resolve(SaleChannelService::class);
        $syncTransactionQueries = resolve(SyncTransactionQueries::class);
        $saleChannels = $saleChannelService->getModifySaleChannels(
            SyncTypes::CATEGORY->value,
            session('admin_company_id')
        );

        $hasPendingSyncTransaction = $syncTransactionQueries->hasPendingSyncTransaction(
            SyncTypes::CATEGORY->value,
            session('admin_company_id')
        );

        return Inertia::render('categories/Index', [
            'exportPermission' => PermissionList::getExportPermissionName('category'),
            'saleChannels' => $saleChannels,
            'hasPendingSyncTransaction' => $hasPendingSyncTransaction,
        ]);
    }

    /**
     * @return array<string, array>
     */
    public function fetchCategories(): array
    {
        $collection = $this->categoryQueries->listQuery(session('admin_company_id'));

        return [
            'data' => CategoryListData::collection($collection)->all(),
        ];
    }

    public function create(?int $parentCategoryId = null): Response
    {
        return Inertia::render('categories/Manage', [
            'parentCategoryId' => $parentCategoryId,
        ]);
    }

    public function store(CategoryData $categoryData): RedirectResponse
    {
        $this->categoryQueries->addNew($categoryData, session('admin_company_id'));

        return to_route('admin.categories.index')->with('success', 'Category added successfully.');
    }

    public function edit(int $categoryId): Response
    {
        $category = $this->categoryQueries->getById($categoryId, session('admin_company_id'));
        $category['square_url'] = $category->getDiskBasedFirstMediaUrl('square_image');
        $category['portrait_urls'] = $category->getDiskBasedMediaUrls('portrait_images');
        $category['landscape_urls'] = $category->getDiskBasedMediaUrls('landscape_images');

        return Inertia::render('categories/Manage', [
            'category' => $category,
        ]);
    }

    public function update(CategoryData $categoryData, int $categoryId): RedirectResponse
    {
        $this->categoryQueries->update($categoryData, $categoryId, session('admin_company_id'));

        return to_route('admin.categories.index')->with('success', 'Category updated successfully.');
    }

    public function getChildCategories(int $categoryId): Collection
    {
        return $this->categoryQueries->getChildCategoriesWithBasicColumns($categoryId, session('admin_company_id'));
    }

    public function getParentCategories(): array
    {
        return [
            'categories' => $this->categoryQueries->getParentByCompanyId(session('admin_company_id')),
        ];
    }

    /**
     * @return array<string, Collection>
     */
    public function getFilteredCategories(Request $request): array
    {
        return [
            'categories' => $this->categoryQueries->getFilteredCategoriesByCompanyId(
                $request->input('search_text'),
                session('admin_company_id')
            ),
        ];
    }

    public function exportCategories(string $filename): BinaryFileResponse
    {
        $companyId = session('admin_company_id');

        $categories = $this->categoryQueries->listQuery($companyId);

        $categoryExportService = resolve(CategoryExportService::class);
        $categoryData = $categoryExportService->exportCategory($categories, $filename);

        return Excel::download(new CategoryExport($categoryData['categories'], $categoryData['columns']), $filename);
    }

    public function exportBulkUpdateCategories(): BinaryFileResponse
    {
        $categories = $this->categoryQueries->getCategoriesForBulkUpdate(session('admin_company_id'));

        return Excel::download(new CategoryBulkUpdateExport($categories), 'category-bulk-update.xlsx');
    }

    public function getCategoriesList(Request $request): array
    {
        return [
            'categories' => $this->categoryQueries->getWithBasicColumns(session('admin_company_id')),
        ];
    }

    public function getCategorySalesSummary(Request $request): array
    {
        $filterData = $request->all();
        $filterData['type'] = (int) $filterData['type'];

        $categories = $this->categoryQueries->getCategorySalesSummary($filterData, session('admin_company_id'));

        return [
            'categories' => $categories,
            'total_sales' => $categories->sum('total_sales'),
            'total_units_sold' => $categories->sum('total_units_sold'),
        ];
    }

    public function removeSquareImage(int $categoryId): void
    {
        $this->categoryQueries->removeSquareImage($categoryId, session('admin_company_id'));
    }

    public function removePortraitImage(int $categoryId, int $mediaId): void
    {
        $this->categoryQueries->removeImage($categoryId, $mediaId, session('admin_company_id'), 'portrait_images');
    }

    public function removeLandscapeImage(int $categoryId, int $mediaId): void
    {
        $this->categoryQueries->removeImage($categoryId, $mediaId, session('admin_company_id'), 'landscape_images');
    }

    public function syncData(int $saleChannelId, Request $request): void
    {
        CategorySyncMainJob::dispatch($saleChannelId, session('admin_company_id'))->onQueue('high');
        $saleChannelService = resolve(SaleChannelService::class);

        /** @var Admin $admin */
        $admin = $request->user();

        $saleChannelService->updateSyncData(
            $saleChannelId,
            SyncTypes::CATEGORY->value,
            $admin,
            session('admin_company_id')
        );
    }
}
