<?php

declare(strict_types=1);

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Product\Enums\Statuses;
use App\Domains\ProductAgeingReport\ProductAgeingQueries;
use App\Models\Company;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductAgeing;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

beforeEach(function (): void {
    $this->company = Company::factory()->create([
        'code' => '123456',
        'email' => 'companya@example.com',
    ]);

    $this->location = Location::factory()->create([
        'company_id' => $this->company->getKey(),
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->product = Product::factory()->create([
        'company_id' => $this->company->id,
        'compound_product_name' => 'ABCD',
        'code' => 'A1236',
        'upc' => 'UPC',
        'article_number' => '1234',
        'status' => Statuses::ACTIVE->value,
        'is_non_inventory' => false,
        'is_non_selling_item' => false,
        'is_available_in_pos' => true,
        'is_available_in_ecommerce' => false,
    ]);

    $this->productAgeing = ProductAgeing::factory(2)->create([
        'product_id' => $this->product->id,
        'location_id' => $this->location->id,
    ]);

    $this->productAgeingQueries = new ProductAgeingQueries();
});

test('it calls getPaginatedProductsAgeingReportByMonthAndYear method and returns the paginate data', function (): void {
    $filterData = [
        'search_text' => '',
        'sort_by' => 'id',
        'sort_direction' => 'desc',
        'per_page' => 1,
        'product_id' => '',
        'category_ids' => [],
        'brand_ids' => [],
        'department_ids' => [],
        'size_ids' => [],
        'color_ids' => [],
        'location_ids' => [],
        'article_numbers' => [],
        'tag_ids' => [],
        'age_of_product_type' => '',
        'age_category_id' => '',
        'last_selling_date_range' => [],
        'product_collection_id' => null,
    ];

    $response = $this->productAgeingQueries->getPaginatedProductsAgeingReportByMonthAndYear(
        $filterData,
        $this->company->id
    );

    expect($response)->toBeInstanceOf(LengthAwarePaginator::class);
    expect($response->first()->toArray())->toHaveKey('product_id', $this->product->id);
});

test('it calls getPaginatedProductsAgeingReport method and returns the paginate data', function (): void {
    $filterData = [
        'search_text' => '',
        'sort_by' => 'id',
        'sort_direction' => 'desc',
        'per_page' => 1,
        'product_id' => '',
        'category_ids' => [],
        'brand_ids' => [],
        'department_ids' => [],
        'size_ids' => [],
        'color_ids' => [],
        'location_ids' => [],
        'article_numbers' => [],
        'tag_ids' => [],
        'age_of_product_type' => '',
        'age_category_id' => '',
        'last_selling_date_range' => [],
        'product_collection_id' => null,
    ];

    $response = $this->productAgeingQueries->getPaginatedProductsAgeingReport($filterData, $this->company->id);

    expect($response)->toBeInstanceOf(LengthAwarePaginator::class);
    expect($response->first()->toArray())->toHaveKey('product_id', $this->product->id);
});

test('it calls getProductsAgeingReportForExport method and returns the paginate data', function (): void {
    $filterData = [
        'search_text' => '',
        'sort_by' => 'id',
        'sort_direction' => 'desc',
        'per_page' => 1,
        'product_id' => '',
        'category_ids' => [],
        'brand_ids' => [],
        'department_ids' => [],
        'size_ids' => [],
        'color_ids' => [],
        'location_ids' => [],
        'article_numbers' => [],
        'tag_ids' => [],
        'age_of_product_type' => '',
        'age_category_id' => '',
        'last_selling_date_range' => [],
        'product_collection_id' => null,
    ];

    $response = $this->productAgeingQueries->getProductsAgeingReportForExport($filterData, $this->company->id);

    expect($response)->toBeInstanceOf(Collection::class);
    expect($response->first()->toArray())->toHaveKey('product_id', $this->product->id);
});

test('it calls getProductsAgeingReportByMonthAndYearForExport method and returns the paginate data', function (): void {
    $filterData = [
        'search_text' => '',
        'sort_by' => 'id',
        'sort_direction' => 'desc',
        'per_page' => 1,
        'product_id' => '',
        'category_ids' => [],
        'brand_ids' => [],
        'department_ids' => [],
        'size_ids' => [],
        'color_ids' => [],
        'location_ids' => [],
        'article_numbers' => [],
        'tag_ids' => [],
        'age_of_product_type' => '',
        'age_category_id' => '',
        'last_selling_date_range' => [],
        'product_collection_id' => null,
    ];

    $response = $this->productAgeingQueries->getProductsAgeingReportByMonthAndYearForExport(
        $filterData,
        $this->company->id
    );

    expect($response)->toBeInstanceOf(Collection::class);
    expect($response->first()->toArray())->toHaveKey('product_id', $this->product->id);
});

test('it calls getProductsAgeingReportForConsolidate method and returns the paginate data', function (): void {
    $filterData = [
        'search_text' => '',
        'sort_by' => 'id',
        'sort_direction' => 'desc',
        'per_page' => 1,
        'product_id' => '',
        'category_ids' => [],
        'brand_ids' => [],
        'department_ids' => [],
        'size_ids' => [],
        'color_ids' => [],
        'location_ids' => [],
        'article_numbers' => [],
        'tag_ids' => [],
        'age_of_product_type' => '',
        'age_category_id' => '',
        'last_selling_date_range' => [],
        'product_collection_id' => null,
    ];

    $response = $this->productAgeingQueries->getProductsAgeingReportForConsolidate($filterData, $this->company->id);

    expect($response)->toBeInstanceOf(ProductAgeing::class);
    expect($response->toArray())->toHaveKey('product_id', $this->product->id);
});

test(
    'it calls getConsolidateProductsAgeingReportByMonthAndYear method and returns the paginate data',
    function (): void {
        $filterData = [
            'search_text' => '',
            'sort_by' => 'id',
            'sort_direction' => 'desc',
            'per_page' => 1,
            'product_id' => '',
            'category_ids' => [],
            'brand_ids' => [],
            'department_ids' => [],
            'size_ids' => [],
            'color_ids' => [],
            'location_ids' => [],
            'article_numbers' => [],
            'tag_ids' => [],
            'age_of_product_type' => '',
            'age_category_id' => '',
            'last_selling_date_range' => [],
            'product_collection_id' => null,
        ];

        $response = $this->productAgeingQueries->getConsolidateProductsAgeingReportByMonthAndYear(
            $filterData,
            $this->company->id
        );

        expect($response)->toBeInstanceOf(ProductAgeing::class);
        expect($response->toArray())->toHaveKey('product_id', $this->product->id);
    }
);

test(
    'it calls addNew method to save the data',
    function (): void {
        $productAgeingData = [
            'last_selling_date' => '',
            'quantity_sold' => '',
            'quantity_remaining' => '',
            'first_month_sold' => '',
            'second_month_sold' => '',
            'third_month_sold' => '',
            'fourth_month_sold' => '',
            'fifth_month_sold' => '',
            'sixth_month_sold' => '',
            'seventh_month_sold' => '',
            'eighth_month_sold' => '',
            'ninth_month_sold' => '',
            'tenth_month_sold' => '',
            'eleventh_month_sold' => '',
            'twelfth_month_sold' => '',
        ];

        $this->productAgeingQueries->addNew(
            $productAgeingData,
            $this->location->getKey(),
            $this->product->getKey(),
            $this->product->created_at->format('Y-m-d'),
        );

        $this->assertDatabaseHas(ProductAgeing::class, [
            'product_id' => $this->product->getKey(),
            'location_id' => $this->location->getKey(),
            'product_created_at' => $this->product->created_at->format('Y-m-d'),
        ]);
    }
);

test(
    'it calls update method to update the data',
    function (): void {
        $productAgeingData = [
            'last_selling_date' => '',
            'quantity_sold' => '20',
            'quantity_remaining' => '',
            'first_month_sold' => '',
            'second_month_sold' => '',
            'third_month_sold' => '',
            'fourth_month_sold' => '',
            'fifth_month_sold' => '',
            'sixth_month_sold' => '',
            'seventh_month_sold' => '',
            'eighth_month_sold' => '',
            'ninth_month_sold' => '',
            'tenth_month_sold' => '',
            'eleventh_month_sold' => '',
            'twelfth_month_sold' => '',
            'first_transfer_in' => '',
            'first_goods_received_note' => '',
        ];

        $this->productAgeingQueries->update($productAgeingData, $this->location->getKey(), $this->product->getKey());

        $this->assertDatabaseHas(ProductAgeing::class, [
            'product_id' => $this->product->getKey(),
            'location_id' => $this->location->getKey(),
            'quantity_sold' => 20,
        ]);
    }
);

test(
    'it calls updateQuantityRemaining method to update the remaining quantity using product and location id ',
    function (): void {
        $this->productAgeingQueries->updateQuantityRemaining(
            10,
            $this->location->getKey(),
            $this->product->getKey(),
        );

        $this->assertDatabaseHas(ProductAgeing::class, [
            'product_id' => $this->product->getKey(),
            'location_id' => $this->location->getKey(),
            'quantity_remaining' => 10,
        ]);
    }
);

test('it calls getProductAgeingExportCount method and returns the count data', function (): void {
    $filterData = [
        'search_text' => '',
        'sort_by' => 'id',
        'sort_direction' => 'desc',
        'per_page' => 1,
        'product_id' => '',
        'category_ids' => [],
        'brand_ids' => [],
        'department_ids' => [],
        'size_ids' => [],
        'color_ids' => [],
        'location_ids' => [],
        'article_numbers' => [],
        'tag_ids' => [],
        'age_of_product_type' => '',
        'age_category_id' => '',
        'last_selling_date_range' => [],
        'product_collection_id' => null,
    ];

    $response = $this->productAgeingQueries->getProductAgeingExportCount($filterData, $this->company->id);
    $this->assertEquals(2, $response);
});

test('it calls exportProductAgeingRecords method and returns the paginate data', function (): void {
    $filterData = [
        'search_text' => '',
        'sort_by' => 'id',
        'sort_direction' => 'desc',
        'per_page' => 1,
        'product_id' => '',
        'category_ids' => [],
        'brand_ids' => [],
        'department_ids' => [],
        'size_ids' => [],
        'color_ids' => [],
        'location_ids' => [],
        'article_numbers' => [],
        'tag_ids' => [],
        'age_of_product_type' => '',
        'age_category_id' => '',
        'last_selling_date_range' => [],
        'product_collection_id' => null,
    ];

    $response = $this->productAgeingQueries->exportProductAgeingRecords($filterData, $this->company->id, 0, 200);

    expect($response)->toBeInstanceOf(Collection::class);
    expect($response->first()->toArray())->toHaveKey('product_id', $this->product->id);
});

test('it calls getProductAgeingByMonthAndYearExportCount method and returns the paginate data', function (): void {
    $filterData = [
        'search_text' => '',
        'sort_by' => 'id',
        'sort_direction' => 'desc',
        'per_page' => 1,
        'product_id' => '',
        'category_ids' => [],
        'brand_ids' => [],
        'department_ids' => [],
        'size_ids' => [],
        'color_ids' => [],
        'location_ids' => [],
        'article_numbers' => [],
        'tag_ids' => [],
        'age_of_product_type' => '',
        'age_category_id' => '',
        'last_selling_date_range' => [],
        'product_collection_id' => null,
    ];

    $response = $this->productAgeingQueries->getProductAgeingByMonthAndYearExportCount(
        $filterData,
        $this->company->id
    );
    $this->assertEquals(2, $response);
});

test('it calls exportProductAgeingByMonthAndYearRecords method and returns the paginate data', function (): void {
    $filterData = [
        'search_text' => '',
        'sort_by' => 'id',
        'sort_direction' => 'desc',
        'per_page' => 1,
        'product_id' => '',
        'category_ids' => [],
        'brand_ids' => [],
        'department_ids' => [],
        'size_ids' => [],
        'color_ids' => [],
        'location_ids' => [],
        'article_numbers' => [],
        'tag_ids' => [],
        'age_of_product_type' => '',
        'age_category_id' => '',
        'last_selling_date_range' => [],
        'product_collection_id' => null,
    ];

    $response = $this->productAgeingQueries->exportProductAgeingByMonthAndYearRecords(
        $filterData,
        $this->company->id,
        0,
        10
    );

    expect($response)->toBeInstanceOf(Collection::class);
    expect($response->first()->toArray())->toHaveKey('product_id', $this->product->id);
});

test(
    'it calls getPaginatedProductsAgeingReportByArticleNumber method and returns the paginate data',
    function (): void {
        $filterData = [
            'search_text' => '',
            'sort_by' => 'id',
            'sort_direction' => 'desc',
            'per_page' => 1,
            'product_id' => '',
            'category_ids' => [],
            'brand_ids' => [],
            'department_ids' => [],
            'size_ids' => [],
            'color_ids' => [],
            'article_numbers' => [],
            'location_ids' => [],
            'tag_ids' => [],
            'age_of_product_type' => '',
            'last_selling_date_range' => [],
            'product_collection_id' => null,
        ];

        $response = $this->productAgeingQueries->getPaginatedProductsAgeingReportByArticleNumber(
            $filterData,
            $this->company->id
        );

        expect($response)->toBeInstanceOf(LengthAwarePaginator::class);
        expect($response->first()->toArray())->toHaveKey('product_id', $this->product->id);
    }
);

test(
    'it calls getProductsAgeingReportByArticleNumberForExport method and returns the paginate data',
    function (): void {
        $filterData = [
            'search_text' => '',
            'sort_by' => 'id',
            'sort_direction' => 'desc',
            'per_page' => 1,
            'product_id' => '',
            'category_ids' => [],
            'brand_ids' => [],
            'department_ids' => [],
            'size_ids' => [],
            'color_ids' => [],
            'article_numbers' => [],
            'location_ids' => [],
            'tag_ids' => [],
            'age_of_product_type' => '',
            'last_selling_date_range' => [],
            'product_collection_id' => null,
        ];

        $response = $this->productAgeingQueries->getProductsAgeingReportByArticleNumberForExport(
            $filterData,
            $this->company->id
        );

        expect($response)->toBeInstanceOf(Collection::class);
        expect($response->first()->toArray())->toHaveKey('product_id', $this->product->id);
    }
);

test(
    'it calls getPaginatedProductsAgeingReportByUpc method and returns the paginate data',
    function (): void {
        $filterData = [
            'search_text' => '',
            'sort_by' => 'id',
            'sort_direction' => 'desc',
            'per_page' => 1,
            'product_id' => '',
            'category_ids' => [],
            'brand_ids' => [],
            'department_ids' => [],
            'size_ids' => [],
            'color_ids' => [],
            'article_numbers' => [],
            'location_ids' => [],
            'tag_ids' => [],
            'age_of_product_type' => '',
            'last_selling_date_range' => [],
            'product_collection_id' => null,
        ];

        $response = $this->productAgeingQueries->getPaginatedProductsAgeingReportByUpc(
            $filterData,
            $this->company->id
        );

        expect($response)->toBeInstanceOf(LengthAwarePaginator::class);
        expect($response->first()->toArray())->toHaveKey('product_id', $this->product->id);
    }
);

test(
    'it calls getProductsAgeingReportByUpcForExport method and returns the paginate data',
    function (): void {
        $filterData = [
            'search_text' => '',
            'sort_by' => 'id',
            'sort_direction' => 'desc',
            'per_page' => 1,
            'product_id' => '',
            'category_ids' => [],
            'brand_ids' => [],
            'department_ids' => [],
            'size_ids' => [],
            'color_ids' => [],
            'article_numbers' => [],
            'location_ids' => [],
            'tag_ids' => [],
            'age_of_product_type' => '',
            'last_selling_date_range' => [],
            'product_collection_id' => null,
        ];

        $response = $this->productAgeingQueries->getProductsAgeingReportByUpcForExport(
            $filterData,
            $this->company->id
        );

        expect($response)->toBeInstanceOf(Collection::class);
        expect($response->first()->toArray())->toHaveKey('product_id', $this->product->id);
    }
);
