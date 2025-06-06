<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\ImportRecord\Enums\ColumnValidationIssueTypes;
use App\Domains\ImportRecord\Enums\ImportTypes;
use App\Domains\PaymentType\Enums\PaymentTypeImportColumns;
use App\Domains\PaymentType\Imports\ImportPaymentTypeBulkUpdate;
use App\Domains\PaymentType\PaymentTypeQueries;
use App\Models\ImportRecord;

test('the validate method returns blank array when no error in given details', function (): void {
    $companyId = 1;
    $getPaymentTypeBulkUpdateData = getPaymentTypeBulkUpdateData();

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

    $this->mock(PaymentTypeQueries::class, function ($mock) use ($getPaymentTypeBulkUpdateData, $companyId): void {
        $mock->shouldReceive('paymentTypeExists')
            ->once()
            ->with($getPaymentTypeBulkUpdateData['name'], $companyId)
            ->andReturn(true);
    });

    $importPaymentTypeBulkUpdate = new ImportPaymentTypeBulkUpdate();
    $redirectResponse = $importPaymentTypeBulkUpdate->validate($getPaymentTypeBulkUpdateData, $importRecord);
    $this->assertEquals([], $redirectResponse);
});

test(
    'name, is_member_required and is_available_for_refund are required for import record',
    function (): void {
        $companyId = 1;

        $importRecord = ImportRecord::factory()->make([
            'company_id' => $companyId,
            'created_by_id' => 1,
        ]);

        $getPaymentTypeBulkUpdateData = [
            'name' => '',
            'is_member_required' => '',
            'is_available_for_refund' => '',
        ];

        $this->mock(PaymentTypeQueries::class, function ($mock) use ($getPaymentTypeBulkUpdateData, $companyId): void {
            $mock->shouldReceive('paymentTypeExists')
                ->once()
                ->with($getPaymentTypeBulkUpdateData['name'], $companyId)
                ->andReturn(false);
        });

        $importPaymentTypeBulkUpdate = new ImportPaymentTypeBulkUpdate();
        $redirectResponse = $importPaymentTypeBulkUpdate->validate($getPaymentTypeBulkUpdateData, $importRecord);
        $this->assertEquals(4, is_countable($redirectResponse) ? count($redirectResponse) : 0);
    }
);

test('save method works for the payment type details update', function (): void {
    $companyId = 1;

    $getPaymentTypeBulkUpdateData = getPaymentTypeBulkUpdateData();

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'type_id' => ImportTypes::PAYMENT_TYPE_BULK_UPDATE->value,
        'created_by_id' => 1,
        'created_by_type' => ModelMapping::ADMIN->name,
    ]);

    $this->mock(PaymentTypeQueries::class, function ($mock): void {
        $mock->shouldReceive('updateByName')
            ->times(1);
    });

    $importPaymentTypeBulkUpdate = new ImportPaymentTypeBulkUpdate();
    $importPaymentTypeBulkUpdate->save($getPaymentTypeBulkUpdateData, $importRecord);
    $this->assertTrue(true);
});

test('validate import Payment Type Bulk Update Import Columns', function (): void {
    $requiredHeaderColumns = PaymentTypeImportColumns::getArrayValues();

    $importPaymentTypeBulkUpdate = new ImportPaymentTypeBulkUpdate();
    $response = $importPaymentTypeBulkUpdate->validateColumns($requiredHeaderColumns, [], 1);
    $this->assertTrue(ColumnValidationIssueTypes::COLUMN_ISSUE->value === $response['type']);
    $this->assertTrue($response['status']);
});

function getPaymentTypeBulkUpdateData(): array
{
    return [
        'name' => 'payment-1',
        'is_member_required' => 'Yes',
        'is_available_for_refund' => 'No',
        'payment_terminal_key' => '1234',
    ];
}
