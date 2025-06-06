<?php

declare(strict_types=1);

use App\Domains\GenuineReceiptVerification\GenuineReceiptVerificationQueries;
use App\Models\GenuineReceiptVerification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

beforeEach(function (): void {
    $this->genuineReceiptVerificationQueries = new GenuineReceiptVerificationQueries();
});

test('A getById method call returns a GenuineReceiptVerification instance.', function (): void {
    $genuineReceiptVerification = GenuineReceiptVerification::factory()->create();

    $result = $this->genuineReceiptVerificationQueries->getById($genuineReceiptVerification->id);

    expect($result)->toBeInstanceOf(GenuineReceiptVerification::class);
});

test('A addNew method call returns an integer ID.', function (): void {
    $data = [
        'name' => 'John Doe',
        'mobile_number' => '1234567890',
        'receipt_number' => '123456',
        'sale_id' => 1,
        'is_genuine' => 1,
        'remarks' => '',
    ];
    $genuineReceiptVerificationData = GenuineReceiptVerification::factory()->create([
        'id' => 1,
        'name' => 'John Doe',
        'mobile_number' => '1234567890',
        'receipt_number' => '123456',
        'sale_id' => 1,
        'is_genuine' => true,
        'remarks' => '',
    ]);

    $array = $genuineReceiptVerificationData->toArray();
    unset($array['id']);

    $genuineReceiptVerification = $this->genuineReceiptVerificationQueries->addNew($array);

    expect($genuineReceiptVerification)->toHaveKey('id');
});

test('A update method call updates the GenuineReceiptVerification record.', function (): void {
    $genuineReceiptVerification = GenuineReceiptVerification::factory()->create();
    $data = [
        'name' => 'John Doe',
        'mobile_number' => '1234567890',
        'receipt_number' => '123456',
        'sale_id' => 1,
        'is_genuine' => 1,
        'remarks' => 'This is a genuine receipt.',
    ];

    $this->genuineReceiptVerificationQueries->update($genuineReceiptVerification, $data);

    $updatedRecord = $this->genuineReceiptVerificationQueries->getById($genuineReceiptVerification->id);

    expect($updatedRecord->remarks)->toBe('This is a genuine receipt.');
});

test('A getPaginatedReceiptVerificationReport method call returns a LengthAwarePaginator instance.', function (): void {
    $filterData = [
        'search_text' => '',
        'location_ids' => [],
        'date_range' => [],
        'sort_by' => null,
        'is_genuine' => null,
        'sort_direction' => 'asc',
        'per_page' => 15,
    ];

    $companyId = 1;
    $result = $this->genuineReceiptVerificationQueries->getPaginatedReceiptVerificationReport($filterData, $companyId);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
});

test('A getReceiptVerificationReportDataForExport method call returns a Collection instance.', function (): void {
    $filterData = [
        'search_text' => '',
        'is_genuine' => null,
        'location_ids' => [],
        'date_range' => [],
        'sort_by' => null,
        'sort_direction' => 'asc',
    ];

    $companyId = 1;
    $result = $this->genuineReceiptVerificationQueries->getReceiptVerificationReportDataForExport(
        $filterData,
        $companyId
    );

    expect($result)->toBeInstanceOf(Collection::class);
});
