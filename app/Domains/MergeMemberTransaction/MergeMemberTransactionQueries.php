<?php

declare(strict_types=1);

namespace App\Domains\MergeMemberTransaction;

use App\Domains\Common\Enums\ModelMapping;
use App\Models\MergeMemberTransaction;
use Illuminate\Foundation\Auth\User;

class MergeMemberTransactionQueries
{
    public function addNew(User $user, int $oldMemberId, int $newMemberId): MergeMemberTransaction
    {
        return MergeMemberTransaction::create([
            'user_id' => $user->id,
            'user_type' => ModelMapping::getCaseName($user::class),
            'old_member_id' => $oldMemberId,
            'new_member_id' => $newMemberId,
        ]);
    }

    public function getBasicColumnsName(): string
    {
        return 'id,user_id,user_type,old_member_id,new_member_id';
    }
}
