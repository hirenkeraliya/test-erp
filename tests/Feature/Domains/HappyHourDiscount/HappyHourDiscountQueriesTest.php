<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\HappyHourDiscount\DataObjects\HappyHourDiscountData;
use App\Domains\HappyHourDiscount\DataObjects\HappyHourDiscountDataForPos;
use App\Domains\HappyHourDiscount\Enums\ProductTypes;
use App\Domains\HappyHourDiscount\HappyHourDiscountQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Models\Admin;
use App\Models\Brand;
use App\Models\Cashier;
use App\Models\Category;
use App\Models\Company;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Department;
use App\Models\Employee;
use App\Models\HappyHourDiscount;
use App\Models\HappyHourDiscountTransaction;
use App\Models\Location;
use App\Models\StoreManager;
use App\Models\Style;

beforeEach(function (): void {
    $this->companyId = Company::factory()->create()->id;
    $this->cashier = Cashier::factory()->create();
    $this->location = Location::factory()->create([
        'id' => 1,
        'company_id' => $this->companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);
    $this->counter = Counter::factory()->create([
        'location_id' => $this->location->id,
    ]);

    $this->counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $this->counter->id,
        'cashier_id' => $this->cashier->id,
    ]);

    $this->happyHourDiscount = HappyHourDiscount::factory()->create([
        'location_id' => $this->location->id,
        'company_id' => $this->companyId,
        'name' => 'Abc',
    ]);

    $this->happyHourDiscountTransaction = HappyHourDiscountTransaction::factory()->create([
        'happy_hour_discount_id' => $this->happyHourDiscount->id,
        'counter_update_id' => $this->counterUpdate->id,
    ]);

    $this->happyHourDiscount->happyHourDiscountTransaction = $this->happyHourDiscountTransaction;

    $this->happyHourDiscountQueries = new HappyHourDiscountQueries();
});

test(
    'getPaginatedHappyHourDiscounts method returns happy hour discount list as expected',
    function (): void {
        $happyHourDiscountQueries = new HappyHourDiscountQueries();
        $happyHourDiscount = HappyHourDiscount::factory()->create();
        $filterData = [
            'per_page' => 10,
            'company_id' => $happyHourDiscount->company_id,
            'product_type_id' => $happyHourDiscount->product_type_id,
            'search_text' => null,
            'sort_by' => 'id',
            'sort_direction' => 'desc',
            'location_id' => $happyHourDiscount->location_id,
            'after_updated_at' => null,
        ];
        $response = $happyHourDiscountQueries->getPaginatedHappyHourDiscounts($filterData);
        expect($response->first()->toArray())
            ->toHaveKeys(
                [
                    'id',
                    'location_id',
                    'product_type_id',
                    'name',
                    'new_price',
                    'start_date',
                    'end_date',
                    'happy_hour_discount_transaction',
                    'happy_hour_discount_transactions',
                ]
            );
    }
);

test('New happyHourDiscount can be added', function (): void {
    $cashier = Cashier::factory()->create();
    $happyHourDiscount = $this->happyHourDiscountQueries->addNew(
        new HappyHourDiscountDataForPos(
            'hhdXYZ',
            ProductTypes::ALL->value,
            'abc',
            '500',
            '2024-01-04 04:25:50',
            '2024-01-04 04:50:50',
            '2024-01-04 04:20:50',
            1,
            '123456',
            null,
            null,
            null
        ),
        $this->companyId,
        $cashier,
        $this->location->id,
        $this->counterUpdate->id,
    );

    expect($happyHourDiscount->toArray())
        ->toHaveKey('location_id', $this->location->id)
        ->toHaveKey('product_type_id', ProductTypes::ALL->value)
        ->toHaveKey('name', 'abc')
        ->toHaveKey('new_price', 500.00);

    $this->assertDatabaseHas('happy_hour_discounts', [
        'location_id' => $this->location->id,
        'product_type_id' => ProductTypes::ALL->value,
        'name' => 'abc',
        'new_price' => 500.00,
    ]);

    $this->assertDatabaseHas('happy_hour_discount_transactions', [
        'happy_hour_discount_id' => $happyHourDiscount->id,
        'counter_update_id' => $this->counterUpdate->id,
        'offline_id' => 'hhdXYZ',
    ]);
});

test('New happyHourDiscount can be added with product type brand', function (): void {
    $brand = Brand::factory()->create();
    $cashier = Cashier::factory()->create();

    $happyHourDiscount = $this->happyHourDiscountQueries->addNew(
        new HappyHourDiscountDataForPos(
            'hhdXYZ',
            ProductTypes::BRAND->value,
            'abc',
            '500',
            '2024-01-04 04:25:50',
            '2024-01-04 04:50:50',
            '2024-01-04 04:20:50',
            1,
            '123456',
            null,
            null,
            null,
            [$brand->id]
        ),
        $this->companyId,
        $cashier,
        $this->location->id,
        $this->counterUpdate->id,
    );

    expect($happyHourDiscount->toArray())
        ->toHaveKey('location_id', $this->location->id)
        ->toHaveKey('product_type_id', ProductTypes::BRAND->value)
        ->toHaveKey('name', 'abc')
        ->toHaveKey('new_price', 500.00);

    $this->assertDatabaseHas('happy_hour_discounts', [
        'location_id' => $this->location->id,
        'product_type_id' => ProductTypes::BRAND->value,
        'name' => 'abc',
        'new_price' => 500.00,
    ]);

    $this->assertDatabaseHas('happy_hour_discount_transactions', [
        'happy_hour_discount_id' => $happyHourDiscount->id,
        'counter_update_id' => $this->counterUpdate->id,
        'offline_id' => 'hhdXYZ',
    ]);

    $this->assertDatabaseHas('brand_happy_hour_discount', [
        'brand_id' => $brand->id,
        'happy_hour_discount_id' => $happyHourDiscount->id,
    ]);
});

test('New happyHourDiscount can be added with product type department', function (): void {
    $cashier = Cashier::factory()->create();
    $department = Department::factory()->create();

    $happyHourDiscount = $this->happyHourDiscountQueries->addNew(
        new HappyHourDiscountDataForPos(
            'hhdXYZ',
            ProductTypes::DEPARTMENTS->value,
            'abc',
            '500',
            '2024-01-04 04:25:50',
            '2024-01-04 04:50:50',
            '2024-01-04 04:20:50',
            1,
            '123456',
            null,
            null,
            null,
            null,
            null,
            null,
            [$department->id]
        ),
        $this->companyId,
        $cashier,
        $this->location->id,
        $this->counterUpdate->id,
    );

    expect($happyHourDiscount->toArray())
        ->toHaveKey('location_id', $this->location->id)
        ->toHaveKey('product_type_id', ProductTypes::DEPARTMENTS->value)
        ->toHaveKey('name', 'abc')
        ->toHaveKey('new_price', 500.00);

    $this->assertDatabaseHas('happy_hour_discounts', [
        'location_id' => $this->location->id,
        'product_type_id' => ProductTypes::DEPARTMENTS->value,
        'name' => 'abc',
        'new_price' => 500.00,
    ]);

    $this->assertDatabaseHas('happy_hour_discount_transactions', [
        'happy_hour_discount_id' => $happyHourDiscount->id,
        'counter_update_id' => $this->counterUpdate->id,
        'offline_id' => 'hhdXYZ',
    ]);

    $this->assertDatabaseHas('department_happy_hour_discount', [
        'department_id' => $department->id,
        'happy_hour_discount_id' => $happyHourDiscount->id,
    ]);
});

test('New happyHourDiscount can be added with product type style', function (): void {
    $cashier = Cashier::factory()->create();
    $style = Style::factory()->create();

    $happyHourDiscount = $this->happyHourDiscountQueries->addNew(
        new HappyHourDiscountDataForPos(
            'hhdXYZ',
            ProductTypes::STYLE->value,
            'abc',
            '500',
            '2024-01-04 04:25:50',
            '2024-01-04 04:50:50',
            '2024-01-04 04:20:50',
            1,
            '123456',
            null,
            null,
            null,
            null,
            null,
            [$style->id]
        ),
        $this->companyId,
        $cashier,
        $this->location->id,
        $this->counterUpdate->id,
    );

    expect($happyHourDiscount->toArray())
        ->toHaveKey('location_id', $this->location->id)
        ->toHaveKey('product_type_id', ProductTypes::STYLE->value)
        ->toHaveKey('name', 'abc')
        ->toHaveKey('new_price', 500.00);

    $this->assertDatabaseHas('happy_hour_discounts', [
        'location_id' => $this->location->id,
        'product_type_id' => ProductTypes::STYLE->value,
        'name' => 'abc',
        'new_price' => 500.00,
    ]);

    $this->assertDatabaseHas('happy_hour_discount_transactions', [
        'happy_hour_discount_id' => $happyHourDiscount->id,
        'counter_update_id' => $this->counterUpdate->id,
        'offline_id' => 'hhdXYZ',
    ]);

    $this->assertDatabaseHas('happy_hour_discount_style', [
        'style_id' => $style->id,
        'happy_hour_discount_id' => $happyHourDiscount->id,
    ]);
});

test('New happyHourDiscount can be added with product type category', function (): void {
    $cashier = Cashier::factory()->create();
    $category = Category::factory()->create();

    $happyHourDiscount = $this->happyHourDiscountQueries->addNew(
        new HappyHourDiscountDataForPos(
            'hhdXYZ',
            ProductTypes::CATEGORY->value,
            'abc',
            '500',
            '2024-01-04 04:25:50',
            '2024-01-04 04:50:50',
            '2024-01-04 04:20:50',
            1,
            '123456',
            null,
            null,
            null,
            null,
            [$category->id]
        ),
        $this->companyId,
        $cashier,
        $this->location->id,
        $this->counterUpdate->id,
    );

    expect($happyHourDiscount->toArray())
        ->toHaveKey('location_id', $this->location->id)
        ->toHaveKey('product_type_id', ProductTypes::CATEGORY->value)
        ->toHaveKey('name', 'abc')
        ->toHaveKey('new_price', 500.00);

    $this->assertDatabaseHas('happy_hour_discounts', [
        'location_id' => $this->location->id,
        'product_type_id' => ProductTypes::CATEGORY->value,
        'name' => 'abc',
        'new_price' => 500.00,
    ]);

    $this->assertDatabaseHas('happy_hour_discount_transactions', [
        'happy_hour_discount_id' => $happyHourDiscount->id,
        'counter_update_id' => $this->counterUpdate->id,
        'offline_id' => 'hhdXYZ',
    ]);

    $this->assertDatabaseHas('category_happy_hour_discount', [
        'category_id' => $category->id,
        'happy_hour_discount_id' => $happyHourDiscount->id,
    ]);
});

test('Happy hour discount are returned as per page', function (): void {
    $response = $this->happyHourDiscountQueries->listQuery([
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 1,
    ], $this->companyId);

    $this->assertEquals(1, $response->count());
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->happyHourDiscount->name);
});

test('A happy hour discount can be fetched', function (): void {
    $response = $this->happyHourDiscountQueries->getById($this->happyHourDiscount->id, $this->companyId);

    expect($response->toArray())
        ->toHaveKey('name', $this->happyHourDiscount->name)
        ->toHaveKey('new_price', $this->happyHourDiscount->new_price);
});

test('New Happy hour discount can be added', function (): void {
    $user = Admin::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);

    $happyHourDiscountRecord = HappyHourDiscount::factory()->make([
        'name' => 'def',
        'location_id' => $this->location->id,
        'company_id' => $this->companyId,
        'product_type_id' => ProductTypes::ALL->value,
        'start_date' => '2023-01-01',
        'end_date' => '2023-01-01',
    ])->toArray();

    unset($happyHourDiscountRecord['company_id']);

    $this->happyHourDiscountQueries->addNewForAdmin(
        new HappyHourDiscountData(...$happyHourDiscountRecord),
        $user,
        $this->companyId
    );

    $this->assertDatabaseHas('happy_hour_discounts', $happyHourDiscountRecord);
});

test('A Happy hour discount can be updated', function (): void {
    $happyHourDiscountRecord = HappyHourDiscount::factory()->make([
        'name' => 'abcd',
        'location_id' => $this->location->id,
        'company_id' => $this->companyId,
        'product_type_id' => ProductTypes::ALL->value,
        'start_date' => '2023-01-01',
        'end_date' => '2023-01-01',
    ])->toArray();

    unset($happyHourDiscountRecord['company_id']);

    $this->happyHourDiscountQueries->update(
        new HappyHourDiscountData(...$happyHourDiscountRecord),
        $this->happyHourDiscount->id,
        $this->companyId
    );

    $this->assertDatabaseHas('happy_hour_discounts', $happyHourDiscountRecord);
});

test('happyHourDiscountExport method returns happy hour discount as expected', function (): void {
    $response = $this->happyHourDiscountQueries->happyHourDiscountExport([
        'search_text' => 'Abc',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyId);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->happyHourDiscount->id)
        ->toHaveKey('name', $this->happyHourDiscount->name);
});

test('getByOfflineIdsWithRelations return happy hour discount collection', function (): void {
    $response = $this->happyHourDiscountQueries->getByOfflineIdsWithRelations(
        [$this->happyHourDiscountTransaction->offline_id],
        $this->companyId
    );

    expect($response->first()->toArray())
        ->toHaveKey('name', $this->happyHourDiscount->name)
        ->toHaveKey('new_price', $this->happyHourDiscount->new_price)
        ->toHaveKey('happy_hour_discount_transaction.offline_id', $this->happyHourDiscountTransaction->offline_id)
        ->toHaveKeys(['location', 'styles', 'brands', 'categories', 'departments']);
});

test('New Happy hour discount not created but notification send when duplicate found', function (): void {
    $employee = Employee::factory()->create([
        'company_id' => $this->companyId,
    ]);

    $storeManager = StoreManager::factory()->create([
        'employee_id' => $employee->id,
    ]);

    $storeManager->locations()->sync($this->location->id);

    HappyHourDiscount::factory()->create([
        'name' => 'def',
        'location_id' => $this->location->id,
        'company_id' => $this->companyId,
        'product_type_id' => ProductTypes::ALL->value,
        'start_date' => '2024-01-04 04:25:50',
        'end_date' => '2024-01-04 04:50:50',
        'new_price' => 500,
    ]);

    $cashier = Cashier::factory()->create();
    $happyHourDiscount = $this->happyHourDiscountQueries->addNew(
        new HappyHourDiscountDataForPos(
            'hhdXYZ',
            ProductTypes::ALL->value,
            'def',
            '500',
            '2024-01-04 04:25:50',
            '2024-01-04 04:50:50',
            '2024-01-04 04:20:50',
            1,
            '123456',
            null,
            null,
            null
        ),
        $this->companyId,
        $cashier,
        $this->location->id,
        $this->counterUpdate->id,
    );

    $this->assertDatabaseHas('happy_hour_discounts', [
        'location_id' => $this->location->id,
        'product_type_id' => ProductTypes::ALL->value,
        'name' => 'def',
        'new_price' => 500.00,
    ]);

    $this->assertDatabaseHas('happy_hour_discount_transactions', [
        'happy_hour_discount_id' => $happyHourDiscount->id,
        'counter_update_id' => $this->counterUpdate->id,
        'offline_id' => 'hhdXYZ',
    ]);

    $this->assertDatabaseHas('notifications', [
        'to_user_id' => $storeManager->id,
        'to_user_type' => ModelMapping::STORE_MANAGER->name,
        'message' => 'Happy Hour discount name :def is overlapping By '. $cashier->username,
    ]);
});
