<?php

declare(strict_types=1);

namespace App\Domains\SubPaymentType\Exports;

use App\Models\PaymentType;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SubPaymentTypeExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected Collection $subPaymentTypes
    ) {
    }

    public function collection(): Collection
    {
        return $this->subPaymentTypes->map(fn (PaymentType $subPaymentType): array => [
            'name' => $subPaymentType->name,
            'image_name' => $subPaymentType->image_name,
            'status' => $subPaymentType->status ? 'Active' : 'Inactive',
        ]);
    }

    public function headings(): array
    {
        return ['Name', 'Image Name', 'Status'];
    }
}
