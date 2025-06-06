<?php

declare(strict_types=1);

namespace App\Domains\Sale\Enums;

use App\Http\Traits\PrepareEnumDataMethods;
use App\Models\Employee;
use App\Models\Member;
use Illuminate\Support\Str;

enum UserTypes: string
{
    use PrepareEnumDataMethods;

    case MEMBER = 'Member';
    case EMPLOYEE = 'Employee';

    public static function getUserClass(string $userType): string
    {
        if ($userType === self::MEMBER->value) {
            return Member::class;
        }

        return Employee::class;
    }

    public static function getUserType(string $userType): string
    {
        if (Member::class === Str::title('App\\Models\\' . $userType)) {
            return self::MEMBER->value;
        }

        return self::EMPLOYEE->value;
    }
}
