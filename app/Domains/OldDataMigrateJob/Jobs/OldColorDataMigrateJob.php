<?php

declare(strict_types=1);

namespace App\Domains\OldDataMigrateJob\Jobs;

use App\Models\Color;
use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class OldColorDataMigrateJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(): void
    {
        try {
            $company = Company::query()->firstOrFail();

            $oldColors = DB::connection('old_data_mysql')
                ->table('tblinventorycolor')
                ->get();

            foreach ($oldColors as $key => $oldColor) {
                $isExistColor = Color::query()
                    ->where('name', trim((string) $oldColor->Description))
                    ->where('company_id', $company->id)
                    ->exists();

                if ($isExistColor) {
                    DB::table('colors')
                        ->where('company_id', $company->id)
                        ->where('name', trim((string) $oldColor->Description))
                        ->whereNull('code')
                        ->update([
                            'code' => trim((string) $oldColor->ColorCode),
                        ]);

                    continue;
                }

                $isExistColorCode = Color::query()
                    ->where('code', trim((string) $oldColor->ColorCode))
                    ->where('company_id', $company->id)
                    ->exists();

                $colorShortCode = $isExistColorCode ? $oldColor->ColorCode . '-' . $key : $oldColor->ColorCode;
                $colorCode = trim((string) $oldColor->color);
                if ($colorCode && ctype_digit($colorCode)) {
                    $colorCode = dechex((int) $colorCode);
                    if (strlen($colorCode) <= 4 && ctype_digit($colorCode)) {
                        $colorCode = '00' . $colorCode;
                    }

                    if (strlen($colorCode) <= 2) {
                        $colorCode = '00' . $colorCode;
                    }
                }

                $createDate = $oldColor->CreateDate;
                if ($createDate || '0000-00-00 00:00:00' == $createDate || '' == $createDate) {
                    $createDate = now()->format('Y-m-d H:i:s');
                }

                $modifyDate = $oldColor->ModifyDate;
                if ($modifyDate || '0000-00-00 00:00:00' == $modifyDate || '' == $modifyDate) {
                    $modifyDate = now()->format('Y-m-d H:i:s');
                }

                DB::table('colors')->insert([
                    'company_id' => $company->id,
                    'name' => trim((string) $oldColor->Description),
                    'code' => trim((string) $colorShortCode),
                    'color_code' => '' !== $colorCode ? '#' . $colorCode : null,
                    'created_at' => $createDate,
                    'updated_at' => $modifyDate,
                ]);
            }
        } catch (Throwable $throwable) {
            Log::error([
                'error_name' => 'Old color date migration Job  error:',
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
            ]);
        }
    }
}
