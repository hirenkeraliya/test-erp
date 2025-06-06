<?php

declare(strict_types=1);

use App\Domains\MemberAddressChannelReference\MemberAddressChannelReferenceQueries;
use App\Models\MemberAddress;
use App\Models\MemberAddressChannelReference;
use App\Models\SaleChannel;

beforeEach(function (): void {
    $this->memberAddressChannelReferenceQueries = new MemberAddressChannelReferenceQueries();
});

test('a member address channel reference can be added', function (): void {
    $memberAddress = MemberAddress::factory()->create()->id;
    $saleChannelId = SaleChannel::factory()->create()->id;

    $memberAddressChannelReferenceRecord = MemberAddressChannelReference::factory()->make([
        'member_address_id' => $memberAddress,
        'sale_channel_id' => $saleChannelId,
        'external_member_address_id' => $memberAddress,
    ]);

    $this->memberAddressChannelReferenceQueries->addNew($memberAddressChannelReferenceRecord->toArray());

    $this->assertDatabaseHas(MemberAddressChannelReference::class, $memberAddressChannelReferenceRecord->toArray());
});

test('it calls the getByMemberAddressIdAndSaleChannelId to get the external Member Address', function (): void {
    $memberAddressId = MemberAddress::factory()->create()->getKey();
    $saleChannelId = SaleChannel::factory()->create()->getKey();

    $memberAddressChannelReference = MemberAddressChannelReference::factory()->create([
        'sale_channel_id' => $saleChannelId,
        'member_address_id' => $memberAddressId,
        'external_member_address_id' => 1,
    ]);

    $response = $this->memberAddressChannelReferenceQueries->getByMemberAddressIdAndSaleChannelId(
        $memberAddressId,
        $saleChannelId
    );

    expect($response)
        ->toHaveKey('id', $memberAddressChannelReference->getKey())
        ->toHaveKey('member_address_id', $memberAddressId)
        ->toHaveKey('external_member_address_id', 1);
});
