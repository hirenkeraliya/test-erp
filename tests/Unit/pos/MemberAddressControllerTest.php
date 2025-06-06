<?php

declare(strict_types=1);

use App\Domains\MemberAddress\DataObjects\MemberAddressData;
use App\Domains\MemberAddress\MemberAddressQueries;
use App\Http\Controllers\Api\Pos\MemberAddressController;
use App\Models\Member;
use App\Models\MemberAddress;

test('It can store member address', function (): void {
    $companyId = 1;

    $member = Member::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'created_location_id' => 1,
        'card_number' => 'ABCD1234DEFG',
    ]);

    $memberAddressRecord = MemberAddress::factory()->make([
        'member_id' => $member->id,
        'is_primary' => false,
    ])->toArray();

    $memberAddressData = new MemberAddressData(...$memberAddressRecord);

    $this->mock(MemberAddressQueries::class, function ($mock): void {
        $mock->shouldReceive('isPrimary')
            ->once();
        $mock->shouldReceive('updatePrimaryKey')
            ->times(0);
        $mock->shouldReceive('addAddress')
            ->once();
    });
    $memberAddressController = new MemberAddressController();
    $response = $memberAddressController->store($memberAddressData);
    expect($response)->toBeArray();
});

test('It calls the update method of the MemberAddressQueries class', function (): void {
    $companyId = 1;

    $member = Member::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'created_location_id' => 1,
        'card_number' => 'ABCD1234DEFG',
    ]);

    $memberAddressRecord = MemberAddress::factory()->make([
        'member_id' => $member->id,
        'is_primary' => false,
    ])->toArray();

    $memberAddress = MemberAddress::factory()->make([
        'id' => 1,
        'member_id' => $member->id,
        'is_primary' => false,
    ]);

    $memberAddressData = new MemberAddressData(...$memberAddressRecord);

    $this->mock(MemberAddressQueries::class, function ($mock) use ($memberAddress): void {
        $mock->shouldReceive('getById')
            ->once()
            ->andReturn($memberAddress);
        $mock->shouldReceive('update')
            ->once();
    });
    $memberAddressController = new MemberAddressController();
    $memberAddressController->update($memberAddressData, $memberAddress->id);
});

test('It calls the removeAddress method of the MemberAddressQueries class', function (): void {
    $companyId = 1;

    $member = Member::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'created_location_id' => 1,
        'card_number' => 'ABCD1234DEFG',
    ]);

    $memberAddress = MemberAddress::factory()->make([
        'id' => 1,
        'member_id' => $member->id,
        'is_primary' => false,
    ]);

    $this->mock(MemberAddressQueries::class, function ($mock): void {
        $mock->shouldReceive('delete')
            ->once();
    });
    $memberAddressController = new MemberAddressController();
    $memberAddressController->removeAddress($memberAddress->id);
});
