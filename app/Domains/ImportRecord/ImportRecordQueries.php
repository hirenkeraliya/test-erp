<?php

declare(strict_types=1);

namespace App\Domains\ImportRecord;

use App\CommonFunctions;
use App\Domains\Admin\AdminQueries;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Company\CompanyQueries;
use App\Domains\ImportRecord\DataObjects\ImportRecordData;
use App\Domains\ImportRecord\Enums\ImportTypes;
use App\Domains\ImportRecord\Enums\Status;
use App\Domains\ImportRecord\Exports\ImportRecordFailedRowExport;
use App\Domains\Media\MediaQueries;
use App\Domains\MemberGroup\Jobs\MembersSyncWithMemberGroupJob;
use App\Domains\MemberGroup\MemberGroupQueries;
use App\Domains\Storage\Enums\StorageTypes;
use App\Models\Admin;
use App\Models\AutomatedNotification;
use App\Models\DreamPrice;
use App\Models\GoodsReceivedNote;
use App\Models\ImportRecord;
use App\Models\MemberGroup;
use App\Models\ProductCollection;
use App\Models\StockAdjustment;
use App\Models\StockTake;
use App\Models\StoreManager;
use App\Models\WarehouseManager;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use ZipArchive;

class ImportRecordQueries
{
    public function listQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->importRecordQuery($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function listQueryForStoreManager(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->importRecordQueryForStoreManagerAndWarehouseManager($filterData, $companyId)
            ->where('created_by_type', ModelMapping::STORE_MANAGER->name)
            ->paginate($filterData['per_page']);
    }

    public function listQueryForWarehouseManager(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->importRecordQueryForStoreManagerAndWarehouseManager($filterData, $companyId)
            ->where('created_by_type', ModelMapping::WAREHOUSE_MANAGER->name)
            ->paginate($filterData['per_page']);
    }

    public function addNew(
        ImportRecordData $importRecordData,
        Admin|StoreManager|WarehouseManager $user,
        int $companyId,
        DreamPrice|StockTake|GoodsReceivedNote|StockAdjustment|AutomatedNotification|MemberGroup|null $module = null
    ): ImportRecord {
        $importRecordDetails = $importRecordData->all();
        unset($importRecordDetails['product_upload_type_id']);
        unset($importRecordDetails['upload_file']);
        $importRecordDetails['company_id'] = $companyId;
        $importRecordDetails['created_by_id'] = $user->id;
        $importRecordDetails['created_by_type'] = ModelMapping::getCaseName($user::class);
        $importRecordDetails['module_id'] = null !== $module ? $module->id : null;
        $importRecordDetails['module_type'] = null !== $module ? ModelMapping::getCaseName($module::class) : null;
        $importRecord = ImportRecord::create($importRecordDetails);

        $this->uploadFile($importRecord, $importRecordData);

        $importRecord->fresh();

        return $importRecord->load('createdBy');
    }

    public function addNewForStockTake(
        int $typeId,
        Admin|StoreManager|WarehouseManager $user,
        int $companyId,
        DreamPrice|StockTake|null $module = null
    ): ImportRecord {
        $importRecordDetails = [];
        $importRecordDetails['type_id'] = $typeId;
        $importRecordDetails['company_id'] = $companyId;
        $importRecordDetails['created_by_id'] = $user->id;
        $importRecordDetails['created_by_type'] = ModelMapping::getCaseName($user::class);
        $importRecordDetails['module_id'] = null !== $module ? $module->id : null;
        $importRecordDetails['module_type'] = null !== $module ? ModelMapping::getCaseName($module::class) : null;
        $importRecord = ImportRecord::create($importRecordDetails);

        $importRecord->fresh();

        return $importRecord->load('createdBy');
    }

    public function addNewForProductCollection(
        int $typeId,
        Admin $user,
        int $companyId,
        ?ProductCollection $module = null
    ): ImportRecord {
        $importRecordDetails = [];
        $importRecordDetails['type_id'] = $typeId;
        $importRecordDetails['company_id'] = $companyId;
        $importRecordDetails['created_by_id'] = $user->id;
        $importRecordDetails['created_by_type'] = ModelMapping::getCaseName($user::class);
        $importRecordDetails['module_id'] = $module instanceof ProductCollection ? $module->id : null;
        $importRecordDetails['module_type'] = $module instanceof ProductCollection ? ModelMapping::getCaseName(
            $module::class
        ) : null;
        $importRecord = ImportRecord::create($importRecordDetails);

        $importRecord->fresh();

        return $importRecord->load('createdBy');
    }

    public function addNewForMemberGroup(
        int $typeId,
        Admin $user,
        int $companyId,
        ?MemberGroup $module = null
    ): ImportRecord {
        $importRecordDetails = [];
        $importRecordDetails['type_id'] = $typeId;
        $importRecordDetails['company_id'] = $companyId;
        $importRecordDetails['created_by_id'] = $user->id;
        $importRecordDetails['created_by_type'] = ModelMapping::getCaseName($user::class);
        $importRecordDetails['module_id'] = $module instanceof MemberGroup ? $module->id : null;
        $importRecordDetails['module_type'] = $module instanceof MemberGroup ? ModelMapping::getCaseName(
            $module::class
        ) : null;
        $importRecord = ImportRecord::create($importRecordDetails);

        $importRecord->fresh();

        return $importRecord->load('createdBy');
    }

    public function markAsInProgress(ImportRecord $importRecord, int $totalRows): void
    {
        $importRecord->records_in_file = $totalRows;
        $importRecord->status = Status::IN_PROGRESS->value;
        $importRecord->save();
    }

    public function markAsCompleted(ImportRecord $importRecord): void
    {
        $importRecord->status = Status::COMPLETED->value;
        $importRecord->save();
        $this->memberGroupSyncWithMembers($importRecord);
    }

    public function markAsCompletedFromMemberGroup(ImportRecord $importRecord): void
    {
        $importRecord->status = Status::COMPLETED->value;
        $importRecord->save();
    }

    private function memberGroupSyncWithMembers(ImportRecord $importRecord): void
    {
        if ($importRecord->module_type === ModelMapping::MEMBER_GROUP->name) {
            $memberGroupQueries = resolve(MemberGroupQueries::class);
            $memberGroup = $memberGroupQueries->getItemTypeSmartMemberGroup((int) $importRecord->module_id);
            if ($memberGroup) {
                $adminQueries = resolve(AdminQueries::class);
                $admin = $adminQueries->getById($importRecord->created_by_id);

                $importRecord = $this->addNewForMemberGroup(
                    ImportTypes::MEMBER_GROUP->value,
                    $admin,
                    $memberGroup->company_id,
                    $memberGroup,
                );
                MembersSyncWithMemberGroupJob::dispatch(
                    $memberGroup->id,
                    $memberGroup->company_id,
                    $importRecord->id
                )->onQueue(config('horizon.default_queue_name'));
            }
        }
    }

    public function saveHeaderColumns(ImportRecord $importRecord, array $headerColumns): void
    {
        $importRecord->header_columns = $headerColumns;
        $importRecord->save();
    }

    public function incrementFailedRecordsCount(ImportRecord $importRecord): void
    {
        $importRecord->records_failed += 1;
        $importRecord->save();
    }

    public function incrementImportedRecordsCount(ImportRecord $importRecord): void
    {
        $importRecord->records_imported += 1;
        $importRecord->save();
    }

    public function generateFailedRecordsFile(ImportRecord $importRecord): void
    {
        if (! $importRecord->records_failed) {
            return;
        }

        $filename = now()->format('y-m-d h-i-s') . '.xlsx';

        $binaryFileResponse = Excel::download(
            new ImportRecordFailedRowExport($importRecord, $importRecord->header_columns ?: []),
            $filename
        );

        $filePath = $binaryFileResponse->getFile()->getPathname();

        $importRecord->addMedia($filePath)
            ->setFileName($filename)
            ->toMediaCollection('failed_rows_file');
    }

    public function generateFailedRecordsImage(ImportRecord $importRecord): void
    {
        if (! $importRecord->records_failed) {
            return;
        }

        $filename = null;
        $zipArchive = new ZipArchive();
        $zipFileName = Carbon::now()->format('d-m-Y_H:i:s') . '.zip';
        $zipFilePath = Storage::disk(StorageTypes::PUBLIC->value)->path('product_image_zip/' . $zipFileName);

        if ($zipArchive->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            $files = Storage::disk(StorageTypes::PUBLIC->value)->files('extract/' . $importRecord->getKey());
            foreach ($files as $file) {
                $zipArchive->addFile(Storage::disk(StorageTypes::PUBLIC->value)->path($file), basename($file));
            }

            $filename = now()->format('y-m-d h-i-s') . '.xlsx';

            $binaryFileResponse = Excel::download(
                new ImportRecordFailedRowExport($importRecord, $importRecord->header_columns ?: []),
                $filename
            );
            $filePath = $binaryFileResponse->getFile()->getPathname();

            $zipArchive->addFile($filePath, $filename);

            $zipArchive->close();
            Storage::disk(StorageTypes::PUBLIC->value)->delete($filename);
        }

        $importRecord->addMedia(
            Storage::disk(StorageTypes::PUBLIC->value)->path('product_image_zip/' . $zipFileName)
        )->toMediaCollection('failed_rows_file');

        Storage::disk(StorageTypes::PUBLIC->value)->delete($zipFilePath);
    }

    public function getUploadedMedia(ImportRecord $importRecord): Media
    {
        /** @var Media */
        return $importRecord->getDiskBasedFirstMedia('upload_file');
    }

    public function getFilePath(ImportRecord $importRecord): string
    {
        return $importRecord->getLocalFilePath('upload_file');
    }

    public function getUploadedMediaUrl(ImportRecord $importRecord): string
    {
        return $importRecord->getDiskBasedFirstMediaUrl('upload_file');
    }

    public function getImportRecordExport(array $filterData, int $companyId): Collection
    {
        return $this->importRecordQuery($filterData, $companyId)->get();
    }

    private function uploadFile(ImportRecord $importRecord, ImportRecordData $importRecordData): void
    {
        $importRecord->addMedia($importRecordData->upload_file)
            ->toMediaCollection('upload_file');
    }

    private function importRecordQuery(array $filterData, int $companyId): Builder
    {
        $mediaQueries = resolve(MediaQueries::class);

        return ImportRecord::query()
            ->select(
                'id',
                'company_id',
                'created_by_id',
                'type_id',
                'status',
                'records_imported',
                'records_failed',
                'created_at',
                'created_by_type',
                'module_type',
                'records_in_file'
            )
            ->with(['media:' . $mediaQueries->getBasicColumnNames(), 'createdBy.employee:id,staff_id'])
            ->where('company_id', $companyId)
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->whereAny(
                        ['records_imported', 'records_failed', 'created_at'],
                        'LIKE',
                        '%' . $filterData['search_text'] . '%'
                    )
                        ->orWhereIntegerInRaw('type_id', ImportTypes::getMatchingCases($filterData['search_text']))
                        ->orWhereIntegerInRaw('status', Status::getMatchingCases($filterData['search_text']));
                });
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->when($filterData['import_record_id'], function ($query) use ($filterData): void {
                $query->whereId($filterData['import_record_id']);
            })
            ->when($filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('created_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
                    ->where('created_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]));
            })
            ->when($filterData['import_type'], function ($query) use ($filterData): void {
                $query->where('type_id', (int) $filterData['import_type']);
            })
            ->when($filterData['status'], function ($query) use ($filterData): void {
                $query->where('status', (int) $filterData['status']);
            });
    }

    private function importRecordQueryForStoreManagerAndWarehouseManager(array $filterData, int $companyId): Builder
    {
        return ImportRecord::query()
            ->select(
                'id',
                'company_id',
                'created_by_id',
                'type_id',
                'status',
                'records_imported',
                'records_failed',
                'created_at',
                'created_by_type',
                'module_type',
                'records_in_file'
            )
            ->with(['createdBy.employee:id,staff_id'])
            ->where('company_id', $companyId)
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query
                        ->whereAny(
                            ['records_imported', 'records_failed', 'created_at'],
                            'LIKE',
                            '%' . $filterData['search_text'] . '%'
                        )
                        ->orWhereIntegerInRaw('type_id', ImportTypes::getMatchingCases($filterData['search_text']))
                        ->orWhereIntegerInRaw('status', Status::getMatchingCases($filterData['search_text']));
                });
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->when($filterData['import_record_id'], function ($query) use ($filterData): void {
                $query->whereId($filterData['import_record_id']);
            })
            ->when($filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('created_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
                    ->where('created_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]));
            })
            ->when($filterData['import_type'], function ($query) use ($filterData): void {
                $query->where('type_id', (int) $filterData['import_type']);
            })
            ->when($filterData['status'], function ($query) use ($filterData): void {
                $query->where('status', (int) $filterData['status']);
            });
    }

    public function getPendingImportRecordCount(string $moduleType, int $companyId): int
    {
        return ImportRecord::query()
            ->select('id')
            ->where('company_id', $companyId)
            ->where('module_type', $moduleType)
            ->whereNotIn('status', [Status::COMPLETED->value])
            ->count();
    }

    public function getByIdWithCompany(int $id, int $companyId): ImportRecord
    {
        $companyQueries = resolve(CompanyQueries::class);

        return ImportRecord::query()
            ->with('company:' . $companyQueries->getBasicColumnNamesWithCode())
            ->where('company_id', $companyId)
            ->findOrFail($id);
    }

    public function getById(int $id, int $companyId): ImportRecord
    {
        return ImportRecord::query()
            ->where('company_id', $companyId)
            ->findOrFail($id);
    }

    public function getBasicColumns(): string
    {
        return 'id,company_id,type_id,status,records_in_file,records_imported,records_failed,created_by_id,created_by_type,module_id,module_type';
    }

    public function getModuleWithStatusColumns(): string
    {
        return 'id,status,module_id,module_type';
    }
}
