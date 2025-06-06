<?php

declare(strict_types=1);

namespace App\Domains\LoyaltyPoint\Interfaces;

use App\Models\Employee;
use App\Models\Member;
use Illuminate\Database\Eloquent\Model;

interface LoyaltyPointsInterface
{
    public function decreaseLoyaltyPoints(int $userId, int $loyaltyPoints): void;

    public function getByIdWithMembershipAndLoyaltyPoints(int $companyId, int $userId): Member|Employee;

    public function increaseLoyaltyPoints(Model $user, int $loyaltyPoints): void;
}
