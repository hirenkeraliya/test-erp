<?php

declare(strict_types=1);

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Member\DataObjects\MemberData;
use App\Models\Company;
use App\Models\Location;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->companyId = Company::factory()->create()->id;

    $this->location = Location::factory()->create([
        'company_id' => $this->companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->member = Member::factory()->create([
        'company_id' => $this->companyId,
        'email' => 'email@email.com',
        'mobile_number' => '012345678911',
    ]);
});

test('company wise unique email validation works while adding.', function (): void {
    Storage::fake('public');

    $uploadedFile = UploadedFile::fake()->image('avatar.jpg');
    setCompanyIdInSession($this->companyId);

    $records = createMemberRecord(
        $this->member->email,
        $this->member->mobile_number,
        $this->companyId,
        $this->location->id
    );
    $records['photo'] = $uploadedFile;
    $request = new Request($records);

    $request->validate(MemberData::rules($request));
})->throws(ValidationException::class);

test('user can add different mobile_number with same company.', function (): void {
    Storage::fake('public');

    $uploadedFile = UploadedFile::fake()->image('avatar.jpg');
    setCompanyIdInSession($this->companyId);

    $records = createMemberRecord('welcome@email.com', '601999999999', $this->companyId, $this->location->id);
    $records['photo'] = $uploadedFile;
    $request = new Request($records);

    $request->validate(MemberData::rules($request));

    $this->assertTrue(true);
});

test('user can add a member with the same name for the different company.', function (): void {
    $companyB = Company::factory()->create();
    $location = Location::factory()->create([
        'company_id' => $companyB->id,
        'type_id' => LocationTypes::STORE->value,
    ]);

    setCompanyIdInSession($companyB->id);
    $memberDetails = createMemberRecord('welcome@email.com', '601999999999', $this->companyId, $location->id);
    $request = new Request($memberDetails);
    $request->validate(MemberData::rules($request));
    $this->assertTrue(true);
});

function createMemberRecord(string $email, string $mobileNumber, int $companyId, int $locationId): array
{
    return Member::factory()->make([
        'company_id' => $companyId,
        'email' => $email,
        'mobile_number' => $mobileNumber,
        'created_location_id' => $locationId,
    ])->toArray();
}
