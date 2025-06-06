<?php

declare(strict_types=1);

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Member\DataObjects\UpdateMemberAddressData;
use App\Domains\MemberAddress\DataObjects\AppMemberAddressData;
use App\Domains\MemberAddress\DataObjects\MemberAddressData;
use App\Domains\MemberAddress\MemberAddressQueries;
use App\Models\Company;
use App\Models\Location;
use App\Models\Member;
use App\Models\MemberAddress;

beforeEach(function (): void {
    $this->memberAddressQueries = new MemberAddressQueries();

    $this->companyId = Company::factory()->create()->id;

    $this->location = Location::factory()->create([
        'company_id' => $this->companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->member = Member::factory()->create([
        'company_id' => $this->companyId,
        'first_name' => 'member_one',
        'created_location_id' => $this->location->id,
    ]);

    $this->memberAddress = MemberAddress::factory()->create([
        'member_id' => $this->member->id,
        'name' => 'abcde',
        'address_line_1' => 'abcdeeee',
        'is_primary' => true,
    ]);
});

test('a member address can be added', function (): void {
    $memberId = Member::factory()->create()->id;

    $memberAddressRecord = MemberAddress::factory()->make([
        'member_id' => $memberId,
    ]);

    $this->memberAddressQueries->addNew($memberAddressRecord->toArray());

    $this->assertDatabaseHas('member_addresses', [
        'member_id' => $memberAddressRecord->member_id,
        'name' => $memberAddressRecord->name,
    ]);
});

test(
    'getMemberAddressDetails method returns the member address details as expected',
    function (): void {
        $response = $this->memberAddressQueries->getMemberAddressDetails($this->member->id);

        expect($response->first()->toArray())
            ->toHaveKey('id', $this->memberAddress->id)
            ->toHaveKey('name', $this->memberAddress->name)
            ->toHaveKey('address_line_1', $this->memberAddress->address_line_1);
    }
);

test(
    'getById method returns member address data.',
    function (): void {
        $response = $this->memberAddressQueries->getById($this->memberAddress->id, $this->member->id);
        expect($response->toArray())
            ->toHaveKey('id', $this->memberAddress->id);
    }
);

test('It updateMemberAddress method call and update member address', function (): void {
    $memberAddressRecord = MemberAddress::factory(2)->make([
        'member_id' => $this->member->id,
        'name' => 'abcde',
        'contact_mobile_number' => '789567894',
        'contact_email' => 'test@gmail.com',
        'address_line_1' => 'address line1',
        'address_line_2' => 'address line2',
        'city' => 'rajkot',
        'area_code' => '45678903',
        'is_primary' => true,
    ])->toArray();

    unset($memberAddressRecord['member_id']);
    $updateMemberAddressData = new UpdateMemberAddressData($memberAddressRecord);

    $this->memberAddressQueries->updateMemberAddress($updateMemberAddressData, $this->member->id);

    $this->assertDatabaseHas(MemberAddress::class, [
        'member_id' => $this->member->id,
        'name' => current($memberAddressRecord)['name'],
        'address_line_1' => current($memberAddressRecord)['address_line_1'],
    ]);
});

test('isPrimary method returns member address data.', function (): void {
    $response = $this->memberAddressQueries->isPrimary($this->member->id);
    expect($response->toArray())
        ->toHaveKey('id', $this->memberAddress->id);
});

test('updatePrimaryKey method returns update primary key.', function (): void {
    $memberId = Member::factory()->create()->id;

    $memberAddressRecord = MemberAddress::factory()->create([
        'member_id' => $memberId,
        'is_primary' => true,
    ]);

    $this->memberAddressQueries->updatePrimaryKey($memberAddressRecord->id, $memberId);

    $this->assertDatabaseHas('member_addresses', [
        'member_id' => $memberAddressRecord->member_id,
        'is_primary' => false,
    ]);
});

test('call delete method delete the member address', function (): void {
    $this->memberAddressQueries->delete($this->memberAddress->id);
    $this->assertSoftDeleted($this->memberAddress);
});

test('addAddress method add member address', function (): void {
    $memberId = Member::factory()->create()->id;

    $memberAddressRecord = MemberAddress::factory()->make([
        'id' => 1,
        'member_id' => $memberId,
        'is_primary' => true,
    ]);

    $this->memberAddressQueries->addAddress(new MemberAddressData(
        member_id: $memberId,
        name: 'Primary',
        contact_mobile_number: $memberAddressRecord->contact_mobile_number,
        contact_email: null,
        address_line_1: 'test',
        address_line_2: 'test',
        city: 'test',
        area_code: 'test',
        is_primary: true
    ));

    $this->assertDatabaseHas('member_addresses', [
        'member_id' => $memberId,
        'name' => 'primary',
    ]);
});

test('update method update member address', function (): void {
    $memberId = Member::factory()->create()->id;

    $memberAddressRecord = MemberAddress::factory()->create([
        'member_id' => $memberId,
        'is_primary' => true,
    ]);

    $this->memberAddressQueries->update(new MemberAddressData(
        member_id: $memberId,
        name: 'Primary',
        contact_mobile_number: $memberAddressRecord->contact_mobile_number,
        contact_email: null,
        address_line_1: 'test1',
        address_line_2: 'test1',
        city: 'test1',
        area_code: 'test1',
        is_primary: true
    ), $memberAddressRecord);

    $this->assertDatabaseHas('member_addresses', [
        'member_id' => $memberId,
    ]);
});

test('addAddressForMemberApp method add member address', function (): void {
    $memberId = Member::factory()->create()->id;

    $memberAddressRecord = MemberAddress::factory()->make([
        'id' => 1,
        'member_id' => $memberId,
        'is_primary' => true,
    ]);

    $this->memberAddressQueries->addAddressForMemberApp(new AppMemberAddressData(
        name: 'Primary',
        contact_mobile_number: $memberAddressRecord->contact_mobile_number,
        contact_email: null,
        address_line_1: 'test',
        address_line_2: 'test',
        city: 'test',
        area_code: 'test',
        is_primary: true
    ), $memberId);

    $this->assertDatabaseHas('member_addresses', [
        'member_id' => $memberId,
        'name' => 'primary',
    ]);
});

test('updateForMemberApp method update member address', function (): void {
    $memberId = Member::factory()->create()->id;

    $memberAddressRecord = MemberAddress::factory()->create([
        'member_id' => $memberId,
        'is_primary' => true,
    ]);

    $this->memberAddressQueries->updateForMemberApp(new AppMemberAddressData(
        name: 'Primary',
        contact_mobile_number: $memberAddressRecord->contact_mobile_number,
        contact_email: null,
        address_line_1: 'test1',
        address_line_2: 'test1',
        city: 'test1',
        area_code: 'test1',
        is_primary: true
    ), $memberAddressRecord, $memberId);

    $this->assertDatabaseHas('member_addresses', [
        'member_id' => $memberId,
    ]);
});

test(
    'the deleteOldMember method delete the member address queries member id',
    function (): void {
        $memberAddress = MemberAddress::factory()->create();

        $this->assertDatabaseHas(MemberAddress::class, [
            'id' => $memberAddress->getKey(),
            'member_id' => $memberAddress->member_id,
            'deleted_at' => null,
        ]);

        $this->memberAddressQueries->deleteOldMember($memberAddress->member_id);

        $memberAddress->refresh();

        $this->assertDatabaseHas(MemberAddress::class, [
            'id' => $memberAddress->getKey(),
            'member_id' => $memberAddress->member_id,
            'deleted_at' => $memberAddress->deleted_at,
        ]);
    }
);
