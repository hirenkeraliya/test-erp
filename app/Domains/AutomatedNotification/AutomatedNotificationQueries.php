<?php

declare(strict_types=1);

namespace App\Domains\AutomatedNotification;

use App\Domains\AutomatedNotification\DataObjects\AutomatedNotificationData;
use App\Domains\AutomatedNotification\Enums\AutomatedNotificationTimeframeTypes;
use App\Domains\AutomatedNotification\Enums\AutomatedNotificationTypes;
use App\Domains\AutomatedNotificationProduct\AutomatedNotificationProductQueries;
use App\Domains\AutomatedNotificationStore\AutomatedNotificationStoreQueries;
use App\Domains\Color\ColorQueries;
use App\Domains\EmailRecipient\EmailRecipientQueries;
use App\Domains\ImportRecord\ImportRecordQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\Size\SizeQueries;
use App\Models\AutomatedNotification;
use App\Models\AutomatedNotificationMonthDate;
use App\Models\AutomatedNotificationStore;
use App\Models\AutomatedNotificationWeekDay;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;

class AutomatedNotificationQueries
{
    public function listQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->automatedNotificationQuery($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function addNew(AutomatedNotificationData $automatedNotificationData, int $companyId): AutomatedNotification
    {
        $data = [
            'company_id' => $companyId,
            'name' => $automatedNotificationData->name,
            'description' => $automatedNotificationData->description,
            'type_id' => $automatedNotificationData->type_id,
            'timeframe_type_id' => $automatedNotificationData->timeframe_type_id,
            'low_stock_alert_threshold' => $automatedNotificationData->low_stock_alert_threshold,
            'sent_notification' => $automatedNotificationData->sent_notification,
        ];

        $automatedNotification = AutomatedNotification::create($data);
        $this->updateAutomatedNotificationRelationDetails($automatedNotification, $automatedNotificationData);

        return $automatedNotification;
    }

    public function getAll(int $companyId): SupportCollection
    {
        return AutomatedNotification::select('type_id')->where('company_id', $companyId)->whereNotIn(
            'type_id',
            [
                AutomatedNotificationTypes::LOW_STOCK_LOCATION->value,
                AutomatedNotificationTypes::LOW_STOCK_PRODUCT->value,
            ]
        )->get();
    }

    public function getByIdWithStores(int $automatedNotificationId, int $companyId): AutomatedNotification
    {
        $locationQueries = resolve(LocationQueries::class);

        return AutomatedNotification::select('id')
            ->with(['locations:'. $locationQueries->getNameColumnName()])
            ->where('company_id', $companyId)
            ->whereIn('type_id', [
                AutomatedNotificationTypes::LOW_STOCK_COMPANY->value,
                AutomatedNotificationTypes::LOW_STOCK_LOCATION->value,
                AutomatedNotificationTypes::LOW_STOCK_PRODUCT->value,
            ])
            ->findOrFail($automatedNotificationId);
    }

    public function getById(int $automatedNotificationId, int $companyId): AutomatedNotification
    {
        $importRecordQueries = resolve(ImportRecordQueries::class);

        return AutomatedNotification::select('id', 'type_id', 'timeframe_type_id', 'name', 'description')
            ->with(['importRecord:' . $importRecordQueries->getModuleWithStatusColumns()])
            ->where('company_id', $companyId)
            ->findOrFail($automatedNotificationId);
    }

    public function getLowStockNotificationByCompanyIdAndType(int $companyId): ?AutomatedNotification
    {
        return AutomatedNotification::select('id', 'low_stock_alert_threshold')
            ->where('company_id', $companyId)
            ->where('type_id', AutomatedNotificationTypes::LOW_STOCK_COMPANY->value)
            ->first();
    }

    public function getByIdWithRelations(int $automatedNotificationId, int $companyId): AutomatedNotification
    {
        $emailRecipientQueries = resolve(EmailRecipientQueries::class);
        $automatedNotificationStoreQueries = resolve(AutomatedNotificationStoreQueries::class);
        $automatedNotificationProductQueries = resolve(AutomatedNotificationProductQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $importRecordQueries = resolve(ImportRecordQueries::class);

        return AutomatedNotification::select(
            'id',
            'name',
            'description',
            'type_id',
            'timeframe_type_id',
            'low_stock_alert_threshold',
            'sent_notification',
            'exclude_type_id',
        )
            ->with([
                'monthly',
                'weekly',
                'automatedEmailRecipients:' . $emailRecipientQueries->getReceiverNameColumn(),
                'automatedNotificationStores:' . $automatedNotificationStoreQueries->getBasicColumns(),
                'automatedNotificationStores.location:' . $locationQueries->getNameColumnName(),
                'automatedNotificationProducts:' . $automatedNotificationProductQueries->getBasicColumns(),
                'automatedNotificationProducts.product:' . $productQueries->getColumnsForPromoterCommissionReport(),
                'automatedNotificationProducts.location:' . $locationQueries->getNameColumnName(),
                'products:' . $productQueries->getBasicColumnNames(),
                'products.color:' . $colorQueries->getBasicColumnNames(),
                'products.size:' . $sizeQueries->getBasicColumnNames(),
                'importRecord:' . $importRecordQueries->getModuleWithStatusColumns(),
            ])
            ->where('company_id', $companyId)
            ->findOrFail($automatedNotificationId);
    }

    public function getByTypeIdWithRelations(int $typeId): SupportCollection
    {
        return AutomatedNotification::select(
            'id',
            'type_id',
            'company_id',
            'low_stock_alert_threshold',
            'sent_notification'
        )
            ->with(['monthly', 'weekly'])
            ->where('type_id', $typeId)
            ->get();
    }

    public function getByIdWithRelationsForJob(int $automatedNotificationId): AutomatedNotification
    {
        $emailRecipientQueries = resolve(EmailRecipientQueries::class);
        $automatedNotificationStoreQueries = resolve(AutomatedNotificationStoreQueries::class);
        $automatedNotificationProductQueries = resolve(AutomatedNotificationProductQueries::class);
        $productQueries = resolve(ProductQueries::class);

        return AutomatedNotification::select(
            'id',
            'type_id',
            'company_id',
            'low_stock_alert_threshold',
            'sent_notification'
        )
            ->with([
                'automatedEmailRecipients:' . $emailRecipientQueries->getReceiverEmailColumn(),
                'automatedNotificationStores:' . $automatedNotificationStoreQueries->getBasicColumns(),
                'automatedNotificationProducts:' . $automatedNotificationProductQueries->getBasicColumns(),
                'products:' . $productQueries->getColumnNameAndId(),
            ])
            ->findOrFail($automatedNotificationId);
    }

    public function update(
        AutomatedNotificationData $automatedNotificationData,
        AutomatedNotification $automatedNotification,
        int $companyId
    ): void {
        $data = [
            'name' => $automatedNotificationData->name,
            'description' => $automatedNotificationData->description,
            'type_id' => $automatedNotificationData->type_id,
            'timeframe_type_id' => $automatedNotificationData->timeframe_type_id,
            'low_stock_alert_threshold' => $automatedNotificationData->low_stock_alert_threshold,
            'sent_notification' => $automatedNotificationData->sent_notification,
        ];

        $automatedNotification->update($data);
        $this->updateAutomatedNotificationRelationDetails($automatedNotification, $automatedNotificationData);
    }

    public function getAutomatedNotificationExport(array $filterData, int $companyId): SupportCollection
    {
        return $this->automatedNotificationQuery($filterData, $companyId)->get();
    }

    public function removeSelectedStores(int $automatedNotificationId, int $companyId): void
    {
        $automatedNotification = AutomatedNotification::select('id')
            ->where('company_id', $companyId)
            ->where('id', $automatedNotificationId)
            ->first();

        if ($automatedNotification) {
            $automatedNotification->automatedNotificationStores()->delete();
        }
    }

    public function getByIdWithAutomatedNotificationStores(
        int $automatedNotificationId,
        int $companyId
    ): AutomatedNotification {
        $automatedNotificationStoreQueries = resolve(AutomatedNotificationStoreQueries::class);
        $locationQueries = resolve(LocationQueries::class);

        return AutomatedNotification::select('id')
            ->with([
                'automatedNotificationStores:' . $automatedNotificationStoreQueries->getBasicColumns(),
                'automatedNotificationStores.location:' . $locationQueries->getNameColumnName(),
            ])
            ->where('company_id', $companyId)
            ->findOrFail($automatedNotificationId);
    }

    public function removeSelectedProducts(int $automatedNotificationId, int $companyId): void
    {
        $automatedNotification = AutomatedNotification::select('id')
            ->where('company_id', $companyId)
            ->where('id', $automatedNotificationId)
            ->first();

        if ($automatedNotification) {
            $automatedNotification->automatedNotificationProducts()->delete();
        }
    }

    public function getByIdWithAutomatedNotificationProducts(
        int $automatedNotificationId,
        int $companyId
    ): AutomatedNotification {
        $automatedNotificationProductQueries = resolve(AutomatedNotificationProductQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $productQueries = resolve(ProductQueries::class);

        return AutomatedNotification::select('id')
            ->with([
                'automatedNotificationProducts:' . $automatedNotificationProductQueries->getBasicColumns(),
                'automatedNotificationProducts.product:' . $productQueries->getColumnsForPromoterCommissionReport(),
                'automatedNotificationProducts.location:' . $locationQueries->getNameColumnName(),
            ])
            ->where('company_id', $companyId)
            ->findOrFail($automatedNotificationId);
    }

    public function updateProductIdsInAutomatedNotificationProductPivot(int $oldProductId, int $newProductId): void
    {
        DB::table('automated_notification_product')
        ->where('product_id', $oldProductId)
            ->update([
                'product_id' => $newProductId,
            ]);
    }

    public function checkExistsByLocationsAndCompany(int $companyId, array $locationIds): bool
    {
        return AutomatedNotification::select('id')
            ->where('company_id', $companyId)
            ->whereHas('automatedNotificationStores', function ($query) use ($locationIds): void {
                $query->whereIn('location_id', $locationIds);
            })
            ->exists();
    }

    public function checkExistsByProductLocationAndCompany(int $companyId, int $productId, int $locationId): bool
    {
        return AutomatedNotification::select('id')
            ->where('company_id', $companyId)
            ->whereHas('automatedNotificationProducts', function ($query) use ($productId, $locationId): void {
                $query->where('product_id', $productId)
                    ->where('location_id', $locationId);
            })
            ->exists();
    }

    public function getByIdAndCompanyId(int $automatedNotificationId, int $companyId): ?AutomatedNotification
    {
        return AutomatedNotification::select('id', 'type_id', 'timeframe_type_id')
            ->where('company_id', $companyId)
            ->find($automatedNotificationId);
    }

    private function automatedNotificationQuery(array $filterData, int $companyId): Builder
    {
        return AutomatedNotification::query()
            ->select('id', 'type_id', 'timeframe_type_id', 'name', 'description', 'sent_notification')
            ->where('company_id', $companyId)
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->orWhereIntegerInRaw(
                        'type_id',
                        AutomatedNotificationTypes::getMatchingCases($filterData['search_text'])
                    )
                        ->orWhereIntegerInRaw(
                            'timeframe_type_id',
                            AutomatedNotificationTimeframeTypes::getMatchingCases($filterData['search_text'])
                        );
                });
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                if ('timeframe_type' === $filterData['sort_by']) {
                    $query->orderBy('timeframe_type_id', $filterData['sort_direction']);
                } else {
                    $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
                }
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }

    private function updateAutomatedNotificationRelationDetails(
        AutomatedNotification $automatedNotification,
        AutomatedNotificationData $automatedNotificationData
    ): void {
        $automatedNotification->monthly()->delete();
        $automatedNotification->weekly()->delete();
        $automatedNotification->automatedNotificationStores()->delete();

        if ($automatedNotificationData->week_days) {
            foreach ($automatedNotificationData->week_days as $week) {
                AutomatedNotificationWeekDay::create([
                    'automated_notification_id' => $automatedNotification->id,
                    'week_day' => $week,
                ]);
            }
        }

        if ($automatedNotificationData->month_dates) {
            foreach ($automatedNotificationData->month_dates as $monthDate) {
                AutomatedNotificationMonthDate::create([
                    'automated_notification_id' => $automatedNotification->id,
                    'month_date' => $monthDate,
                ]);
            }
        }

        $automatedNotification->automatedEmailRecipients()->sync(
            $automatedNotificationData->automated_email_recipients ?? []
        );

        if ($automatedNotificationData->locations) {
            foreach ($automatedNotificationData->locations as $location) {
                AutomatedNotificationStore::create([
                    'automated_notification_id' => $automatedNotification->id,
                    'location_id' => $location['id'],
                    'low_stock_alert_threshold' => $location['low_stock_alert_threshold'],
                ]);
            }
        }

        $automatedNotification->locations()->sync($automatedNotificationData->product_location_ids ?? []);
    }
}
