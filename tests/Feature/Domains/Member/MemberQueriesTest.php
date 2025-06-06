<?php

declare(strict_types=1);

use App\Domains\Azentio\DataObjects\AzentioMemberData;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Company\Enums\LocationAssignmentTypes;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Member\DataObjects\AppMemberData;
use App\Domains\Member\DataObjects\MemberData;
use App\Domains\Member\DataObjects\OrderMemberData;
use App\Domains\Member\Enums\MemberChannelEnum;
use App\Domains\Member\Enums\Status;
use App\Domains\Member\Enums\Types;
use App\Domains\Member\MemberQueries;
use App\Domains\Sale\Enums\SaleStatus;
use App\Domains\SiteConfiguration\Enums\SiteConfigurationTypes;
use App\Models\Admin;
use App\Models\Company;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Member;
use App\Models\MemberChannelReference;
use App\Models\MemberGroup;
use App\Models\MemberGroupMember;
use App\Models\Membership;
use App\Models\Promoter;
use App\Models\Sale;
use App\Models\SaleChannel;
use App\Models\SiteConfiguration;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    $this->company = Company::factory()->create([
        'new_member_free_loyalty_points' => 2,
        'loyalty_point_expiration_days' => 10,
    ]);

    $this->location = Location::factory()->create([
        'company_id' => $this->company->id,
        'type_id' => LocationTypes::STORE->value,
        'loyalty_point_expiration_days' => 10,
    ]);

    $this->memberA = Member::factory()->create([
        'company_id' => $this->company->id,
        'first_name' => 'member_one',
        'created_location_id' => $this->location->id,
        'loyalty_points' => 157,
        'card_number' => 'ABCD1234DEFG',
        'status' => Status::ACTIVE->value,
        'is_email_verified' => 0,
    ]);

    $this->memberB = Member::factory()->create([
        'company_id' => $this->company->id,
        'first_name' => 'member_two',
    ]);

    $this->memberQueries = new MemberQueries();
});

test('Members can be searched', function (): void {
    $response = $this->memberQueries->listQueryForMembers([
        'search_text' => 'member_one',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'location_ids' => null,
        'membership_ids' => null,
        'member_group_ids' => null,
        'date_range' => null,
        'preference_id' => null,
        'purchase_filter_type_id' => null,
    ], $this->company->id);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('first_name', $this->memberA->first_name)
        ->toHaveKey('email', $this->memberA->email);
});

test('A member can be added', function (): void {
    Storage::fake('public');

    $uploadedFile = UploadedFile::fake()->image('avatar.jpg');
    $newMemberRecord = Member::factory()->make([
        'company_id' => $this->company->id,
        'created_location_id' => $this->location->id,
    ])->toArray();
    $newMemberRecord['photo'] = $uploadedFile;

    unset(
        $newMemberRecord['company_id'],
        $newMemberRecord['created_by_id'],
        $newMemberRecord['created_by_type'],
        $newMemberRecord['last_purchase_date'],
        $newMemberRecord['loyalty_points'],
        $newMemberRecord['member_addresses'],
    );

    $admin = Admin::factory()->create();
    $member = $this->memberQueries->addNewForAdminAndStoreManager(
        new MemberData(...$newMemberRecord),
        $this->company->id,
        $admin,
        MemberChannelEnum::ADMIN->value
    );
    unset($newMemberRecord['photo']);

    $this->assertDatabaseHas('members', $newMemberRecord);
    $this->assertDatabaseHas('loyalty_points', [
        'member_id' => $member->id,
        'expiry_date' => now()->addDays($this->company->loyalty_point_expiration_days)->format('Y-m-d'),
        'points' => $this->company->new_member_free_loyalty_points,
    ]);
    $this->assertDatabaseHas('loyalty_point_updates', [
        'member_id' => $member->id,
        'points' => $this->company->new_member_free_loyalty_points,
    ]);
    $this->assertDatabaseHas('media', [
        'model_type' => ModelMapping::MEMBER->name,
        'collection_name' => 'photo',
        'file_name' => $uploadedFile->name,
        'mime_type' => 'image/jpeg',
    ]);
});

test('A member can be fetched', function (): void {
    $response = $this->memberQueries->getByIdWithMedia($this->memberA->id, $this->company->id);
    $member = $this->memberA->load('media', 'createdInLocation:id,company_id,name,code,type_id,city_id');
    $member['member_addresses'] = [];

    unset(
        $member['updated_at'],
        $member['created_at'],
        $member['created_by_id'],
        $member['created_by_type'],
        $member['last_purchase_date'],
        $member['loyalty_points'],
        $member['company'],
        $member['status'],
    );
    $this->assertEquals($member->toArray(), $response->toArray());
});

test('A member can be updated', function (): void {
    Storage::fake('public');

    $uploadedFile = UploadedFile::fake()->image('avatar.jpg');
    $memberRecord = Member::factory()->make()->toArray();
    $memberRecord['photo'] = $uploadedFile;

    unset(
        $memberRecord['company_id'],
        $memberRecord['created_by_id'],
        $memberRecord['created_by_type'],
        $memberRecord['last_purchase_date'],
    );

    $this->memberQueries->update(new MemberData(...$memberRecord), $this->memberA->id, $this->company->id);

    unset($memberRecord['photo']);
    $this->assertDatabaseHas('members', $memberRecord);
    $this->assertDatabaseHas('media', [
        'model_type' => ModelMapping::MEMBER->name,
        'collection_name' => 'photo',
        'file_name' => $uploadedFile->name,
        'mime_type' => 'image/jpeg',
    ]);
});

test('A member set fcm token', function (): void {
    $this->memberQueries->updateFcmToken($token = 'test1234', $this->memberA->id, $this->company->id);

    $this->assertDatabaseHas('members', [
        'fcm_token' => $token,
    ]);
});

test(
    'getPaginatedListForPos method return the paginated list of members',
    function (): void {
        $filterData = [
            'per_page' => 1,
            'page' => 1,
            'sort_by' => null,
            'sort_direction' => null,
            'search_text' => $this->memberA->first_name,
            'after_updated_at' => null,
        ];

        $response = $this->memberQueries->getPaginatedListForPos($filterData, $this->company->id);

        expect($response->first()->toArray())
            ->toHaveKey('first_name', $this->memberA->first_name)
            ->toHaveKey('email', $this->memberA->email)
            ->toHaveKeys(['media', 'vouchers']);
    }
);

test(
    'getMemberDetailsForPos method returns the member details',
    function (): void {
        $response = $this->memberQueries->getMemberDetailsForPos($this->memberA->id, $this->company->id);

        expect($response->toArray())
            ->toHaveKey('id', $this->memberA->id)
            ->toHaveKey('first_name', $this->memberA->first_name)
            ->toHaveKey('email', $this->memberA->email)
            ->toHaveKeys(['media', 'vouchers']);
    }
);

test(
    'getByIdForMemberUpdatePointsAndTotalSalesJob method returns the member details',
    function (): void {
        $response = $this->memberQueries->getByIdForMemberUpdatePointsAndTotalSalesJob($this->memberA->id);

        expect($response->toArray())
            ->toHaveKey('id', $this->memberA->id)
            ->toHaveKey('total_earned_points', $this->memberA->total_earned_points)
            ->toHaveKey('total_expired_points', $this->memberA->total_expired_points)
            ->toHaveKey('total_redeemed_points', $this->memberA->total_redeemed_points)
            ->toHaveKey('total_sales', $this->memberA->total_sales);
    }
);

test('emailTakenByAnotherMember method returns boolean as expected', function (): void {
    $response = $this->memberQueries->emailTakenByAnotherMember(
        $this->memberA->email,
        $this->company->id,
        $this->memberA->mobile_number
    );
    $this->assertFalse($response);

    $response = $this->memberQueries->emailTakenByAnotherMember(
        $this->memberA->email,
        $this->company->id,
        $this->memberB->mobile_number
    );
    $this->assertTrue($response);
});

test('memberExistsById method returns member details', function (): void {
    $response = $this->memberQueries->memberExistsById($this->company->id, $this->memberA->id);
    $member = $this->memberA;
    $this->assertEquals($member['id'], $response['id']);
});

test(
    'getMemberWithStore method return the list',
    function (): void {
        $response = $this->memberQueries->getMemberWithStore($this->company->id);

        expect($response->first()->toArray())
            ->toHaveKey('first_name', $this->memberA->first_name)
            ->toHaveKey('email', $this->memberA->email)
            ->toHaveKey('created_in_location.name', $this->location->name);
    }
);

test('updateByMobileNumber method update the member data', function (): void {
    $memberRecord = Member::factory()->make()->toArray();

    unset(
        $memberRecord['notes'],
        $memberRecord['last_purchase_date'],
    );

    $this->memberQueries->updateByMobileNumber(
        $memberRecord,
        $this->memberB->company_id,
        $this->memberB->mobile_number
    );

    $this->assertDatabaseHas('members', $memberRecord);
});

test('existsByMobileNumber method returns boolean as expected', function (): void {
    $response = $this->memberQueries->existsByMobileNumber($this->memberA->mobile_number, $this->company->id);
    $this->assertTrue($response);

    $response = $this->memberQueries->existsByMobileNumber('UPCABCDEFGH', $this->company->id);
    $this->assertFalse($response);
});

test('existsByEmail method returns boolean as expected', function (): void {
    $response = $this->memberQueries->existsByEmail($this->memberA->email, $this->company->id);
    $this->assertTrue($response);

    $response = $this->memberQueries->existsByEmail('UPCABCDEFGH', $this->company->id);
    $this->assertFalse($response);
});

test('checkMobileNumberOrEmailExists method returns boolean as expected', function (): void {
    $response = $this->memberQueries->checkMobileNumberOrEmailExists($this->memberA->mobile_number);
    $this->assertTrue($response);

    $response = $this->memberQueries->checkMobileNumberOrEmailExists($this->memberA->email);
    $this->assertTrue($response);

    $response = $this->memberQueries->checkMobileNumberOrEmailExists('UPCABCDEFGH');
    $this->assertFalse($response);
});

test('checkCompanyDelete method returns boolean as expected', function (): void {
    $response = $this->memberQueries->checkCompanyDelete('email', $this->memberA->email);
    expect($response)->toBeTrue();

    $response = $this->memberQueries->checkCompanyDelete('mobile_number', $this->memberA->mobile_number);
    expect($response)->toBeTrue();

    $this->company->delete();

    $response = $this->memberQueries->checkCompanyDelete('email', $this->memberA->email);
    expect($response)->toBeFalse();

    $response = $this->memberQueries->checkCompanyDelete('mobile_number', $this->memberA->mobile_number);
    expect($response)->toBeFalse();
});

test('updateOtpBasedOnMobileNumber method generated otp and update otp', function (): void {
    $this->assertDatabaseHas('members', [
        'otp' => null,
        'otp_expire_date' => null,
    ]);

    $this->memberQueries->updateOtpBasedOnMobileNumber($this->memberA->mobile_number, '9999');

    $this->assertDatabaseHas('members', [
        'otp' => '9999',
        'otp_expire_date' => Carbon::now()->addMinutes(10),
    ]);
});

test('updateOtpBasedOnEmail method generated otp and update otp', function (): void {
    $this->assertDatabaseHas('members', [
        'otp' => null,
        'otp_expire_date' => null,
    ]);

    $this->memberQueries->updateOtpBasedOnEmail($this->memberA->email, '9999');

    $this->assertDatabaseHas('members', [
        'otp' => '9999',
        'otp_expire_date' => Carbon::now()->addMinutes(10),
    ]);
});

test(
    'updateLastLoginTime method updates the last login time',
    function (): void {
        $memberForValidation = Member::factory()->create([
            'first_name' => 'testing',
            'mobile_number' => '11111111',
        ]);

        $this->assertDatabaseHas('members', [
            'last_login_at' => null,
        ]);

        $this->memberQueries->updateLastLoginTime($memberForValidation);

        $memberForValidation->refresh();

        $this->assertDatabaseHas('members', [
            'last_login_at' => $memberForValidation->last_login_at,
        ]);
    }
);

test('it saves the member details', function (): void {
    $location = Location::factory()->create([
        'company_id' => $this->company->id,
        'type_id' => LocationTypes::STORE->value,
    ]);
    $memberDetails = Member::factory()->make([
        'company_id' => $this->company->id,
        'created_location_id' => $location->id,
    ])->toArray();

    unset($memberDetails['last_purchase_date']);

    $this->memberQueries->create($memberDetails);
    $this->assertDatabaseHas('members', $memberDetails);
});

test('updateLastPurchaseDate method update the details', function (): void {
    $this->freezeTime();
    $this->memberQueries->updateLastPurchaseDate($this->company->id, $this->memberA->id);
    $this->assertDatabaseHas('members', [
        'id' => $this->memberA->id,
        'last_purchase_date' => now()->format('Y-m-d H:i:s'),
    ]);
});

test('searchMembersForFilter can search member', function (): void {
    $response = $this->memberQueries->searchMembersForFilter([
        'search_text' => 'member_one',
        'number_of_records' => 5,
    ], $this->company->id);

    expect($response->first()->toArray())
        ->toHaveKey('first_name', $this->memberA->first_name);
});

test(
    'getMembersByBirthDate method returns the specified companies members list as expected',
    function (): void {
        $date = Carbon::now();

        $this->memberA->date_of_birth = $date->format('Y-m-d');
        $this->memberA->company_id = $this->company->id;
        $this->memberA->save();

        $response = $this->memberQueries->getMembersByBirthDate($date, [$this->company->id]);

        expect($response)->toBeInstanceOf(Collection::class);

        expect($response->first()->toArray())
            ->toHaveKey('id', $this->memberA->id)
            ->toHaveKey('date_of_birth', $this->memberA->date_of_birth);
    }
);

test('A member can be fetched with membership', function (): void {
    $membership = Membership::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $this->memberA->membership_id = $membership->id;
    $this->memberA->save();
    $this->memberA->membership = $membership;
    $response = $this->memberQueries->getByIdWithMembership($this->memberA->id, $this->company->id);
    $this->memberA->load('membership');

    expect($response->toArray())
        ->toHaveKey('first_name', $this->memberA->first_name)
        ->toHaveKey('email', $this->memberA->email)
        ->toHaveKey('membership_id', $this->memberA->membership_id)
        ->toHaveKey('membership');
});

test(
    'getDateOfBirthAndBirthdayVoucherLastGeneratedColumnAtById method returns the date_of_batch and birthday_voucher_last_generated_at column',
    function (): void {
        $this->memberA->birthday_voucher_last_generated_at = now()->format('Y-m-d');
        $this->memberA->save();

        $response = $this->memberQueries->getDateOfBirthAndBirthdayVoucherLastGeneratedColumnAtById(
            $this->company->id,
            $this->memberA->id
        );

        expect($response->toArray())
            ->toHaveKey('date_of_birth', $this->memberA->date_of_birth)
            ->toHaveKey('birthday_voucher_last_generated_at', $this->memberA->birthday_voucher_last_generated_at);
    }
);

test('updateSpentTillNow method updates the spent_till_now column', function (): void {
    $this->memberQueries->updateSpentTillNow(100, $this->memberA->id);

    $this->assertDatabaseHas('members', [
        'id' => $this->memberA->id,
        'spent_till_now' => 100,
    ]);
});

test('updateSalesQuantity method updates the total_sale_qty column', function (): void {
    $this->memberQueries->updateSalesQuantity(100, $this->memberA->id);

    $this->assertDatabaseHas('members', [
        'id' => $this->memberA->id,
        'total_sale_qty' => 100,
    ]);
});

test('updateBirthdayVoucherDetails method updates birthday voucher details', function (): void {
    $voucher = Voucher::factory()->create([
        'member_id' => $this->memberA,
    ]);

    $this->memberQueries->updateBirthdayVoucherDetails($this->memberA, $voucher->id);

    $this->assertDatabaseHas('members', [
        'id' => $this->memberA->id,
        'birthday_voucher_last_generated_at' => Carbon::now()->format('Y-m-d'),
        'last_birthday_voucher_id' => $voucher->id,
    ]);
});

test('setMembershipId method sets the membership_id column', function (): void {
    $membership = Membership::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $this->memberQueries->setMembershipId($membership->id, $this->memberA->id);
    $this->assertDatabaseHas('members', [
        'id' => $this->memberA->id,
        'membership_id' => $membership->id,
    ]);
});

test(
    'getMemberByMobileNumber method returns the member details as expected',
    function (): void {
        $this->memberA->mobile_number = '123456789';
        $this->memberA->save();

        $response = $this->memberQueries->getMemberByMobileNumber(
            $this->memberA->mobile_number,
            $this->company->id
        );

        expect($response->first()->toArray())
            ->toHaveKey('id', $this->memberA->id)
            ->toHaveKey('first_name', $this->memberA->first_name)
            ->toHaveKey('last_name', $this->memberA->last_name)
            ->toHaveKey('mobile_number', $this->memberA->mobile_number);
    }
);

test('decreaseLoyaltyPoints method decreases the member loyalty points as expected', function (): void {
    $this->memberA->loyalty_points = 10;
    $this->memberA->save();

    $this->memberQueries->decreaseLoyaltyPoints($this->memberA->id, 5);

    $this->assertDatabaseHas('members', [
        'id' => $this->memberA->id,
        'loyalty_points' => $this->memberA->loyalty_points - 5,
    ]);
});

test(
    'getByIdWithMembershipAndLoyaltyPoints method returns the membership_id and loyalty_points column',
    function (): void {
        $response = $this->memberQueries->getByIdWithMembershipAndLoyaltyPoints(
            $this->company->id,
            $this->memberA->id
        );

        expect($response->first()->toArray())
            ->toHaveKey('membership_id', $this->memberA->membership_id)
            ->toHaveKey('loyalty_points', $this->memberA->loyalty_points);
    }
);

test('increaseLoyaltyPoints method updates the loyalty points', function (): void {
    $member = Member::factory()->create([
        'company_id' => $this->company->id,
        'first_name' => 'member_one',
        'created_location_id' => $this->location,
        'loyalty_points' => 10,
    ]);

    $this->memberQueries->increaseLoyaltyPoints($member, 10);

    $this->assertDatabaseHas('members', [
        'id' => $member->id,
        'loyalty_points' => 20,
    ]);
});

test(
    'assignMembershipToMember method assign membership to newly created member if available',
    function (): void {
        $member = Member::factory()->create([
            'company_id' => $this->company->getKey(),
            'first_name' => 'member_one',
            'created_location_id' => $this->location,
            'loyalty_points' => 10,
        ]);

        $membership = Membership::factory()->create([
            'lifetime_value' => 0.00,
            'company_id' => $this->company->getKey(),
        ]);

        $this->memberQueries->assignMembershipToMember($member->getKey(), $this->company->getKey());

        $this->assertDatabaseHas('members', [
            'id' => $member->getKey(),
            'membership_id' => $membership->getKey(),
        ]);

        $this->assertDatabaseHas('membership_assignments', [
            'membership_id' => $membership->getKey(),
            'member_id' => $member->getKey(),
        ]);
    }
);

test(
    'getMemberByCardNumber method returns the member details as expected',
    function (): void {
        $this->memberA->card_number = 'ABC1234';
        $this->memberA->save();

        $response = $this->memberQueries->getMemberByCardNumber($this->memberA->card_number, $this->company->id);

        expect($response->first()->toArray())
            ->toHaveKey('id', $this->memberA->id)
            ->toHaveKey('first_name', $this->memberA->first_name)
            ->toHaveKey('last_name', $this->memberA->last_name)
            ->toHaveKey('card_number', $this->memberA->card_number);
    }
);

test('A member can be uploadProfilePhoto', function (): void {
    $memberRecord = [];
    Storage::fake('public');

    $uploadedFile = UploadedFile::fake()->image('avatar.jpg');
    $memberRecord['photo'] = $uploadedFile;

    $this->memberQueries->uploadProfilePhoto($memberRecord, $this->memberA);

    $this->assertDatabaseHas('media', [
        'model_type' => ModelMapping::MEMBER->name,
        'collection_name' => 'photo',
        'file_name' => $uploadedFile->name,
        'mime_type' => 'image/jpeg',
    ]);
});

test('A member app can be updated profile', function (): void {
    $member = Member::factory()->create();

    $this->memberQueries->updateMemberProfile(new AppMemberData(
        title_id: $member->title_id,
        race_id: $member->race_id,
        gender_id: $member->gender_id,
        first_name: $member->first_name,
        last_name: $member->last_name,
        email: $member->email,
        address_line_1: 'test',
        address_line_2: 'test',
        city: 'test',
        area_code: 'test',
    ), $member);

    $this->assertDatabaseHas('members', [
        'first_name' => $member->first_name,
        'last_name' => $member->last_name,
        'email' => $member->email,
    ]);
});

test('getActiveBirthdayVoucher returns the voucher details', function (): void {
    $member = Member::factory()->create([
        'company_id' => $this->company->getKey(),
        'first_name' => 'member_one',
        'created_location_id' => $this->location,
        'loyalty_points' => 10,
    ]);

    $voucher = Voucher::factory()->create([
        'member_id' => $member->id,
        'used_at' => null,
        'expiry_date' => Carbon::now()->addDay(),
        'cancelled_at' => null,
    ]);

    $member->last_birthday_voucher_id = $voucher->id;
    $member->save();

    $member->birthdayVoucher = $voucher;

    $response = $this->memberQueries->getActiveBirthdayVoucher($this->company->id, $member->id);

    expect($response->birthdayVoucher->toArray())
        ->toHaveKey('id', $voucher->id)
        ->toHaveKey('discount_type', $voucher->discount_type)
        ->toHaveKey('minimum_spend_amount', $voucher->minimum_spend_amount);
});

test('The addNewMemberForRegistration method should save the member details for registration.', function (): void {
    $member = Member::factory()->make([
        'company_id' => $this->company->id,
        'created_location_id' => $this->location->id,
    ])->toArray();

    $this->memberQueries->addNewMemberForRegistration(
        $member,
        $this->location->id,
        $this->company->id,
        MemberChannelEnum::QR_CODE->value
    );

    $this->assertDatabaseHas('members', [
        'company_id' => $this->company->id,
        'created_location_id' => $this->location->id,
        'first_name' => $member['first_name'],
        'notes' => 'Generated by QR-code on ' . $this->location->name,
    ]);
});

test(
    'The deleteMember method prepend the member id with email & mobile number while delete member from app',
    function (): void {
        $member = Member::factory()->create([
            'id' => 9999,
            'company_id' => $this->company->id,
            'created_location_id' => $this->location->id,
            'email' => 'test@test.com',
            'mobile_number' => '1234567890',
            'card_number' => '1234567890',
            'notes' => 'qwer ' . now()->format('d-m-Y H:i:s') . ':' . Status::getFormattedCaseName(
                Status::DELETED_BY_USER->value),
        ]);

        $this->memberQueries->deleteMember($member);

        $this->assertDatabaseHas('members', [
            'email' => $member->id . ltrim($member->email, (string) $member->id),
            'mobile_number' => $member->id . ltrim($member->mobile_number, (string) $member->id),
            'card_number' => $member->id . ltrim($member->card_number, (string) $member->id),
            'status' => Status::DELETED_BY_USER->value,
            'notes' => $member->notes,
        ]);
    }
);

test(
    'The deleteMemberByAdmin method prepend the member id with email & mobile number while delete member from admin',
    function (): void {
        $adminId = Admin::factory()->create()->id;

        $member = Member::factory()->create([
            'id' => 9999,
            'company_id' => $this->company->id,
            'created_location_id' => $this->location->id,
            'email' => 'test@test.com',
            'mobile_number' => '1234567890',
            'card_number' => '1234567890',
            'notes' => 'asdf ' . now()->format('d-m-Y H:i:s') . ':' . Status::getFormattedCaseName(
                Status::DELETED_BY_ADMIN->value
            ).':'.$adminId,
        ]);

        $this->memberQueries->deleteMemberByAdmin($member, $adminId);

        $this->assertDatabaseHas('members', [
            'email' => $member->id . ltrim($member->email, (string) $member->id),
            'mobile_number' => $member->id . ltrim($member->mobile_number, (string) $member->id),
            'card_number' => $member->id . ltrim($member->card_number, (string) $member->id),
            'status' => Status::DELETED_BY_ADMIN->value,
            'notes' => $member->notes,
        ]);
    }
);

test('updateWelcomeMemberVoucherDetails method updates welcome member voucher details', function (): void {
    Carbon::setTestNow(Carbon::now()->format('Y-m-d H:i:s'));

    $voucher = Voucher::factory()->create([
        'member_id' => $this->memberA,
    ]);

    $this->memberQueries->updateWelcomeMemberVoucherDetails($this->memberA, $voucher->id);

    $this->assertDatabaseHas('members', [
        'id' => $this->memberA->id,
        'welcome_member_voucher_generated_at' => Carbon::now()->format('Y-m-d H:i:s'),
        'welcome_member_voucher_id' => $voucher->id,
    ]);

    Carbon::setTestNow();
});

test('The addNewMemberByPromoter method should save the member details.', function (): void {
    $member = Member::factory()->make([
        'company_id' => $this->company->id,
        'created_location_id' => $this->location->id,
    ])->toArray();

    $promoter = Promoter::factory()->create();

    $this->memberQueries->addNewMemberByPromoter(
        $member,
        $this->location->id,
        $this->company->id,
        $promoter,
        MemberChannelEnum::PROMOTER->value
    );

    $this->assertDatabaseHas('members', [
        'company_id' => $this->company->id,
        'created_location_id' => $this->location->id,
        'first_name' => $member['first_name'],
        'notes' => 'Register by Promoter',
    ]);
});

test('A member can be fetched by company id with membership', function (): void {
    $membership = Membership::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $this->memberA->membership_id = $membership->id;
    $this->memberA->save();
    $this->memberA->membership = $membership;
    $response = $this->memberQueries->getByIdAndCompanyIdWithMembership($this->memberA->id, $this->company->id);
    $this->memberA->load('membership');

    expect($response->toArray())
        ->toHaveKey('first_name', $this->memberA->first_name)
        ->toHaveKey('email', $this->memberA->email)
        ->toHaveKey('membership_id', $this->memberA->membership_id)
        ->toHaveKey('membership');
});

test(
    'getPaginatedListForStoreManagerApp method return the paginated list of members',
    function (): void {
        $filterData = [
            'per_page' => 1,
            'page' => 1,
            'sort_by' => null,
            'sort_direction' => null,
            'search_text' => $this->memberA->first_name,
        ];

        $response = $this->memberQueries->getPaginatedListForStoreManagerAndPromoterApp(
            $filterData,
            $this->company->id
        );

        expect($response->first()->toArray())
            ->toHaveKey('first_name', $this->memberA->first_name);
    }
);

test(
    'loadRelationsForPos method returns the member details',
    function (): void {
        $member = Member::factory()->create([
            'company_id' => $this->company->getKey(),
            'first_name' => 'member_one',
            'created_location_id' => $this->location,
            'loyalty_points' => 10,
        ]);

        $voucher = Voucher::factory()->create([
            'member_id' => $member->id,
            'used_at' => null,
            'expiry_date' => Carbon::now()->addDay(),
            'cancelled_at' => null,
        ]);

        VoucherTransaction::factory()->create([
            'voucher_id' => $voucher->id,
        ]);

        $member->last_birthday_voucher_id = $voucher->id;
        $member->save();

        $member->birthdayVoucher = $voucher;

        $response = $this->memberQueries->loadRelationsForPos($member);

        expect($response->toArray())
            ->toHaveKey('id', $member->id)
            ->toHaveKey('first_name', $member->first_name)
            ->toHaveKey('email', $member->email)
            ->toHaveKeys([
                'media',
                'vouchers',
                'vouchers.0.voucher_transactions',
                'vouchers.0.voucher_transactions.0.sale',
                'vouchers.0.voucher_transactions.0.location',
                'vouchers.0.voucher_configuration',
                'vouchers.0.voucher_configuration.products',
                'vouchers.0.voucher_configuration.categories',
                'birthday_voucher',
                'member_group_members',
            ]);
    }
);

test('updatePointsAndTotalSales method update member data', function (): void {
    $member = Member::factory()->create([
        'company_id' => $this->company->id,
        'created_location_id' => $this->location->id,
        'total_earned_points' => 100,
        'total_redeemed_points' => 150,
        'total_sales' => 200,
    ]);

    $this->assertDatabaseHas('members', [
        'total_earned_points' => 100,
        'total_redeemed_points' => 150,
        'total_sales' => 200,
    ]);

    $preferredItems = [
        'preferences_products' => '',
        'preferences_color' => '',
        'preferences_size' => '',
        'preferences_category' => '',
        'preferred_date' => '',
    ];

    $this->memberQueries->updatePointsAndTotalSales($member, 200, 300, 400, $preferredItems);

    $this->assertDatabaseHas('members', [
        'total_earned_points' => 200,
        'total_redeemed_points' => 300,
        'total_sales' => 400,
    ]);
});

test('getByIdForNewMemberBenefitsJob return member', function (): void {
    $response = $this->memberQueries->getByIdForNewMemberBenefitsJob($this->memberA->id, $this->company->id);

    expect($response->toArray())
        ->toHaveKey('id', $this->memberA->id)
        ->toHaveKey('company_id', $this->memberA->company_id)
        ->toHaveKey('created_location_id', $this->memberA->created_location_id)
        ->toHaveKey('loyalty_points', $this->memberA->loyalty_points)
        ->toHaveKey('membership_id', $this->memberA->membership_id)
        ->toHaveKey('welcome_member_voucher_id', $this->memberA->welcome_member_voucher_id)
        ->toHaveKey('welcome_member_voucher_generated_at', $this->memberA->welcome_member_voucher_generated_at)
        ->toHaveKeys([
            'company.id',
            'company.new_member_free_loyalty_points',
            'company.default_location_id',
            'company.location_assignment_type',
        ]);
});

test('A member can be update and also update member benefits', function (): void {
    Storage::fake('public');

    $member = Member::factory()->create([
        'company_id' => $this->company->id,
        'created_location_id' => null,
    ]);

    $memberRecord = Member::factory()->make([
        'company_id' => $this->company->id,
        'created_location_id' => $this->location->id,
    ])->toArray();

    unset(
        $memberRecord['company_id'],
        $memberRecord['created_by_id'],
        $memberRecord['created_by_type'],
        $memberRecord['last_purchase_date'],
        $memberRecord['loyalty_points'],
    );

    $uploadedFile = UploadedFile::fake()->image('avatar.jpg');
    $memberRecord['photo'] = $uploadedFile;

    $this->memberQueries->update(new MemberData(...$memberRecord), $member->id, $this->company->id);
    unset($memberRecord['photo']);
    $this->assertDatabaseHas('members', $memberRecord);

    $this->assertDatabaseHas('loyalty_points', [
        'member_id' => $member->id,
        'expiry_date' => now()->addDays($this->company->loyalty_point_expiration_days)->format('Y-m-d'),
        'points' => $this->company->new_member_free_loyalty_points,
    ]);

    $this->assertDatabaseHas('loyalty_point_updates', [
        'member_id' => $member->id,
        'points' => $this->company->new_member_free_loyalty_points,
    ]);
});

test('addLoyaltyPoints method update the member loyalty points as expected', function (): void {
    $this->memberA->loyalty_points = 0;
    $this->memberA->save();

    $this->assertDatabaseHas('members', [
        'id' => $this->memberA->id,
        'loyalty_points' => 0,
    ]);

    $this->memberQueries->addLoyaltyPoints($this->memberA, 5);

    $this->assertDatabaseHas('members', [
        'id' => $this->memberA->id,
        'loyalty_points' => 5,
    ]);
});

test('storeUpdate method update the member loyalty points as expected', function (): void {
    $locationId = $this->memberA->created_location_id;
    $this->memberA->created_location_id = null;
    $this->memberA->save();

    $this->assertDatabaseHas('members', [
        'id' => $this->memberA->id,
        'created_location_id' => null,
    ]);

    $this->memberQueries->storeUpdate($this->memberA, $locationId);

    $this->assertDatabaseHas('members', [
        'id' => $this->memberA->id,
        'created_location_id' => $locationId,
    ]);
});

test('The addNewOpenMemberRegistration method should save the member details for registration.', function (): void {
    $this->company->new_member_free_loyalty_points = 50;
    $this->company->location_assignment_type = LocationAssignmentTypes::DEFAULT_LOCATION->value;
    $this->company->default_location_id = $this->location->id;
    $this->company->save();

    SiteConfiguration::factory()->create([
        'type_id' => SiteConfigurationTypes::DEFAULT_COMPANY->value,
        'value' => $this->company->id,
    ]);

    $member = Member::factory()->make([
        'company_id' => $this->company->id,
        'date_of_birth' => now()->subDays(1)->format('Y-m-d'),
    ])->toArray();

    $this->memberQueries->addNewOpenMemberRegistration($member, MemberChannelEnum::M_COMMERCE->value);

    $this->assertDatabaseHas('members', [
        'first_name' => $member['first_name'],
        'email' => $member['email'],
        'date_of_birth' => $member['date_of_birth'],
        'mobile_number' => $member['mobile_number'],
    ]);

    $this->assertDatabaseHas('loyalty_points', [
        'expiry_date' => now()->addDays($this->company->loyalty_point_expiration_days)->format('Y-m-d'),
        'points' => $this->company->new_member_free_loyalty_points,
    ]);

    $this->assertDatabaseHas('loyalty_point_updates', [
        'points' => $this->company->new_member_free_loyalty_points,
    ]);
});

test(
    'fetchMembersListForPos method return the paginated list of members',
    function (): void {
        $filterData = [
            'per_page' => 1,
            'page' => 1,
            'sort_by' => null,
            'sort_direction' => null,
            'search_text' => $this->memberA->first_name,
        ];

        $this->memberA->employee_id = Employee::factory()->create()->id;
        $this->memberA->save();

        $response = $this->memberQueries->fetchMembersListForPos($filterData, $this->company->id);

        expect($response->first()->toArray())
            ->toHaveKey('first_name', $this->memberA->first_name)
            ->toHaveKey('email', $this->memberA->email)
            ->toHaveKeys(['media', 'vouchers']);
    }
);

test('The addNewEmployeeMember method should save the member details for registration.', function (): void {
    $this->company->new_member_free_loyalty_points = 50;
    $this->company->location_assignment_type = LocationAssignmentTypes::DEFAULT_LOCATION->value;
    $this->company->default_location_id = $this->location->id;
    $this->company->save();

    $member = Member::factory()->make([
        'company_id' => $this->company->id,
        'date_of_birth' => now()->subDays(1)->format('Y-m-d'),
        'mobile_number' => 1234567890,
        'email' => 'other@gmail.com',
    ])->toArray();

    $member['address_line_1'] = '';
    $member['address_line_2'] = '';
    $member['city'] = '';
    $member['area_code'] = '';

    $this->memberQueries->addNewEmployeeMember($member, $this->company);

    $this->assertDatabaseHas('members', [
        'first_name' => $member['first_name'],
        'email' => $member['email'],
        'date_of_birth' => $member['date_of_birth'],
        'mobile_number' => $member['mobile_number'],
    ]);

    $this->assertDatabaseHas('loyalty_points', [
        'expiry_date' => now()->addDays($this->company->loyalty_point_expiration_days)->format('Y-m-d'),
        'points' => $this->company->new_member_free_loyalty_points,
    ]);

    $this->assertDatabaseHas('loyalty_point_updates', [
        'points' => $this->company->new_member_free_loyalty_points,
    ]);
});

test('removeEmployeeId method update employee_id null', function (): void {
    $employee = Employee::factory()->create();
    $this->memberA->employee_id = $employee->id;
    $this->memberA->save();

    $this->assertDatabaseHas('members', [
        'id' => $this->memberA->id,
        'employee_id' => $employee->id,
    ]);

    $this->memberQueries->removeEmployeeId($employee->id);

    $this->assertDatabaseHas('members', [
        'id' => $this->memberA->id,
        'employee_id' => null,
    ]);
});

test('addEmployeeId method set employee_id', function (): void {
    $employee = Employee::factory()->create([
        'email' => 'test@gmail.com',
        'status' => false,
    ]);

    $this->memberA->employee_id = null;
    $this->memberA->company_id = $employee->company_id;
    $this->memberA->email = $employee->email;
    $this->memberA->save();

    $this->assertDatabaseHas('members', [
        'id' => $this->memberA->id,
        'employee_id' => null,
    ]);

    $this->memberQueries->addEmployeeId($employee);

    $this->assertDatabaseHas('members', [
        'id' => $this->memberA->id,
        'employee_id' => $employee->id,
    ]);
});

test('addEmployeeId method not set employee_id when email is not found in member', function (): void {
    $employee = Employee::factory()->create();

    $this->memberA->employee_id = null;
    $this->memberA->save();

    $this->assertDatabaseHas('members', [
        'id' => $this->memberA->id,
        'employee_id' => null,
    ]);

    $this->memberQueries->addEmployeeId($employee);

    $this->assertDatabaseHas('members', [
        'id' => $this->memberA->id,
        'employee_id' => null,
    ]);
});

test('getLoyaltyPointsById return member', function (): void {
    $response = $this->memberQueries->getLoyaltyPointsById($this->memberA->id);

    expect($response->toArray())
        ->toHaveKey('loyalty_points', $this->memberA->loyalty_points);
});

test(
    'The addNewMemberRegistrationForEcommerce method should save the member details for registration.',
    function (): void {
        $this->company->new_member_free_loyalty_points = 50;
        $this->company->save();

        $member = Member::factory()->make([
            'id' => 1,
            'company_id' => $this->company->id,
            'date_of_birth' => now()->subDays(1)->format('Y-m-d'),
            'created_location_id' => $this->location->id,
        ])->toArray();

        unset($member['created_at']);
        unset($member['updated_at']);

        $saleChannel = SaleChannel::factory()->create();
        $member['image_url'] = null;

        $this->memberQueries->addNewMemberRegistrationForEcommerce(
            $member,
            MemberChannelEnum::E_COMMERCE->value,
            $saleChannel->id
        );

        $this->assertDatabaseHas('members', [
            'first_name' => $member['first_name'],
            'email' => $member['email'],
            'date_of_birth' => $member['date_of_birth'],
            'mobile_number' => $member['mobile_number'],
        ]);

        $this->assertDatabaseHas('loyalty_points', [
            'expiry_date' => now()->addDays($this->company->loyalty_point_expiration_days)->format('Y-m-d'),
            'points' => $this->company->new_member_free_loyalty_points,
        ]);
    }
);

test('decreaseExpiredLoyaltyPoints method decreases the member loyalty points as expected', function (): void {
    $this->memberA->loyalty_points = 10;
    $this->memberA->total_expired_points = 10;
    $this->memberA->save();

    $this->memberQueries->decreaseExpiredLoyaltyPoints($this->memberA, 5);

    $this->assertDatabaseHas('members', [
        'id' => $this->memberA->id,
        'loyalty_points' => 5,
        'total_expired_points' => 15,
    ]);
});

test(
    'The getMembersByMemberTypeIds method return members by type and company.',
    function (): void {
        $memberTypeId = Types::VIP->value;

        Member::factory()->create([
            'company_id' => $this->company->id,
            'type_id' => $memberTypeId,
        ]);

        $response = $this->memberQueries->getMembersByMemberTypeIds([$memberTypeId], $this->company->id);
        expect($response->first()->toArray())
            ->toHaveKey('id');
    }
);

test(
    'The getMembersByStoreIds method return members by store and company.',
    function (): void {
        $location = Location::factory()->create([
            'company_id' => $this->company->id,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $member = Member::factory()->create([
            'company_id' => $this->company->id,
            'created_location_id' => $location->id,
        ]);

        $response = $this->memberQueries->getMembersByStoreIds([$location->id], $this->company->id);
        expect($response->first()->toArray())
            ->toHaveKey('id', $member->id);
    }
);

test(
    'The getSeasonalMemberData method return member count.',
    function (): void {
        $location = Location::factory()->create([
            'company_id' => $this->company->id,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $member = Member::factory()->create([
            'company_id' => $this->company->id,
            'created_location_id' => $location->id,
        ]);

        $date = Carbon::parse(Carbon::now())->format('Y-m-d');

        $filterData['start_date'] = $date;
        $filterData['end_date'] = $date;
        $filterData['location_id'] = $location->id;

        $response = $this->memberQueries->getSeasonalMemberData($filterData, $this->company->id);
        expect($response->first()->toArray())
            ->toHaveKey('id', $member->id)
            ->toHaveKey('members_count', 1)
            ->toHaveKey('date', $date);
    }
);

test('A member can be added using the method addNewFromEcommerceOrder', function (): void {
    $newMemberRecord = Member::factory()->make([
        'company_id' => $this->company->id,
        'created_location_id' => $this->location->id,
    ])->toArray();

    unset(
        $newMemberRecord['company_id'],
        $newMemberRecord['title_id'],
        $newMemberRecord['race_id'],
        $newMemberRecord['last_name'],
        $newMemberRecord['gender_id'],
        $newMemberRecord['date_of_birth'],
        $newMemberRecord['address_line_1'],
        $newMemberRecord['address_line_2'],
        $newMemberRecord['city'],
        $newMemberRecord['area_code'],
        $newMemberRecord['company_registration_number'],
        $newMemberRecord['company_tax_number'],
        $newMemberRecord['company_address'],
        $newMemberRecord['company_phone'],
        $newMemberRecord['notes'],
        $newMemberRecord['created_by_id'],
        $newMemberRecord['created_by_type'],
        $newMemberRecord['last_purchase_date'],
        $newMemberRecord['loyalty_points'],
    );

    $this->memberQueries->addNewFromEcommerceOrder(new OrderMemberData(...$newMemberRecord), $this->company->id);

    $this->assertDatabaseHas('members', $newMemberRecord);
});

test('The findMemberByMobileNumber method return member by mobile number.',
    function (): void {
        $location = Location::factory()->create([
            'company_id' => $this->company->id,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $member = Member::factory()->create([
            'company_id' => $this->company->id,
            'created_location_id' => $location->id,
            'mobile_number' => '987656781',
        ]);

        $response = $this->memberQueries->findMemberByMobileNumber($member->mobile_number, $this->company->id);
        expect($response->toArray())
            ->toHaveKey('id', $member->id)
            ->toHaveKey('mobile_number', $member->mobile_number);
    }
);

test(
    'The findMemberByEmail method return member by email.',
    function (): void {
        $location = Location::factory()->create([
            'company_id' => $this->company->id,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $member = Member::factory()->create([
            'company_id' => $this->company->id,
            'created_location_id' => $location->id,
            'mobile_number' => '987656781',
            'email' => 'test@abc.com',
        ]);

        $response = $this->memberQueries->findMemberByEmail($member->email, $this->company->id);
        expect($response->toArray())
            ->toHaveKey('id', $member->id)
            ->toHaveKey('mobile_number', $member->mobile_number)
            ->toHaveKey('email', $member->email);
    }
);

test('The updateMemberForEcommerce method return updated member data', function (): void {
    $location = Location::factory()->create([
        'company_id' => $this->company->id,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $member = Member::factory()->create([
        'company_id' => $this->company->id,
        'created_location_id' => $location->id,
        'mobile_number' => '987656781',
        'first_name' => 'abc',
        'email' => 'test@abc.com',
    ]);

    $saleChannel = SaleChannel::factory()->create();

    MemberChannelReference::factory()->create([
        'sale_channel_id' => $saleChannel->id,
        'member_id' => $member->id,
        'external_member_id' => $member->id,
    ]);

    $updateMemberData = [
        'first_name' => $member->first_name,
        'last_name' => $member->last_name,
        'email' => $member->email,
        'mobile_number' => $member->mobile_number,
        'date_of_birth' => $member->date_of_birth,
        'image_url' => null,
        'gender_id' => 1,
    ];

    $this->memberQueries->updateMemberForEcommerce($member->id, $updateMemberData, $saleChannel->id);

    $this->assertDatabaseHas('members', [
        'first_name' => $member->first_name,
        'email' => $member->email,
    ]);
});

test('getIdByName method returns id of the member', function (): void {
    $response = $this->memberQueries->getIdByName(
        $this->memberA->first_name,
        $this->memberA->mobile_number,
        $this->company->id
    );

    $this->assertEquals($this->memberA->id, $response);
});

test('A member can be fetched by employee id and company id with membership', function (): void {
    $membership = Membership::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $this->memberA->membership_id = $membership->id;
    $this->memberA->employee_id = Employee::factory()->create()->id;
    $this->memberA->save();
    $this->memberA->membership = $membership;

    $response = $this->memberQueries->getByEmployeeIdAndCompanyIdWithMembership(
        $this->memberA->employee_id,
        $this->company->id
    );
    $this->memberA->load('membership');

    expect($response->toArray())
        ->toHaveKey('first_name', $this->memberA->first_name)
        ->toHaveKey('email', $this->memberA->email)
        ->toHaveKey('membership_id', $this->memberA->membership_id)
        ->toHaveKey('membership');
});

test('getByIdCompanyIdWithMembership method return member data with membership', function (): void {
    $membership = Membership::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $this->memberA->membership_id = $membership->id;
    $this->memberA->save();
    $this->memberA->membership = $membership;
    $response = $this->memberQueries->getByIdCompanyIdWithMembership($this->memberA->id, $this->company->id);
    $this->memberA->load('membership');

    expect($response->toArray())
        ->toHaveKey('first_name', $this->memberA->first_name)
        ->toHaveKey('email', $this->memberA->email)
        ->toHaveKey('membership_id', $this->memberA->membership_id)
        ->toHaveKey('membership');
});

test('getByEmployeeIdWithEmployee method return member data with employee', function (): void {
    $employee = Employee::factory()->create();

    $this->memberA->employee_id = $employee->id;
    $this->memberA->save();
    $this->memberA->employee = $employee;
    $response = $this->memberQueries->getByEmployeeIdWithEmployee($this->company->id, $employee->id);
    $this->memberA->load('employee');

    expect($response->toArray())
        ->toHaveKey('employee_id', $this->memberA->employee_id)
        ->toHaveKey('employee');
});

test('isMemberExistsByEmployee method return true when member exists', function (): void {
    $employee = Employee::factory()->create();

    $this->memberA->email = $employee->email;
    $this->memberA->mobile_number = $employee->mobile_number;
    $this->memberA->company_id = $employee->company_id;
    $this->memberA->save();

    $response = $this->memberQueries->isMemberExistsByEmployee($employee);
    $this->assertTrue($response);
});

test('isMemberExistsByEmployee method return false when member not exists', function (): void {
    $employee = Employee::factory()->create([
        'email' => 'abc@gmail.com',
        'mobile_number' => '123456789',
    ]);

    $this->memberA->email = 'xyz@gmail.com';
    $this->memberA->mobile_number = '123456888';
    $this->memberA->company_id = $employee->company_id;
    $this->memberA->save();

    $response = $this->memberQueries->isMemberExistsByEmployee($employee);
    $this->assertFalse($response);
});

test('getByEmployee method return member', function (): void {
    $employee = Employee::factory()->create();

    $this->memberA->email = $employee->email;
    $this->memberA->mobile_number = $employee->mobile_number;
    $this->memberA->company_id = $employee->company_id;
    $this->memberA->save();

    $response = $this->memberQueries->getByEmployee($employee);
    expect($response->toArray())
        ->toHaveKey('id', $this->memberA->id)
        ->toHaveKey('email', $this->memberA->email);
});

test('getByEmployee method return null', function (): void {
    $employee = Employee::factory()->create([
        'email' => 'abc@gmail.com',
        'mobile_number' => '123456789',
    ]);

    $this->memberA->email = 'xyz@gmail.com';
    $this->memberA->mobile_number = '123456888';
    $this->memberA->company_id = $employee->company_id;
    $this->memberA->save();

    $response = $this->memberQueries->getByEmployee($employee);
    $this->assertNull($response);
});

test(
    'getPaginatedListForEcommerce method return the paginated list of members',
    function (): void {
        $filterData = [
            'per_page' => 1,
            'page' => 1,
            'sort_by' => null,
            'sort_direction' => null,
            'search_text' => '',
            'after_updated_at' => null,
            'mobile_number' => null,
            'email' => null,
        ];

        $response = $this->memberQueries->getPaginatedListForEcommerce($filterData, $this->company->id);

        expect($response->first()->toArray())
            ->toHaveKey('first_name', $this->memberB->first_name)
            ->toHaveKey('email', $this->memberB->email);
    }
);

test('existsByMobileOrEmail method returns boolean as expected', function (): void {
    $response = $this->memberQueries->existsByMobileOrEmail($this->memberA->mobile_number, null, $this->company->id);
    $this->assertTrue($response);

    $response = $this->memberQueries->existsByMobileOrEmail(null, $this->memberA->email, $this->company->id);
    $this->assertTrue($response);

    $response = $this->memberQueries->existsByMobileOrEmail('ABCDEFF', null, $this->company->id);
    $this->assertFalse($response);
});

test('getByIdForEcommerce method return member', function (): void {
    $this->memberA->channel_id = MemberChannelEnum::E_COMMERCE->value;
    $this->memberA->save();

    $response = $this->memberQueries->getByIdForEcommerce($this->memberA->id, $this->company->id);

    expect($response->toArray())
        ->toHaveKey('id', $this->memberA->id)
        ->toHaveKey('company_id', $this->memberA->company_id)
        ->toHaveKey('channel_id', $this->memberA->channel_id)
        ->toHaveKey('created_location_id', $this->memberA->created_location_id);
});

test(
    'getMemberByMobileNumberForEcommerce method return the list',
    function (): void {
        $this->memberA->channel_id = MemberChannelEnum::E_COMMERCE->value;
        $this->memberA->save();

        $response = $this->memberQueries->getMemberByMobileNumberForEcommerce(
            $this->memberA->mobile_number,
            $this->company->id
        );

        expect($response->first()->toArray())
            ->toHaveKey('first_name', $this->memberA->first_name)
            ->toHaveKey('email', $this->memberA->email);
    }
);

test(
    'getMemberIdsForSalesChannel method return the member id list',
    function (): void {
        $memberGroup = MemberGroup::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'test group',
        ]);

        $member = Member::factory()->create([
            'company_id' => $this->company->id,
            'channel_id' => MemberChannelEnum::E_COMMERCE->value,
        ]);

        MemberGroupMember::factory()->create([
            'member_id' => $member->id,
            'member_group_id' => $memberGroup->id,
        ]);

        $filterData = [
            'sort_by' => 'id',
            'sort_direction' => 'asc',
            'per_page' => 10,
            'after_updated_at' => null,
            'member_group_id' => $memberGroup->id,
        ];

        $response = $this->memberQueries->getMemberIdsForSalesChannel($filterData);

        expect($response->first()->toArray())
            ->toHaveKey('id', $member->id);
    }
);

test(
    'getActiveMemberDetailsById method call and return proper response',
    function (): void {
        $response = $this->memberQueries->getActiveMemberDetailsById($this->memberA->id, $this->company->id);

        expect($response->toArray())
            ->toHaveKey('id', $this->memberA->id)
            ->toHaveKey('company_id', $this->memberA->company_id)
            ->toHaveKey('first_name', $this->memberA->first_name)
            ->toHaveKey('mobile_number', $this->memberA->mobile_number)
            ->toHaveKey('loyalty_points', $this->memberA->loyalty_points);
    }
);

test('call changeStatus method change the member status', function (): void {
    $this->memberQueries->changeStatus($this->memberA->id);

    $this->assertDatabaseHas('members', [
        'id' => $this->memberA->id,
        'status' => Status::INACTIVE->value,
    ]);
});

test('call checkEmailExists method return response based on status', function (): void {
    $member = Member::factory()->create();
    $response = $this->memberQueries->checkEmailExists($member->email);
    expect($response)->toBeTrue();

    $member->status = Status::INACTIVE->value;
    $member->save();

    $response = $this->memberQueries->checkEmailExists($member->email);
    expect($response)->toBeFalse();
});

test('call checkMobileNumberExists method return response based on status', function (): void {
    $member = Member::factory()->create();
    $response = $this->memberQueries->checkMobileNumberExists($member->mobile_number);
    expect($response)->toBeTrue();

    $member->status = Status::INACTIVE->value;
    $member->save();

    $response = $this->memberQueries->checkMobileNumberExists($member->mobile_number);
    expect($response)->toBeFalse();
});

test('call validateEmailOtp method return active member records with relation.', function (): void {
    $data['otp'] = '9999';
    $this->memberA->otp = $data['otp'];
    $this->memberA->save();

    $response = $this->memberQueries->validateEmailOtp($data, $this->memberA->email);

    expect($response->toArray())
        ->toHaveKeys(
            [
                'id',
                'title_id',
                'type_id',
                'gender_id',
                'race_id',
                'first_name',
                'last_name',
                'email',
                'mobile_number',
                'date_of_birth',
                'total_orders',
                'company_id',
                'company_name',
                'company_registration_number',
                'company_tax_number',
                'company_phone',
                'notes',
                'spent_till_now',
                'last_purchase_date',
                'loyalty_points',
                'membership_id',
                'card_number',
                'created_location_id',
                'created_at',
                'otp',
                'otp_expire_date',
                'employee_id',
                'created_in_location',
                'membership',
                'company',
                'media',
                'member_addresses',
                'primary_member_address',
            ]
        );
});

test('call validateMobileOtp method return active member records with relation.', function (): void {
    $data['otp'] = '9999';
    $this->memberA->otp = $data['otp'];
    $this->memberA->save();

    $response = $this->memberQueries->validateMobileOtp($data, $this->memberA->mobile_number);

    expect($response->toArray())
        ->toHaveKeys(
            [
                'id',
                'title_id',
                'type_id',
                'gender_id',
                'race_id',
                'first_name',
                'last_name',
                'email',
                'mobile_number',
                'date_of_birth',
                'total_orders',
                'company_id',
                'company_name',
                'company_registration_number',
                'company_tax_number',
                'company_phone',
                'notes',
                'spent_till_now',
                'last_purchase_date',
                'loyalty_points',
                'membership_id',
                'card_number',
                'created_location_id',
                'created_at',
                'otp',
                'otp_expire_date',
                'employee_id',
                'created_in_location',
                'membership',
                'company',
                'media',
                'member_addresses',
                'primary_member_address',
            ]
        );
});

test('call topTenSellingMembers method return member purchase amounts.', function (): void {
    $date = Carbon::now();

    $counter = Counter::factory()->create([
        'location_id' => $this->location->id,
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
        'opened_by_pos_at' => $date->subMonth()->format('Y-m-d h:i:s'),
    ]);

    Sale::factory()->create([
        'member_id' => $this->memberA->id,
        'counter_update_id' => $counterUpdate->id,
        'happened_at' => $date,
        'status' => SaleStatus::REGULAR_SALE->value,
    ]);

    $response = $this->memberQueries->topTenSellingMembers(
        $this->company->id,
        0,
        now()->subYear()->startOfYear()->format('Y-m-d'),
        now()->format('Y-m-d')
    );

    expect($response->first()->toArray())
        ->toHaveKeys(['id', 'first_name', 'last_name', 'total_sales']);
});

test('call checkCardNumberExists method return boolean.', function (): void {
    $response = $this->memberQueries->checkCardNumberExists($this->memberA->card_number);

    expect($response)->toBe(true);
});

test('call findMemberByMobileNumberOrCardNumber method return member.', function (): void {
    $memberData = [
        'mobile_number' => $this->memberA->mobile_number,
        'card_number' => null,
    ];
    $response = $this->memberQueries->findMemberByMobileNumberOrCardNumber($memberData, $this->company->id);

    expect($response->toArray())->toHaveKeys(['id', 'mobile_number']);
});

test('call getMemberNameForFilter method return member name.', function (): void {
    $memberData = [
        'mobile_number' => $this->memberA->mobile_number,
        'card_number' => null,
    ];
    $response = $this->memberQueries->getMemberNameForFilter($this->memberA->id);
    expect($response)->toBeString();
});

test('it can create or update a member from Azentio Member', function (): void {
    $company = Company::factory()->create([
        'name' => 'Order Test',
    ]);

    $member = Member::factory()->make([
        'company_id' => $company->getKey(),
        'email' => 'azentio@example.com',
    ]);

    $memberData = new AzentioMemberData(
        $member->first_name,
        $company->getKey(),
        $member->mobile_number,
        $member->card_number,
        $member->email,
    );

    $this->memberQueries->createOrUpdateMemberFromAzentioMember($memberData->toArray());

    $this->assertDatabaseHas(Member::class, [
        'email' => $member->email,
        'company_id' => $company->getKey(),
        'first_name' => $member->first_name,
        'is_azentio_member' => 1,
    ]);

    $memberData = new AzentioMemberData(
        'Hey New Member Name',
        $company->getKey(),
        $member->mobile_number,
        $member->card_number,
        $member->email,
    );

    $this->memberQueries->createOrUpdateMemberFromAzentioMember($memberData->toArray());

    $this->assertDatabaseHas(Member::class, [
        'email' => $member->email,
        'company_id' => $company->getKey(),
        'first_name' => 'Hey New Member Name',
        'is_azentio_member' => 1,
    ]);
});
