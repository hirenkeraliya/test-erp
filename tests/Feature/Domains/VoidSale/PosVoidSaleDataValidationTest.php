<?php

declare(strict_types=1);

use App\Domains\VoidSale\DataObjects\PosVoidSaleData;
use App\Models\Company;
use App\Models\VoidSaleReason;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->companyId = Company::factory()->create([
        'name' => 'Company A',
    ])->id;
});

test(
    'user cannot select Void Codes with different company.',
    function (): void {
        $companyBId = Company::factory()->create([
            'name' => 'Company B',
        ])->id;

        $voidSaleReason = VoidSaleReason::factory()->create([
            'reason' => 'Test',
            'company_id' => $companyBId,
        ]);

        $voidSaleDetails = [
            'voided_by_store_manager_id' => 1,
            'passcode' => '123456',
            'void_sale_reason_id' => $voidSaleReason->id,
        ];

        $request = new Request($voidSaleDetails);

        $request->validate(PosVoidSaleData::rules($this->companyId));
    }
)->throws(ValidationException::class);

test(
    'user can select Void Codes with same company.',
    function (): void {
        $voidSaleReason = VoidSaleReason::factory()->create([
            'reason' => 'Test',
            'company_id' => $this->companyId,
        ]);

        $voidSaleDetails = [
            'voided_by_store_manager_id' => 1,
            'passcode' => '123456',
            'void_sale_reason_id' => $voidSaleReason->id,
        ];

        $request = new Request($voidSaleDetails);
        $request->validate(PosVoidSaleData::rules($this->companyId));
        $this->assertTrue(true);
    }
);
