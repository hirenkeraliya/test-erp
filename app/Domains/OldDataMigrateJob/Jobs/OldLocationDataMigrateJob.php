<?php

declare(strict_types=1);

namespace App\Domains\OldDataMigrateJob\Jobs;

use App\Models\Company;
use App\Models\Country;
use App\Models\Location;
use App\Models\Region;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class OldLocationDataMigrateJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(): void
    {
        try {
            $company = Company::query()->firstOrFail();
            $countryId = Country::query()->where('name', 'Malaysia')->first()?->id;

            $oldLocations = DB::connection('old_data_mysql')
                ->table('tblmalocation')
                ->get();

            foreach ($oldLocations as $key => $oldLocation) {
                $store = Location::query()
                    ->where('name', trim((string) $oldLocation->LocationName))
                    ->where('company_id', $company->id)
                    ->first();

                $locationCode = trim((string) $oldLocation->LocationCode);

                if ($store) {
                    continue;
                }

                $regionId = Region::query()
                    ->where('code', trim((string) $oldLocation->RegionCode))
                    ->where('company_id', $company->id)
                    ->first()
                    ?->id;

                $isStoreCodeExist = Location::query()
                    ->where('code', $locationCode)
                    ->where('company_id', $company->id)
                    ->exists();

                $locationCode = $isStoreCodeExist ? $locationCode . '-' . $key : $locationCode;

                $telePhone = trim((string) $oldLocation->TelePhone);

                $telePhone = $telePhone ?: '601112103616' . $key;

                $isStorePhoneExist = Location::query()
                    ->where('phone', $telePhone)
                    ->where('company_id', $company->id)
                    ->exists();

                $telePhone = $isStorePhoneExist ? $telePhone . '-' . $key : $telePhone;

                $createDate = $oldLocation->CreateDate;
                if ($createDate || '0000-00-00 00:00:00' == $createDate || '' == $createDate) {
                    $createDate = now()->format('Y-m-d H:i:s');
                }

                $modifyDate = $oldLocation->ModifyDate;
                if ($modifyDate || '0000-00-00 00:00:00' == $modifyDate || '' == $modifyDate) {
                    $modifyDate = now()->format('Y-m-d H:i:s');
                }

                DB::table('stores')->insert([
                    'company_id' => $company->id,
                    'name' => trim((string) $oldLocation->LocationName),
                    'code' => $locationCode,
                    'region_id' => $regionId,
                    'created_at' => $createDate,
                    'updated_at' => $modifyDate,
                    'country_id' => $countryId,
                    'address_line_2' => trim((string) $oldLocation->Address2) . ', ' . trim(
                        (string) $oldLocation->Address3
                    ),
                    'fax' => trim((string) $oldLocation->Fax),
                    'address_line_1' => trim((string) $oldLocation->Address1),
                    'registration_number' => $company->social_security_number,
                    'sst_number' => $company->social_security_number,
                    'email' => $company->email,
                    'sales_tax_percentage' => 0,
                    'sales_return_days_limit' => 7,
                    'is_automatic_day_close' => 0,
                    'receipt_footer' => '',
                    'open_time' => '10:00:00',
                    'close_time' => '18:00:00',
                    'city' => 'Melaka',
                    'area_code' => '81200',
                    'phone' => $telePhone,
                ]);
            }
        } catch (Throwable $throwable) {
            Log::error([
                'error_name' => 'Old Location date migration Job  error:',
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
            ]);
        }
    }
}
