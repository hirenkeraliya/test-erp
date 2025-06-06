<?php

declare(strict_types=1);

namespace App\Domains\EmployeeTransaction;

use App\Domains\Common\Enums\ModelMapping;
use App\Models\EmployeeTransaction;
use Illuminate\Foundation\Auth\User;

class EmployeeTransactionQueries
{
    public function addNew(int $employeeId, bool $status, User $user): void
    {
        EmployeeTransaction::create([
            'employee_id' => $employeeId,
            'status' => $status,
            'user_id' => $user->id,
            'user_type' => ModelMapping::getCaseName($user::class),
        ]);
    }
}
