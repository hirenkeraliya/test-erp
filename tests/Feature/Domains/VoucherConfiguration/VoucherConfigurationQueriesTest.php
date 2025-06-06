<?php

declare(strict_types=1);

use App\Domains\VoucherConfiguration\DataObjects\VoucherConfigurationData;
use App\Domains\VoucherConfiguration\Enums\ExcludeByTypes;
use App\Domains\VoucherConfiguration\Enums\VoucherTypes;
use App\Domains\VoucherConfiguration\VoucherConfigurationQueries;
use App\Models\Admin;
use App\Models\Company;
use App\Models\Product;
use App\Models\VoucherConfiguration;
use Carbon\Carbon;

beforeEach(function (): void {
    $this->company = Company::factory()->create([
        'auto_birthday_voucher_generation' => true,
    ]);

    $this->voucherConfiguration = VoucherConfiguration::factory()->create([
        'company_id' => $this->company->id,
        'voucher_type' => VoucherTypes::BIRTHDAY_VOUCHER->value,
        'get_value' => 100,
        'status' => 1,
    ]);

    $this->voucherConfigurationQueries = new VoucherConfigurationQueries();
});

test('Voucher Configuration can be searched', function (): void {
    VoucherConfiguration::factory()->create([
        'company_id' => $this->company->id,
        'voucher_type' => VoucherTypes::TIER_VOUCHER->value,
    ]);

    $response = $this->voucherConfigurationQueries->listQuery([
        'search_text' => 'Birthday Voucher',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'status' => null,
        'restricted_by_type_id' => null,
        'voucher_type_id' => null,
        'discount_type_id' => null,
        'type' => null,
    ], $this->company->id);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('get_value', $this->voucherConfiguration->get_value)
        ->toHaveKey('voucher_type', VoucherTypes::BIRTHDAY_VOUCHER->value)
        ->toHaveKey('discount_type', $this->voucherConfiguration->discount_type);
});

test('New voucher configuration can be added', function (): void {
    $newVoucherConfigurationRecord = VoucherConfiguration::factory()->make([
        'company_id' => $this->company->id,
        'voucher_type' => VoucherTypes::TIER_VOUCHER->value,
        'exclude_by_type' => ExcludeByTypes::NONE->value,
        'start_date' => '2022-06-27',
        'end_date' => '2022-06-28',
        'title' => 'abcd',
    ])->toArray();

    unset($newVoucherConfigurationRecord['company_id']);

    $tiers = [
        'minimum_spend_amount' => 50,
        'maximum_spend_amount' => 10,
        'get_value' => 5,
    ];

    $admin = Admin::factory()->create();

    $newVoucherConfigurationRecord['tiers'] = [$tiers];

    $newVoucherConfigurationRecord['category_ids'] = [];
    $newVoucherConfigurationRecord['product_ids'] = [];
    $newVoucherConfigurationRecord['membership_ids'] = [];
    $newVoucherConfigurationRecord['image'] = null;
    $newVoucherConfigurationRecord['thumbnail'] = null;

    $this->voucherConfigurationQueries->addNew(
        new VoucherConfigurationData(...$newVoucherConfigurationRecord),
        $this->company->id,
        $admin
    );

    unset($newVoucherConfigurationRecord['image'], $newVoucherConfigurationRecord['tiers'], $newVoucherConfigurationRecord['product_ids'], $newVoucherConfigurationRecord['category_ids'], $newVoucherConfigurationRecord['membership_ids'],  $newVoucherConfigurationRecord['thumbnail'], $newVoucherConfigurationRecord['sale_channel_ids']);

    $this->assertDatabaseHas('voucher_configurations', $newVoucherConfigurationRecord);
    $this->assertDatabaseHas('voucher_configuration_tiers', $tiers);
});

test('A voucher configuration can be fetched', function (): void {
    $response = $this->voucherConfigurationQueries->getById($this->voucherConfiguration->id, $this->company->id);

    expect($response->toArray())
        ->toHaveKey('restricted_by_type', $this->voucherConfiguration->restricted_by_type)
        ->toHaveKey('get_value', $this->voucherConfiguration->get_value)
        ->toHaveKey('validity_days', $this->voucherConfiguration->validity_days)
        ->toHaveKey('exclude_by_type', $this->voucherConfiguration->exclude_by_type);
});

test('A voucher configuration can be updated', function (): void {
    $newVoucherConfigurationRecord = VoucherConfiguration::factory()->make([
        'company_id' => $this->company->id,
        'voucher_type' => VoucherTypes::TIER_VOUCHER->value,
        'exclude_by_type' => ExcludeByTypes::NONE->value,
        'start_date' => '2022-06-27',
        'end_date' => '2022-06-28',
        'title' => 'abcde',
    ])->toArray();
    unset($newVoucherConfigurationRecord['company_id']);

    $newVoucherConfigurationRecord['tiers'] = [];
    $newVoucherConfigurationRecord['category_ids'] = [];
    $newVoucherConfigurationRecord['product_ids'] = [];
    $newVoucherConfigurationRecord['membership_ids'] = [];
    $newVoucherConfigurationRecord['image'] = null;
    $newVoucherConfigurationRecord['thumbnail'] = null;

    $this->voucherConfigurationQueries->update(
        new VoucherConfigurationData(...$newVoucherConfigurationRecord),
        $this->voucherConfiguration->id,
        $this->company->id
    );

    unset($newVoucherConfigurationRecord['image'], $newVoucherConfigurationRecord['tiers'], $newVoucherConfigurationRecord['product_ids'], $newVoucherConfigurationRecord['category_ids'], $newVoucherConfigurationRecord['membership_ids'],  $newVoucherConfigurationRecord['thumbnail'], $newVoucherConfigurationRecord['sale_channel_ids']
    );

    $this->assertDatabaseHas('voucher_configurations', $newVoucherConfigurationRecord);
});

test('getBirthdayVoucherId method returns the birthday voucher configuration id', function (): void {
    $response = $this->voucherConfigurationQueries->getBirthdayVoucherId($this->company->id);

    $this->assertEquals($this->voucherConfiguration->id, $response);
});

test('getListForPosWithRelatedData method returns voucher with all related data', function (): void {
    $response = $this->voucherConfigurationQueries->getListForPosWithRelatedData($this->company->id);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->voucherConfiguration->id)
        ->toHaveKey('restricted_by_type', $this->voucherConfiguration->restricted_by_type)
        ->toHaveKey('voucher_type', $this->voucherConfiguration->voucher_type)
        ->toHaveKey('exclude_by_type', $this->voucherConfiguration->exclude_by_type)
        ->toHaveKey('discount_type', $this->voucherConfiguration->discount_type)
        ->toHaveKeys(['products', 'categories', 'voucher_configuration_tiers']);
});

test('getBirthDayVoucherConfigurationByCompanyId method returns birthday voucher configuration', function (): void {
    $response = $this->voucherConfigurationQueries->getBirthDayVoucherConfigurationByCompanyId($this->company->id);

    expect($response->toArray())
        ->toHaveKey('id', $this->voucherConfiguration->id)
        ->toHaveKeys(
            ['use_minimum_spend_amount', 'validity_days', 'discount_type', 'get_value', 'start_date', 'end_date']
        );
});

test('getByIdForBirthdayVoucher method returns the birthday voucher details', function (): void {
    $this->voucherConfiguration->voucher_type = VoucherTypes::BIRTHDAY_VOUCHER->value;
    $this->voucherConfiguration->start_date = Carbon::yesterday()->format('Y-m-d');
    $this->voucherConfiguration->end_date = Carbon::tomorrow()->format('Y-m-d');
    $this->voucherConfiguration->save();

    $response = $this->voucherConfigurationQueries->getByIdForBirthdayVoucher(
        $this->voucherConfiguration->id,
        $this->company->id,
        Carbon::today()
    );

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->voucherConfiguration->id)
        ->toHaveKeys(
            ['use_minimum_spend_amount', 'validity_days', 'discount_type', 'get_value', 'start_date', 'end_date']
        );
});

test('getBirthdayVoucherConfiguration method returns the birthday vouchers as expected', function (): void {
    $this->voucherConfiguration->voucher_type = VoucherTypes::BIRTHDAY_VOUCHER->value;
    $this->voucherConfiguration->start_date = Carbon::yesterday()->format('Y-m-d');
    $this->voucherConfiguration->end_date = Carbon::tomorrow()->format('Y-m-d');
    $this->voucherConfiguration->save();

    $response = $this->voucherConfigurationQueries->getBirthdayVoucherConfiguration(Carbon::today());

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->voucherConfiguration->id)
        ->toHaveKey('voucher_type', $this->voucherConfiguration->voucher_type)
        ->toHaveKey('restricted_by_type', $this->voucherConfiguration->restricted_by_type)
        ->toHaveKey('exclude_by_type', $this->voucherConfiguration->exclude_by_type);
});

test(
    'getBirthdayVoucherConfiguration method returns empty collection when birthday voucher is not in range of current date',
    function (): void {
        $this->voucherConfiguration->voucher_type = VoucherTypes::BIRTHDAY_VOUCHER->value;
        $this->voucherConfiguration->start_date = Carbon::today()->format('Y-m-d');
        $this->voucherConfiguration->end_date = Carbon::tomorrow()->format('Y-m-d');
        $this->voucherConfiguration->save();

        $response = $this->voucherConfigurationQueries->getBirthdayVoucherConfiguration(Carbon::yesterday());

        expect($response)->toBeEmpty();
    }
);

test('it can change the status of the vouchers configuration', function (): void {
    $this->voucherConfigurationQueries->setStatus($this->voucherConfiguration->id, $this->company->id, false);
    $this->assertDatabaseHas('voucher_configurations', [
        'id' => $this->voucherConfiguration->id,
        'status' => 0,
    ]);

    $this->voucherConfigurationQueries->setStatus($this->voucherConfiguration->id, $this->company->id, true);
    $this->assertDatabaseHas('voucher_configurations', [
        'id' => $this->voucherConfiguration->id,
        'status' => 1,
    ]);
});

test(
    'getExpiredBirthdayVoucher returns expired birthday voucher based on the birthday voucher configuration expiry date as expected',
    function (): void {
        $this->voucherConfiguration->end_date = Carbon::yesterday();
        $this->voucherConfiguration->save();

        $response = $this->voucherConfigurationQueries->getExpiredBirthdayVoucher($this->company->id);
        expect($response)->toBeInstanceOf(VoucherConfiguration::class);
    }
);

test(
    'getExpiredBirthdayVoucher returns null if no expired birthday voucher found as expected',
    function (): void {
        $this->voucherConfiguration->end_date = Carbon::tomorrow();
        $this->voucherConfiguration->save();

        $response = $this->voucherConfigurationQueries->getExpiredBirthdayVoucher($this->company->id);
        $this->assertEquals(null, $response);
    }
);

test('removeSelectedProducts method removes the products', function (): void {
    $voucherConfiguration = VoucherConfiguration::factory()->create([
        'voucher_type' => VoucherTypes::TIER_VOUCHER->value,
        'exclude_by_type' => ExcludeByTypes::PRODUCTS->value,
    ]);

    $productId = Product::factory()->create([
        'company_id' => $this->company->id,
    ])->id;

    $voucherConfiguration->products()->attach([$productId]);

    $this->assertDatabaseHas('voucher_configuration_product', [
        'product_id' => $productId,
        'voucher_configuration_id' => $voucherConfiguration->id,
    ]);

    $this->voucherConfigurationQueries->removeSelectedProducts([
        'id' => $voucherConfiguration->id,
    ]);

    $this->assertDatabaseMissing('voucher_configuration_product', [
        'product_id' => $productId,
        'voucher_configuration_id' => $voucherConfiguration->id,
    ]);
});

test('getVouchersConfigurationExport method returns voucher configuration as expected', function (): void {
    $response = $this->voucherConfigurationQueries->getVouchersConfigurationExport([
        'search_text' => 'Birthday Voucher',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'status' => null,
        'restricted_by_type_id' => null,
        'voucher_type_id' => null,
        'discount_type_id' => null,
        'type' => null,
    ], $this->company->id);

    expect($response->first()->toArray())
        ->toHaveKey('get_value', $this->voucherConfiguration->get_value)
        ->toHaveKey('voucher_type', VoucherTypes::BIRTHDAY_VOUCHER->value)
        ->toHaveKey('discount_type', $this->voucherConfiguration->discount_type);
});

test(
    'getWelcomeMemberVoucherConfigurationByCompanyId method returns birthday voucher configuration',
    function (): void {
        $voucherConfiguration = VoucherConfiguration::factory()->create([
            'company_id' => $this->company->id,
            'voucher_type' => VoucherTypes::WELCOME_MEMBER->value,
            'start_date' => now()->subDay()->format('Y-m-d'),
            'end_date' => now()->addDay()->format('Y-m-d'),
            'get_value' => 100,
            'status' => 1,
        ]);

        $response = $this->voucherConfigurationQueries->getWelcomeMemberVoucherConfigurationByCompanyId(
            $this->company->id,
            now()
        );

        expect($response->toArray())
            ->toHaveKey('id', $voucherConfiguration->id)
            ->toHaveKey('voucher_type', VoucherTypes::WELCOME_MEMBER->value)
            ->toHaveKeys(
                ['use_minimum_spend_amount', 'validity_days', 'discount_type', 'get_value', 'start_date', 'end_date']
            );
    }
);

test('getListLoyaltyPointForPosWithRelatedData method returns voucher with all related data', function (): void {
    $voucherConfiguration = VoucherConfiguration::factory()->create([
        'company_id' => $this->company->id,
        'voucher_type' => VoucherTypes::LOYALTY_POINT->value,
        'get_value' => 100,
        'status' => 1,
    ]);

    $response = $this->voucherConfigurationQueries->getListLoyaltyPointForPosWithRelatedData($this->company->id);

    expect($response->first()->toArray())
        ->toHaveKey('id', $voucherConfiguration->id)
        ->toHaveKey('restricted_by_type', $voucherConfiguration->restricted_by_type)
        ->toHaveKey('voucher_type', $voucherConfiguration->voucher_type)
        ->toHaveKey('exclude_by_type', $voucherConfiguration->exclude_by_type)
        ->toHaveKey('discount_type', $voucherConfiguration->discount_type)
        ->toHaveKeys(['products', 'categories', 'voucher_configuration_tiers', 'memberships']);
});

test('getVouchersConfigurationForApplication method returns paginated results as expected', function (): void {
    $voucherConfiguration = VoucherConfiguration::factory()->create([
        'company_id' => $this->company->id,
        'voucher_type' => VoucherTypes::TIER_VOUCHER->value,
        'start_date' => Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d'),
        'end_date' => Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d'),
        'status' => true,
    ]);

    $response = $this->voucherConfigurationQueries->getVouchersConfigurationForApplication([
        'sort_by' => null,
        'sort_direction' => null,
        'search_text' => null,
        'per_page' => 1,
        'selected_date' => Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d'),
    ], $this->company->id);

    expect($response->toArray()['data'][0])
        ->toHaveKey('restricted_by_type', $voucherConfiguration->restricted_by_type)
        ->toHaveKey('voucher_type', $voucherConfiguration->voucher_type);
});

test('getListForEcommerceWithRelatedData method returns voucher with all related data', function (): void {
    $voucherConfiguration = VoucherConfiguration::factory()->create([
        'company_id' => $this->company->id,
        'voucher_type' => VoucherTypes::TIER_VOUCHER->value,
        'get_value' => 100,
        'status' => 1,
    ]);

    $response = $this->voucherConfigurationQueries->getListForEcommerceWithRelatedData($this->company->id);

    expect($response->first()->toArray())
        ->toHaveKey('id', $voucherConfiguration->id)
        ->toHaveKey('restricted_by_type', $voucherConfiguration->restricted_by_type)
        ->toHaveKey('voucher_type', $voucherConfiguration->voucher_type)
        ->toHaveKey('exclude_by_type', $voucherConfiguration->exclude_by_type)
        ->toHaveKey('discount_type', $voucherConfiguration->discount_type)
        ->toHaveKeys(['products', 'categories', 'voucher_configuration_tiers']);
});
