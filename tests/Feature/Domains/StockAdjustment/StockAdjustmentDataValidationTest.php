<?php

declare(strict_types=1);

use App\Domains\StockAdjustment\DataObjects\StockAdjustmentData;
use App\Domains\StockAdjustment\Enums\StockAdjustmentTypes;
use App\Models\Company;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->companyId = Company::factory()->create()->id;

    setCompanyIdInSession($this->companyId);
});

test(
    'user cannot select different company employee while adding.',
    function (): void {
        $employee = Employee::factory()->create();

        $request = new Request([
            'type_id' => StockAdjustmentTypes::STI->value,
            'approved_by_employee_id' => $employee->id,
            'reason' => 'test',
            'uploaded_file' => new UploadedFile(
                public_path('files/stock-adjustments-sample-file-stock-in.xlsx'),
                'example.xlsx',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                null,
                true
            ),
        ]);

        StockAdjustmentData::validate($request);
    }
)->throws(ValidationException::class);

test('validation passes when all stock adjustments details are provided', function (): void {
    $employee = Employee::factory()->create([
        'company_id' => $this->companyId,
    ]);

    $request = new Request([
        'type_id' => StockAdjustmentTypes::STI->value,
        'approved_by_employee_id' => $employee->id,
        'reason' => 'test',
        'uploaded_file' => new UploadedFile(
            public_path('files/stock-adjustments-sample-file-stock-in.xlsx'),
            'example.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        ),
    ]);

    StockAdjustmentData::validate($request);

    $this->assertTrue(true);
});
