<?php

declare(strict_types=1);

use App\Domains\Company\RoundOffConfigurationToSixDigits;

test('It can return round off value', function (string $amount, float $roundOffValue): void {
    $response = RoundOffConfigurationToSixDigits::roundOffCalculationFor($amount);

    expect($response)->toBe($roundOffValue);
})->with([
    ['10.152001', -0.000001],
    ['10.152002', -0.000002],
    ['10.152003', 0.000002],
    ['10.152004', 0.000001],
    ['10.152005', 0.000000],
    ['10.152006', -0.000001],
    ['10.152007', -0.000002],
    ['10.152008', 0.000002],
    ['10.152009', 0.000001],
    ['10.000010', 0.000000],
    ['10.000000', 0.000000],
    ['10.00003', 0.000000],
    ['10', 0.000000],
    ['10.00009', 0.000000],
    ['10.00000', 0.000000],
]);

test('It can return round off list', function (): void {
    $roundOffConfigurationToSixDigits = new RoundOffConfigurationToSixDigits();
    $response = $roundOffConfigurationToSixDigits->getList();
    expect($response)
        ->toHaveKey('0.decimal_place', '.000001')
        ->toHaveKey('0.value', '-0.000001')
        ->toHaveKey('1.decimal_place', '.000002')
        ->toHaveKey('1.value', '-0.000002')
        ->toHaveKey('2.decimal_place', '.000003')
        ->toHaveKey('2.value', '0.000002')
        ->toHaveKey('3.decimal_place', '.000004')
        ->toHaveKey('3.value', '0.000001')
        ->toHaveKey('4.decimal_place', '.000005')
        ->toHaveKey('4.value', '0.000000')
        ->toHaveKey('5.decimal_place', '.000006')
        ->toHaveKey('5.value', '-0.000001')
        ->toHaveKey('6.decimal_place', '.000007')
        ->toHaveKey('6.value', '-0.000002')
        ->toHaveKey('7.decimal_place', '.000008')
        ->toHaveKey('7.value', '0.000002')
        ->toHaveKey('8.decimal_place', '.000009')
        ->toHaveKey('8.value', '0.000001')
        ->toHaveKey('9.decimal_place', '.000000')
        ->toHaveKey('9.value', '0.000000');
});
