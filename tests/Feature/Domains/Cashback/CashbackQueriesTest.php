<?php

declare(strict_types=1);

use App\Domains\Cashback\CashbackQueries;
use App\Domains\Cashback\DataObjects\CashbackData;
use App\Domains\Cashback\Enums\ExcludeByTypes;
use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Product\Enums\Statuses;
use App\Models\Cashback;
use App\Models\Category;
use App\Models\Company;
use App\Models\Location;
use App\Models\MasterProduct;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;

beforeEach(function (): void {
    $this->companyId = Company::factory()->create()->id;

    $this->cashbackA = Cashback::factory()->create([
        'company_id' => $this->companyId,
        'name' => 'DEF',
        'start_date' => now()->startOfMonth()->format('Y-m-d'),
        'end_date' => now()->endOfMonth()->format('Y-m-d'),
    ]);

    $this->cashbackB = Cashback::factory()->create([
        'company_id' => $this->companyId,
        'name' => 'ABC',
        'start_date' => now()->startOfMonth()->format('Y-m-d'),
        'end_date' => now()->endOfMonth()->format('Y-m-d'),
    ]);

    $this->cashbackQueries = new CashbackQueries();
});

test('Cashback can be searched', function (): void {
    $response = $this->cashbackQueries->listQuery([
        'search_text' => 'DEF',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'date_range' => null,
        'location_ids' => null,
    ], $this->companyId);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->cashbackA->name);
});

test('A cashback can be stored', function (): void {
    $location = Location::factory()->create([
        'company_id' => $this->companyId,
    ]);

    $product = Product::factory()->create([
        'company_id' => $this->companyId,
    ]);

    $cashbackArray = Cashback::factory()->make([
        'company_id' => $this->companyId,
        'exclude_by_type' => ExcludeByTypes::PRODUCTS->value,
    ])->toArray();

    $cashbackArray['location_ids'] = [$location->id];
    $cashbackArray['category_ids'] = [];
    $cashbackArray['product_ids'] = [$product->id];
    $cashbackArray['tiers'] = [];

    unset($cashbackArray['company_id']);

    $this->cashbackQueries->addNew(new CashbackData(...$cashbackArray), $this->companyId);

    unset($cashbackArray['location_ids'], $cashbackArray['category_ids'], $cashbackArray['product_ids'], $cashbackArray['tiers']);

    $this->assertDatabaseHas('cashbacks', $cashbackArray);

    $this->assertDatabaseHas('cashback_location', [
        'location_id' => $location->id,
    ]);

    $this->assertDatabaseHas('cashback_product', [
        'product_id' => $product->id,
    ]);
});

test('A cashback can be fetched with stores, products and categories when product variant is false', function (): void {
    Config::set('app.product_variant', false);

    $location = Location::factory()->create();
    $this->cashbackA->locations()->sync($location->id);

    $product = Product::factory()->create([
        'company_id' => $this->companyId,
    ]);
    $this->cashbackA->products()->sync($product->id);

    $category = Category::factory()->create([
        'company_id' => $this->companyId,
    ]);
    $this->cashbackA->categories()->sync($category->id);

    $response = $this->cashbackQueries->getByIdWithStoresProductsAndCategories($this->cashbackA->id, $this->companyId);

    expect($response->toArray())
        ->toHaveKey('name', $this->cashbackA->name)
        ->toHaveKey('exclude_by_type', $this->cashbackA->exclude_by_type)
        ->toHaveKey('discount_value', $this->cashbackA->discount_value)
        ->toHaveKey('start_date', $this->cashbackA->start_date)
        ->toHaveKey('end_date', $this->cashbackA->end_date)
        ->toHaveKey('locations.0.id', $location->id)
        ->toHaveKey('categories.0.id', $category->id)
        ->toHaveKey('products.0.id', $product->id);
});

test('A cashback can be fetched with stores, products and categories when product variant is true', function (): void {
    Config::set('app.product_variant', true);

    $location = Location::factory()->create();
    $this->cashbackA->locations()->sync($location->id);

    $masterProduct = MasterProduct::factory()->create([
        'company_id' => $this->companyId,
        'has_batch' => false,
        'is_non_inventory' => false,
    ]);

    $product = Product::factory()->create([
        'company_id' => $this->companyId,
        'master_product_id' => $masterProduct->id,
    ]);
    $this->cashbackA->products()->sync($product->id);

    $category = Category::factory()->create([
        'company_id' => $this->companyId,
    ]);
    $this->cashbackA->categories()->sync($category->id);

    $response = $this->cashbackQueries->getByIdWithStoresProductsAndCategories($this->cashbackA->id, $this->companyId);

    expect($response->toArray())
        ->toHaveKey('name', $this->cashbackA->name)
        ->toHaveKey('exclude_by_type', $this->cashbackA->exclude_by_type)
        ->toHaveKey('discount_value', $this->cashbackA->discount_value)
        ->toHaveKey('start_date', $this->cashbackA->start_date)
        ->toHaveKey('end_date', $this->cashbackA->end_date)
        ->toHaveKey('locations.0.id', $location->id)
        ->toHaveKey('categories.0.id', $category->id)
        ->toHaveKey('products.0.id', $product->id);
});

test('A cashback can be updated', function (): void {
    $location = Location::factory()->create([
        'company_id' => $this->companyId,
    ]);

    $product = Product::factory()->create([
        'company_id' => $this->companyId,
    ]);

    $cashbackArray = Cashback::factory()->make([
        'company_id' => $this->companyId,
        'exclude_by_type' => ExcludeByTypes::PRODUCTS->value,
        'discount_type_id' => DiscountTypes::FLAT->value,
        'discount_value' => 20,
    ])->toArray();

    $cashbackArray['location_ids'] = [$location->id];
    $cashbackArray['category_ids'] = [];
    $cashbackArray['product_ids'] = [$product->id];
    $cashbackArray['tiers'] = [];

    unset($cashbackArray['company_id']);

    $this->cashbackQueries->update(new CashbackData(...$cashbackArray), $this->cashbackA->id, $this->companyId);

    unset($cashbackArray['location_ids'], $cashbackArray['category_ids'], $cashbackArray['product_ids'], $cashbackArray['tiers']);

    $this->assertDatabaseHas('cashbacks', $cashbackArray);

    $this->assertDatabaseHas('cashback_location', [
        'location_id' => $location->id,
    ]);

    $this->assertDatabaseHas('cashback_product', [
        'product_id' => $product->id,
    ]);
});

test(
    'getListForPosWithRelatedData method returns the cashback list with related data as expected',
    function (): void {
        $location = Location::factory()->create([
            'company_id' => $this->companyId,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $product = Product::factory()->create([
            'company_id' => $this->companyId,
        ]);

        $this->cashbackA->locations()->sync($location->id);

        $this->cashbackA->products()->sync($product->id);

        $response = $this->cashbackQueries->getListForPosWithRelatedData($location);

        expect($response)->toBeInstanceOf(Collection::class);

        expect($response->first()->toArray())
            ->toHaveKey('name', $this->cashbackA->name)
            ->toHaveKey('exclude_by_type', $this->cashbackA->exclude_by_type)
            ->toHaveKey('discount_value', $this->cashbackA->discount_value)
            ->toHaveKey('minimum_spend_amount', $this->cashbackA->minimum_spend_amount)
            ->toHaveKey('start_date', $this->cashbackA->start_date)
            ->toHaveKey('end_date', $this->cashbackA->end_date)
            ->toHaveKeys(['products', 'categories', 'cashback_prices']);
    }
);

test(
    'getByIdWithRelations method returns the cashback with related data as expected',
    function (): void {
        $location = Location::factory()->create([
            'company_id' => $this->companyId,
        ]);

        $product = Product::factory()->create([
            'company_id' => $this->companyId,
        ]);

        $this->cashbackA->locations()->sync($location->id);

        $this->cashbackA->products()->sync($product->id);

        $response = $this->cashbackQueries->getByIdWithRelations($this->cashbackA->id, $this->companyId);

        expect($response->toArray())
            ->toHaveKey('name', $this->cashbackA->name)
            ->toHaveKey('exclude_by_type', $this->cashbackA->exclude_by_type)
            ->toHaveKey('discount_value', $this->cashbackA->discount_value)
            ->toHaveKey('minimum_spend_amount', $this->cashbackA->minimum_spend_amount)
            ->toHaveKeys(['products', 'categories']);
    }
);

test('removeSelectedProducts method removes the selected products', function (): void {
    $cashback = Cashback::factory()->create([
        'company_id' => $this->companyId,
        'exclude_by_type' => ExcludeByTypes::PRODUCTS->value,
    ]);

    $productId = Product::factory()->create([
        'company_id' => $this->companyId,
    ])->id;

    $cashback->products()->attach([$productId]);

    $this->assertDatabaseHas('cashback_product', [
        'product_id' => $productId,
        'cashback_id' => $cashback->id,
    ]);

    $this->cashbackQueries->removeSelectedProducts([
        'id' => $cashback->id,
    ], $this->companyId);

    $this->assertDatabaseMissing('cashback_product', [
        'product_id' => $productId,
        'cashback_id' => $cashback->id,
    ]);
});

test('getCashbacksExport method returns cashback as expected', function (): void {
    $response = $this->cashbackQueries->getCashbacksExport([
        'search_text' => $this->cashbackA->name,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'date_range' => null,
        'location_ids' => null,
    ], $this->companyId);

    expect($response->first()->toArray())
        ->toHaveKey('name', $this->cashbackA->name)
        ->toHaveKey('exclude_by_type', $this->cashbackA->exclude_by_type)
        ->toHaveKey('discount_value', $this->cashbackA->discount_value)
        ->toHaveKey('minimum_spend_amount', $this->cashbackA->minimum_spend_amount);
});

test('getCashbacksForApplication method returns paginated results as expected', function (): void {
    $cashback = Cashback::factory()->create([
        'company_id' => $this->companyId,
        'exclude_by_type' => ExcludeByTypes::PRODUCTS->value,
        'start_date' => Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d'),
        'end_date' => Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d'),
    ]);

    $response = $this->cashbackQueries->getCashbacksForApplication([
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 1,
        'location_ids' => null,
        'search_text' => null,
        'selected_date' => Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d'),
    ], $this->companyId);

    expect($response->toArray()['data'][0])
        ->toHaveKey('name', $cashback->name)
        ->toHaveKey('exclude_by_type', $cashback->exclude_by_type);
});

test('getCashbacksStoreWiseForApplication method returns cashbacks as expected', function (): void {
    $cashback = Cashback::factory()->create([
        'company_id' => $this->companyId,
        'exclude_by_type' => ExcludeByTypes::PRODUCTS->value,
        'start_date' => Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d'),
        'end_date' => Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d'),
    ]);

    $location = Location::factory()->create([
        'company_id' => $this->companyId,
    ]);

    $cashback->locations()->sync($location->id);

    $response = $this->cashbackQueries->getCashbacksStoreWiseForApplication($this->companyId, $location->id);

    expect($response->first()->toArray())
        ->toHaveKey('name', $cashback->name)
        ->toHaveKey('exclude_by_type', $cashback->exclude_by_type);
});

test(
    'the getByIdWithCashbackProducts method can get cashback with products when product variant is false',
    function (): void {
        Config::set('app.product_variant', false);

        $cashback = Cashback::factory()->create([
            'company_id' => $this->companyId,
            'exclude_by_type' => ExcludeByTypes::PRODUCTS->value,
            'start_date' => Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d'),
            'end_date' => Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d'),
        ]);

        $response = $this->cashbackQueries->getByIdWithCashbackProducts($cashback->id, $this->companyId);

        expect($response->toArray())
            ->toHaveKey('id', $cashback->id)
            ->toHaveKeys(['products']);
    }
);

test(
    'the getByIdWithCashbackProducts method can get cashback with products when product variant is true',
    function (): void {
        Config::set('app.product_variant', false);

        $masterProduct = MasterProduct::factory()->create([
            'company_id' => $this->companyId,
            'has_batch' => false,
            'is_non_inventory' => false,
        ]);

        $product = Product::factory()->create([
            'company_id' => $this->companyId,
            'compound_product_name' => 'ABCD131333',
            'code' => '131313',
            'upc' => 'wrwrwr',
            'article_number' => '12346644',
            'status' => Statuses::ACTIVE->value,
            'is_non_inventory' => false,
            'is_non_selling_item' => false,
            'is_available_in_pos' => true,
            'is_available_in_ecommerce' => false,
            'master_product_id' => $masterProduct->id,
        ]);

        $cashback = Cashback::factory()->create([
            'company_id' => $this->companyId,
            'exclude_by_type' => ExcludeByTypes::PRODUCTS->value,
            'start_date' => Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d'),
            'end_date' => Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d'),
        ]);

        $cashback->products()->attach([$product->id]);

        $response = $this->cashbackQueries->getByIdWithCashbackProducts($cashback->id, $this->companyId);

        expect($response->toArray())
            ->toHaveKey('id', $cashback->id)
            ->toHaveKeys(['products']);
    }
);
