<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\ImportRecord\Enums\ImportTypes;
use App\Domains\Member\Imports\ImportMemberAddress;
use App\Domains\Member\MemberQueries;
use App\Domains\MemberAddress\MemberAddressQueries;
use App\Models\ImportRecord;

test('first_name, mobile_number, address_line_1, is_primary are required for import record', function (): void {
    $companyId = 1;

    $memberAddressData = [
        'first_name' => '',
        'mobile_number' => '',
        'address_line_1' => '',
        'area_code' => '',
        'is_primary' => '',
    ];

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

    $importMemberAddress = new ImportMemberAddress();
    $redirectResponse = $importMemberAddress->validate($memberAddressData, $importRecord);
    $this->assertEquals(5, is_countable($redirectResponse) ? count($redirectResponse) : 0);
});

test('It calls create method to store member details', function (): void {
    $companyId = 1;

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'type_id' => ImportTypes::MEMBER_ADDRESS->value,
        'created_by_id' => 1,
        'created_by_type' => ModelMapping::ADMIN->name,
    ]);

    $memberAddressRecord = [
        'member_id' => 1,
        'first_name' => 'abcde',
        'mobile_number' => '78946443354',
        'name' => 'abcde',
        'contact_mobile_number' => 'abcde',
        'contact_email' => 'abcde',
        'address_line_1' => 'test address',
        'address_line_2' => '',
        'city' => '',
        'area_code' => '',
        'is_primary' => false,
    ];

    $this->mock(MemberQueries::class, function ($mock) use ($memberAddressRecord, $companyId): void {
        $mock->shouldReceive('getIdByName')
            ->once()
            ->with($memberAddressRecord['first_name'], $memberAddressRecord['mobile_number'], $companyId);
    });

    $this->mock(MemberAddressQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $importMemberAddress = new ImportMemberAddress();
    $importMemberAddress->save($memberAddressRecord, $importRecord);
    $this->assertTrue(true);
});
