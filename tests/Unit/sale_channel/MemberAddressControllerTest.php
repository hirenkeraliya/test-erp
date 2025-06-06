<?php

declare(strict_types=1);

use App\Domains\MemberAddress\DataObjects\EcommerceMemberAddressData;
use App\Domains\MemberAddress\MemberAddressQueries;
use App\Domains\MemberAddressChannelReference\MemberAddressChannelReferenceQueries;
use App\Domains\MemberChannelReference\MemberChannelReferenceQueries;
use App\Domains\Order\Enums\OrderStatus;
use App\Domains\SaleChannel\Enums\SaleChannelTypes;
use App\Http\Controllers\Api\SaleChannel\MemberAddress\MemberAddressController;
use App\Models\Member;
use App\Models\MemberAddress;

test('It can store member address', function (): void {
    $companyId = 1;

    $member = Member::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'created_location_id' => 1,
        'card_number' => 'ABCD1234DEFG',
        'mobile_number' => '1234567890',
        'email' => 'test@gmail.com',
    ]);

    $memberAddressRecord = MemberAddress::factory()->make([
        'id' => 1,
        'member_id' => $member->id,
        'is_primary' => false,
    ]);

    $memberAddressData = new EcommerceMemberAddressData(...[
        'external_member_id' => $member->id,
        'external_member_address_id' => 1,
        'contact_mobile_number' => $member->mobile_number,
        'first_name' => null,
        'last_name' => null,
        'contact_email' => $member->email,
        'address_line_1' => null,
        'address_line_2' => null,
        'city' => null,
        'area_code' => null,
        'is_primary' => true,
    ]);

    $this->mock(MemberChannelReferenceQueries::class, function ($mock) use ($member): void {
        $mock->shouldReceive('getByMemberId')
            ->once()
            ->andReturn($member->id);
    });

    $this->mock(MemberAddressChannelReferenceQueries::class, function ($mock): void {
        $mock->shouldReceive('firstOrCreate')
            ->once();
    });

    $memberAddressQueries = $this->mock(MemberAddressQueries::class, function ($mock) use (
        $memberAddressData,
        $memberAddressRecord,
        $member
    ): void {
        $mock->shouldReceive('isPrimary')
            ->once();
        $mock->shouldReceive('updatePrimaryKey')
            ->times(0);
        $mock->shouldReceive('addAddressForEcommerce')
            ->once()
            ->with($memberAddressData, $member->id)
            ->andReturn($memberAddressRecord);
    });

    [$saleChannel, $request] = setRequestUserForSaleChannel();

    $memberAddressController = new MemberAddressController($memberAddressQueries);
    $response = $memberAddressController->store($memberAddressData, $request);
    expect($response)->toBeArray();
});

test('It calls the update method of the MemberAddressQueries class', function (): void {
    $companyId = 1;

    $member = Member::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'created_location_id' => 1,
        'card_number' => 'ABCD1234DEFG',
        'mobile_number' => '1234567890',
        'email' => 'test@gmail.com',
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

    $memberAddressData = new EcommerceMemberAddressData(...[
        'external_member_id' => $member->id,
        'external_member_address_id' => 1,
        'contact_mobile_number' => $member->mobile_number,
        'first_name' => null,
        'last_name' => null,
        'contact_email' => $member->email,
        'address_line_1' => null,
        'address_line_2' => null,
        'city' => null,
        'area_code' => null,
        'is_primary' => false,
    ]);

    $this->mock(MemberChannelReferenceQueries::class, function ($mock) use ($member): void {
        $mock->shouldReceive('getByMemberId')
            ->once()
            ->andReturn($member->id);
    });

    $this->mock(MemberAddressChannelReferenceQueries::class, function ($mock) use ($memberAddress): void {
        $mock->shouldReceive('getByMemberAddressId')
            ->once()
            ->andReturn($memberAddress->id);
    });

    $memberAddressQueries = $this->mock(MemberAddressQueries::class, function ($mock): void {
        $mock->shouldReceive('isPrimary')
            ->once();
        $mock->shouldReceive('updateForEcommerce')
            ->once();
    });

    [$saleChannel, $request] = setRequestUserForSaleChannel();

    $memberAddressController = new MemberAddressController($memberAddressQueries);
    $memberAddressController->update($memberAddressData, $request, $memberAddress->id);
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

    $this->mock(MemberAddressChannelReferenceQueries::class, function ($mock) use ($memberAddress): void {
        $mock->shouldReceive('getByMemberAddressId')
            ->once()
            ->andReturn($memberAddress->id);
    });

    $memberAddressQueries = $this->mock(MemberAddressQueries::class, function ($mock): void {
        $mock->shouldReceive('delete')
            ->once();
    });

    [$saleChannel, $request] = setRequestUserForSaleChannel(
        [],
        [
            'id' => 1,
            'company_id' => 1,
            'default_location_id' => 1,
            'inventory_deduct_order_status' => OrderStatus::PLACED,
            'type_id' => SaleChannelTypes::ECOMMERCE->value,
        ],
    );

    $memberAddressController = new MemberAddressController($memberAddressQueries);
    $memberAddressController->removeAddress($request, $memberAddress->id);
});

test('It calls the getMemberAddressDetails method of the MemberAddressQueries class', function (): void {
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

    $memberAddressQueries = $this->mock(MemberAddressQueries::class, function ($mock) use ($memberAddress): void {
        $mock->shouldReceive('getMemberAddressDetails')
            ->once()
            ->andReturn(collect([$memberAddress]));
    });

    [$saleChannel, $request] = setRequestUserForSaleChannel();

    $memberAddressController = new MemberAddressController($memberAddressQueries);
    $memberAddressController->getList($request, $member->id);
});
