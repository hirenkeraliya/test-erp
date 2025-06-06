<?php

declare(strict_types=1);

namespace App\Domains\User\Exports;

use App\Domains\User\Enums\UserTypes;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class UserExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        protected Collection $users
    ) {
    }

    public function collection(): Collection
    {
        return $this->users->map(function (User $user): array {
            /** @var Employee $employee */
            $employee = $user->employee;

            return [
                'username' => $user->username,
                'employee' => $employee->first_name,
                'type' => UserTypes::getFormattedCaseName($user->type_id),
            ];
        });
    }

    public function headings(): array
    {
        return ['username', 'employee', 'type'];
    }
}
