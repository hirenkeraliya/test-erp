<?php

declare(strict_types=1);

namespace App\Domains\OldDataMigrateJob\Jobs;

use App\Models\Company;
use App\Models\Region;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class OldRegionDataMigrateJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(): void
    {
        try {
            $company = Company::query()->firstOrFail();

            $oldRegions = DB::connection('old_data_mysql')
                ->table('tblmaregion')
                ->get();

            foreach ($oldRegions as $key => $oldRegion) {
                $region = Region::query()
                    ->where('name', trim((string) $oldRegion->RegionName))
                    ->where('company_id', $company->id)
                    ->first();

                $regionCode = trim((string) $oldRegion->RegionCode);

                if ($region) {
                    $region->code = $regionCode;
                    $region->save();
                    continue;
                }

                $isRegionCodeExist = Region::query()
                    ->where('code', $regionCode)
                    ->where('company_id', $company->id)
                    ->exists();

                $regionCode = $isRegionCodeExist ? $regionCode . '-' . $key : $regionCode;

                $createDate = $oldRegion->CreateDate;
                if ($createDate || '0000-00-00 00:00:00' == $createDate || '' == $createDate) {
                    $createDate = now()->format('Y-m-d H:i:s');
                }

                $modifyDate = $oldRegion->ModifyDate;
                if ($modifyDate || '0000-00-00 00:00:00' == $modifyDate || '' == $modifyDate) {
                    $modifyDate = now()->format('Y-m-d H:i:s');
                }

                DB::table('regions')->insert([
                    'company_id' => $company->id,
                    'name' => trim((string) $oldRegion->RegionName),
                    'code' => $regionCode,
                    'created_at' => $createDate,
                    'updated_at' => $modifyDate,
                ]);
            }
        } catch (Throwable $throwable) {
            Log::error([
                'error_name' => 'Old Region date migration Job  error:',
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
            ]);
        }
    }
}
