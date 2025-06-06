<?php

declare(strict_types=1);

use App\Domains\Member\DataObjects\RegisterMemberData;
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
    ]);
});

test('unique email validation works while adding.', function (): void {
    $records = createMember($this->member->email, $this->member->mobile_number, $this->companyId);
    $request = new Request($records);

    $request->validate(RegisterMemberData::rules());
})->throws(ValidationException::class);

test('unique mobile number validation works while adding.', function (): void {
    $records = createMember('new@gmial.com', $this->member->mobile_number, $this->companyId);
    $request = new Request($records);

    $request->validate(RegisterMemberData::rules());
})->throws(ValidationException::class);

test('date of birth before today validation works while adding.', function (): void {
    $records = createMember('new@gmail.com', '987654321', $this->companyId, now()->format('Y-m-d'));
    $request = new Request($records);

    $request->validate(RegisterMemberData::rules());
})->throws(ValidationException::class);

function createMember(string $email, string $mobileNumber, int $companyId, ?string $date_of_birth = null): array
{
    return Member::factory()->make([
        'company_id' => $companyId,
        'email' => $email,
        'mobile_number' => $mobileNumber,
        'date_of_birth' => $date_of_birth ?? now()->subDay()->format('Y-m-d'),
    ])->toArray();
}
