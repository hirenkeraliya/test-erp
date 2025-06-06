<?php

declare(strict_types=1);

use App\Domains\MembershipAssignment\MembershipAssignmentQueries;
use App\Models\Company;
use App\Models\Member;
use App\Models\Membership;
use App\Models\MembershipAssignment;
use App\Models\Sale;

beforeEach(function (): void {
    $this->companyId = Company::factory()->create()->id;

    $this->membershipAssignmentQueries = new MembershipAssignmentQueries();
});

test('membershipAssignment can be added', function (): void {
    $sale = Sale::factory()->create([
        'happened_at' => '2022-01-04 04:20:50',
    ]);

    $membership = Membership::factory()->create([
        'company_id' => $this->companyId,
    ]);
    $this->membershipAssignmentQueries->addNew($membership->id, $sale->member_id, $sale->happened_at);

    $this->assertDatabaseHas('membership_assignments', [
        'membership_id' => $membership->id,
        'member_id' => $sale->member_id,
        'happened_at' => $sale->happened_at,
    ]);
});

test(
    'the updateMember method update the membership assignment queries member id to new member id',
    function (): void {
        $member = Member::factory()->create();

        $MembershipAssignment = MembershipAssignment::factory()->create();

        $this->assertDatabaseHas(MembershipAssignment::class, [
            'id' => $MembershipAssignment->getKey(),
            'member_id' => $MembershipAssignment->member_id,
        ]);

        $this->membershipAssignmentQueries->updateMember($MembershipAssignment->member_id, $member->getKey());

        $this->assertDatabaseHas(MembershipAssignment::class, [
            'id' => $MembershipAssignment->getKey(),
            'member_id' => $member->getKey(),
        ]);
    }
);
