<?php

declare(strict_types=1);

use App\Domains\CashMovementReason\DataObjects\CashMovementReasonData;
use App\Models\CashMovementReason;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->companyAId = Company::factory()->create()->id;
    $this->companyBId = Company::factory()->create()->id;

    $this->cashMovementReasonA = CashMovementReason::factory()->create([
        'company_id' => $this->companyAId,
        'reason' => 'Cash movement reason 1',
        'type_id' => 1,
    ]);
    $this->cashMovementReasonB = CashMovementReason::factory()->create([
        'company_id' => $this->companyBId,
        'reason' => 'Cash movement reason 2',
        'type_id' => 2,
    ]);

    setCompanyIdInSession($this->companyAId);
});

test('user cannot add same cash movement return reason with same company.', function (): void {
    $request = new Request([
        'reason' => $this->cashMovementReasonA->reason,
        'type_id' => $this->cashMovementReasonA->type_id,
    ]);

    CashMovementReasonData::validate($request);
})->throws(ValidationException::class);

test('user can add same cash movement reason with different company.', function (): void {
    setCompanyIdInSession($this->companyBId);

    $request = new Request([
        'reason' => $this->cashMovementReasonA->reason,
        'type_id' => $this->cashMovementReasonA->type_id,
    ]);

    CashMovementReasonData::validate($request);
    $this->assertTrue(true);
});
