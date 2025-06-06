<?php

declare(strict_types=1);

use App\Domains\SubPaymentType\DataObjects\SubPaymentTypeData;
use App\Domains\SubPaymentType\SubPaymentTypeQueries;
use App\Models\Company;
use App\Models\PaymentType;

beforeEach(function (): void {
    $this->companyA = Company::factory()->create();

    $this->paymentType = PaymentType::factory()->create([
        'company_id' => $this->companyA->id,
        'name' => 'JKLM',
    ]);

    $this->subPaymentTypeA = PaymentType::factory()->create([
        'company_id' => $this->companyA->id,
        'name' => 'ABCD',
        'parent_payment_type_id' => $this->paymentType->id,
    ]);
    $this->subPaymentTypeB = PaymentType::factory()->create([
        'company_id' => $this->companyA->id,
        'name' => 'XYZW',
        'parent_payment_type_id' => $this->paymentType->id,
    ]);

    $this->subPaymentTypeQueries = new SubPaymentTypeQueries();
});

test('Sub Payment Types can be searched', function (): void {
    $response = $this->subPaymentTypeQueries->listQuery([
        'search_text' => 'ABCD',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->paymentType->id, $this->companyA->id);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->subPaymentTypeA->name);
});

test('New sub payment type can be added', function (): void {
    $this->subPaymentTypeQueries->addNew(
        new SubPaymentTypeData(
            'payment_type',
            false,
            false,
            false,
            false,
            false,
            true,
            'cash.png',
            'payment_type',
            false,
            false,
            true
        ),
        $this->paymentType->id,
        $this->companyA->id
    );

    $this->assertDatabaseHas('payment_types', [
        'company_id' => $this->companyA->id,
        'name' => 'payment_type',
        'parent_payment_type_id' => $this->paymentType->id,
        'is_member_required' => false,
        'is_available_for_refund' => false,
        'status' => true,
        'image_name' => 'cash.png',
        'is_available_in_pos' => true,
    ]);
});

test('A payment type can be fetched', function (): void {
    $response = $this->subPaymentTypeQueries->getById(
        $this->paymentType->id,
        $this->subPaymentTypeA->id,
        $this->companyA->id
    );
    expect($response->toArray())
        ->toHaveKey('name', $this->subPaymentTypeA->name)
        ->toHaveKey('is_member_required', $this->subPaymentTypeA->is_member_required)
        ->toHaveKey('is_available_for_refund', $this->subPaymentTypeA->is_available_for_refund)
        ->toHaveKey('status', $this->subPaymentTypeA->status)
        ->toHaveKey('is_available_in_pos', $this->subPaymentTypeA->is_available_in_pos);
});

test('A payment type can be updated', function (): void {
    $this->subPaymentTypeQueries->update(
        new SubPaymentTypeData(
            'payment_type_1',
            false,
            false,
            false,
            false,
            false,
            false,
            'cash.png',
            'payment_type_1',
            false,
            false,
            false
        ),
        $this->paymentType->id,
        $this->subPaymentTypeA->id,
        $this->companyA->id
    );

    $this->assertDatabaseHas('payment_types', [
        'company_id' => $this->companyA->id,
        'name' => 'payment_type_1',
        'is_member_required' => false,
        'is_available_for_refund' => false,
        'image_name' => 'cash.png',
        'is_available_in_pos' => false,
    ]);
});

test('it can change the status of the sub payment type', function (): void {
    $this->subPaymentTypeQueries->setStatus($this->subPaymentTypeA->id, $this->companyA->id, false);

    $this->assertDatabaseHas('payment_types', [
        'id' => $this->subPaymentTypeA->id,
        'status' => false,
    ]);
});

test('getSubPaymentTypesExport method returns sub payment types as expected', function (): void {
    $response = $this->subPaymentTypeQueries->getSubPaymentTypesExport([
        'search_text' => $this->subPaymentTypeA->name,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->paymentType->id, $this->companyA->id);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->subPaymentTypeA->id)
        ->toHaveKey('name', $this->subPaymentTypeA->name);
});
