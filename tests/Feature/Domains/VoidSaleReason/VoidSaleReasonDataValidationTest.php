<?php

declare(strict_types=1);

use App\Domains\Common\Enums\SaleReturnOrVoidSaleReasonTypes;
use App\Domains\VoidSaleReason\DataObjects\VoidSaleReasonData;
use App\Models\Company;
use App\Models\VoidSaleReason;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->companyAId = Company::factory()->create()->id;
    $this->companyBId = Company::factory()->create()->id;

    $this->voidSaleReasonsA = VoidSaleReason::factory()->create([
        'company_id' => $this->companyAId,
        'reason' => 'Sale return reason 1',
    ]);

    setCompanyIdInSession($this->companyAId);
});

test('user cannot add same void sale reason with same company.', function (): void {
    $request = new Request([
        'reason' => $this->voidSaleReasonsA->reason,
        'type_ids' => [SaleReturnOrVoidSaleReasonTypes::POS->value],
    ]);

    VoidSaleReasonData::validate($request);
})->throws(ValidationException::class);

test('user can add same void sale reason with different company.', function (): void {
    setCompanyIdInSession($this->companyBId);

    $request = new Request([
        'reason' => $this->voidSaleReasonsA->reason,
        'type_ids' => [SaleReturnOrVoidSaleReasonTypes::POS->value],
    ]);

    VoidSaleReasonData::validate($request);
    $this->assertTrue(true);
});
