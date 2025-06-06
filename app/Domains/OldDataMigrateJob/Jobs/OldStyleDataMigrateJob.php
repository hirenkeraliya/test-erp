<?php

declare(strict_types=1);

namespace App\Domains\OldDataMigrateJob\Jobs;

use App\Models\Company;
use App\Models\Style;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class OldStyleDataMigrateJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(): void
    {
        try {
            $company = Company::query()->firstOrFail();

            $oldStyles = DB::connection('old_data_mysql')
                ->table('tblinventorydesign')
                ->get();

            foreach ($oldStyles as $oldStyle) {
                $isStyleExist = Style::query()
                    ->where('name', trim((string) $oldStyle->Description))
                    ->where('company_id', $company->id)
                    ->exists();

                if ($isStyleExist) {
                    DB::table('styles')
                        ->where('company_id', $company->id)
                        ->where('name', trim((string) $oldStyle->Description))
                        ->whereNull('code')
                        ->update([
                            'code' => trim((string) $oldStyle->DesignCode),
                        ]);

                    continue;
                }

                $createDate = $oldStyle->CreateDate;
                if ($createDate || '0000-00-00 00:00:00' == $createDate || '' == $createDate) {
                    $createDate = now()->format('Y-m-d H:i:s');
                }

                $modifyDate = $oldStyle->ModifyDate;
                if ($modifyDate || '0000-00-00 00:00:00' == $modifyDate || '' == $modifyDate) {
                    $modifyDate = now()->format('Y-m-d H:i:s');
                }

                DB::table('styles')->insert([
                    'company_id' => $company->id,
                    'name' => trim((string) $oldStyle->Description),
                    'code' => trim((string) $oldStyle->DesignCode),
                    'created_at' => $createDate,
                    'updated_at' => $modifyDate,
                ]);
            }
        } catch (Throwable $throwable) {
            Log::error([
                'error_name' => 'Old style date migration Job  error:',
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
            ]);
        }
    }
}
