<?php

declare(strict_types=1);

use App\Domains\Member\DataObjects\PosMemberData;
use App\Models\Company;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->companyId = Company::factory()->create()->id;

    $this->member = Member::factory()->create([
        'company_id' => $this->companyId,
        'email' => 'email@email.com',
        'mobile_number' => '012345678911',
        'card_number' => 'abcd1234defg',
    ]);
});

test('company wise unique email validation works while adding.', function (): void {
    $records = createPosMemberRecord(
        $this->member->email,
        $this->member->mobile_number,
        $this->companyId,
        $this->member->card_number
    );
    $request = new Request($records);

    $request->validate(PosMemberData::rules($request, $this->companyId));
})->throws(ValidationException::class);

test('company wise unique card number validation works while adding.', function (): void {
    $records = createPosMemberRecord(
        $this->member->email,
        $this->member->mobile_number,
        $this->companyId,
        $this->member->card_number
    );
    $request = new Request($records);

    $request->validate(PosMemberData::rules($request, $this->companyId));
})->throws(ValidationException::class);

test('user can add different mobile_number with same company.', function (): void {
    $records = createPosMemberRecord('welcome@email.com', '601112145678', $this->companyId, 'abcd5678efgh');
    $request = new Request($records);

    $request->validate(PosMemberData::rules($request, $this->companyId));

    $this->assertTrue(true);
});

test('user can add a member with the same email for the different company.', function (): void {
    $company2 = Company::factory()->create();
    $memberDetails = createPosMemberRecord('email@email.com', '601112145678', $company2->id, 'abcd1234defg');
    $request = new Request($memberDetails);
    $request->validate(PosMemberData::rules($request, $company2->id));
    $this->assertTrue(true);
});

test('user can add a member with the same card number for the different company.', function (): void {
    $company2 = Company::factory()->create();
    $memberDetails = createPosMemberRecord('email@email.com', '601112145678', $company2->id, 'abcd1234defg');
    $request = new Request($memberDetails);
    $request->validate(PosMemberData::rules($request, $company2->id));
    $this->assertTrue(true);
});

function createPosMemberRecord(string $email, string $mobileNumber, int $companyId, string $cardNumber): array
{
    return Member::factory()->make([
        'company_id' => $companyId,
        'email' => $email,
        'mobile_number' => $mobileNumber,
        'card_number' => $cardNumber,
    ])->toArray();
}
