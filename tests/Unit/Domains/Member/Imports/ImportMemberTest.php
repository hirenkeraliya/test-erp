<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\ImportRecord\Enums\ImportTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\LoyaltyPoint\Services\LoyaltyPointService;
use App\Domains\Member\Enums\Genders;
use App\Domains\Member\Enums\Races;
use App\Domains\Member\Enums\Titles;
use App\Domains\Member\Enums\Types;
use App\Domains\Member\Imports\ImportMember;
use App\Domains\Member\MemberQueries;
use App\Models\Admin;
use App\Models\ImportRecord;
use App\Models\Member;

test('the validate method returns blank array when no error in given details', function (): void {
    $companyId = 1;

    $memberData = getMemberData();

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

    $this->mock(MemberQueries::class, function ($mock) use ($companyId, $memberData): void {
        $mock->shouldReceive('existsByMobileNumber')
            ->once()
            ->with($memberData['mobile_number'], $companyId)
            ->andReturn(false);
        $mock->shouldReceive('existsByEmail')
            ->once()
            ->with($memberData['email'], $companyId)
            ->andReturn(false);
        $mock->shouldReceive('existsByCardNumber')
            ->once()
            ->with($memberData['card_number'], $companyId)
            ->andReturn(false);
    });

    $this->mock(LocationQueries::class, function ($mock) use ($companyId, $memberData): void {
        $mock->shouldReceive('doStoreNameExist')
            ->once()
            ->with($memberData['created_location'], $companyId)
            ->andReturn(true);
    });

    $ImportMember = new ImportMember();
    $redirectResponse = $ImportMember->validate($memberData, $importRecord);
    $this->assertEquals([], $redirectResponse);
});

test('the validate method returns error messages when mobile number not valid', function (): void {
    $companyId = 1;

    $memberData = getMemberData();

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

    $memberData['mobile_number'] = '2216516565';

    $this->mock(MemberQueries::class, function ($mock) use ($companyId, $memberData): void {
        $mock->shouldReceive('existsByMobileNumber')
            ->times(0)
            ->with($memberData['mobile_number'], $companyId)
            ->andReturn(false);
        $mock->shouldReceive('existsByEmail')
            ->once()
            ->with($memberData['email'], $companyId)
            ->andReturn(false);
        $mock->shouldReceive('existsByCardNumber')
            ->once()
            ->with($memberData['card_number'], $companyId)
            ->andReturn(false);
    });

    $this->mock(LocationQueries::class, function ($mock) use ($companyId, $memberData): void {
        $mock->shouldReceive('doStoreNameExist')
            ->once()
            ->with($memberData['created_location'], $companyId)
            ->andReturn(true);
    });

    $ImportMember = new ImportMember();
    $redirectResponse = $ImportMember->validate($memberData, $importRecord);
    $this->assertEquals(1, is_countable($redirectResponse) ? count($redirectResponse) : 0);
});

test('the validate method returns error messages', function (): void {
    $companyId = 1;

    $memberData = getMemberData();

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

    $this->mock(MemberQueries::class, function ($mock) use ($companyId, $memberData): void {
        $mock->shouldReceive('existsByMobileNumber')
            ->once()
            ->with($memberData['mobile_number'], $companyId)
            ->andReturn(true);
        $mock->shouldReceive('existsByEmail')
            ->once()
            ->with($memberData['email'], $companyId)
            ->andReturn(true);
        $mock->shouldReceive('existsByCardNumber')
            ->once()
            ->with($memberData['card_number'], $companyId)
            ->andReturn(false);
    });

    $this->mock(LocationQueries::class, function ($mock) use ($companyId, $memberData): void {
        $mock->shouldReceive('doStoreNameExist')
            ->once()
            ->with($memberData['created_location'], $companyId)
            ->andReturn(false);
    });

    $ImportMember = new ImportMember();
    $redirectResponse = $ImportMember->validate($memberData, $importRecord);
    $this->assertEquals(3, is_countable($redirectResponse) ? count($redirectResponse) : 0);
});

test('first_name, mobile_number, email,card_number and create_store are required for import record', function (): void {
    $companyId = 1;

    $memberData = [
        'first_name' => '',
        'mobile_number' => '',
        'email' => '',
        'created_location' => '',
        'card_number' => '',
        'type' => '',
        'title' => '',
        'race' => '',
        'gender' => '',
    ];

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

    $ImportMember = new ImportMember();
    $redirectResponse = $ImportMember->validate($memberData, $importRecord);
    $this->assertEquals(9, is_countable($redirectResponse) ? count($redirectResponse) : 0);
});

test('It calls create method to store member details', function (): void {
    $companyId = 1;

    $memberData = [
        'created_location' => 'name',
    ];

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'type_id' => ImportTypes::MEMBERS->value,
        'created_by_id' => 1,
        'created_by_type' => ModelMapping::ADMIN->name,
    ]);

    $importRecord->createdBy = new Admin();

    $memberRecord = [
        'company_id' => 1,
        'uuid' => '',
        'type' => '',
        'title' => '',
        'race' => '',
        'first_name' => 'abcd',
        'last_name' => '',
        'gender' => '',
        'date_of_birth' => '',
        'mobile_number' => '123123',
        'email' => '',
        'address_line_1' => '',
        'address_line_2' => '',
        'city' => '',
        'area_code' => '',
        'company_name' => '',
        'company_registration_number' => '',
        'company_tax_number' => '',
        'company_phone' => '',
        'created_by_id' => '',
        'created_by_type' => '',
        'created_store_id' => 0,
        'created_location' => 'name',
        'notes' => '',
        'loyalty_points' => '100',
        'card_number' => 'abc123456789',
    ];

    $this->mock(LocationQueries::class, function ($mock) use ($memberData, $companyId): void {
        $mock->shouldReceive('getIdByName')
            ->once()
            ->with($memberData['created_location'], $companyId);
    });

    $this->mock(MemberQueries::class, function ($mock): void {
        $mock->shouldReceive('create')
            ->once()
            ->andReturn(new Member());
    });

    $this->mock(LoyaltyPointService::class, function ($mock): void {
        $mock->shouldReceive('increaseLoyaltyPointsForAdmin')
            ->once();
    });

    $ImportMember = new ImportMember();
    $ImportMember->save($memberRecord, $importRecord);
    $this->assertTrue(true);
});

function getMemberData(): array
{
    return [
        'first_name' => 'first name',
        'mobile_number' => '601112145678',
        'email' => 'test1@test.com',
        'created_location' => 'new_store',
        'card_number' => 'defg1234',
        'type' => Types::VIP->name,
        'title' => Titles::DATIN->name,
        'race' => Races::MALAY->name,
        'gender' => Genders::MALE->name,
    ];
}
