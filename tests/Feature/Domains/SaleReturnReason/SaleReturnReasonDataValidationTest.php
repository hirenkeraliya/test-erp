<?php

declare(strict_types=1);

use App\Domains\Common\Enums\SaleReturnOrVoidSaleReasonTypes;
use App\Domains\SaleReturnReason\DataObjects\SaleReturnReasonData;
use App\Models\Company;
use App\Models\SaleReturnReason;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->companyAId = Company::factory()->create()->id;
    $this->companyBId = Company::factory()->create()->id;

    $this->saleReturnReasonA = SaleReturnReason::factory()->create([
        'company_id' => $this->companyAId,
        'reason' => 'Sale return reason 1',
        'put_back_in_inventory' => true,
    ]);
    $this->saleReturnReasonB = SaleReturnReason::factory()->create([
        'company_id' => $this->companyBId,
        'reason' => 'Sale return reason 2',
        'put_back_in_inventory' => true,
    ]);

    setCompanyIdInSession($this->companyAId);
});

test('user cannot add same sale return reason with same company.', function (): void {
    $request = new Request([
        'reason' => $this->saleReturnReasonA->reason,
        'put_back_in_inventory' => $this->saleReturnReasonA->put_back_in_inventory,
        'type_ids' => [SaleReturnOrVoidSaleReasonTypes::POS->value],
    ]);

    SaleReturnReasonData::validate($request);
})->throws(ValidationException::class);

test('user can add same sale return reason with different company.', function (): void {
    setCompanyIdInSession($this->companyBId);

    $request = new Request([
        'reason' => $this->saleReturnReasonA->reason,
        'put_back_in_inventory' => $this->saleReturnReasonA->put_back_in_inventory,
        'type_ids' => [SaleReturnOrVoidSaleReasonTypes::POS->value],
    ]);

    SaleReturnReasonData::validate($request);
    $this->assertTrue(true);
});
