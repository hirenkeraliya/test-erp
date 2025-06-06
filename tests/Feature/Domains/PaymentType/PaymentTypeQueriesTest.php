<?php

declare(strict_types=1);

use App\Domains\PaymentType\DataObjects\PaymentTypeData;
use App\Domains\PaymentType\PaymentTypeQueries;
use App\Models\Company;
use App\Models\PaymentType;
use App\Models\SaleChannel;
use App\Models\SalePayment;

beforeEach(function (): void {
    $this->companyA = Company::factory()->create();

    $this->paymentTypeA = PaymentType::factory()->create([
        'company_id' => $this->companyA->id,
        'name' => 'ABCD',
        'status' => 1,
        'is_member_required' => true,
        'image_name' => 'cash.png',
        'is_available_in_pos' => true,
    ]);
    $this->paymentTypeB = PaymentType::factory()->create([
        'company_id' => $this->companyA->id,
        'name' => 'XYZ',
        'parent_payment_type_id' => $this->paymentTypeA->id,
        'status' => 1,
        'is_member_required' => false,
        'image_name' => 'cash.png',
        'is_available_in_pos' => true,
    ]);

    $this->paymentTypeQueries = new PaymentTypeQueries();
});

test('Payment Types can be searched', function (): void {
    $response = $this->paymentTypeQueries->listQuery([
        'search_text' => 'ABCD',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyA->id);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->paymentTypeA->name);
});

test('New payment type can be added', function (): void {
    $this->paymentTypeQueries->addNew(
        new PaymentTypeData(
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
            'site_key',
            'secret_key'
        ),
        $this->companyA->id
    );

    $this->assertDatabaseHas('payment_types', [
        'company_id' => $this->companyA->id,
        'name' => 'payment_type',
        'is_member_required' => false,
        'is_available_for_refund' => false,
        'status' => true,
        'image_name' => 'cash.png',
        'site_key' => 'site_key',
        'secret_key' => 'secret_key',
    ]);
});

test('A payment type can be fetched', function (): void {
    $response = $this->paymentTypeQueries->getById($this->paymentTypeA->id, $this->companyA->id);
    expect($response->toArray())
        ->toHaveKey('name', $this->paymentTypeA->name)
        ->toHaveKey('is_member_required', $this->paymentTypeA->is_member_required)
        ->toHaveKey('is_available_for_refund', $this->paymentTypeA->is_available_for_refund)
        ->toHaveKey('trigger_card_payment_machine', $this->paymentTypeA->trigger_card_payment_machine)
        ->toHaveKey('trigger_qr_code_payment_machine', $this->paymentTypeA->trigger_qr_code_payment_machine)
        ->toHaveKey('trigger_card_affin_payment_machine', $this->paymentTypeA->trigger_card_affin_payment_machine)
        ->toHaveKey('is_card_payment', $this->paymentTypeA->is_card_payment)
        ->toHaveKey('image_name', $this->paymentTypeA->image_name)
        ->toHaveKey('payment_terminal_key', $this->paymentTypeA->payment_terminal_key)
        ->toHaveKey('trigger_card_bank_rakyat_terminal', $this->paymentTypeA->trigger_card_bank_rakyat_terminal)
        ->toHaveKey('is_available_in_ecommerce', $this->paymentTypeA->is_available_in_ecommerce)
        ->toHaveKey('is_available_in_pos', $this->paymentTypeA->is_available_in_pos)
        ->toHaveKey('site_key', $this->paymentTypeA->site_key)
        ->toHaveKey('secret_key', $this->paymentTypeA->secret_key)
        ->toHaveKey('status', $this->paymentTypeA->status);
});

test('A payment type can be updated', function (): void {
    $this->paymentTypeQueries->update(
        new PaymentTypeData(
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
            false
        ),
        $this->paymentTypeA->id,
        $this->companyA->id
    );

    $this->assertDatabaseHas('payment_types', [
        'company_id' => $this->companyA->id,
        'name' => 'payment_type_1',
        'is_member_required' => false,
        'is_available_for_refund' => false,
        'status' => false,
        'image_name' => 'cash.png',
    ]);
});

test('getActiveOnlyWithSubPaymentTypes method return the list', function (): void {
    $response = $this->paymentTypeQueries->getActiveOnlyWithSubPaymentTypes($this->companyA->id);
    expect($response->first()->toArray())
        ->toHaveKey('name', $this->paymentTypeA->name)
        ->toHaveKey('is_member_required', $this->paymentTypeA->is_member_required)
        ->toHaveKey('is_available_for_refund', $this->paymentTypeA->is_available_for_refund)
        ->toHaveKey('active_sub_payment_types.0.id', $this->paymentTypeB->id)
        ->toHaveKey('active_sub_payment_types.0.name', $this->paymentTypeB->name)
        ->toHaveKey('status', $this->paymentTypeA->status);
});

test('getActiveOnlyAndAvailableInPosWithSubPaymentTypes method return the list', function (): void {
    $response = $this->paymentTypeQueries->getActiveOnlyAndAvailableInPosWithSubPaymentTypes($this->companyA->id);
    expect($response->first()->toArray())
        ->toHaveKey('name', $this->paymentTypeA->name)
        ->toHaveKey('is_member_required', $this->paymentTypeA->is_member_required)
        ->toHaveKey('is_available_for_refund', $this->paymentTypeA->is_available_for_refund)
        ->toHaveKey('active_sub_payment_types.0.id', $this->paymentTypeB->id)
        ->toHaveKey('active_sub_payment_types.0.name', $this->paymentTypeB->name)
        ->toHaveKey('status', $this->paymentTypeA->status);
});

test('Payment types can be fetched', function (): void {
    $response = $this->paymentTypeQueries->getByIds(
        [$this->paymentTypeA->id, $this->paymentTypeB->id],
        $this->companyA->id
    );

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->paymentTypeA->id)
        ->toHaveKey('is_member_required', $this->paymentTypeA->is_member_required)
        ->toHaveKey('status', $this->paymentTypeA->status);
});

test('it can change the status of the payment type', function (): void {
    $this->paymentTypeQueries->setStatus($this->paymentTypeA->id, $this->companyA->id, false);

    $this->assertDatabaseHas('payment_types', [
        'id' => $this->paymentTypeA->id,
        'status' => false,
    ]);
});

test('getAllPaymentTypesForReport method returns all payment types', function (): void {
    $response = $this->paymentTypeQueries->getAllPaymentTypesForReport($this->companyA->id);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->paymentTypeA->id)
        ->toHaveKey('name', $this->paymentTypeA->name);
});

test('getPaymentTypesExport method returns payment type as expected', function (): void {
    $response = $this->paymentTypeQueries->getPaymentTypesExport([
        'search_text' => $this->paymentTypeA->name,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyA->id);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->paymentTypeA->id)
        ->toHaveKey('name', $this->paymentTypeA->name);
});

test('getActivePaymentTypesForBulkUpdate method call and return proper response', function (): void {
    $response = $this->paymentTypeQueries->getActivePaymentTypesForBulkUpdate($this->companyA->id);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->paymentTypeA->id)
        ->toHaveKey('name', $this->paymentTypeA->name)
        ->toHaveKey('is_member_required', $this->paymentTypeA->is_member_required)
        ->toHaveKey('is_available_for_refund', $this->paymentTypeA->is_available_for_refund)
        ->toHaveKey('payment_terminal_key', $this->paymentTypeA->payment_terminal_key);
});

test('A payment type can be updated by name', function (): void {
    $this->paymentTypeQueries->updateByName(
        [
            'company_id' => $this->companyA->id,
            'name' => $this->paymentTypeA->name,
            'is_member_required' => $this->paymentTypeA->is_member_required,
            'is_available_for_refund' => $this->paymentTypeA->is_available_for_refund,
            'payment_terminal_key' => '123456',
        ],
        $this->paymentTypeA->name,
        $this->companyA->id
    );

    $this->assertDatabaseHas('payment_types', [
        'company_id' => $this->companyA->id,
        'payment_terminal_key' => '123456',
    ]);
});

test('paymentTypeExists method returns boolean as expected', function (): void {
    $response = $this->paymentTypeQueries->paymentTypeExists('test', $this->companyA->id);
    $this->assertFalse($response);

    $response = $this->paymentTypeQueries->paymentTypeExists($this->paymentTypeA->name, $this->companyA->id);
    $this->assertTrue($response);
});

test('getPaymentTypeListForReport method returns payment type list as expected', function (): void {
    $filterData = [
        'search_text' => '',
        'location_ids' => '',
        'counter_ids' => '',
        'payment_type_id' => $this->paymentTypeA->id,
        'date' => [],
        'sort_by' => '',
        'sort_direction' => '',
        'per_page' => '',
    ];

    SalePayment::factory()->create([
        'payment_type_id' => $this->paymentTypeA->id,
    ]);

    $response = $this->paymentTypeQueries->getPaymentTypeListForReport($filterData, $this->companyA->id);
    expect($response->first()->toArray())
        ->toHaveKey('id', $this->paymentTypeA->id)
        ->toHaveKey('name', $this->paymentTypeA->name);
});

test('getPaymentTypeTransactionList method returns payment type list as expected', function (): void {
    $filterData = [
        'id' => $this->paymentTypeA->id,
        'location_ids' => '',
        'counter_ids' => '',
        'date' => [],
    ];

    $salePayment = SalePayment::factory()->create([
        'payment_type_id' => $this->paymentTypeA->id,
    ]);

    $response = $this->paymentTypeQueries->getPaymentTypeTransactionList($filterData);
    expect($response->first())
        ->toHaveKey('amount', $salePayment->amount);
});

test('getPaymentTypeListExport method returns payment type list for export as expected', function (): void {
    $filterData = [
        'search_text' => '',
        'location_ids' => '',
        'counter_ids' => '',
        'payment_type_id' => $this->paymentTypeA->id,
        'date' => [],
        'sort_by' => '',
        'sort_direction' => '',
        'per_page' => '',
    ];

    SalePayment::factory()->create([
        'payment_type_id' => $this->paymentTypeA->id,
    ]);

    $response = $this->paymentTypeQueries->getPaymentTypeListExport($filterData, $this->companyA->id);
    expect($response->first()->toArray())
        ->toHaveKey('id', $this->paymentTypeA->id)
        ->toHaveKey('name', $this->paymentTypeA->name);
});

test('validatePaymentTypeSaleChannelMatch returns true when payment type and sale channel match', function (): void {
    $saleChannel = SaleChannel::factory()->create();
    $this->paymentTypeA->saleChannels()->attach($saleChannel->id);

    $result = $this->paymentTypeQueries->validatePaymentTypeSaleChannelMatch($this->paymentTypeA, $saleChannel);

    expect($result)->toBeTrue();
});
