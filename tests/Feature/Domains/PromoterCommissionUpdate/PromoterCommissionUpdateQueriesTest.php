<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\PromoterCommissionUpdate\PromoterCommissionUpdateQueries;
use App\Domains\Sale\Enums\SaleStatus;
use App\Models\Company;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Product;
use App\Models\Promoter;
use App\Models\PromoterCommission;
use App\Models\PromoterCommissionUpdate;
use App\Models\PromoterGroup;
use App\Models\Sale;
use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;

beforeEach(function (): void {
    $this->company = Company::factory()->create();

    $this->employeeA = Employee::factory()->create([
        'company_id' => $this->company->id,
        'first_name' => 'ABCD',
    ]);

    $this->promoterGroup = PromoterGroup::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'ABCD',
    ]);

    $this->promoterA = Promoter::factory()->create([
        'employee_id' => $this->employeeA->id,
        'group_id' => $this->promoterGroup->id,
    ]);

    $this->promoterCommission = PromoterCommission::factory()->create([
        'promoter_id' => $this->promoterA->id,
    ]);

    $location = Location::factory()->create([
        'type_id' => LocationTypes::STORE->value,
        'company_id' => $this->company->id,
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
        'layaway_pending_amount' => null,
    ]);

    $saleItem = SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'sale_return_item_id' => null,
        'returned_quantity' => 0,
        'is_exchange' => false,
    ]);

    $this->promoterCommissionUpdate = PromoterCommissionUpdate::factory()->create([
        'promoter_commission_id' => $this->promoterCommission->id,
        'affected_by_id' => $saleItem->id,
        'affected_by_type' => ModelMapping::SALE_ITEM->name,
    ]);

    $this->employeeB = Employee::factory()->create([
        'company_id' => $this->company->id,
        'first_name' => 'XYZ',
    ]);

    $this->promoterB = Promoter::factory()->create([
        'employee_id' => $this->employeeB->id,
    ]);

    $this->promoterCommissionUpdateQueries = new PromoterCommissionUpdateQueries();
});

test('promotion commission update can be added', function (): void {
    $insertRecord = [
        'promoter_commission_id' => $this->promoterCommission->id,
        'affected_by_id' => 1,
        'affected_by_type' => ModelMapping::SALE_ITEM->name,
        'department_id' => null,
        'location_id' => null,
        'brand_id' => null,
        'commission_percentage' => 1,
        'commission_amount' => 1,
    ];

    $this->promoterCommissionUpdateQueries->addNew($insertRecord);

    $this->assertDatabaseHas('promoter_commission_updates', $insertRecord);
});

test(
    'getPaginatedCommissionDetailsByPromoter method returns promoter commission details as expected',
    function (): void {
        $response = $this->promoterCommissionUpdateQueries->getPaginatedCommissionDetailsByPromoter([
            'search_text' => null,
            'sort_by' => null,
            'sort_direction' => null,
            'per_page' => 15,
            'department_ids' => null,
            'location_ids' => null,
            'brand_ids' => null,
        ], $this->promoterCommission->id);

        $this->assertEquals(1, $response->total());

        expect($response->getCollection()->first()->toArray())
        ->toHaveKey('commission_percentage', $this->promoterCommissionUpdate->commission_percentage)
        ->toHaveKey('commission_amount', $this->promoterCommissionUpdate->commission_amount);
    }
);

test('getPromoterCommissionDetailsForExport method returns promoter details as expected', function (): void {
    $response = $this->promoterCommissionUpdateQueries->getPromoterCommissionDetailsForExport([
        'search_text' => '',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'department_ids' => null,
        'location_ids' => null,
        'brand_ids' => null,
    ], $this->promoterCommission->id);

    expect($response->first()->toArray())
        ->toHaveKey('promoter_commission_id', $this->promoterCommission->id);
});

test('getPromoterCommissionReportByItem method returns promoter details as expected', function (): void {
    $date = Carbon::create('2023', '06', '01');

    $location = Location::factory()->create([
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
        'happened_at' => $date,
        'status' => SaleStatus::REGULAR_SALE->value,
        'layaway_pending_amount' => null,
    ]);

    $product = Product::factory()->create([
        'company_id' => $location->company_id,
        'is_non_selling_item' => false,
    ]);

    $saleItem = SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'sale_return_item_id' => null,
        'returned_quantity' => 0,
        'product_id' => $product->id,
        'is_exchange' => false,
    ]);

    $promoterCommissionId = PromoterCommission::factory()->create([
        'commission_date' => $date->format('Y-m-d'),
        'promoter_id' => $this->promoterA->id,
    ])->id;

    $promoterCommissionUpdate = PromoterCommissionUpdate::factory()->create([
        'promoter_commission_id' => $promoterCommissionId,
        'affected_by_id' => $saleItem->id,
        'affected_by_type' => ModelMapping::SALE_ITEM->name,
        'location_id' => $location->id,
        'commission_amount' => 50,
        'commission_percentage' => 50,
    ]);

    $response = $this->promoterCommissionUpdateQueries->getPromoterCommissionReportByItem([
        'location_ids' => [$location->id],
        'month_range' => [$date->format('m'), $date->format('Y')],
        'department_ids' => null,
        'brand_ids' => null,
        'group_ids' => null,
    ]);

    expect($response->first()->toArray())
        ->toHaveKey('promoter_commission_id', $promoterCommissionId)
        ->toHaveKey('commission_percentage', $promoterCommissionUpdate['commission_percentage'])
        ->toHaveKey('commission_amount', $promoterCommissionUpdate['commission_amount']);
});

test('deleteByPromoterCommissionId method delete promoter commission update', function (): void {
    $promoterCommission = PromoterCommission::factory()->create();

    $promoterCommissionUpdate = PromoterCommissionUpdate::factory()->create([
        'promoter_commission_id' => $promoterCommission->id,
    ]);

    $this->promoterCommissionUpdateQueries->deleteByPromoterCommissionIds([$promoterCommission->id]);
    $this->assertSoftDeleted($promoterCommissionUpdate);
});

test('getPromoterCommissionAmount returns the collection of the promoter commission', function (): void {
    $date = Carbon::now();
    $location = Location::factory()->create([
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
        'happened_at' => $date,
        'status' => SaleStatus::REGULAR_SALE->value,
        'layaway_pending_amount' => null,
    ]);

    $product = Product::factory()->create();

    $saleItem = SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'sale_return_item_id' => null,
        'returned_quantity' => 0,
        'is_exchange' => false,
        'product_id' => $product->id,
    ]);

    $promoterCommission = PromoterCommission::factory()->create([
        'commission_date' => $date->subMonth()->format('Y-m-d'),
    ]);

    $promoterCommissionUpdate = PromoterCommissionUpdate::factory()->create([
        'promoter_commission_id' => $promoterCommission->id,
        'affected_by_id' => $saleItem,
        'affected_by_type' => ModelMapping::SALE_ITEM->value,
        'commission_amount' => 0.00,
        'location_id' => $location->id,
    ]);

    $dateRange = [$date->startOfMonth()->format('Y-m-d'), $date->endOfMonth()->format('Y-m-d')];

    $response = $this->promoterCommissionUpdateQueries->getPromoterCommissionAmount(
        $dateRange,
        $location->id,
        $promoterCommission->promoter_id
    );

    expect($response->first()->toArray())
        ->toHaveKey('promoter_commission_id', $promoterCommissionUpdate->promoter_commission_id)
        ->toHaveKey('commission_amount', $promoterCommissionUpdate->commission_amount);
});

test(
    'getPromoterCommissionHistory should return paginated commission history for a specific promoter and location',
    function (): void {
        $location = Location::factory()->create([
            'company_id' => $this->company->id,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $counter = Counter::factory()->create([
            'location_id' => $location->id,
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->id,
        ]);

        $saleHappenedAt = Carbon::now()->subMonth()->lastOfMonth()->format('Y-m-d');

        $sale = Sale::factory()->create([
            'member_id' => null,
            'counter_update_id' => $counterUpdate->id,
            'layaway_pending_amount' => null,
            'status' => SaleStatus::REGULAR_SALE->value,
            'happened_at' => $saleHappenedAt,
        ]);

        $product = Product::factory()->create();

        $saleItem = SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'returned_quantity' => 0,
            'quantity' => 10,
        ]);

        $saleItem->promoters()->attach([$this->promoterA->id]);

        $promoterCommissionId = PromoterCommission::factory()->create([
            'promoter_id' => $this->promoterA->id,
        ])->id;

        PromoterCommissionUpdate::factory()->create([
            'promoter_commission_id' => $promoterCommissionId,
            'affected_by_id' => $saleItem->id,
            'affected_by_type' => ModelMapping::getCaseName($saleItem::class),
        ]);

        $filterData = [
            'sort_by' => 'id',
            'sort_direction' => 'desc',
            'per_page' => 10,
            'date_range' => [
                Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d'),
                Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d'),
            ],
        ];

        $response = $this->promoterCommissionUpdateQueries->promoterCommissionBasedOnPromoterAndLocation(
            $filterData,
            $this->promoterA->id,
            $location->id,
        );

        expect($response->where('happened_at', $saleHappenedAt)->first()->toArray())
            ->toHaveKey('happened_at', $saleHappenedAt)
            ->toHaveKey('units_sold', $saleItem->quantity);
    }
);

test(
    'getPromoterCommissionBySingleData should return paginated commission history list for a specific promoter and location',
    function (): void {
        $location = Location::factory()->create([
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
            'member_id' => null,
            'counter_update_id' => $counterUpdate->id,
            'layaway_pending_amount' => null,
            'status' => SaleStatus::REGULAR_SALE->value,
            'happened_at' => Carbon::now(),
        ]);

        $product = Product::factory()->create();

        $saleItem = SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'returned_quantity' => 0,
        ]);

        $saleItem->promoters()->attach([$this->promoterA->id]);

        $date = Carbon::now()->format('Y-m-d H:i:s');

        $filterData = [
            'sort_by' => 'id',
            'sort_direction' => 'desc',
            'per_page' => 10,
            'selected_date' => $date,
            'location_id' => $location->id,
        ];

        $response = $this->promoterCommissionUpdateQueries->getPromoterCommissionBySingleData(
            $filterData,
            $location->id,
            $this->promoterA->id
        );

        expect($response)->toBeInstanceOf(LengthAwarePaginator::class);
    }
);

test('fetchCommissionDetailsById method returns promoter details by id', function (): void {
    $department = Department::factory()->create();

    $promoterCommissionId = PromoterCommission::factory()->create()->id;
    $promoterCommissionUpdate = PromoterCommissionUpdate::factory()->create([
        'promoter_commission_id' => $promoterCommissionId,
        'affected_by_id' => 1,
        'affected_by_type' => ModelMapping::SALE_ITEM->name,
        'department_id' => $department->id,
    ]);

    $response = $this->promoterCommissionUpdateQueries->fetchCommissionDetailsById($promoterCommissionUpdate->id);

    expect($response)
        ->toHaveKey('promoter_commission_id', $promoterCommissionUpdate->promoter_commission_id)
        ->toHaveKey('department_id', $department->id)
        ->toHaveKey('commission_amount', $promoterCommissionUpdate->commission_amount);
});
