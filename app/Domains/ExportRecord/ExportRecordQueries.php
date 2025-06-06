<?php

declare(strict_types=1);

namespace App\Domains\ExportRecord;

use App\CommonFunctions;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\ExportRecord\Enums\ExportRecordStatuses;
use App\Domains\ExportRecord\Enums\ExportRecordTypes;
use App\Domains\Media\MediaQueries;
use App\Models\ExportRecord;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;

class ExportRecordQueries
{
    public function listQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->exportRecordQuery($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function getExportRecordExport(array $filterData, int $companyId): SupportCollection
    {
        return $this->exportRecordQuery($filterData, $companyId)->get();
    }

    public function exportRecordQuery(array $filterData, int $companyId): Builder
    {
        $mediaQueries = resolve(MediaQueries::class);

        return ExportRecord::query()
            ->select(
                'id',
                'created_by_id',
                'created_at',
                'company_id',
                'type_id',
                'created_by_type',
                'module_type',
                'status',
                'total_records',
                'total_exported_records',
            )
            ->with(['media:' . $mediaQueries->getBasicColumnNames(), 'createdBy.employee:id,staff_id'])
            ->where('company_id', $companyId)
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->whereAny(
                        ['total_records', 'total_exported_records', 'created_at'],
                        'LIKE',
                        '%' . $filterData['search_text'] . '%'
                    )
                        ->orWhereIntegerInRaw(
                            'type_id',
                            ExportRecordTypes::getMatchingCases($filterData['search_text'])
                        )
                        ->orWhereIntegerInRaw(
                            'status',
                            ExportRecordStatuses::getMatchingCases($filterData['search_text'])
                        );
                });
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->when($filterData['export_record_id'], function ($query) use ($filterData): void {
                $query->whereId($filterData['export_record_id']);
            })
            ->when($filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('created_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
                    ->where('created_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]));
            })
            ->when($filterData['export_type'], function ($query) use ($filterData): void {
                $query->where('type_id', (int) $filterData['export_type']);
            })
            ->when($filterData['status'], function ($query) use ($filterData): void {
                $query->where('status', (int) $filterData['status']);
            });
    }

    public function getPaginatedExportListForBarcode(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->getExportListForBarcodeRecordsQuery($filterData, $companyId)
            ->paginate($filterData['per_page']);
    }

    public function getExportListForBarcodeRecords(array $filterData, int $companyId): Collection
    {
        return $this->getExportListForBarcodeRecordsQuery($filterData, $companyId)
            ->get();
    }

    public function addNew(
        User $user,
        array $filters,
        int $companyId,
        int $typeId,
        ?array $headers = null,
        ?int $totalRecords = 0,
        ?int $totalExportedRecords = 0
    ): ExportRecord {
        return ExportRecord::create([
            'created_by_type' => ModelMapping::getCaseName($user::class),
            'created_by_id' => $user->getKey(),
            'filters' => $filters,
            'company_id' => $companyId,
            'type_id' => $typeId,
            'job_queued_at' => Carbon::now(),
            'headers' => $headers,
            'total_records' => $totalRecords,
            'total_exported_records' => $totalExportedRecords,
        ]);
    }

    public function updateStartedAtAndJobId(int $exportRecordId, int $companyId, string $jobId): void
    {
        $exportRecord = ExportRecord::query()
            ->where('company_id', $companyId)
            ->findOrFail($exportRecordId);
        $exportRecord->job_started_at = Carbon::now()->toDateTimeString();
        $exportRecord->status = ExportRecordStatuses::IN_PROGRESS->value;
        $exportRecord->job_id = $jobId;
        $exportRecord->save();
    }

    public function addMedia(string $filePath, int $exportRecordId, int $companyId): void
    {
        $exportRecord = ExportRecord::query()
            ->where('company_id', $companyId)
            ->findOrFail($exportRecordId);

        $exportRecord->addMedia($filePath)->toMediaCollection('export_file');
    }

    public function markAsCompletedAndJobEndedAt(int $exportRecordId, int $companyId, string $jobEndTime): void
    {
        $exportRecord = ExportRecord::query()
            ->where('company_id', $companyId)
            ->findOrFail($exportRecordId);
        $exportRecord->status = ExportRecordStatuses::GENERATED->value;
        $exportRecord->job_ended_at = $jobEndTime;
        $exportRecord->save();
    }

    public function markStatusAsFailed(int $exportRecordId, int $companyId, string $jobEndTime): void
    {
        $exportRecord = ExportRecord::query()
            ->where('company_id', $companyId)
            ->findOrFail($exportRecordId);

        $exportRecord->status = ExportRecordStatuses::FAILED->value;
        $exportRecord->job_ended_at = $jobEndTime;
        $exportRecord->save();
    }

    private function getExportListForBarcodeRecordsQuery(array $filterData, int $companyId): Builder
    {
        return ExportRecord::query()
            ->select('id', 'company_id', 'type_id', 'created_by_type', 'status')
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where('status', 'like', '%' . $filterData['search_text'] . '%');
            })
            ->where('company_id', $companyId)
            ->where('type_id', ExportRecordTypes::BARCODE->value)
            ->when((int) $filterData['status'] === ExportRecordStatuses::PENDING->value, function ($query): void {
                $query->where('status', ExportRecordStatuses::PENDING->value);
            })
            ->when((int) $filterData['status'] === ExportRecordStatuses::IN_PROGRESS->value, function ($query): void {
                $query->where('status', ExportRecordStatuses::IN_PROGRESS->value);
            })
            ->when((int) $filterData['status'] === ExportRecordStatuses::FAILED->value, function ($query): void {
                $query->where('status', ExportRecordStatuses::FAILED->value);
            })
            ->when((int) $filterData['status'] === ExportRecordStatuses::GENERATED->value, function ($query): void {
                $query->where('status', ExportRecordStatuses::GENERATED->value);
            })
            ->when($filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('job_queued_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
                    ->where('job_queued_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]));
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }

    public function getPendingBarcodeExportRecordPendingCount(int $companyId): int
    {
        return ExportRecord::query()
            ->select('id')
            ->where('company_id', $companyId)
            ->where('type_id', ExportRecordTypes::BARCODE->value)
            ->whereNotIn('status', [ExportRecordStatuses::GENERATED->value, ExportRecordStatuses::FAILED->value])
            ->count();
    }

    public function incrementExportedRecordsCount(ExportRecord $exportRecord, int $rowsCount): void
    {
        $exportRecord->total_exported_records += $rowsCount;
        $exportRecord->save();
    }

    public function getFiltersById(int $id, int $companyId): ExportRecord
    {
        return ExportRecord::query()
            ->select('id', 'filters')
            ->where('company_id', $companyId)
            ->findOrFail($id);
    }

    public function listQueryForStoreManager(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->exportRecordQueryForStoreManagerAndWarehouseManager($filterData, $companyId)
            ->where('created_by_type', ModelMapping::STORE_MANAGER->name)
            ->paginate($filterData['per_page']);
    }

    public function exportListQueryForStoreManager(array $filterData, int $companyId): SupportCollection
    {
        return $this->exportRecordQueryForStoreManagerAndWarehouseManager($filterData, $companyId)
            ->where('created_by_type', ModelMapping::STORE_MANAGER->name)
            ->get();
    }

    public function listQueryForWarehouseManager(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->exportRecordQueryForStoreManagerAndWarehouseManager($filterData, $companyId)
            ->where('created_by_type', ModelMapping::WAREHOUSE_MANAGER->name)
            ->paginate($filterData['per_page']);
    }

    public function exportListQueryForWarehouseManager(array $filterData, int $companyId): SupportCollection
    {
        return $this->exportRecordQueryForStoreManagerAndWarehouseManager($filterData, $companyId)
            ->where('created_by_type', ModelMapping::WAREHOUSE_MANAGER->name)
            ->get();
    }

    public function exportRecordCountForProductHistory(int $companyId): int
    {
        $today = Carbon::now();

        return ExportRecord::query()
            ->select('id')
            ->where('company_id', $companyId)
            ->where('type_id', ExportRecordTypes::PRODUCTS->value)
            ->where('created_at', '>=', CommonFunctions::addStartTime($today->format('Y-m-d')))
            ->where('created_at', '<=', CommonFunctions::addEndTime($today->format('Y-m-d')))
            ->count();
    }

    private function exportRecordQueryForStoreManagerAndWarehouseManager(array $filterData, int $companyId): Builder
    {
        return ExportRecord::query()
            ->select(
                'id',
                'created_by_id',
                'created_at',
                'company_id',
                'type_id',
                'created_by_type',
                'module_type',
                'status',
                'total_records',
                'total_exported_records',
            )
            ->with(['createdBy.employee:id,staff_id'])
            ->where('company_id', $companyId)
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query
                        ->whereAny(
                            ['total_records', 'total_exported_records', 'created_at'],
                            'LIKE',
                            '%' . $filterData['search_text'] . '%'
                        )
                        ->orWhereIntegerInRaw(
                            'type_id',
                            ExportRecordTypes::getMatchingCases($filterData['search_text'])
                        )
                        ->orWhereIntegerInRaw(
                            'status',
                            ExportRecordStatuses::getMatchingCases($filterData['search_text'])
                        );
                });
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->when($filterData['export_record_id'], function ($query) use ($filterData): void {
                $query->whereId($filterData['export_record_id']);
            })
            ->when($filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('created_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
                    ->where('created_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]));
            })
            ->when($filterData['export_type'], function ($query) use ($filterData): void {
                $query->where('type_id', (int) $filterData['export_type']);
            })
            ->when($filterData['status'], function ($query) use ($filterData): void {
                $query->where('status', (int) $filterData['status']);
            });
    }
}
