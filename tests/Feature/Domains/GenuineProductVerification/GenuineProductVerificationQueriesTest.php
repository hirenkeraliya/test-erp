<?php

declare(strict_types=1);

use App\Domains\GenuineProductVerification\GenuineProductVerificationQueries;
use App\Models\GenuineProductVerification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

beforeEach(function (): void {
    $this->genuineProductVerificationQueries = new GenuineProductVerificationQueries();
});

test('A getById method call returns a GenuineProductVerification instance.', function (): void {
    $genuineProductVerification = GenuineProductVerification::factory()->create();

    $result = $this->genuineProductVerificationQueries->getById($genuineProductVerification->id);

    expect($result)->toBeInstanceOf(GenuineProductVerification::class);
});

test('A addNew method call returns an integer ID.', function (): void {
    $data = [
        'name' => 'John Doe',
        'mobile_number' => '1234567890',
        'qr_code' => '123456',
        'product_id' => 1,
        'receipt_number' => '123456',
        'sale_id' => 1,
        'is_genuine' => 1,
        'remarks' => '',
    ];
    $genuineProductVerificationData = GenuineProductVerification::factory()->create([
        'id' => 1,
        'name' => 'John Doe',
        'mobile_number' => '1234567890',
        'qr_code' => '123456',
        'product_id' => 1,
        'receipt_number' => '123456',
        'sale_id' => 1,
        'is_genuine' => true,
        'remarks' => '',
    ]);

    $array = $genuineProductVerificationData->toArray();
    unset($array['id']);

    $genuineProductVerification = $this->genuineProductVerificationQueries->addNew($array);

    expect($genuineProductVerification)->toHaveKey('id');
});

test('A update method call updates the GenuineProductVerification record.', function (): void {
    $genuineProductVerification = GenuineProductVerification::factory()->create();
    $data = [
        'name' => 'John Doe',
        'mobile_number' => '1234567890',
        'qr_code' => '123456',
        'product_id' => 1,
        'receipt_number' => '123456',
        'sale_id' => 1,
        'is_genuine' => 1,
        'remarks' => 'This is a genuine product.',
    ];

    $this->genuineProductVerificationQueries->update($genuineProductVerification, $data);

    $updatedRecord = $this->genuineProductVerificationQueries->getById($genuineProductVerification->id);

    expect($updatedRecord->remarks)->toBe('This is a genuine product.');
});

test('A getPaginatedProductVerificationReport method call returns a LengthAwarePaginator instance.', function (): void {
    $filterData = [
        'search_text' => '',
        'product_ids' => [],
        'location_ids' => [],
        'date_range' => [],
        'sort_by' => null,
        'is_genuine' => null,
        'sort_direction' => 'asc',
        'per_page' => 15,
    ];

    $companyId = 1;
    $result = $this->genuineProductVerificationQueries->getPaginatedProductVerificationReport($filterData, $companyId);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
});

test('A getProductVerificationReportDataForExport method call returns a Collection instance.', function (): void {
    $filterData = [
        'search_text' => '',
        'product_ids' => [],
        'is_genuine' => null,
        'location_ids' => [],
        'date_range' => [],
        'sort_by' => null,
        'sort_direction' => 'asc',
    ];

    $companyId = 1;
    $result = $this->genuineProductVerificationQueries->getProductVerificationReportDataForExport(
        $filterData,
        $companyId
    );

    expect($result)->toBeInstanceOf(Collection::class);
});
