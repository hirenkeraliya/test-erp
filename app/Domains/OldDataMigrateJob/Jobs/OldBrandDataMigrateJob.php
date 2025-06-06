<?php

declare(strict_types=1);

namespace App\Domains\OldDataMigrateJob\Jobs;

use App\Models\Brand;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class OldBrandDataMigrateJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(): void
    {
        try {
            $oldBrands = DB::connection('old_data_mysql')
                ->table('tblinvbrand')
                ->get();

            foreach ($oldBrands as $oldBrand) {
                $isBrandExist = Brand::query()
                    ->where('code', trim((string) $oldBrand->BrandCode))
                    ->orWhere('name', trim((string) $oldBrand->BrandName))
                    ->exists();

                $createDate = $oldBrand->CreateDate;
                if ($createDate || '0000-00-00 00:00:00' == $createDate || '' == $createDate) {
                    $createDate = now()->format('Y-m-d H:i:s');
                }

                $modifyDate = $oldBrand->ModifyDate;
                if ($modifyDate || '0000-00-00 00:00:00' == $modifyDate || '' == $modifyDate) {
                    $modifyDate = now()->format('Y-m-d H:i:s');
                }

                if (! $isBrandExist) {
                    DB::table('brands')->insert([
                        'name' => trim((string) $oldBrand->BrandName),
                        'code' => trim((string) $oldBrand->BrandCode),
                        'created_at' => $createDate,
                        'updated_at' => $modifyDate,
                    ]);
                }
            }
        } catch (Throwable $throwable) {
            Log::error([
                'error_name' => 'Old brand date migration Job  error:',
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
            ]);
        }
    }
}
