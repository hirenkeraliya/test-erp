<?php

declare(strict_types=1);

namespace App\Domains\Department\Exports;

use App\Models\Department;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DepartmentExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected Collection $departments
    ) {
    }

    public function collection(): Collection
    {
        return $this->departments->map(fn (Department $department): array => [
            'name' => $department->name,
            'code' => $department->code,
        ]);
    }

    public function headings(): array
    {
        return ['Name', 'Code'];
    }
}
