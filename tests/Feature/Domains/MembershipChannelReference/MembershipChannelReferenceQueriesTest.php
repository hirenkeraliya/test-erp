<?php

declare(strict_types=1);

use App\Domains\MembershipChannelReference\MembershipChannelReferenceQueries;
use App\Models\Membership;
use App\Models\MembershipChannelReference;
use App\Models\SaleChannel;

beforeEach(function (): void {
    $this->membershipChannelReferenceQueries = new MembershipChannelReferenceQueries();
});

test('a membership channel reference can be added', function (): void {
    $membership = Membership::factory()->create()->id;
    $saleChannelId = SaleChannel::factory()->create()->id;

    $membershipChannelReferenceRecord = MembershipChannelReference::factory()->make([
        'membership_id' => $membership,
        'sale_channel_id' => $saleChannelId,
        'external_membership_id' => $membership,
    ]);

    $this->membershipChannelReferenceQueries->addNew($membershipChannelReferenceRecord->toArray());

    $this->assertDatabaseHas(MembershipChannelReference::class, $membershipChannelReferenceRecord->toArray());
});

test('it calls the getByMembershipIdAndSaleChannelId to get the external Membership', function (): void {
    $membershipId = Membership::factory()->create()->getKey();
    $saleChannelId = SaleChannel::factory()->create()->getKey();

    $membershipChannelReference = MembershipChannelReference::factory()->create([
        'sale_channel_id' => $saleChannelId,
        'membership_id' => $membershipId,
        'external_membership_id' => 1,
    ]);

    $response = $this->membershipChannelReferenceQueries->getBymembershipIdAndSaleChannelId(
        $membershipId,
        $saleChannelId
    );

    expect($response)
        ->toHaveKey('id', $membershipChannelReference->getKey())
        ->toHaveKey('membership_id', $membershipId)
        ->toHaveKey('external_membership_id', 1);
});
