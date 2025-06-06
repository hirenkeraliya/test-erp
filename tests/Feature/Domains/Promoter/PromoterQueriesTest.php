<?php

declare(strict_types=1);

use App\Domains\Common\Enums\SaleReturnOrVoidSaleReasonTypes;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Promoter\DataObjects\ChangePasswordData;
use App\Domains\Promoter\DataObjects\PromoterData;
use App\Domains\Promoter\Enums\SalesByPromoterReportTypes;
use App\Domains\Promoter\PromoterQueries;
use App\Domains\Sale\Enums\SaleStatus;
use App\Models\Admin;
use App\Models\Company;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Employee;
use App\Models\location;
use App\Models\Product;
use App\Models\Promoter;
use App\Models\PromoterGroup;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

beforeEach(function (): void {
    $this->company = Company::factory()->create();

    $this->promoterGroupA = PromoterGroup::factory()->create([
        'company_id' => $this->company->id,
        'type_id' => SaleReturnOrVoidSaleReasonTypes::POS->value,
    ]);

    $this->employeeA = Employee::factory()->create([
        'company_id' => $this->company->id,
        'first_name' => 'ABCD',
    ]);

    $this->promoterA = Promoter::factory()->create([
        'employee_id' => $this->employeeA->id,
        'group_id' => $this->promoterGroupA->id,
        'username' => 'test',
    ]);

    $this->employeeB = Employee::factory()->create([
        'company_id' => $this->company->id,
        'first_name' => 'XYZ',
    ]);

    $this->employeeC = Employee::factory()->create([
        'company_id' => $this->company->id,
        'first_name' => 'XYZ',
    ]);

    $this->promoterB = Promoter::factory()->create([
        'employee_id' => $this->employeeB->id,
        'group_id' => $this->promoterGroupA->id,
    ]);

    $this->promoterQueries = new PromoterQueries();
});

test('promoters can be searched', function (): void {
    $response = $this->promoterQueries->listQuery([
        'search_text' => 'ABCD',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'location_ids' => null,
        'group_ids' => null,
    ], $this->company->id);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('employee.first_name', $this->employeeA->first_name);
});

test('getPaginatedSalesByPromoters method returns sales by promoters as expected', function (): void {
    $sale = Sale::factory()->create([
        'layaway_pending_amount' => null,
        'status' => SaleStatus::REGULAR_SALE->value,
    ]);
    $saleItem = SaleItem::factory()->create([
        'sale_id' => $sale->id,
    ]);

    $saleItem->promoters()->sync($this->promoterA->id);

    $response = $this->promoterQueries->getPaginatedSalesByPromoters([
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'date_range' => null,
        'promoter_id' => $this->promoterA->id,
        'location_ids' => null,
        'brand_ids' => null,
        'department_ids' => null,
        'group_ids' => null,
        'sales_filter_types' => [1],
    ], $this->company->id);

    $this->assertEquals(1, $response->total());
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('id', $this->promoterA->id)
        ->toHaveKeys([
            'employee',
            'employee.first_name',
            'employee.last_name',
            'employee.staff_id',
            'total_units_sold',
            'total_amount_sold',
            'total_discount_amount',
            'total_tax_amount',
            'total_units_returned',
            'total_returned_amount',
        ]);
});

test(
    'getItemSoldCountForTheGivenPromoter method returns sale items unit sold and sales return item unit return counts of the promoters as expected',
    function (): void {
        $location = location::factory()->create([
            'company_id' => $this->company->id,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $counter = Counter::factory()->create([
            'location_id' => $location->id,
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->id,
        ]);

        $sale = Sale::factory()->create([
            'counter_update_id' => $counterUpdate->id,
            'status' => SaleStatus::REGULAR_SALE->value,
            'happened_at' => '2023-07-01 00:00:00',
        ]);

        $saleItem = SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'quantity' => 10,
        ]);

        $saleItem->promoters()->sync($this->promoterA->id);

        $this->promoterA->locations()->sync($location->id);

        $response = $this->promoterQueries->getItemSoldCountForTheGivenPromoter(
            ['2023-07-01', '2023-07-31'],
            $location->id,
            $this->promoterA->id
        );

        expect($response->toArray())
            ->toHaveKey('id', $this->promoterA->id)
            ->toHaveKeys(['total_units_sold']);
    }
);

test(
    'getPromotersWiseSales method returns sales and sales return counts and amount of the promoters as expected',
    function (): void {
        $location = location::factory()->create([
            'company_id' => $this->company->id,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $counter = Counter::factory()->create([
            'location_id' => $location->id,
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->id,
        ]);

        $sale = Sale::factory()->create([
            'counter_update_id' => $counterUpdate->id,
            'status' => SaleStatus::REGULAR_SALE->value,
            'happened_at' => '2023-07-01 00:00:00',
        ]);

        $saleItem = SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'quantity' => 10,
        ]);

        $saleItem->promoters()->sync($this->promoterA->id);

        $this->promoterA->locations()->sync($location->id);

        $response = $this->promoterQueries->getPromotersWiseSales([
            'sort_by' => 'id',
            'sort_direction' => 'desc',
            'per_page' => 15,
            'start_date' => '2023-07-01',
            'end_date' => '2023-07-31',
            'location_id' => $location->id,
        ], $this->promoterA->id);

        $this->assertEquals(1, $response->total());
        expect($response->getCollection()->first()->toArray())
        ->toHaveKey('id', $this->promoterA->id)
        ->toHaveKeys([
            'total_units_sold',
            'total_amount_sold',
            'happened_at',
            'total_units_returned',
            'total_returned_amount',
            'total_amount',
        ]);
    }
);

test('New promoter can be added', function (): void {
    $employee = Employee::factory()->create([
        'company_id' => $this->company->id,
        'first_name' => 'Test',
    ]);

    $promoter = Promoter::factory()->make([
        'employee_id' => $employee->id,
        'username' => 'ABCDE',
        'password' => '12345',
    ]);

    $monthlySalesTarget = 100;
    $code = 'test';

    $location = location::factory()->create([
        'type_id' => LocationTypes::STORE->value,
    ]);

    $admin = Admin::factory()->create();

    $this->promoterQueries->addNew(
        new PromoterData($employee->id, $promoter->username, $promoter->password, $monthlySalesTarget, $code, [
            $location->id,
        ], 0.00, 0.00),
        $admin
    );

    $this->assertDatabaseHas('promoters', [
        'employee_id' => $employee->id,
        'monthly_sales_target' => $monthlySalesTarget,
        'code' => $code,
    ]);

    $this->assertDatabaseHas('location_promoter', [
        'location_id' => $location->id,
    ]);
});

test('A promoter can be fetched with employee and locations', function (): void {
    $location = location::factory()->create([]);
    $this->promoterA->locations()->sync($location->id);

    setCompanyIdInSession($this->company->id);

    $response = $this->promoterQueries->getByIdWithEmployeeAndLocations($this->promoterA->id, $this->company->id);
    expect($response->toArray())
        ->toHaveKey('employee_id', $this->employeeA->id)
        ->toHaveKey('monthly_sales_target', $this->promoterA->monthly_sales_target)
        ->toHaveKey('locations.0.id', $location->id)
        ->toHaveKey('employee.id', $this->employeeA->id);
});

test('A promoter can be updated', function (): void {
    $location = location::factory()->create([
        'type_id' => LocationTypes::STORE->value,
    ]);

    setCompanyIdInSession($this->company->id);
    $monthlySalesTarget = 100;

    $this->promoterQueries->update(
        new PromoterData($this->employeeA->id, 'ABCD', '12345', $monthlySalesTarget, 'test', [
            $location->id,
        ], 0.00, 0.00),
        $this->promoterA->id,
        $this->company->id
    );

    $this->assertDatabaseHas('promoters', [
        'employee_id' => $this->employeeA->id,
        'monthly_sales_target' => $monthlySalesTarget,
        'username' => 'ABCD',
        'code' => 'test',
    ]);

    $this->assertDatabaseHas('location_promoter', [
        'location_id' => $location->id,
    ]);
});

test('A promoter set fcm token', function (): void {
    $this->promoterQueries->updateFcmToken($token = 'test1234', $this->promoterA->id, $this->company->id);

    $this->assertDatabaseHas('promoters', [
        'fcm_token' => $token,
    ]);
});

test('A promoter can be fetched', function (): void {
    $location = location::factory()->create([]);
    $this->promoterA->locations()->sync($location->id);

    setCompanyIdInSession($this->company->id);

    $response = $this->promoterQueries->getById($this->promoterA->id, $this->company->id);
    expect($response->toArray())
        ->toHaveKey('employee_id', $this->employeeA->id);
});

test(
    'getPromoterListForPosAndOrders method return the list',
    function (): void {
        $location = location::factory()->create([
            'company_id' => $this->company->id,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $employeeC = Employee::factory()->create([
            'company_id' => $this->company->id,
            'first_name' => 'MNO',
        ]);

        $promoterGroup = PromoterGroup::factory()->create([
            'company_id' => $this->company->id,
            'type_id' => SaleReturnOrVoidSaleReasonTypes::ORDERS->value,
        ]);

        Promoter::factory()->create([
            'employee_id' => $employeeC->id,
            'group_id' => $promoterGroup->id,
        ]);

        $this->promoterA->locations()->attach($location->id);

        $response = $this->promoterQueries->getPromoterListForPosAndOrders($location->id, $this->company->id);

        $PromoterCount = Promoter::whereHas('locations', function ($query) use ($location): void {
            $query->where('id', $location->id);
        })
            ->where(function ($query): void {
                $query->whereNull('group_id')
                    ->orWhereHas('promoterGroup', function ($query): void {
                        $query->where('type_id', SaleReturnOrVoidSaleReasonTypes::POS->value);
                    });
            })
            ->count();

        expect($response)->toBeInstanceOf(Collection::class);
        expect($response->count())->toBe($PromoterCount);

        expect($response->first()->toArray())
            ->toHaveKey('employee.first_name', $this->employeeA->first_name)
            ->toHaveKey('employee.email', $this->employeeA->email)
            ->toHaveKey('employee_id', $this->employeeA->id);
    }
);

test(
    'getPromoterListWithLocationsForStoreManagerAPI method return the list',
    function (): void {
        $location = location::factory()->create([
            'company_id' => $this->company->id,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $filterData = [
            'location_id' => $location->id,
            'search_text' => null,
        ];

        $this->promoterA->locations()->attach($location->id);

        $response = $this->promoterQueries->getPromoterListWithLocationsForStoreManagerAPI(
            $location->company_id,
            $filterData
        );

        expect($response->first()->toArray())
            ->toHaveKey('employee.first_name', $this->employeeA->first_name)
            ->toHaveKey('employee.email', $this->employeeA->email)
            ->toHaveKey('employee_id', $this->employeeA->id);
    }
);

test('doAllPromotersExist method works as expected', function (): void {
    $response = $this->promoterQueries->doAllPromotersExist(
        [$this->promoterA->id, $this->promoterB->id],
        $this->company->id
    );
    expect($response)->toBeTrue();
});

test('A promoter can be fetched with locations', function (): void {
    $location = location::factory()->create([]);
    $this->promoterA->locations()->sync($location->id);

    $response = $this->promoterQueries->getPromotersWithLocations();

    expect($response->first()->toArray())
        ->toHaveKey('monthly_sales_target', $this->promoterA->monthly_sales_target)
        ->toHaveKey('locations.0.id', $location->id);
});

test('getSalesByPromotersExport method returns the promoter sale data with relations for export', function (): void {
    $sale = Sale::factory()->create([
        'layaway_pending_amount' => null,
        'status' => SaleStatus::REGULAR_SALE->value,
    ]);

    $saleItem = SaleItem::factory()->create([
        'sale_id' => $sale->id,
    ]);

    $saleItem->promoters()->sync($this->promoterA->id);

    $response = $this->promoterQueries->getSalesByPromotersExport([
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'date_range' => null,
        'promoter_id' => $this->promoterA->id,
        'location_ids' => null,
        'brand_ids' => null,
        'department_ids' => null,
        'group_ids' => null,
        'sales_filter_types' => [1],
    ], $this->company->id);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->promoterA->id)
        ->toHaveKeys([
            'employee',
            'employee.first_name',
            'employee.last_name',
            'employee.staff_id',
            'total_units_sold',
            'total_amount_sold',
            'total_discount_amount',
            'total_tax_amount',
            'total_units_returned',
            'total_returned_amount',
        ]);
});

test(
    'getAllWithMonthlySalesAndCompanyDetailsForPeriod method returns the period wise promoter monthly sales',
    function (): void {
        [$location, $counterUpdate] = seedRecordsForPromoterQueries($this->company->id);

        $currentDate = Carbon::now()->subMonthNoOverflow()->startOfMonth();
        $startDate = Carbon::now()->subMonthNoOverflow()->startOfMonth()->toDateTimeString();
        $endDate = Carbon::now()->subMonthNoOverflow()->endOfMonth()->toDateTimeString();

        $sale = Sale::factory()->create([
            'counter_update_id' => $counterUpdate->id,
            'layaway_pending_amount' => null,
            'status' => SaleStatus::REGULAR_SALE->value,
            'happened_at' => $currentDate,
        ]);

        $saleItem = SaleItem::factory()->create([
            'sale_id' => $sale->id,
        ]);

        $saleItem->promoters()->sync($this->promoterA);

        $response = $this->promoterQueries->getAllWithMonthlySalesAndCompanyDetailsForPeriod(
            $startDate,
            $endDate,
            [$this->promoterA->id]
        );

        expect($response->first()->toArray())
            ->toHaveKeys(
                [
                    'employee_id',
                    'monthly_sales_target',
                    'default_commission_amount_percentage',
                    'monthly_target_commission_percentage',
                ]
            );
    }
);

test(
    'getPromoterCommissionReturnItemsByIdAndPeriod method returns the period wise promoter monthly return items',
    function (): void {
        [$location, $counterUpdate] = seedRecordsForPromoterQueries($this->company->id);

        $currentDate = Carbon::now()->subMonthNoOverflow()->startOfMonth();
        $startDate = Carbon::now()->subMonthNoOverflow()->startOfMonth()->toDateTimeString();
        $endDate = Carbon::now()->subMonthNoOverflow()->endOfMonth()->toDateTimeString();

        $saleItem = SaleItem::factory()->create();

        $saleReturn = SaleReturn::factory()->create([
            'counter_update_id' => $counterUpdate->id,
            'happened_at' => $currentDate,
        ]);

        SaleReturnItem::factory()->create([
            'sale_return_id' => $saleReturn->id,
            'original_sale_item_id' => $saleItem->id,
        ]);

        $saleItem->promoters()->sync($this->promoterA);

        $response = $this->promoterQueries->getPromoterCommissionReturnItemsByIdAndPeriod(
            $this->promoterA->id,
            $startDate,
            $endDate
        );

        expect($response->first()->toArray())
            ->toHaveKeys(['id', 'sale_return_id', 'original_sale_item_id', 'total_price_paid']);
    }
);

test('getPromoterByLocations method returns promoter as expected by location ids', function (): void {
    $location = location::factory()->create([
        'company_id' => $this->company->id,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->promoterA->locations()->sync($location->id);

    $response = $this->promoterQueries->getPromoterByLocations([$location->id]);

    expect($response->first()->toArray())
        ->toHaveKey('employee.first_name', $this->employeeA->first_name)
        ->toHaveKeys(['employee']);
});

test('getPromoterByPromoterGroup method returns promoter as expected by promoter group ids', function (): void {
    $response = $this->promoterQueries->getPromoterByPromoterGroup([$this->promoterGroupA->id]);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->promoterA->id)
        ->toHaveKeys(['id']);
});

test('getPromoterListOfSelectedStore method returns promoter as expected by location id', function (): void {
    $location = location::factory()->create([
        'company_id' => $this->company->id,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->promoterA->locations()->sync($location->id);

    $response = $this->promoterQueries->getPromoterListOfSelectedStore($location->id);

    expect($response->first()->toArray())
        ->toHaveKey('employee.first_name', $this->employeeA->first_name)
        ->toHaveKeys(['employee']);
});

test('doExistsByEmployeeId method returns promoter as expected', function (): void {
    $response = $this->promoterQueries->doExistsByEmployeeId($this->employeeA->id);
    expect($response)->toBeTrue();

    $response = $this->promoterQueries->doExistsByEmployeeId(null);
    expect($response)->toBeFalse();
});

test('doesCodeExist method returns promoter as expected', function (): void {
    $response = $this->promoterQueries->doesCodeExist($this->promoterA->code, $this->company->id, null);
    expect($response)->toBeTrue();

    $response = $this->promoterQueries->doesCodeExist('.', $this->company->id, null);
    expect($response)->toBeFalse();
});

test('getPromotersExport method returns promoter as expected', function (): void {
    $response = $this->promoterQueries->getPromotersExport([
        'search_text' => 'ABCD',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'location_ids' => null,
        'group_ids' => null,
    ], $this->company->id);

    expect($response->first()->toArray())
        ->toHaveKey('employee.first_name', $this->employeeA->first_name);
});

test('getForSalesByPromotersByDetailsReport method returns sales by promoters as expected', function (): void {
    [$location, $counterUpdate, $companyId] = seedRecordsForPromoterQueries($this->company->id);

    $productId = Product::factory()->create([
        'company_id' => $companyId,
        'is_non_selling_item' => false,
    ])->id;

    $date = now();

    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'layaway_pending_amount' => null,
        'status' => SaleStatus::REGULAR_SALE->value,
        'happened_at' => $date->format('Y-m-d H:i:s'),
    ]);

    $saleItem = SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'is_exchange' => false,
        'sale_return_item_id' => null,
        'product_id' => $productId,
    ]);

    $saleItem->promoters()->sync($this->promoterA);

    $this->promoterA->locations()->sync($location->id);

    $response = $this->promoterQueries->getForSalesByPromotersByDetailsReport([
        'location_ids' => [$location->id],
        'date_range' => [$date->format('Y-m-d'), $date->format('Y-m-d')],
        'filter_by' => SalesByPromoterReportTypes::BY_DETAILS->value,
        'brand_ids' => null,
        'category_ids' => null,
        'department_ids' => null,
        'group_ids' => null,
        'promoter_ids' => [$this->promoterA->id],
    ], $companyId);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->promoterA->id)
        ->toHaveKeys(
            [
                'employee',
                'sale_items',
                'sale_items.0.product',
                'sale_items.0.sale',
                'sale_items.0.sale.counter_update',
                'sale_items.0.sale.counter_update.counter',
            ]
        );
});

test('getIds method returns promoter ids as expected', function (): void {
    $response = $this->promoterQueries->getIds();
    expect($response->first()->toArray())
        ->toHaveKey('id');
});

test('getPromoterCount method returns promoter as expected by location id', function (): void {
    $location = location::factory()->create([
        'company_id' => $this->company->id,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->promoterA->locations()->sync($location->id);

    $response = $this->promoterQueries->getPromoterCount($location->id);

    expect($response)->toEqual(1);
});

function seedRecordsForPromoterQueries(int $companyId): array
{
    $location = location::factory()->create([
        'company_id' => $companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $counter = Counter::factory()->create([
        'location_id' => $location->id,
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
    ]);

    return [$location, $counterUpdate, $companyId];
}

test('getSalesByPromotersTotals method returns the promoter sale totals', function (): void {
    $sale = Sale::factory()->create([
        'layaway_pending_amount' => null,
        'status' => SaleStatus::REGULAR_SALE->value,
    ]);

    $saleItem = SaleItem::factory()->create([
        'sale_id' => $sale->id,
    ]);

    $saleItem->promoters()->sync($this->promoterA->id);

    $response = $this->promoterQueries->getSalesByPromotersTotals([
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'date_range' => null,
        'promoter_id' => $this->promoterA->id,
        'location_ids' => null,
        'brand_ids' => null,
        'department_ids' => null,
        'sales_filter_types' => [1],
    ], $this->company->id);

    expect($response)
        ->toHaveKeys([
            'total_units_returned',
            'total_returned_amount',
            'total_tax_amount',
            'total_discount_amount',
            'total_units_sold',
            'total_amount_sold',
        ]);
});

test('A promoter can update password', function (): void {
    $requestParameter = [
        'new_password' => '123456789',
    ];

    $promoter = Promoter::factory()->create([
        'password' => bcrypt('123456'),
    ]);

    $this->promoterQueries->changePassword($promoter, new ChangePasswordData(...$requestParameter));

    $promoter->refresh();
    $this->assertTrue(Hash::check($requestParameter['new_password'], $promoter->password));
});

test(
    'getSalesByPromotersForDashboard method returns the promoter sale data with relations for export',
    function (): void {
        $date = now();
        $sale = Sale::factory()->create([
            'layaway_pending_amount' => null,
            'status' => SaleStatus::REGULAR_SALE->value,
            'happened_at' => $date->format('Y-m-d H:i:s'),
        ]);

        $saleItem = SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'quantity' => 50,
            'total_price_paid' => 50,
        ]);

        $this->promoterA->employee = $this->employeeA;

        $saleItem->promoters()->attach([$this->promoterA->id]);

        $response = $this->promoterQueries->getSalesByPromotersForDashboard(
            $this->company->id,
            null,
            null,
            $date->format('Y-m-d'),
            $date->format('Y-m-d')
        );

        expect($response->first()->toArray())
            ->toHaveKey('id', $this->promoterA->id)
            ->toHaveKeys([
                'employee',
                'employee.first_name',
                'employee.last_name',
                'employee.staff_id',
                'units_sold',
                'amount_sold',
            ]);
    }
);

test('it retrieves a collection of promoters', function (): void {
    $employee = Employee::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $promoter = Promoter::factory()->create([
        'employee_id' => $employee->id,
    ]);

    $response = $this->promoterQueries->getByIds([$promoter->id]);
    expect($response)->toBeInstanceOf(Collection::class);
    expect(collect($response)->first()->toArray())
        ->toHaveKey('employee.first_name', $employee->first_name);
});

test('getTotalAmountForSalePromoterTarget method returns sales by promoters as expected', function (): void {
    [$location, $counterUpdate, $companyId] = seedRecordsForPromoterQueries($this->company->id);

    $productId = Product::factory()->create([
        'company_id' => $companyId,
        'is_non_selling_item' => false,
    ])->id;

    $date = now();

    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'layaway_pending_amount' => null,
        'status' => SaleStatus::REGULAR_SALE->value,
        'happened_at' => $date->format('Y-m-d H:i:s'),
    ]);

    $saleItem = SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'is_exchange' => false,
        'sale_return_item_id' => null,
        'product_id' => $productId,
        'total_price_paid' => 100.20,
    ]);

    $saleItem->promoters()->attach([$this->promoterA->id, $this->promoterB->id]);

    $this->promoterA->locations()->sync($location->id);

    $response = $this->promoterQueries->getTotalAmountForSalePromoterTarget(
        $date->format('Y-m-d'),
        $date->format('Y-m-d'),
        [$this->promoterA->id]
    );

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->promoterA->id)
        ->toHaveKey('amount_sold', 50.10);
});

test('A promoters can be fetched by staff_ids', function (): void {
    $response = $this->promoterQueries->getPromotersOfStaffIds([$this->employeeA->staff_id], $this->company->id);

    expect($response->first()->toArray())
        ->toHaveKey('employee_id', $this->employeeA->id);
});

test('A getPromoterById can be fetched promoter', function (): void {
    setCompanyIdInSession($this->company->id);

    $response = $this->promoterQueries->getPromoterById($this->promoterA->id, $this->company->id, false);
    expect($response->toArray())
        ->toHaveKey('id', $this->promoterA->id)
        ->toHaveKey('employee_id', $this->employeeA->id)
        ->toHaveKey('employee');
});

test('usernameTakenByAnotherPromoter method returns boolean as expected', function (): void {
    $response = $this->promoterQueries->usernameTakenByAnotherPromoter(
        $this->promoterA->username,
        (string) $this->employeeA->mobile_number,
        $this->company->id
    );
    $this->assertFalse($response);

    Promoter::factory()->create([
        'employee_id' => $this->employeeC->id,
        'group_id' => $this->promoterGroupA->id,
        'username' => 'test',
    ]);

    $response = $this->promoterQueries->usernameTakenByAnotherPromoter(
        $this->promoterA->username,
        (string) $this->employeeA->mobile_number,
        $this->company->id
    );
    $this->assertTrue($response);
});

test('A promoter can be updated by employee id', function (): void {
    $location = location::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $this->promoterQueries->updateByEmployeeId(
        [
            'username' => 'test',
            'code' => '123456',
            'location_ids' => [$location->id],
        ],
        $this->employeeA->id,
        $this->company->id,
    );

    $this->assertDatabaseHas('promoters', [
        'username' => 'test',
        'code' => '123456',
    ]);
});

test('getPromoterForBulkUpdate method call and return proper response', function (): void {
    $response = $this->promoterQueries->getPromoterForBulkUpdate($this->company->id);

    expect($response->last()->toArray())
        ->toHaveKey('id', $this->promoterA->id)
        ->toHaveKey('code', $this->promoterA->code)
        ->toHaveKey('username', $this->promoterA->username)
        ->toHaveKey('group_id', $this->promoterA->group_id)
        ->toHaveKey('employee_id', $this->promoterA->employee_id)
        ->toHaveKeys(['locations', 'employee', 'promoter_group']);
});

test('loadEmployee method returns promoter With Employee as expected', function (): void {
    $response = $this->promoterQueries->loadEmployee($this->promoterA);

    expect($response->toArray())
        ->toHaveKeys(['id', 'employee_id', 'employee']);
});

test('createToken method returns promoter token as expected', function (): void {
    $response = $this->promoterQueries->createToken($this->promoterA);

    expect($response)
        ->toBeString();
});

test('getPromoterByUsername method returns promoter as expected', function (): void {
    $response = $this->promoterQueries->getPromoterByUsername($this->promoterA->username);

    expect($response->toArray())
        ->toHaveKeys(['id', 'employee_id', 'employee']);
});

test('Get Promoters name for export PDF headers', function (): void {
    $response = $this->promoterQueries->getByIdsWithName([$this->promoterGroupA->id]);

    $this->assertIsString($response);
});

test('Get getTopSellingPromoter data', function (): void {
    $date = Carbon::now();
    $company = Company::factory()->create([
        'name' => 'test',
    ]);
    $targetId = 0;
    $dateRange = [$date->startOfYear()->format('Y-m-d H:i:s'), $date->endOfYear()->format('Y-m-d H:i:s')];
    $response = $this->promoterQueries->getTopSellingPromoter($company->id, $targetId, $dateRange, []);

    expect($response)
    ->toBeCollection();
});

test('Get getWorstSellingPromoter data', function (): void {
    $date = Carbon::now();
    $company = Company::factory()->create([
        'name' => 'test',
    ]);
    $targetId = 0;
    $dateRange = [$date->startOfYear()->format('Y-m-d H:i:s'), $date->endOfYear()->format('Y-m-d H:i:s')];
    $response = $this->promoterQueries->getWorstSellingPromoter($company->id, $targetId, $dateRange, []);

    expect($response)
    ->toBeCollection();
});
