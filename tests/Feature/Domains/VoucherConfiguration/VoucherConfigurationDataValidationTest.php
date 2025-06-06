<?php

declare(strict_types=1);

use App\Domains\VoucherConfiguration\DataObjects\VoucherConfigurationData;
use App\Domains\VoucherConfiguration\Enums\ExcludeByTypes;
use App\Domains\VoucherConfiguration\Enums\VoucherTypes;
use App\Models\Category;
use App\Models\Company;
use App\Models\VoucherConfiguration;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->companyId = Company::factory()->create()->id;
});

test('voucher configuration validation passes all the details are provides.', function (): void {
    $categoryId = Category::factory()->create()->id;
    $voucherConfigurationA = VoucherConfiguration::factory()->create([
        'company_id' => $this->companyId,
        'voucher_type' => VoucherTypes::TIER_VOUCHER->value,
        'exclude_by_type' => ExcludeByTypes::NONE->value,
        'start_date' => '2022-06-27',
        'end_date' => '2022-06-28',
        'is_available_in_ecommerce' => false,
    ]);

    $prepareData = [
        'tiers' => [[
            'minimum_spend_amount' => 10,
            'maximum_spend_amount' => 20,
            'get_value' => 10,
        ]],
        'category_ids' => [$categoryId],
        'sale_channel_ids' => [],
    ];

    $voucherConfigurationDetails = array_merge($voucherConfigurationA->toArray(), $prepareData);

    $request = new Request($voucherConfigurationDetails);
    $request->validate(VoucherConfigurationData::rules($request));
    $this->assertTrue(true);
});

test(
    'voucher configuration validation fails when voucher type is tiers but tier details is not specified.',
    function (): void {
        $voucherConfigurationA = VoucherConfiguration::factory()->create([
            'company_id' => $this->companyId,
            'voucher_type' => VoucherTypes::TIER_VOUCHER->value,
            'exclude_by_type' => ExcludeByTypes::NONE->value,
            'start_date' => '2022-06-27',
            'end_date' => '2022-06-28',
        ]);

        $request = new Request($voucherConfigurationA->toArray());
        $request->validate(VoucherConfigurationData::rules($request));
    }
)->throws(ValidationException::class);

test(
    'voucher configuration validation fails when exclude by type is categories but categories details is not specified',
    function (): void {
        $voucherConfigurationA = VoucherConfiguration::factory()->create([
            'company_id' => $this->companyId,
            'voucher_type' => VoucherTypes::BIRTHDAY_VOUCHER->value,
            'exclude_by_type' => ExcludeByTypes::CATEGORIES->value,
            'start_date' => '2022-06-27',
            'end_date' => '2022-06-28',
        ]);

        $request = new Request($voucherConfigurationA->toArray());
        $request->validate(VoucherConfigurationData::rules($request));
    }
)->throws(ValidationException::class);

test(
    'voucher configuration validation fails when exclude by type is product but product details is not specified.',
    function (): void {
        $voucherConfigurationA = VoucherConfiguration::factory()->create([
            'company_id' => $this->companyId,
            'voucher_type' => VoucherTypes::BIRTHDAY_VOUCHER->value,
            'exclude_by_type' => ExcludeByTypes::PRODUCTS->value,
            'start_date' => '2022-06-27',
            'end_date' => '2022-06-28',
        ]);

        $request = new Request($voucherConfigurationA->toArray());
        $request->validate(VoucherConfigurationData::rules($request));
    }
)->throws(ValidationException::class);
