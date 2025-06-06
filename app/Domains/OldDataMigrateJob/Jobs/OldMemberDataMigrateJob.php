<?php

declare(strict_types=1);

namespace App\Domains\OldDataMigrateJob\Jobs;

use App\CommonFunctions;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Member\Enums\Genders;
use App\Domains\Member\Enums\Status;
use App\Domains\Member\Enums\Types;
use App\Models\Company;
use App\Models\Location;
use App\Models\Member;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class OldMemberDataMigrateJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private int $companyId;

    public function __construct(
        private array $oldMemberCodes,
    ) {
        $this->companyId = Company::query()->firstOrFail()->id;
    }

    public function handle(): void
    {
        try {
            $locations = Location::query()
                ->where('company_id', $this->companyId)
                ->where('type_id', LocationTypes::STORE->value)
                ->get();

            $oldMembers = DB::connection('old_data_mysql')
                ->table('tblmember')
                ->whereIn('MemberCode', $this->oldMemberCodes)
                ->get();

            foreach ($oldMembers as $key => $oldMember) {
                if (trim((string) $oldMember->Mobile) === '') {
                    continue;
                }

                $member = Member::query()
                    ->where('mobile_number', trim((string) $oldMember->Mobile))
                    ->where('company_id', $this->companyId)
                    ->first();

                if ($member) {
                    continue;
                }

                $code = trim((string) $oldMember->MemberCode);

                $isCodeExist = Member::query()
                    ->where('card_number', $code)
                    ->where('company_id', $this->companyId)
                    ->exists();

                $code = $isCodeExist ? $code . '-' . $key : $code;

                $email = trim((string) $oldMember->eMail);

                $isEmailExist = Member::query()
                    ->where('email', $email)
                    ->where('company_id', $this->companyId)
                    ->exists();

                $email = $isEmailExist ? null : $email;

                $createDate = $oldMember->CreateDate;
                if ($createDate || '0000-00-00 00:00:00' == $createDate || '' == $createDate) {
                    $createDate = now()->format('Y-m-d H:i:s');
                }

                $modifyDate = $oldMember->ModifyDate;
                if ($modifyDate || '0000-00-00 00:00:00' == $modifyDate || '' == $modifyDate) {
                    $modifyDate = now()->format('Y-m-d H:i:s');
                }

                $memberId = DB::table('members')->insertGetId([
                    'company_id' => $this->companyId,
                    'created_at' => $createDate,
                    'updated_at' => $modifyDate,
                    'type_id' => Types::REGULAR->value,
                    'created_location_id' => $this->getCreatedLocationId($locations, $oldMember),
                    'card_number' => $code,
                    'first_name' => trim((string) $oldMember->MemberName),
                    'date_of_birth' => trim((string) $oldMember->DateOfBirth),
                    'mobile_number' => trim((string) $oldMember->Mobile),
                    'email' => $email,
                    'status' => 1 === $oldMember->Active ? Status::ACTIVE->value : Status::DELETED_BY_ADMIN->value,
                    'gender_id' => trim(
                        (string) $oldMember->Sex
                    ) === 'M' ? Genders::MALE->value : Genders::FEMALE->value,
                ]);

                if ($memberId) {
                    DB::table('member_addresses')->insert([
                        'member_id' => $memberId,
                        'name' => trim((string) $oldMember->MemberName),
                        'contact_mobile_number' => trim((string) $oldMember->Mobile),
                        'contact_email' => $email,
                        'address_line_1' => trim((string) $oldMember->Address1),
                        'address_line_2' => trim((string) $oldMember->Address2),
                        'city' => trim((string) $oldMember->Address3),
                        'area_code' => trim((string) $oldMember->ZipCode),
                        'is_primary' => true,
                        'created_at' => $createDate,
                        'updated_at' => $modifyDate,
                    ]);
                }
            }
        } catch (Throwable $throwable) {
            Log::error([
                'error_name' => 'Old Member date migration Job  error:',
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
            ]);
        }
    }

    public function generateUniqueCardNumber(): string
    {
        $cardNumber = CommonFunctions::getTwelveDigitNumber();

        $existCardNumbers = Member::whereCaseSensitive('card_number', $cardNumber)->exists();

        if ($existCardNumbers) {
            return $this->generateUniqueCardNumber();
        }

        return $cardNumber;
    }

    public function getCreatedLocationId(Collection $locations, object $oldMember): ?int
    {
        $location = $locations
            ->first(
                fn ($location): bool => strcasecmp(
                    $location->code,
                    /* @phpstan-ignore-next-line */
                    trim((string) $oldMember->CreateLocation)
                ) === 0
            );

        if ($location) {
            return $location->id;
        }

        $oldLocation = DB::connection('old_data_mysql')
            ->table('tblmalocation')
            /* @phpstan-ignore-next-line */
            ->where('LocationCode', $oldMember->CreateLocation)
            ->first();

        if ($oldLocation) {
            $location = $locations
                ->first(
                    fn ($location): bool => strcasecmp(
                        $location->name,
                        /* @phpstan-ignore-next-line */
                        trim((string) $oldLocation->LocationName)
                    ) === 0
                );

            if ($location) {
                return $location->id;
            }
        }

        return null;
    }
}
