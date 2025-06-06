<?php

declare(strict_types=1);

use App\Domains\StockTransferReason\DataObjects\StockTransferReasonData;
use App\Models\Company;
use App\Models\StockTransferReason;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->companyAId = Company::factory()->create()->id;
    $this->companyBId = Company::factory()->create()->id;

    $this->stockTransferReasonA = StockTransferReason::factory()->create([
        'company_id' => $this->companyAId,
        'name' => 'ABCD',
        'code' => 'ABCD',
    ]);
    $this->stockTransferReasonB = StockTransferReason::factory()->create([
        'company_id' => $this->companyBId,
        'name' => 'XYZW',
        'code' => 'XYZW',
    ]);

    setCompanyIdInSession($this->companyAId);
});

test('user cannot add same stock transfer reason with same company.', function (): void {
    $request = new Request([
        'name' => $this->stockTransferReasonA->name,
        'code' => $this->stockTransferReasonA->code,
    ]);

    StockTransferReasonData::validate($request);
})->throws(ValidationException::class);

test('user can add same stock transfer reason with different company.', function (): void {
    setCompanyIdInSession($this->companyBId);

    $request = new Request([
        'name' => $this->stockTransferReasonA->name,
        'code' => $this->stockTransferReasonA->code,
    ]);

    StockTransferReasonData::validate($request);
    $this->assertTrue(true);
});
