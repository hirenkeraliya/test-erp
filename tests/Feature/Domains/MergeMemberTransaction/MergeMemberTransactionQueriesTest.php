<?php

declare(strict_types=1);

use App\Domains\MergeMemberTransaction\MergeMemberTransactionQueries;
use App\Models\Admin;
use App\Models\Member;
use App\Models\MergeMemberTransaction;

beforeEach(function (): void {
    $this->mergeMemberTransaction = new MergeMemberTransactionQueries();
});

test('can add merge member transaction can be added', function (): void {
    $user = Admin::factory()->create();
    $memberAId = Member::factory()->create()->id;
    $memberBId = Member::factory()->create()->id;

    $this->mergeMemberTransaction->addNew($user, $memberBId, $memberAId);

    $this->assertDatabaseHas(MergeMemberTransaction::class, [
        'user_id' => $user->id,
        'old_member_id' => $memberBId,
        'new_member_id' => $memberAId,
    ]);
});
