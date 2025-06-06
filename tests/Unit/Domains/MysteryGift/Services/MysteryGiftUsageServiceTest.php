<?php

declare(strict_types=1);

use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\Currency\CurrencyQueries;
use App\Domains\MysteryGift\MysteryGiftUsagesQueries;
use App\Domains\MysteryGift\Services\MysteryGiftUsageService;
use App\Domains\MysteryGiftProduct\MysteryGiftProductQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\Sale\Enums\SaleStatus;
use App\Domains\Sale\SaleQueries;
use App\Domains\Voucher\VoucherQueries;
use App\Domains\VoucherConfiguration\VoucherConfigurationQueries;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Employee;
use App\Models\MysteryGift;
use App\Models\MysteryGiftProduct;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Voucher;
use App\Models\VoucherConfiguration;

beforeEach(function (): void {
    $this->company = Company::factory()->make([
        'id' => 1,
        'default_country_id' => 1,
    ]);

    $this->mysteryGift = MysteryGift::factory()->make([
        'id' => 1,
        'company_id' => $this->company->id,
        'start_date' => '2023-01-01',
        'end_date' => '2023-01-10',
        'minimum_spend_amount_for_flat_amount' => 100,
        'max_flat_amount' => 50,
        'minimum_spend_amount_for_percentage' => 200,
        'min_percentage' => 1,
        'max_percentage' => 10,
        'is_flat_amount' => true,
        'is_percentage' => true,
    ]);

    $this->mysteryGiftA = MysteryGift::factory()->make([
        'id' => 2,
        'company_id' => $this->company->id,
        'start_date' => '2023-01-01',
        'end_date' => '2023-01-10',
        'minimum_spend_amount_for_flat_amount' => 100,
        'min_flat_amount' => 10,
        'max_flat_amount' => 50,
        'minimum_spend_amount_for_percentage' => 200,
        'is_flat_amount' => true,
        'is_percentage' => true,
    ]);

    $this->employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => $this->company->id,
        'designation_id' => 1,
    ]);

    $this->sale = Sale::factory()->make([
        'id' => 1,
        'status' => SaleStatus::REGULAR_SALE->value,
        'member_id' => 1,
        'counter_update_id' => 1,
    ]);

    $this->voucherConfiguration = VoucherConfiguration::factory()->make([
        'id' => 1,
        'company_id' => $this->company->id,
        'voucher_type' => 'flat',
        'value' => 10,
        'start_date' => '2023-01-01',
        'end_date' => '2023-01-10',
    ]);

    $this->voucherConfigurationA = VoucherConfiguration::factory()->make([
        'id' => 2,
        'mystery_gift_id' => $this->mysteryGift->id,
        'company_id' => $this->company->id,
        'value' => 10,
        'start_date' => '2023-01-01',
        'end_date' => '2023-01-10',
        'discount_type' => DiscountTypes::PERCENTAGE->value,
    ]);

    $this->voucherConfigurationB = VoucherConfiguration::factory()->make([
        'id' => 3,
        'mystery_gift_id' => $this->mysteryGift->id,
        'company_id' => $this->company->id,
        'value' => 10,
        'start_date' => '2023-01-01',
        'end_date' => '2023-01-10',
        'discount_type' => DiscountTypes::FLAT->value,
    ]);

    $this->mysteryGiftUsageService = new MysteryGiftUsageService();
});

test('checkRequestDetails method returns redirect when member_id is not set', function (): void {
    $this->sale->member_id = null;

    $this->mock(SaleQueries::class, function ($mock): void {
        $mock->shouldReceive('getSaleByOfflineId')
            ->once()
            ->andReturn($this->sale);
    });

    $response = $this->mysteryGiftUsageService->checkRequestDetails('receipt123', 'location123');

    expect($response)->toBe([
        'status' => 'redirect',
        'route' => 'front.mystery_gift.add_member',
        'params' => [
            'locationId' => 'location123',
            'receiptId' => 'receipt123',
        ],
    ]);
});

test('isGiftExpired method returns true for expired gift', function (): void {
    $this->mysteryGift->end_date = now()->subDay()->toDateString();

    $isExpired = $this->mysteryGiftUsageService->isGiftExpired($this->mysteryGift);

    expect($isExpired)->toBeTrue();
});

test('isGiftExpired method returns false for valid gift', function (): void {
    $this->mysteryGift->end_date = now()->addDay()->toDateString();

    $isExpired = $this->mysteryGiftUsageService->isGiftExpired($this->mysteryGift);

    expect($isExpired)->toBeFalse();
});

test('isDatesValid method returns true for valid dates', function (): void {
    $isValid = $this->mysteryGiftUsageService->isDatesValid($this->mysteryGift, '2023-01-05 12:00:00');

    expect($isValid)->toBeTrue();
});

test('isDatesValid method returns false for invalid dates', function (): void {
    $isValid = $this->mysteryGiftUsageService->isDatesValid($this->mysteryGift, '2023-01-15 12:00:00');

    expect($isValid)->toBeFalse();
});

test('generateRandomFlatAmount method generates correct voucher', function (): void {
    $this->mock(VoucherConfigurationQueries::class, function ($mock): void {
        $mock->shouldReceive('getVoucherIdByMysteryGiftId')
            ->once()
            ->andReturn($this->voucherConfigurationB);
    });

    $voucher = Voucher::factory()->make([
        'id' => 1,
        'voucher_configuration_id' => $this->voucherConfigurationB->id,
        'member_id' => 1,
        'generated_by_sale_id' => 1,
    ]);

    $this->mock(VoucherQueries::class, function ($mock) use ($voucher): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->andReturn($voucher);
    });

    $this->mock(MysteryGiftUsagesQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $currency = Currency::factory()->make([
        'country_id' => 1,
        'symbol' => '$',
    ]);

    $this->mock(CurrencyQueries::class, function ($mock) use ($currency): void {
        $mock->shouldReceive('getByCompanyId')
            ->with($this->company->id)
            ->once()
            ->andReturn($currency);
    });

    $result = $this->mysteryGiftUsageService->generateRandomFlatAmount($this->mysteryGiftA, $this->sale);

    expect($result['type'])->toBe('flat');
    expect($result['status'])->toBeTrue();
    expect($result['value'])->toContain('$');
});

test('generateRandomPercentage method generates correct voucher', function (): void {
    $this->mock(VoucherConfigurationQueries::class, function ($mock): void {
        $mock->shouldReceive('getVoucherIdByMysteryGiftId')
            ->once()
            ->andReturn($this->voucherConfigurationA);
    });

    $voucher = Voucher::factory()->make([
        'id' => 1,
        'voucher_configuration_id' => $this->voucherConfigurationA->id,
        'member_id' => 1,
        'generated_by_sale_id' => 1,
    ]);

    $this->mock(VoucherQueries::class, function ($mock) use ($voucher): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->andReturn($voucher);
    });

    $this->mock(MysteryGiftUsagesQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $result = $this->mysteryGiftUsageService->generateRandomPercentage($this->mysteryGift, $this->sale);

    expect($result['type'])->toBe('discount');
    expect($result['status'])->toBeTrue();
    expect($result['value'])->toContain('%');
});

test('getFreeProductPromoCode method generates correct promo code', function (): void {
    $mysteryGiftProduct = MysteryGiftProduct::factory()->make([
        'id' => 1,
        'mystery_gift_id' => $this->mysteryGift->id,
        'product_id' => 1,
        'quantity' => 2,
    ]);

    $this->mock(MysteryGiftProductQueries::class, function ($mock) use ($mysteryGiftProduct): void {
        $mock->shouldReceive('getRandomProductId')
            ->once()
            ->andReturn($mysteryGiftProduct);
    });

    $product = Product::factory()->make([
        'id' => 1,
        'company_id' => $this->company->id,
        'name' => 'Test Product',
        'unit_of_measure_id' => 1,
        'season_id' => 1,
        'department_id' => 1,
        'color_id' => 1,
        'size_id' => 1,
        'brand_id' => 1,
        'style_id' => 1,
        'upc' => '123456789012',
    ]);

    $this->mock(ProductQueries::class, function ($mock) use ($product): void {
        $mock->shouldReceive('getByIdOnlyNameAndUpc')
            ->once()
            ->andReturn($product);
    });

    $this->mock(MysteryGiftUsagesQueries::class, function ($mock): void {
        $mock->shouldReceive('existsByCouponCode')
            ->once()
            ->andReturn(false);

        $mock->shouldReceive('addNew')
            ->once();
    });

    $result = $this->mysteryGiftUsageService->getFreeProductPromoCode($this->mysteryGift, $this->sale);

    expect($result['type'])->toBe('product');
    expect($result['status'])->toBeTrue();
    expect($result['value'])->toContain('Free Test Product');
});
