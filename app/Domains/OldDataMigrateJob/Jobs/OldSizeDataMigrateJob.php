<?php

declare(strict_types=1);

namespace App\Domains\OldDataMigrateJob\Jobs;

use App\Models\Company;
use App\Models\Size;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class OldSizeDataMigrateJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(): void
    {
        try {
            $company = Company::query()->firstOrFail();

            $oldSizes = DB::connection('old_data_mysql')
                ->table('tblinventorysize')
                ->get();

            foreach ($oldSizes as $oldSize) {
                $isSizeExist = Size::query()
                    ->where('name', trim((string) $oldSize->Description))
                    ->where('company_id', $company->id)
                    ->exists();

                if ($isSizeExist) {
                    DB::table('sizes')
                        ->where('company_id', $company->id)
                        ->where('name', trim((string) $oldSize->Description))
                        ->whereNull('code')
                        ->update([
                            'code' => trim((string) $oldSize->SizeCode),
                        ]);

                    continue;
                }

                $createDate = $oldSize->CreateDate;
                if ($createDate || '0000-00-00 00:00:00' == $createDate || '' == $createDate) {
                    $createDate = now()->format('Y-m-d H:i:s');
                }

                $modifyDate = $oldSize->ModifyDate;
                if ($modifyDate || '0000-00-00 00:00:00' == $modifyDate || '' == $modifyDate) {
                    $modifyDate = now()->format('Y-m-d H:i:s');
                }

                DB::table('sizes')->insert([
                    'company_id' => $company->id,
                    'name' => trim((string) $oldSize->Description),
                    'code' => trim((string) $oldSize->SizeCode),
                    'sort_order' => trim((string) $oldSize->SortCode),
                    'created_at' => $createDate,
                    'updated_at' => $modifyDate,
                ]);
            }
        } catch (Throwable $throwable) {
            Log::error([
                'error_name' => 'Old size date migration Job  error:',
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
            ]);
        }
    }
}
