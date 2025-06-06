<?php

declare(strict_types=1);

use App\Domains\SubPaymentType\DataObjects\SubPaymentTypeData;
use App\Models\Company;
use App\Models\PaymentType;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->companyAId = Company::factory()->create()->id;
    $this->companyBId = Company::factory()->create()->id;

    $this->paymentTypeA = PaymentType::factory()->create([
        'company_id' => $this->companyAId,
        'name' => 'JKLM',
    ]);

    $this->paymentTypeB = PaymentType::factory()->create([
        'company_id' => $this->companyBId,
        'name' => 'PQRS',
    ]);

    $this->subPaymentTypeA = PaymentType::factory()->create([
        'company_id' => $this->companyAId,
        'name' => 'ABCD',
        'parent_payment_type_id' => $this->paymentTypeA->id,
        'image_name' => 'cash.png',
    ]);

    $this->subPaymentTypeB = PaymentType::factory()->create([
        'company_id' => $this->companyBId,
        'name' => 'XYZW',
        'parent_payment_type_id' => $this->paymentTypeB->id,
        'image_name' => 'debit.png',
    ]);

    setCompanyIdInSession($this->companyAId);
});

test('user cannot add same sub payment type name with the same company.', function (): void {
    $request = new Request([
        'name' => $this->subPaymentTypeA->name,
        'is_member_required' => $this->subPaymentTypeA->is_member_required,
        'is_available_for_refund' => $this->subPaymentTypeA->is_available_for_refund,
        'trigger_card_payment_machine' => $this->subPaymentTypeA->trigger_card_payment_machine,
        'trigger_qr_code_payment_machine' => $this->subPaymentTypeA->trigger_qr_code_payment_machine,
        'trigger_card_affin_payment_machine' => $this->subPaymentTypeA->trigger_card_affin_payment_machine,
        'trigger_card_bank_rakyat_terminal' => $this->subPaymentTypeA->trigger_card_bank_rakyat_terminal,
        'is_card_payment' => $this->subPaymentTypeA->is_card_payment,
        'status' => $this->subPaymentTypeA->status,
        'image_name' => $this->subPaymentTypeA->image_name,
    ]);

    SubPaymentTypeData::validate($request);
})->throws(ValidationException::class);

test('user can add same sub payment type name with different company.', function (): void {
    setCompanyIdInSession($this->companyBId);

    $request = new Request([
        'name' => $this->subPaymentTypeA->name,
        'is_member_required' => $this->subPaymentTypeA->is_member_required,
        'is_available_for_refund' => $this->subPaymentTypeA->is_available_for_refund,
        'trigger_card_payment_machine' => $this->subPaymentTypeA->trigger_card_payment_machine,
        'trigger_qr_code_payment_machine' => $this->subPaymentTypeA->trigger_qr_code_payment_machine,
        'trigger_card_affin_payment_machine' => $this->subPaymentTypeA->trigger_card_affin_payment_machine,
        'trigger_card_bank_rakyat_terminal' => $this->subPaymentTypeA->trigger_card_bank_rakyat_terminal,
        'is_card_payment' => $this->subPaymentTypeA->is_card_payment,
        'status' => $this->subPaymentTypeA->status,
        'image_name' => $this->subPaymentTypeA->image_name,
    ]);

    SubPaymentTypeData::validate($request);
    $this->assertTrue(true);
});
