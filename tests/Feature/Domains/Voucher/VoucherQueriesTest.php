<?php

declare(strict_types=1);

use App\Domains\Sale\Enums\SaleStatus;
use App\Domains\Voucher\Enums\VoucherStatusTypes;
use App\Domains\Voucher\VoucherQueries;
use App\Domains\VoucherConfiguration\Enums\RestrictedByTypes;
use App\Domains\VoucherConfiguration\Enums\VoucherTypes;
use App\Models\Cashier;
use App\Models\Company;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Member;
use App\Models\PosMismatch;
use App\Models\Sale;
use App\Models\Voucher;
use App\Models\VoucherConfiguration;
use Carbon\Carbon;
use Illuminate\Http\Request;

beforeEach(function (): void {
    $this->company = Company::factory()->create();

    $this->voucherConfiguration = VoucherConfiguration::factory()->create([
        'company_id' => $this->company->id,
        'voucher_type' => VoucherTypes::BIRTHDAY_VOUCHER->value,
        'restricted_by_type' => RestrictedByTypes::MEMBER_ONLY->value,
    ]);

    $this->member = Member::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $this->voucherQueries = new VoucherQueries();
});

test('New voucher can be added', function (): void {
    $this->voucherQueries->addNew(
        $this->voucherConfiguration,
        5,
        $this->voucherConfiguration->discount_type,
        Carbon::now(),
        $this->member->id,
    );

    $this->assertDatabaseHas('vouchers', [
        'voucher_configuration_id' => $this->voucherConfiguration->id,
        'member_id' => $this->member->id,
        'discount_type' => $this->voucherConfiguration->discount_type,
        'expiry_date' => Carbon::now()->format('Y-m-d'),
    ]);
});

test(
    'the getPaginatedList method returns the paginated vouchers list',
    function (): void {
        $employeeId = Employee::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'employee@company.test',
        ])->id;

        $location = Location::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $counter = Counter::factory()->create([
            'location_id' => $location,
        ]);
        $cashier = Cashier::factory()->create([
            'employee_id' => $employeeId,
            'username' => 'Cashier',
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'cashier_id' => $cashier->id,
            'counter_id' => $counter->id,
        ]);

        $sale = Sale::factory()->create([
            'member_id' => null,
            'counter_update_id' => $counterUpdate->id,
        ]);

        $voucher = Voucher::factory()->create([
            'voucher_configuration_id' => $this->voucherConfiguration->id,
            'member_id' => $this->member->id,
            'generated_by_sale_id' => $sale->id,
            'cancelled_at' => null,
        ]);

        $filterData = [
            'per_page' => 1,
            'after_updated_at' => null,
        ];

        $request = new Request($filterData);
        $request->setUserResolver(fn (): Cashier => $cashier);

        $response = $this->voucherQueries->getPaginatedList($filterData, $this->company->id);

        $this->assertEquals(1, $response->count());

        expect($response->first()->toArray())
            ->toHaveKey('id', $voucher->id)
            ->toHaveKey('voucher_configuration_id', $this->voucherConfiguration->id)
            ->toHaveKey('discount_type', $voucher->discount_type)
            ->toHaveKey('minimum_spend_amount', $voucher->minimum_spend_amount)
            ->toHaveKey('voucher_configuration')
            ->toHaveKey('member');
    }
);

test('It can return voucher, products and categories', function (): void {
    $voucher = Voucher::factory()->create([
        'voucher_configuration_id' => $this->voucherConfiguration->id,
        'member_id' => $this->member->id,
        'number' => 'ABC123',
    ]);

    $response = $this->voucherQueries->getByVoucherNumberAndCompanyIdWithProductsAndCategories(
        $voucher->number,
        $this->company->id
    );

    expect($response->toArray())
        ->toHaveKey('id', $voucher->id)
        ->toHaveKey('voucher_configuration_id', $this->voucherConfiguration->id)
        ->toHaveKey('discount_type', $voucher->discount_type)
        ->toHaveKey('minimum_spend_amount', $voucher->minimum_spend_amount)
        ->toHaveKeys(['voucher_configuration.products', 'voucher_configuration.categories', 'voucher_configuration']);
});

test(
    'the doVoucherNumbersExist method returns boolean as expected',
    function (): void {
        $employeeId = Employee::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'employee@company.test',
        ])->id;

        $location = Location::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $counter = Counter::factory()->create([
            'location_id' => $location,
        ]);
        $cashier = Cashier::factory()->create([
            'employee_id' => $employeeId,
            'username' => 'Cashier',
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'cashier_id' => $cashier->id,
            'counter_id' => $counter->id,
        ]);

        $sale = Sale::factory()->create([
            'member_id' => null,
            'counter_update_id' => $counterUpdate->id,
        ]);

        Voucher::factory()->create([
            'voucher_configuration_id' => $this->voucherConfiguration->id,
            'member_id' => $this->member->id,
            'generated_by_sale_id' => $sale->id,
            'number' => 'TEST_NUMBER',
        ]);

        $response = $this->voucherQueries->doVoucherNumbersExist(['TEST_NUMBER'], $this->company->id);
        $this->assertTrue($response);

        $response = $this->voucherQueries->doVoucherNumbersExist(['TEST_NUMBER_1'], $this->company->id);
        $this->assertFalse($response);
    }
);

test('markAsUsed method set voucher used date', function (): void {
    $voucher = Voucher::factory()->create([
        'voucher_configuration_id' => $this->voucherConfiguration->id,
        'member_id' => $this->member->id,
        'number' => 'ABC123',
        'used_at' => null,
    ]);

    $this->voucherQueries->markAsUsed($voucher, '2022-01-11 10:10:10');

    $this->assertDatabaseHas('vouchers', [
        'id' => $voucher->id,
        'voucher_configuration_id' => $this->voucherConfiguration->id,
        'member_id' => $this->member->id,
        'used_at' => '2022-01-11 10:10:10',
    ]);
});

test('getCountByCounterUpdateId method returns the count of vouchers', function (): void {
    $counterUpdate = CounterUpdate::factory()->create();
    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'layaway_pending_amount' => null,
        'status' => SaleStatus::REGULAR_SALE->value,
    ]);

    Voucher::factory()->create([
        'voucher_configuration_id' => $this->voucherConfiguration->id,
        'member_id' => $this->member->id,
        'generated_by_sale_id' => $sale->id,
        'number' => 'ABC123',
        'used_at' => null,
    ]);

    $response = $this->voucherQueries->getCountByCounterUpdateId($counterUpdate->id);
    $this->assertEquals(1, $response);
});

test(
    'loadVoucherWithMismatchesRelations method loads the mismatches as expected',
    function (): void {
        $voucher = Voucher::factory()->create();
        PosMismatch::factory()->create([
            'module_id' => $voucher->id,
            'module_type' => $voucher::class,
        ]);

        $response = $this->voucherQueries->loadVoucherWithMismatchesRelations($voucher);

        expect($response->toArray())
            ->toHaveKeys(['mismatches', 'discount_type', 'id', 'voucher_configuration_id']);
    }
);

test(
    'getVouchersBySaleId method returns vouchers data.',
    function (): void {
        $sale = Sale::factory()->create();

        $voucher = Voucher::factory()->create([
            'generated_by_sale_id' => $sale->id,
        ]);

        $response = $this->voucherQueries->getVouchersBySaleId($sale->id);

        expect($response->first()->toArray())
            ->toHaveKey('id', $voucher->id);
    }
);

test(
    'updateCancelledAt method returns vouchers data.',
    function (): void {
        $sale = Sale::factory()->create();

        $voucher = Voucher::factory()->create([
            'generated_by_sale_id' => $sale->id,
            'cancelled_at' => null,
        ]);

        $this->voucherQueries->updateCancelledAt($voucher);

        $voucher->refresh();

        $this->assertDatabaseHas('vouchers', [
            'id' => $voucher->id,
            'cancelled_at' => $voucher->cancelled_at,
        ]);
    }
);

test(
    'getById method returns vouchers data.',
    function (): void {
        $voucher = Voucher::factory()->create();

        $response = $this->voucherQueries->getById($voucher->id);

        expect($response->toArray())
            ->toHaveKey('id', $voucher->id);
    }
);

test(
    'resetUsedAt method returns vouchers data.',
    function (): void {
        $sale = Sale::factory()->create();

        $voucher = Voucher::factory()->create([
            'generated_by_sale_id' => $sale->id,
        ]);

        $this->voucherQueries->resetUsedAt($voucher);

        $voucher->refresh();

        $this->assertDatabaseHas('vouchers', [
            'id' => $voucher->id,
            'used_at' => null,
        ]);
    }
);

test(
    'the getPaginatedVoucherList method returns the paginated vouchers list',
    function (): void {
        $employeeId = Employee::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'employee@company.test',
        ])->id;

        $location = Location::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $counter = Counter::factory()->create([
            'location_id' => $location,
        ]);
        $cashier = Cashier::factory()->create([
            'employee_id' => $employeeId,
            'username' => 'Cashier',
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'cashier_id' => $cashier->id,
            'counter_id' => $counter->id,
        ]);

        $sale = Sale::factory()->create([
            'member_id' => null,
            'counter_update_id' => $counterUpdate->id,
        ]);

        $voucher = Voucher::factory()->create([
            'voucher_configuration_id' => $this->voucherConfiguration->id,
            'member_id' => $this->member->id,
            'generated_by_sale_id' => $sale->id,
        ]);

        $filterData = [
            'search_text' => null,
            'sort_by' => null,
            'sort_direction' => null,
            'per_page' => 15,
            'date_range' => null,
            'member_id' => null,
            'location_ids' => null,
            'status_type' => null,
        ];

        $request = new Request($filterData);
        $request->setUserResolver(fn (): Cashier => $cashier);

        $response = $this->voucherQueries->getPaginatedVoucherList($filterData, $this->company->id);

        $this->assertEquals(1, $response->count());

        expect($response->first()->toArray())
            ->toHaveKey('id', $voucher->id)
            ->toHaveKey('voucher_configuration_id', $this->voucherConfiguration->id)
            ->toHaveKey('discount_type', $voucher->discount_type)
            ->toHaveKey('minimum_spend_amount', $voucher->minimum_spend_amount);
    }
);

test(
    'the getCountOfActiveVouchers method returns proper response',
    function (): void {
        $employeeId = Employee::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'employee@company.test',
        ])->id;

        $location = Location::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $counter = Counter::factory()->create([
            'location_id' => $location,
        ]);
        $cashier = Cashier::factory()->create([
            'employee_id' => $employeeId,
            'username' => 'Cashier',
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'cashier_id' => $cashier->id,
            'counter_id' => $counter->id,
        ]);

        $sale = Sale::factory()->create([
            'member_id' => null,
            'counter_update_id' => $counterUpdate->id,
        ]);

        $voucher = Voucher::factory()->create([
            'voucher_configuration_id' => $this->voucherConfiguration->id,
            'member_id' => $this->member->id,
            'generated_by_sale_id' => $sale->id,
            'status' => VoucherStatusTypes::ACTIVE->value,
        ]);

        $filterData = [
            'search_text' => null,
            'sort_by' => null,
            'sort_direction' => null,
            'per_page' => 15,
            'date_range' => null,
            'member_id' => null,
            'location_ids' => null,
            'status_type' => null,
        ];

        $request = new Request($filterData);
        $request->setUserResolver(fn (): Cashier => $cashier);

        $response = $this->voucherQueries->getCountOfActiveVouchers($filterData, $this->company->id);

        expect($response)->toBe(1);
    }
);

test(
    'the getVouchersForExport method returns vouchers as expected',
    function (): void {
        $employeeId = Employee::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'employee@company.test',
        ])->id;

        $location = Location::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $counter = Counter::factory()->create([
            'location_id' => $location,
        ]);
        $cashier = Cashier::factory()->create([
            'employee_id' => $employeeId,
            'username' => 'Cashier',
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'cashier_id' => $cashier->id,
            'counter_id' => $counter->id,
        ]);

        $sale = Sale::factory()->create([
            'member_id' => null,
            'counter_update_id' => $counterUpdate->id,
        ]);

        $voucher = Voucher::factory()->create([
            'voucher_configuration_id' => $this->voucherConfiguration->id,
            'member_id' => $this->member->id,
            'generated_by_sale_id' => $sale->id,
        ]);

        $filterData = [
            'search_text' => null,
            'sort_by' => null,
            'sort_direction' => null,
            'per_page' => 15,
            'date_range' => null,
            'member_id' => null,
            'location_ids' => null,
            'status_type' => null,
        ];

        $request = new Request($filterData);
        $request->setUserResolver(fn (): Cashier => $cashier);

        $response = $this->voucherQueries->getVouchersForExport($filterData, $this->company->id);

        expect($response->first()->toArray())
            ->toHaveKey('id', $voucher->id)
            ->toHaveKey('voucher_configuration_id', $this->voucherConfiguration->id)
            ->toHaveKey('discount_type', $voucher->discount_type)
            ->toHaveKey('minimum_spend_amount', $voucher->minimum_spend_amount);
    }
);

test(
    'the getPaginatedListForMemberApi method returns the paginated vouchers list',
    function (): void {
        $employeeId = Employee::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'employee@company.test',
        ])->id;

        $location = Location::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $counter = Counter::factory()->create([
            'location_id' => $location,
        ]);
        $cashier = Cashier::factory()->create([
            'employee_id' => $employeeId,
            'username' => 'Cashier',
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'cashier_id' => $cashier->id,
            'counter_id' => $counter->id,
        ]);

        $sale = Sale::factory()->create([
            'member_id' => null,
            'counter_update_id' => $counterUpdate->id,
        ]);

        $voucher = Voucher::factory()->create([
            'voucher_configuration_id' => $this->voucherConfiguration->id,
            'member_id' => $this->member->id,
            'generated_by_sale_id' => $sale->id,
            'cancelled_at' => null,
        ]);

        $filterData = [
            'search_text' => null,
            'sort_by' => null,
            'sort_direction' => null,
            'per_page' => 15,
            'status' => null,
        ];

        $response = $this->voucherQueries->getPaginatedListForMemberApi($filterData, $this->member->id);

        $this->assertEquals(1, $response->count());

        expect($response->first()->toArray())
            ->toHaveKey('id', $voucher->id)
            ->toHaveKey('discount_type', $voucher->discount_type)
            ->toHaveKey('minimum_spend_amount', $voucher->minimum_spend_amount)
            ->toHaveKeys(['expiry_date', 'number', 'voucher_configuration.title']);
    }
);

test('New voucher can be added with null expiry_date', function (): void {
    $this->voucherQueries->addNew(
        $this->voucherConfiguration,
        5,
        $this->voucherConfiguration->discount_type,
        null,
        $this->member->id,
    );

    $this->assertDatabaseHas('vouchers', [
        'voucher_configuration_id' => $this->voucherConfiguration->id,
        'member_id' => $this->member->id,
        'discount_type' => $this->voucherConfiguration->discount_type,
        'expiry_date' => null,
    ]);
});

test(
    'the getPaginatedVoucherListForStoreManager method returns the paginated vouchers list for store manager',
    function (): void {
        $employeeId = Employee::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'employee@company.test',
        ])->id;

        $location = Location::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $counter = Counter::factory()->create([
            'location_id' => $location,
        ]);
        $cashier = Cashier::factory()->create([
            'employee_id' => $employeeId,
            'username' => 'Cashier',
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'cashier_id' => $cashier->id,
            'counter_id' => $counter->id,
        ]);

        $sale = Sale::factory()->create([
            'member_id' => null,
            'counter_update_id' => $counterUpdate->id,
        ]);

        $voucher = Voucher::factory()->create([
            'voucher_configuration_id' => $this->voucherConfiguration->id,
            'member_id' => $this->member->id,
            'generated_by_sale_id' => $sale->id,
            'cancelled_at' => null,
        ]);

        $filterData = [
            'search_text' => null,
            'sort_by' => null,
            'sort_direction' => null,
            'per_page' => 15,
            'date_range' => null,
            'member_id' => null,
            'status_type' => null,
        ];

        $request = new Request($filterData);
        $request->setUserResolver(fn (): Cashier => $cashier);

        $response = $this->voucherQueries->getPaginatedVoucherListForStoreManager(
            $filterData,
            $this->company->id,
            $location->id
        );

        $this->assertEquals(1, $response->count());

        expect($response->first()->toArray())
            ->toHaveKey('id', $voucher->id)
            ->toHaveKey('voucher_configuration_id', $this->voucherConfiguration->id)
            ->toHaveKey('discount_type', $voucher->discount_type)
            ->toHaveKey('minimum_spend_amount', $voucher->minimum_spend_amount)
            ->toHaveKey('voucher_configuration');
    }
);

test(
    'the getVouchersForExportStoreManager method returns vouchers as expected',
    function (): void {
        $employeeId = Employee::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'employee@company.test',
        ])->id;

        $location = Location::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $counter = Counter::factory()->create([
            'location_id' => $location,
        ]);
        $cashier = Cashier::factory()->create([
            'employee_id' => $employeeId,
            'username' => 'Cashier',
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'cashier_id' => $cashier->id,
            'counter_id' => $counter->id,
        ]);

        $sale = Sale::factory()->create([
            'member_id' => null,
            'counter_update_id' => $counterUpdate->id,
        ]);

        $voucher = Voucher::factory()->create([
            'voucher_configuration_id' => $this->voucherConfiguration->id,
            'member_id' => $this->member->id,
            'generated_by_sale_id' => $sale->id,
        ]);

        $filterData = [
            'search_text' => null,
            'sort_by' => null,
            'sort_direction' => null,
            'per_page' => 15,
            'date_range' => null,
            'member_id' => null,
            'status_type' => null,
        ];

        $request = new Request($filterData);
        $request->setUserResolver(fn (): Cashier => $cashier);

        $response = $this->voucherQueries->getVouchersForExportStoreManager(
            $filterData,
            $this->company->id,
            $location->id
        );

        expect($response->first()->toArray())
            ->toHaveKey('id', $voucher->id)
            ->toHaveKey('voucher_configuration_id', $this->voucherConfiguration->id)
            ->toHaveKey('discount_type', $voucher->discount_type)
            ->toHaveKey('minimum_spend_amount', $voucher->minimum_spend_amount);
    }
);

test(
    'loadRelations method loads the mismatches as expected',
    function (): void {
        $voucher = Voucher::factory()->create();
        PosMismatch::factory()->create([
            'module_id' => $voucher->id,
            'module_type' => $voucher::class,
        ]);

        $response = $this->voucherQueries->loadRelations($voucher);

        expect($response->toArray())
            ->toHaveKeys(['discount_type', 'id', 'voucher_configuration_id', 'voucher_configuration', 'member']);
    }
);

test(
    'the getVoucherStoreWiseForApplication method returns vouchers list',
    function (): void {
        $employeeId = Employee::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'employee@company.test',
        ])->id;

        $location = Location::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $counter = Counter::factory()->create([
            'location_id' => $location,
        ]);
        $cashier = Cashier::factory()->create([
            'employee_id' => $employeeId,
            'username' => 'Cashier',
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'cashier_id' => $cashier->id,
            'counter_id' => $counter->id,
        ]);

        $sale = Sale::factory()->create([
            'member_id' => null,
            'counter_update_id' => $counterUpdate->id,
        ]);

        $voucher = Voucher::factory()->create([
            'voucher_configuration_id' => $this->voucherConfiguration->id,
            'member_id' => $this->member->id,
            'generated_by_sale_id' => $sale->id,
            'created_by_location_id' => $location->id,
            'cancelled_at' => null,
        ]);

        $response = $this->voucherQueries->getVoucherStoreWiseForApplication($this->company->id, $location->id);

        $this->assertEquals(1, $response->count());

        expect($response->first()->toArray())
            ->toHaveKey('id', $voucher->id)
            ->toHaveKey('voucher_configuration_id', $this->voucherConfiguration->id)
            ->toHaveKey('discount_type', $voucher->discount_type)
            ->toHaveKey('minimum_spend_amount', $voucher->minimum_spend_amount)
            ->toHaveKey('voucher_configuration');
    }
);

test(
    'the updateMember method update the voucher queries member id to new member id',
    function (): void {
        $member = Member::factory()->create();

        $voucher = Voucher::factory()->create();

        $this->assertDatabaseHas(Voucher::class, [
            'id' => $voucher->getKey(),
            'member_id' => $voucher->member_id,
        ]);

        $this->voucherQueries->updateMember($voucher->member_id, $member->getKey());

        $this->assertDatabaseHas(Voucher::class, [
            'id' => $voucher->getKey(),
            'member_id' => $member->getKey(),
        ]);
    }
);
