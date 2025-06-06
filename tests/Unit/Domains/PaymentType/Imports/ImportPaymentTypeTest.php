<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\ImportRecord\Enums\ImportTypes;
use App\Domains\PaymentType\Enums\PaymentTypeImages;
use App\Domains\PaymentType\Imports\ImportPaymentType;
use App\Domains\PaymentType\PaymentTypeQueries;
use App\Models\ImportRecord;

test('the validate method returns blank array when no error in given details', function (): void {
    $companyId = 1;

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

    $paymentTypeData = getPaymentTypeData();
    $importPaymentType = new ImportPaymentType();
    $redirectResponse = $importPaymentType->validate($paymentTypeData, $importRecord);
    $this->assertEquals([], $redirectResponse);
});

test(
    'name, is_member_required, is_available_for_refund and image_name are required for import record',
    function (): void {
        $companyId = 1;

        $paymentTypeData = [
            'name' => '',
            'is_member_required' => '',
            'is_available_for_refund' => '',
            'image_name' => '',
        ];

        $importRecord = ImportRecord::factory()->make([
            'company_id' => $companyId,
            'created_by_id' => 1,
        ]);

        $importPaymentType = new ImportPaymentType();
        $redirectResponse = $importPaymentType->validate($paymentTypeData, $importRecord);
        $this->assertEquals(3, is_countable($redirectResponse) ? count($redirectResponse) : 0);
    }
);

test('It calls addNew method to store payment type details', function (): void {
    $companyId = 1;

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'type_id' => ImportTypes::PAYMENT_TYPES->value,
        'created_by_id' => 1,
        'created_by_type' => ModelMapping::ADMIN->name,
    ]);

    $paymentTypeRecord = [
        'name' => 'payment-2',
        'is_member_required' => 'No',
        'is_available_for_refund' => 'No',
        'image_name' => PaymentTypeImages::E_WALLET->value,
        'payment_terminal_key' => '',
        'is_card_payment' => 'No',
    ];

    $this->mock(PaymentTypeQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $importPaymentType = new ImportPaymentType();
    $importPaymentType->save($paymentTypeRecord, $importRecord);
    $this->assertTrue(true);
});

function getPaymentTypeData(): array
{
    return [
        'name' => 'payment-1',
        'is_member_required' => 'Yes',
        'is_available_for_refund' => 'No',
        'image_name' => PaymentTypeImages::E_WALLET->value,
        'payment_terminal_key' => '1234',
    ];
}
