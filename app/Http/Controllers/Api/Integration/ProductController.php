<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Integration;

use App\Domains\Category\CategoryQueries;
use App\Domains\Company\CompanyQueries;
use App\Domains\Currency\CurrencyQueries;
use App\Domains\Product\DataObjects\ProductDataForIntegration;
use App\Domains\Product\ProductQueries;
use App\Domains\Product\Resources\ProductDetailsForIntegrationResource;
use App\Http\Controllers\Controller;
use App\Models\Integration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProductController extends Controller
{
    public function store(ProductDataForIntegration $productData, Request $request): ?array
    {
        DB::beginTransaction();
        try {
            /** @var Integration $integration */
            $integration = $request->user();
            $companyId = $integration->getCompanyId();

            $productQueries = resolve(ProductQueries::class);
            $product = $productQueries->addNewProductForIntegration($productData, $companyId, $integration);
            DB::commit();

            return [
                'productDetails' => new ProductDetailsForIntegrationResource($product),
            ];
        } catch (Throwable $throwable) {
            Log::error('Store-Product', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            DB::rollBack();

            abort(412, 'An error occurred. Please try again.');
        }
    }

    public function getCategories(Request $request): array
    {
        /** @var Integration $integration */
        $integration = $request->user();
        $companyId = $integration->getCompanyId();

        $categoryQueries = resolve(CategoryQueries::class);

        return [
            'categories' => $categoryQueries->getMainCategoriesWithBasicColumns($companyId),
        ];
    }

    public function getBrands(Request $request): array
    {
        /** @var Integration $integration */
        $integration = $request->user();
        $companyId = $integration->getCompanyId();

        $companyQueries = resolve(CompanyQueries::class);

        return [
            'brands' => $companyQueries->getByIdWithBrands($companyId)->brands,
        ];
    }

    public function getAllProducts(Request $request): array
    {
        /** @var Integration $integration */
        $integration = $request->user();
        $companyId = $integration->getCompanyId();

        $productQueries = resolve(ProductQueries::class);
        $currencyQueries = resolve(CurrencyQueries::class);
        $currency = $currencyQueries->getByCompanyId($companyId);

        $products = $productQueries->getAllByCompanyId($companyId);

        $products->transform(function ($product) use ($currency) {
            $product->currency_code = $currency->code;
            $product->is_product_variant_enabled = config('app.product_variant');

            return $product;
        });

        return [
            'product_variants' => $products,
        ];
    }

    public function getAllProductVariantsCount(Request $request): array
    {
        /** @var Integration $integration */
        $integration = $request->user();
        $companyId = $integration->getCompanyId();

        $productQueries = resolve(ProductQueries::class);

        return [
            'total_product_variants' => $productQueries->getCompanyActiveRegularProductCount($companyId),
        ];
    }
}
