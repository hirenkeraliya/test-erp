<?php

declare(strict_types=1);

use App\Domains\MemberAddress\DataObjects\AppMemberAddressData;
use App\Domains\MemberAddress\MemberAddressQueries;
use App\Http\Controllers\Api\Member\MemberAddressController;
use App\Models\Member;
use App\Models\MemberAddress;
use Illuminate\Http\Request;

test('It can store member address', function (): void {
    $member = Member::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'created_location_id' => 1,
        'card_number' => 'ABCD1234DEFG',
    ]);

    $request = new Request();
    $request->setUserResolver(fn (): Member => $member);

    $memberAddressRecord = new AppMemberAddressData(
        name: 'test',
        contact_mobile_number: '894567802312',
        contact_email: '',
        address_line_1: 'ssadas',
        address_line_2: '',
        city: '',
        area_code: '',
        is_primary: true,
    );

    $this->mock(MemberAddressQueries::class, function ($mock): void {
        $mock->shouldReceive('isPrimary')
            ->once();
        $mock->shouldReceive('updatePrimaryKey')
            ->times(0);
        $mock->shouldReceive('addAddressForMemberApp')
            ->once();
    });
    $memberAddressController = new MemberAddressController();
    $response = $memberAddressController->store($memberAddressRecord, $request);
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

    $memberAddressRecord = new AppMemberAddressData(
        name: 'test',
        contact_mobile_number: '894567802312',
        contact_email: '',
        address_line_1: 'ssadas',
        address_line_2: '',
        city: '',
        area_code: '',
        is_primary: true,
    );

    $memberAddress = MemberAddress::factory()->make([
        'id' => 1,
        'member_id' => $member->id,
        'is_primary' => false,
    ]);

    $request = new Request();
    $request->setUserResolver(fn (): Member => $member);

    $this->mock(MemberAddressQueries::class, function ($mock) use ($memberAddress): void {
        $mock->shouldReceive('getById')
            ->once()
            ->andReturn($memberAddress);
        $mock->shouldReceive('updateForMemberApp')
            ->once();
    });
    $memberAddressController = new MemberAddressController();
    $memberAddressController->update($memberAddressRecord, $memberAddress->id, $request);
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
