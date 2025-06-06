<?php

declare(strict_types=1);

use App\Domains\Company\RoundOffConfiguration;

test('It can return round off value', function (string $amount, float $roundOffValue): void {
    $response = RoundOffConfiguration::roundOffCalculationFor($amount);

    expect($response)->toBe($roundOffValue);
})->with([
    ['10.01', -0.01],
    ['10.02', -0.02],
    ['10.03', 0.02],
    ['10.04', 0.01],
    ['10.05', 0.00],
    ['10.06', -0.01],
    ['10.07', -0.02],
    ['10.08', 0.02],
    ['10.09', 0.01],
    ['10.10', 0.00],
    ['10.00', 0.00],
    ['10.3', 0.00],
    ['10', 0.00],
    ['10.9', 0.00],
    ['10.0', 0.00],
]);
