<?php

declare(strict_types=1);

use App\Domains\Common\Enums\Statuses;
use App\Domains\SaleTargetTimeframe\SaleTargetTimeframeQueries;
use App\Models\Company;
use App\Models\SaleTarget;
use App\Models\SaleTargetTimeframe;

beforeEach(function (): void {
    $this->companyId = Company::factory()->create()->id;

    $this->saleTarget = SaleTarget::factory()->create([
        'company_id' => $this->companyId,
        'name' => 'ABC',
        'status' => Statuses::ACTIVE->value,
    ]);

    $this->saleTargetTimeframeQueries = new SaleTargetTimeframeQueries();
});

test('A new sale target time frame can be added', function (): void {
    $saleTargetTimeframeData = [
        'sale_target_id' => $this->saleTarget->id,
        'target_label' => 'Test',
        'start_date' => now()->format('Y-m-d'),
        'end_date' => now()->format('Y-m-d'),
    ];

    $this->saleTargetTimeframeQueries->addNew($saleTargetTimeframeData);

    $this->assertDatabaseHas('sale_target_timeframes', [
        'sale_target_id' => $saleTargetTimeframeData['sale_target_id'],
        'target_label' => $saleTargetTimeframeData['target_label'],
        'start_date' => $saleTargetTimeframeData['start_date'],
        'end_date' => $saleTargetTimeframeData['end_date'],
    ]);
});

test('deleteBySaleTarget method can delete sale target time frame', function (): void {
    $saleTargetTimeframe = SaleTargetTimeframe::factory()->create([
        'sale_target_id' => $this->saleTarget->id,
    ]);

    $this->assertDatabaseHas('sale_target_timeframes', [
        'sale_target_id' => $saleTargetTimeframe->sale_target_id,
        'target_label' => $saleTargetTimeframe->target_label,
        'start_date' => $saleTargetTimeframe->start_date,
        'end_date' => $saleTargetTimeframe->end_date,
    ]);

    $this->saleTargetTimeframeQueries->deleteBySaleTarget($this->saleTarget);

    $this->assertDatabaseMissing('sale_target_timeframes', [
        'sale_target_id' => $saleTargetTimeframe->sale_target_id,
        'target_label' => $saleTargetTimeframe->target_label,
        'start_date' => $saleTargetTimeframe->start_date,
        'end_date' => $saleTargetTimeframe->end_date,
    ]);
});

test('getByStartAndEndDate method can return sale target time frame', function (): void {
    $date = now();
    $saleTargetTimeframe = SaleTargetTimeframe::factory()->create([
        'sale_target_id' => $this->saleTarget->id,
        'start_date' => $date->subDay()->format('Y-m-d'),
        'end_date' => $date->format('Y-m-d'),
    ]);

    $response = $this->saleTargetTimeframeQueries->getByStartAndEndDate();

    expect($response->first()->toArray())
        ->toHaveKey('id', $saleTargetTimeframe->id)
        ->toHaveKey('sale_target_id', $saleTargetTimeframe->sale_target_id)
        ->toHaveKey('start_date', $saleTargetTimeframe->start_date)
        ->toHaveKey('end_date', $saleTargetTimeframe->end_date)
        ->toHaveKey('target_label', $saleTargetTimeframe->target_label)
        ->toHaveKey('amount', $saleTargetTimeframe->amount)
        ->toHaveKeys(['sale_target', 'sale_target.locations', 'sale_target.promoters', 'sale_achieved_targets']);
});

test('getBySaleTargetId method can return sale target time frame', function (): void {
    $date = now();
    $saleTargetTimeframe = SaleTargetTimeframe::factory()->create([
        'sale_target_id' => $this->saleTarget->id,
        'start_date' => $date->subDay()->format('Y-m-d'),
        'end_date' => $date->format('Y-m-d'),
    ]);

    $response = $this->saleTargetTimeframeQueries->getBySaleTargetId($saleTargetTimeframe->sale_target_id);

    expect($response->first()->toArray())
        ->toHaveKey('id', $saleTargetTimeframe->id)
        ->toHaveKey('sale_target_id', $saleTargetTimeframe->sale_target_id)
        ->toHaveKey('start_date', $saleTargetTimeframe->start_date)
        ->toHaveKey('end_date', $saleTargetTimeframe->end_date)
        ->toHaveKey('target_label', $saleTargetTimeframe->target_label)
        ->toHaveKey('amount', $saleTargetTimeframe->amount)
        ->toHaveKeys(['sale_target', 'sale_target.locations', 'sale_target.promoters', 'sale_achieved_targets']);
});
