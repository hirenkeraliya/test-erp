<?php

declare(strict_types=1);

namespace App\Domains\OldDataMigrateJob\Jobs;

use App\Models\Company;
use App\Models\Department;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class OldDepartmentDataMigrateJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(): void
    {
        try {
            $company = Company::query()->firstOrFail();

            $oldDepartments = DB::connection('old_data_mysql')
                ->table('tblinvdepartment')
                ->get();

            foreach ($oldDepartments as $key => $oldDepartment) {
                $isDepartmentExist = Department::query()
                    ->where('name', trim((string) $oldDepartment->DeptName))
                    ->where('company_id', $company->id)
                    ->exists();

                $isDepartmentCodeExist = Department::query()
                    ->where('code', trim((string) $oldDepartment->DeptCode))
                    ->where('company_id', $company->id)
                    ->exists();

                if (! $isDepartmentExist) {
                    $departmentCode = $isDepartmentCodeExist ? $oldDepartment->DeptCode . '-' . $key : $oldDepartment->DeptCode;

                    $createDate = $oldDepartment->CreateDate;
                    if ($createDate || '0000-00-00 00:00:00' == $createDate || '' == $createDate) {
                        $createDate = now()->format('Y-m-d H:i:s');
                    }

                    $modifyDate = $oldDepartment->ModifyDate;
                    if ($modifyDate || '0000-00-00 00:00:00' == $modifyDate || '' == $modifyDate) {
                        $modifyDate = now()->format('Y-m-d H:i:s');
                    }

                    DB::table('departments')->insert([
                        'company_id' => $company->id,
                        'name' => trim((string) $oldDepartment->DeptName),
                        'code' => trim((string) $departmentCode),
                        'created_at' => $createDate,
                        'updated_at' => $modifyDate,
                    ]);
                }
            }
        } catch (Throwable $throwable) {
            Log::error([
                'error_name' => 'Old Department date migration Job  error:',
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
            ]);
        }
    }
}
