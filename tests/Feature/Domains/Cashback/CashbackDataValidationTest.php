<?php

declare(strict_types=1);

use App\Domains\Cashback\DataObjects\CashbackData;
use App\Domains\Cashback\Enums\ExcludeByTypes;
use App\Domains\Common\Enums\DiscountTypes;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

test('add cashback request is validated.', function (): void {
    $cashbackDetails = [
        'exclude_by_type' => ExcludeByTypes::PRODUCTS->value,
        'discount_type_id' => DiscountTypes::FLAT->value,
        'name' => 'abc',
        'discount_value' => 10.10,
        'minimum_spend_amount' => 10.10,
        'start_date' => '2022-04-10',
        'end_date' => '2022-05-15',
        'location_ids' => [1],
        'category_ids' => [],
        'product_ids' => [1],
    ];

    $request = new Request($cashbackDetails);

    $request->validate(CashbackData::rules($request));

    $this->assertTrue(true);
});

test(
    'products required when exclude by type is product.',
    function (): void {
        $cashbackDetails = [
            'exclude_by_type' => ExcludeByTypes::PRODUCTS->value,
            'name' => 'abc',
            'discount_value' => '10.10',
            'minimum_spend_amount' => '10.10',
            'start_date' => '2022-04-10',
            'end_date' => '2022-05-15',
            'location_ids' => [1],
            'category_ids' => [],
            'product_ids' => [],
        ];
        $request = new Request($cashbackDetails);
        $request->validate(CashbackData::rules($request));
    }
)->throws(ValidationException::class);

test(
    'categories required when exclude by type is categories.',
    function (): void {
        $cashbackDetails = [
            'exclude_by_type' => ExcludeByTypes::CATEGORIES->value,
            'name' => 'abc',
            'discount_value' => '10.10',
            'minimum_spend_amount' => '10.10',
            'start_date' => '2022-04-10',
            'end_date' => '2022-05-15',
            'location_ids' => [1],
            'category_ids' => [],
            'product_ids' => [],
        ];
        $request = new Request($cashbackDetails);
        $request->validate(CashbackData::rules($request));
    }
)->throws(ValidationException::class);
