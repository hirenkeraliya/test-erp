<?php

declare(strict_types=1);

namespace App\Domains\OldDataMigrateJob\Jobs;

use App\Models\Company;
use App\Models\UnitOfMeasure;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class OldUMODataMigrateJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(): void
    {
        try {
            $company = Company::query()->firstOrFail();

            $oldUnitOfMeasures = DB::connection('old_data_mysql')
                ->table('tblinvuom')
                ->get();

            foreach ($oldUnitOfMeasures as $oldUnitOfMeasure) {
                $isUnitOfMeasureExist = UnitOfMeasure::query()
                    ->where('name', trim((string) $oldUnitOfMeasure->UOMDescription))
                    ->where('company_id', $company->id)
                    ->exists();

                if (! $isUnitOfMeasureExist) {
                    $createDate = $oldUnitOfMeasure->CreateDate;
                    if ($createDate || '0000-00-00 00:00:00' == $createDate || '' == $createDate) {
                        $createDate = now()->format('Y-m-d H:i:s');
                    }

                    $modifyDate = $oldUnitOfMeasure->ModifyDate;
                    if ($modifyDate || '0000-00-00 00:00:00' == $modifyDate || '' == $modifyDate) {
                        $modifyDate = now()->format('Y-m-d H:i:s');
                    }

                    DB::table('unit_of_measures')->insert([
                        'company_id' => $company->id,
                        'name' => trim((string) $oldUnitOfMeasure->UOMDescription),
                        'created_at' => $createDate,
                        'updated_at' => $modifyDate,
                    ]);

                    $oldUnitOfMeasureDerivatives = DB::connection('old_data_mysql')
                        ->table('tblinvuomconvmaster')
                        ->where('UomCode', $oldUnitOfMeasure->UOMCode)
                        ->get();

                    if ($oldUnitOfMeasureDerivatives->isNotEmpty()) {
                        foreach ($oldUnitOfMeasureDerivatives as $oldUnitOfMeasureDerivative) {
                            $UnitOfMeasure = UnitOfMeasure::query()
                                ->where('name', trim((string) $oldUnitOfMeasure->UOMDescription))
                                ->where('company_id', $company->id)
                                ->first();

                            if ($UnitOfMeasure) {
                                $oldUnitOfMeasureCreateDate = $oldUnitOfMeasureDerivative->CreateDate;
                                if ($oldUnitOfMeasureCreateDate || '0000-00-00 00:00:00' == $oldUnitOfMeasureCreateDate || '' == $oldUnitOfMeasureCreateDate) {
                                    $oldUnitOfMeasureCreateDate = now()->format('Y-m-d H:i:s');
                                }

                                $oldUnitOfMeasureModifyDate = $oldUnitOfMeasureDerivative->ModifyDate;
                                if ($oldUnitOfMeasureModifyDate || '0000-00-00 00:00:00' == $oldUnitOfMeasureModifyDate || '' == $oldUnitOfMeasureModifyDate) {
                                    $oldUnitOfMeasureModifyDate = now()->format('Y-m-d H:i:s');
                                }

                                DB::table('unit_of_measure_derivatives')->insert([
                                    'unit_of_measure_id' => $UnitOfMeasure->id,
                                    'name' => trim((string) $oldUnitOfMeasureDerivative->BaseUomCode),
                                    'ratio' => trim((string) $oldUnitOfMeasureDerivative->UomConv),
                                    'created_at' => $oldUnitOfMeasureCreateDate,
                                    'updated_at' => $oldUnitOfMeasureModifyDate,
                                ]);
                            }
                        }
                    }
                }
            }
        } catch (Throwable $throwable) {
            Log::error([
                'error_name' => 'Old Unit Of Measure date migration Job  error:',
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
            ]);
        }
    }
}
