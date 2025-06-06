<?php

declare(strict_types=1);

namespace App\Domains\StockAdjustment\DataObjects;

use App\Domains\Employee\EmployeeQueries;
use App\Domains\StockAdjustment\Enums\StockAdjustmentTypes;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;

class StockAdjustmentData extends Data
{
    public function __construct(
        public int $approved_by_employee_id,
        public int $type_id,
        public string $reason,
        public UploadedFile $uploaded_file,
        public ?string $adjustment_date,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        $employeeQueries = resolve(EmployeeQueries::class);

        return [
            'type_id' => ['required', 'integer', 'in:' . StockAdjustmentTypes::getValues()],
            'approved_by_employee_id' => [
                'required',
                'integer',
                Rule::exists('employees', 'id')->where($employeeQueries->filterByCompany(session('admin_company_id'))),
            ],
            'adjustment_date' => ['nullable', 'date', 'date_format:Y-m-d'],
            'reason' => ['required', 'string'],
            'uploaded_file' => [
                'required',
                'file',
                'mimes:xlsx, ods, xls',
                'max:' . config('services.max_upload_size'),
            ],
        ];
    }
}
