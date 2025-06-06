<?php

declare(strict_types=1);

use App\Domains\Cashback\Enums\ConditionTypes;
use App\Domains\Cashback\Enums\ExcludeByTypes;
use App\Domains\CashbackPrice\CashbackPriceQueries;
use App\Models\Cashback;
use App\Models\CashbackPrice;
use App\Models\Company;

beforeEach(function (): void {
    $this->companyId = Company::factory()->create()->id;

    $this->cashbackA = Cashback::factory()->create([
        'company_id' => $this->companyId,
        'name' => 'DEF',
        'exclude_by_type' => ExcludeByTypes::ORIGINAL_ITEM_PRICE->value,
        'start_date' => now()->startOfMonth()->format('Y-m-d'),
        'end_date' => now()->endOfMonth()->format('Y-m-d'),
    ]);

    $this->cashBackPrice = CashbackPrice::factory()->create([
        'cashback_id' => $this->cashbackA->id,
        'condition_operator_type_id' => ConditionTypes::LESS_THAN->value,
        'amount' => 10,
    ]);
    $this->cashbackPriceQueries = resolve(CashbackPriceQueries::class);
});

test('A cashback prices can be stored', function (): void {
    $data = [
        'cashback_id' => $this->cashbackA->id,
        'condition_operator_type_id' => $this->cashBackPrice->condition_operator_type_id,
        'amount' => $this->cashBackPrice->amount,
    ];
    $this->cashbackPriceQueries->addNew($data);
    $this->assertDatabaseHas('cashback_prices', $data);
});

test('A cashback prices can be delete', function (): void {
    $this->cashbackPriceQueries->delete($this->cashbackA);
    $this->assertDatabaseMissing('cashback_prices', $this->cashBackPrice->toArray());
});
