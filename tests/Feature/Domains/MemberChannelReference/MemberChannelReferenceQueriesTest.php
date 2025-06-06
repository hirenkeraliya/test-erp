<?php

declare(strict_types=1);

use App\Domains\MemberChannelReference\MemberChannelReferenceQueries;
use App\Models\Company;
use App\Models\Member;
use App\Models\MemberChannelReference;
use App\Models\SaleChannel;

use function Pest\Laravel\assertDatabaseMissing;

beforeEach(function (): void {
    $this->companyId = Company::factory()->create()->id;

    $this->memberChannelReference = MemberChannelReference::factory()->create([
        'sale_channel_id' => SaleChannel::factory()->create([
            'company_id' => $this->companyId,
        ]),
        'member_id' => Member::factory()->create([
            'company_id' => $this->companyId,
        ]),
        'external_member_id' => Member::factory()->create([
            'company_id' => $this->companyId,
        ]),
    ]);

    $this->memberChannelReferenceQueries = new MemberChannelReferenceQueries();
});

test('get records by memberId', function (): void {
    $response = $this->memberChannelReferenceQueries->getRecordsByMemberId($this->memberChannelReference->member_id);

    expect($response->first()->toArray())
        ->toHaveKeys(['id', 'sale_channel_id', 'member_id', 'external_member_id']);
});

test('delete record by memberId', function (): void {
    $memberChannelReference = MemberChannelReference::factory()->create([
        'member_id' => Member::factory()->create([
            'company_id' => $this->companyId,
        ]),
    ]);

    $this->memberChannelReferenceQueries->deleteOldMemberForMerge($memberChannelReference->member_id);

    assertDatabaseMissing('member_channel_references', $memberChannelReference->toArray());
});

test('a member channel reference can be added', function (): void {
    $member = Member::factory()->create()->id;
    $saleChannelId = SaleChannel::factory()->create()->id;

    $memberChannelReferenceRecord = MemberChannelReference::factory()->make([
        'member_id' => $member,
        'sale_channel_id' => $saleChannelId,
        'external_member_id' => $member,
    ]);

    $this->memberChannelReferenceQueries->addNew($memberChannelReferenceRecord->toArray());

    $this->assertDatabaseHas(MemberChannelReference::class, $memberChannelReferenceRecord->toArray());
});

test('it calls the getByMemberIdAndSaleChannelId to get the external Member', function (): void {
    $memberId = Member::factory()->create()->getKey();
    $saleChannelId = SaleChannel::factory()->create()->getKey();

    $memberChannelReference = MemberChannelReference::factory()->create([
        'sale_channel_id' => $saleChannelId,
        'member_id' => $memberId,
        'external_member_id' => 1,
    ]);

    $response = $this->memberChannelReferenceQueries->getByMemberIdAndSaleChannelId($memberId, $saleChannelId);

    expect($response)
        ->toHaveKey('id', $memberChannelReference->getKey())
        ->toHaveKey('member_id', $memberId)
        ->toHaveKey('external_member_id', 1);
});
