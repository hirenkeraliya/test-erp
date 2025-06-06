<?php

declare(strict_types=1);

namespace App\Domains\Product\DataObjects;

use App\Domains\Category\CategoryQueries;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Product\ProductQueries;
use App\Models\Integration;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\In;
use Illuminate\Validation\Rules\Unique;
use Spatie\LaravelData\Data;

class ProductDataForIntegration extends Data
{
    public function __construct(
        public string $name,
        public int $brand_id,
        public string $upc,
        public int $type_id,
        public array $category_ids,
        public ?float $retail_price,
        public ?string $article_number,
        public ?float $purchase_cost,
    ) {
    }

    /**
     * @return array<string, array<(Exists|In|Unique|string)>>
     */
    public static function rules(Request $request): array
    {
        /** @var Integration $integration */
        $integration = $request->user();
        $companyId = $integration->getCompanyId();

        $productQueries = new ProductQueries();
        $categoryQueries = new CategoryQueries();

        return [
            'name' => ['required', 'string', 'max:255'],
            'brand_id' => ['required', 'integer'],
            'upc' => [
                'required',
                'alpha_num',
                'max:255',
                Rule::unique('products', 'upc')
                    ->where($productQueries->filterByCompany($companyId)),
            ],
            'type_id' => ['required', 'integer', 'in:' . ProductTypes::getValues()],
            'category_ids' => [
                'required',
                'array',
                Rule::exists('categories', 'id')
                    ->where($categoryQueries->filterByCompany($companyId)),
            ],
            'retail_price' => ['nullable', 'numeric', 'between:0,99999999.99'],
            'article_number' => ['nullable', 'string', 'max:255'],
            'purchase_cost' => ['nullable', 'numeric', 'between:0,99999999.99'],
        ];
    }
}
