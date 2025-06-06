<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\ImportRecord\Enums\ImportTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Member\Imports\ImportMembersBulkUpdate;
use App\Domains\Member\MemberQueries;
use App\Models\ImportRecord;

test('the validate method returns blank array when no error in given details', function (): void {
    $companyId = 1;

    $memberData = getMemberUpdateData();

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

    $this->mock(MemberQueries::class, function ($mock) use ($companyId, $memberData): void {
        $mock->shouldReceive('memberExistsByMobileNumber')
            ->once()
            ->with($companyId, $memberData['mobile_number'])
            ->andReturn(true);
        $mock->shouldReceive('emailTakenByAnotherMember')
            ->once()
            ->with($memberData['email'], $companyId, $memberData['mobile_number'])
            ->andReturn(false);
    });

    $this->mock(LocationQueries::class, function ($mock) use ($companyId, $memberData): void {
        $mock->shouldReceive('doStoreNameExist')
            ->once()
            ->with($memberData['created_location'], $companyId)
            ->andReturn(true);
    });

    $ImportMembersBulkUpdate = new ImportMembersBulkUpdate();
    $redirectResponse = $ImportMembersBulkUpdate->validate($memberData, $importRecord);
    $this->assertEquals([], $redirectResponse);
});

test('the validate method returns error messages', function (): void {
    $companyId = 1;

    $memberData = getMemberUpdateData();

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

    $this->mock(MemberQueries::class, function ($mock) use ($companyId, $memberData): void {
        $mock->shouldReceive('emailTakenByAnotherMember')
            ->once()
            ->with($memberData['email'], $companyId, $memberData['mobile_number'])
            ->andReturn(true);
        $mock->shouldReceive('memberExistsByMobileNumber')
            ->once()
            ->with($companyId, $memberData['mobile_number'])
            ->andReturn(false);
    });

    $this->mock(LocationQueries::class, function ($mock) use ($companyId, $memberData): void {
        $mock->shouldReceive('doStoreNameExist')
            ->once()
            ->with($memberData['created_location'], $companyId)
            ->andReturn(true);
    });

    $ImportMembersBulkUpdate = new ImportMembersBulkUpdate();
    $redirectResponse = $ImportMembersBulkUpdate->validate($memberData, $importRecord);
    $this->assertEquals(2, is_countable($redirectResponse) ? count($redirectResponse) : 0);
});

test('save method works for the member details update', function (): void {
    $companyId = 1;
    $locationId = 1;

    $memberData = getMemberUpdateData();

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'type_id' => ImportTypes::MEMBERS_BULK_UPDATE->value,
        'created_by_id' => 1,
        'created_by_type' => ModelMapping::ADMIN->name,
    ]);

    $this->mock(LocationQueries::class, function ($mock) use ($memberData, $companyId, $locationId): void {
        $mock->shouldReceive('getIdByName')
            ->once()
            ->with($memberData['created_location'], $companyId)
            ->andReturn($locationId);
    });

    $this->mock(MemberQueries::class, function ($mock): void {
        $mock->shouldReceive('updateByMobileNumber')
            ->times(1);
    });

    $ImportMembersBulkUpdate = new ImportMembersBulkUpdate();
    $ImportMembersBulkUpdate->save($memberData, $importRecord);
    $this->assertTrue(true);
});

function getMemberUpdateData(): array
{
    return [
        'type' => 'VIP',
        'title' => 'Mr',
        'race' => 'Others',
        'first_name' => 'abcd',
        'last_name' => '',
        'gender' => 'Male',
        'date_of_birth' => '2010-03-30',
        'mobile_number' => '123123',
        'email' => 'abc@xyz.com',
        'address_line_1' => 'abc',
        'address_line_2' => 'xyz',
        'city' => 'mno',
        'area_code' => '15987',
        'company_name' => 'xyz',
        'company_registration_number' => '1212113',
        'company_tax_number' => '1212121',
        'company_phone' => '11212',
        'pic_name' => 'abcxyz',
        'pic_contact' => '1234567890',
        'created_location' => 'abc',
        'last_purchase_date' => '',
        'registered_date' => '',
    ];
}
