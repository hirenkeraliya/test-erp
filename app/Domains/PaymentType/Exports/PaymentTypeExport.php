<?php

declare(strict_types=1);

namespace App\Domains\PaymentType\Exports;

use App\Models\PaymentType;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PaymentTypeExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected Collection $paymentTypes
    ) {
    }

    public function collection(): Collection
    {
        return $this->paymentTypes->map(fn (PaymentType $paymentType): array => [
            'name' => $paymentType->name,
            'image_name' => $paymentType->image_name,
            'status' => $paymentType->status ? 'Active' : 'Inactive',
        ]);
    }

    public function headings(): array
    {
        return ['Name', 'Image Name', 'Status'];
    }
}
