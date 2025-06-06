<?php

declare(strict_types=1);

use App\Domains\PaymentType\DataObjects\PaymentTypeData;
use App\Models\Company;
use App\Models\PaymentType;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->companyAId = Company::factory()->create()->id;
    $this->companyBId = Company::factory()->create()->id;

    $this->paymentTypeA = PaymentType::factory()->create([
        'company_id' => $this->companyAId,
        'name' => 'ABCD',
        'image_name' => 'cash.png',
    ]);
    $this->paymentTypeB = PaymentType::factory()->create([
        'company_id' => $this->companyBId,
        'name' => 'XYZW',
        'image_name' => 'debit.png',
    ]);

    setCompanyIdInSession($this->companyAId);
});

test('user cannot add same name with same company.', function (): void {
    $request = new Request([
        'name' => $this->paymentTypeA->name,
        'is_member_required' => $this->paymentTypeA->is_member_required,
        'is_available_for_refund' => $this->paymentTypeA->is_available_for_refund,
        'trigger_qr_code_payment_machine' => $this->paymentTypeA->trigger_qr_code_payment_machine,
        'trigger_card_payment_machine' => $this->paymentTypeA->trigger_card_payment_machine,
        'trigger_card_affin_payment_machine' => $this->paymentTypeA->trigger_card_affin_payment_machine,
        'trigger_card_bank_rakyat_terminal' => $this->paymentTypeA->trigger_card_bank_rakyat_terminal,
        'is_card_payment' => $this->paymentTypeA->is_card_payment,
        'site_key' => $this->paymentTypeA->site_key,
        'secret_key' => $this->paymentTypeA->secret_key,
        'url' => $this->paymentTypeA->url,
        'status' => $this->paymentTypeA->status,
        'image_name' => $this->paymentTypeA->image_name,
    ]);

    PaymentTypeData::validate($request);
})->throws(ValidationException::class);

test('user can add same name with different company.', function (): void {
    setCompanyIdInSession($this->companyBId);

    $request = new Request([
        'name' => $this->paymentTypeA->name,
        'is_member_required' => $this->paymentTypeA->is_member_required,
        'is_available_for_refund' => $this->paymentTypeA->is_available_for_refund,
        'trigger_card_payment_machine' => $this->paymentTypeA->trigger_card_payment_machine,
        'trigger_qr_code_payment_machine' => $this->paymentTypeA->trigger_qr_code_payment_machine,
        'trigger_card_affin_payment_machine' => $this->paymentTypeA->trigger_card_affin_payment_machine,
        'trigger_card_bank_rakyat_terminal' => $this->paymentTypeA->trigger_card_bank_rakyat_terminal,
        'is_card_payment' => $this->paymentTypeA->is_card_payment,
        'site_key' => $this->paymentTypeA->site_key,
        'secret_key' => $this->paymentTypeA->secret_key,
        'url' => $this->paymentTypeA->url,
        'status' => $this->paymentTypeA->status,
        'image_name' => $this->paymentTypeA->image_name,
    ]);

    PaymentTypeData::validate($request);
    $this->assertTrue(true);
});
