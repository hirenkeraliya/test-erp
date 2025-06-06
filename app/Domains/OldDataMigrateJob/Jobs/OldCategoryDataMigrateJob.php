<?php

declare(strict_types=1);

namespace App\Domains\OldDataMigrateJob\Jobs;

use App\Models\Category;
use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class OldCategoryDataMigrateJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(): void
    {
        try {
            $company = Company::query()->firstOrFail();

            $oldCategories = DB::connection('old_data_mysql')
                ->table('tblinvcategory')
                ->get();

            foreach ($oldCategories as $key => $oldCategory) {
                $isCategoryExist = Category::query()
                    ->where('name', trim((string) $oldCategory->CategoryName))
                    ->where('company_id', $company->id)
                    ->exists();

                $isCategoryCodeExist = Category::query()
                    ->where('code', trim((string) $oldCategory->CategoryCode))
                    ->where('company_id', $company->id)
                    ->exists();

                if (! $isCategoryExist) {
                    $categoryCode = $isCategoryCodeExist ? $oldCategory->CategoryCode . '-' . $key : $oldCategory->CategoryCode;

                    $createDate = $oldCategory->CreateDate;
                    if ($createDate || '0000-00-00 00:00:00' == $createDate || '' == $createDate) {
                        $createDate = now()->format('Y-m-d H:i:s');
                    }

                    $modifyDate = $oldCategory->ModifyDate;
                    if ($modifyDate || '0000-00-00 00:00:00' == $modifyDate || '' == $modifyDate) {
                        $modifyDate = now()->format('Y-m-d H:i:s');
                    }

                    DB::table('categories')->insert([
                        'company_id' => $company->id,
                        'name' => trim((string) $oldCategory->CategoryName),
                        'code' => trim((string) $categoryCode),
                        'created_at' => $createDate,
                        'updated_at' => $modifyDate,
                    ]);
                }
            }
        } catch (Throwable $throwable) {
            Log::error([
                'error_name' => 'Old Category date migration Job  error:',
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
            ]);
        }
    }
}
