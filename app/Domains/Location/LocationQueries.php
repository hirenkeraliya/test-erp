<?php

declare(strict_types=1);

namespace App\Domains\Location;

use App\CommonFunctions;
use App\Domains\Brand\BrandQueries;
use App\Domains\City\CityQueries;
use App\Domains\Company\CompanyQueries;
use App\Domains\Company\CompanySettingQueries;
use App\Domains\Counter\CounterQueries;
use App\Domains\Country\CountryQueries;
use App\Domains\Currency\CurrencyQueries;
use App\Domains\CurrencyRate\CurrencyRateQueries;
use App\Domains\Dashboard\Enums\StoreRevenueDashboardTableFilterTypes;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\Location\DataObjects\LocationData;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Media\MediaQueries;
use App\Domains\Product\Enums\Statuses;
use App\Domains\Product\ProductQueries;
use App\Domains\Region\RegionQueries;
use App\Domains\Sale\Enums\SaleStatus;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Domains\State\StateQueries;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Domains\WarehouseManager\WarehouseManagerQueries;
use App\Models\AutomatedNotification;
use App\Models\Location;
use App\Services\GoogleGeocodingService;
use Carbon\Carbon;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LocationQueries
{
    public function listQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->locationQuery($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function filterByCompanyAndTypeId(int $companyId, int $typeId): Closure
    {
        return fn ($query) => $query->select('id')->where('company_id', $companyId)->where('type_id', $typeId);
    }

    public function filterByCompany(int $companyId): Closure
    {
        return fn ($query) => $query->select('id', 'name')->where('company_id', $companyId);
    }

    public function filterByType(int $typeId): Closure
    {
        return fn ($query) => $query->select('id')->where('type_id', $typeId);
    }

    public function filterByStoreAndCompanyId(int $locationId, int $companyId): Closure
    {
        return fn ($query) => $query->select('id')
            ->where('id', $locationId)
            ->where('company_id', $companyId)
            ->where('type_id', LocationTypes::STORE->value);
    }

    public function filterById(int $locationId, int $typeId): Closure
    {
        return fn ($query) => $query->select('id')->where('type_id', $typeId)->where('id', $locationId);
    }

    public function filterByIds(array $locationIds, int $typeId): Closure
    {
        return fn ($query) => $query->select('id')->where('type_id', $typeId)->whereIntegerInRaw(
            'id',
            $locationIds
        );
    }

    public function addNew(LocationData $locationData, int $companyId): void
    {
        $googleService = resolve(GoogleGeocodingService::class);
        $locationDetails = $locationData->all();
        unset($locationDetails['brand_ids']);
        unset($locationDetails['sale_channel_ids']);

        $locationDetails = $googleService->getCoordinatesForLocation($locationDetails);

        $locationDetails['company_id'] = $companyId;
        $locationDetails['uuid'] = Str::uuid();

        $location = Location::create($locationDetails);
        if ($locationData->brand_ids) {
            $location->brands()->sync($locationData->brand_ids);
        }

        if ($locationData->sale_channel_ids) {
            $location->saleChannels()->sync($locationData->sale_channel_ids);
        }
    }

    public function getByIdWithBrands(int $locationId, int $companyId, BrandQueries $brandQueries): Location
    {
        $countryQueries = resolve(CountryQueries::class);
        $saleChannelQueries = resolve(SaleChannelQueries::class);
        $stateQueries = resolve(StateQueries::class);
        $cityQueries = resolve(CityQueries::class);

        return Location::select(
            'id',
            'name',
            'type_id',
            'code',
            'registration_number',
            'sst_number',
            'email',
            'phone',
            'mobile',
            'fax',
            'address_line_1',
            'address_line_2',
            'area_code',
            'web_site',
            'sales_tax_percentage',
            'sales_return_days_limit',
            'credit_note_expiration_days',
            'loyalty_point_expiration_days',
            'receipt_footer',
            'disclaimer',
            'is_automatic_day_close',
            'automatic_day_close_time',
            'region_id',
            'cash_out_limit_info',
            'cash_out_limit_warning',
            'cash_out_limit_restrict',
            'price_fall_down_percentage',
            'open_time',
            'close_time',
            'share_inventory_to_external_companies',
            'country_id',
            'state_id',
            'city_id',
            'minimum_stock_threshold',
            'maximum_stock_threshold',
            'is_email_verified'
        )
            ->with([
                'brands:' . $brandQueries->getBasicColumnNames(),
                'country:' . $countryQueries->getBasicColumnNames(),
                'saleChannels:' . $saleChannelQueries->getBasicColumnsInString(),
                'state:' . $stateQueries->getBasicColumnNames(),
                'city:' . $cityQueries->getBasicColumnNames(),
            ])
            ->where('company_id', $companyId)
            ->findOrFail($locationId);
    }

    public function getWithBasicColumns(int $companyId): Collection
    {
        return Location::select('id', 'name', 'code', 'company_id', 'region_id', 'type_id')
            ->where('company_id', $companyId)
            ->get();
    }

    public function getWithBasicColumnsOfWarehouse(int $companyId): Collection
    {
        return Location::select('id', 'name', 'company_id')
            ->where('company_id', $companyId)
            ->where('type_id', LocationTypes::WAREHOUSE->value)
            ->get();
    }

    public function getInventoryForLowStockNotificationProduct(
        AutomatedNotification $automatedNotification,
    ): Collection {
        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $warehouseManagerQueries = resolve(WarehouseManagerQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $productQueries = resolve(ProductQueries::class);

        return Location::select('id', 'name', 'company_id', 'type_id')
            ->withCount([
                'inventories as total_record_count' => function ($query) use ($automatedNotification): void {
                    $query->where('stock', '>', 0)
                        ->whereHas('product', function ($productQuery) use ($automatedNotification): void {
                            $productQuery->where('company_id', $automatedNotification->company_id)
                                ->where('status', Statuses::ACTIVE->value)
                                ->where('is_non_inventory', false);
                        })
                        ->join('automated_notification_products as anp', function ($join): void {
                            $join->on('anp.location_id', '=', 'inventories.location_id')
                                ->on('anp.product_id', '=', 'inventories.product_id');
                        })
                        ->where('anp.automated_notification_id', $automatedNotification->id)
                        ->whereColumn('stock', '<=', 'anp.low_stock_alert_threshold');
                },
            ])
            ->with([
                'inventories' => function ($query) use ($automatedNotification): void {
                    $query->where('stock', '>', 0)
                        ->whereHas('product', function ($productQuery) use ($automatedNotification): void {
                            $productQuery->where('company_id', $automatedNotification->company_id)
                                ->where('status', Statuses::ACTIVE->value)
                                ->where('is_non_inventory', false);
                        })
                        ->join('automated_notification_products as anp', function ($join): void {
                            $join->on('anp.location_id', '=', 'inventories.location_id')
                                ->on('anp.product_id', '=', 'inventories.product_id');
                        })
                        ->where('anp.automated_notification_id', $automatedNotification->id)
                        ->whereColumn('stock', '<=', 'anp.low_stock_alert_threshold')
                        ->limit(10);
                },
                'inventories.product:' . $productQueries->getColumnsForReservedInventoryReports(),
                'storeManagers:' . $storeManagerQueries->getEmployeeIdColumnNames(),
                'storeManagers.employee:' . $employeeQueries->getBasicColumnNames(),
                'warehouseManagers:' . $warehouseManagerQueries->getEmployeeIdColumnNames(),
                'warehouseManagers.employee:' . $employeeQueries->getBasicColumnNames(),
            ])
            ->where('company_id', $automatedNotification->company_id)
            ->get();
    }

    public function getInventoryForLowStockNotificationLocation(
        AutomatedNotification $automatedNotification,
    ): Collection {
        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $warehouseManagerQueries = resolve(WarehouseManagerQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $productQueries = resolve(ProductQueries::class);

        return Location::select('id', 'name', 'company_id', 'type_id')
            ->withCount([
                'inventories as total_record_count' => function ($query) use ($automatedNotification): void {
                    $query->where('stock', '>', 0)
                        ->whereNotIn('inventories.id', function ($query) use ($automatedNotification): void {
                            $query->select('inventories.id')
                                ->from('inventories')
                                ->join('automated_notification_products as anp', function ($join): void {
                                    $join->on('anp.location_id', '=', 'inventories.location_id')
                                        ->on('anp.product_id', '=', 'inventories.product_id');
                                })
                                ->where('anp.automated_notification_id', $automatedNotification->id);
                        })
                        ->whereHas('product', function ($productQuery) use ($automatedNotification): void {
                            $productQuery->where('company_id', $automatedNotification->company_id)
                                ->where('status', Statuses::ACTIVE->value)
                                ->where('is_non_inventory', false);
                        })
                        ->join('automated_notification_stores as ans', function ($join): void {
                            $join->on('ans.location_id', '=', 'inventories.location_id');
                        })
                        ->where('ans.automated_notification_id', $automatedNotification->id)
                        ->whereColumn('stock', '<=', 'ans.low_stock_alert_threshold');
                },
            ])
            ->with([
                'inventories' => function ($query) use ($automatedNotification): void {
                    $query->where('stock', '>', 0)
                        ->whereNotIn('inventories.id', function ($query) use ($automatedNotification): void {
                            $query->select('inventories.id')
                                ->from('inventories')
                                ->join('automated_notification_products as anp', function ($join): void {
                                    $join->on('anp.location_id', '=', 'inventories.location_id')
                                        ->on('anp.product_id', '=', 'inventories.product_id');
                                })
                                ->where('anp.automated_notification_id', $automatedNotification->id);
                        })
                        ->whereHas('product', function ($productQuery) use ($automatedNotification): void {
                            $productQuery->where('company_id', $automatedNotification->company_id)
                                ->where('status', Statuses::ACTIVE->value)
                                ->where('is_non_inventory', false);
                        })
                        ->join('automated_notification_stores as ans', function ($join): void {
                            $join->on('ans.location_id', '=', 'inventories.location_id');
                        })
                        ->where('ans.automated_notification_id', $automatedNotification->id)
                        ->whereColumn('stock', '<=', 'ans.low_stock_alert_threshold')
                        ->limit(10);
                },
                'inventories.product:' . $productQueries->getColumnsForReservedInventoryReports(),
                'storeManagers:' . $storeManagerQueries->getEmployeeIdColumnNames(),
                'storeManagers.employee:' . $employeeQueries->getBasicColumnNames(),
                'warehouseManagers:' . $warehouseManagerQueries->getEmployeeIdColumnNames(),
                'warehouseManagers.employee:' . $employeeQueries->getBasicColumnNames(),
            ])
            ->where('company_id', $automatedNotification->company_id)
            ->get();
    }

    public function getInventoryForLowStockNotificationCompany(
        AutomatedNotification $automatedNotification,
    ): Collection {
        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $warehouseManagerQueries = resolve(WarehouseManagerQueries::class);

        return Location::select('id', 'name', 'company_id', 'type_id')
            ->withCount([
                'inventories as total_record_count' => function ($query) use ($automatedNotification): void {
                    $query->where('stock', '>', 0)
                        ->whereHas('product', function ($productQuery) use ($automatedNotification): void {
                            $productQuery->where('company_id', $automatedNotification->company_id)
                                ->where('status', Statuses::ACTIVE->value)
                                ->where('is_non_inventory', false);
                        })
                        ->where('inventories.stock', '<=', $automatedNotification->low_stock_alert_threshold)
                        ->whereNotIn('inventories.id', function ($query) use ($automatedNotification): void {
                            $query->select('inventories.id')
                                ->from('inventories')
                                ->join('automated_notification_products as anp', function ($join): void {
                                    $join->on('anp.location_id', '=', 'inventories.location_id')
                                        ->on('anp.product_id', '=', 'inventories.product_id');
                                })
                                ->where('anp.automated_notification_id', $automatedNotification->id);
                        })
                        ->whereNotIn('inventories.id', function ($query) use ($automatedNotification): void {
                            $query->select('inventories.id')
                                ->from('inventories')
                                ->join('automated_notification_stores as ans', function ($join): void {
                                    $join->on('ans.location_id', '=', 'inventories.location_id');
                                })
                                ->whereNotIn('inventories.id', function ($query) use (
                                    $automatedNotification
                                ): void {
                                    $query->select('inventories.id')
                                        ->from('inventories')
                                        ->join(
                                            'automated_notification_products as anp',
                                            function ($join): void {
                                                $join->on('anp.location_id', '=', 'inventories.location_id')
                                                    ->on('anp.product_id', '=', 'inventories.product_id');
                                            }
                                        )
                                        ->where('anp.automated_notification_id', $automatedNotification->id);
                                });
                        });
                },
            ])
            ->with([
                'inventories' => function ($query) use ($automatedNotification): void {
                    $query->where('stock', '>', 0)
                        ->whereHas('product', function ($productQuery) use ($automatedNotification): void {
                            $productQuery->where('company_id', $automatedNotification->company_id)
                                ->where('status', Statuses::ACTIVE->value)
                                ->where('is_non_inventory', false);
                        })
                        ->where('inventories.stock', '<=', $automatedNotification->low_stock_alert_threshold)
                        ->whereNotIn('inventories.id', function ($query) use ($automatedNotification): void {
                            $query->select('inventories.id')
                                ->from('inventories')
                                ->join('automated_notification_products as anp', function ($join): void {
                                    $join->on('anp.location_id', '=', 'inventories.location_id')
                                        ->on('anp.product_id', '=', 'inventories.product_id');
                                })
                                ->where('anp.automated_notification_id', $automatedNotification->id);
                        })
                        ->whereNotIn('inventories.id', function ($query) use ($automatedNotification): void {
                            $query->select('inventories.id')
                                ->from('inventories')
                                ->join('automated_notification_stores as ans', function ($join): void {
                                    $join->on('ans.location_id', '=', 'inventories.location_id');
                                })
                                ->whereNotIn('inventories.id', function ($query) use ($automatedNotification): void {
                                    $query->select('inventories.id')
                                        ->from('inventories')
                                        ->join('automated_notification_products as anp', function ($join): void {
                                            $join->on('anp.location_id', '=', 'inventories.location_id')
                                                ->on('anp.product_id', '=', 'inventories.product_id');
                                        })
                                        ->where('anp.automated_notification_id', $automatedNotification->id);
                                });
                        })
                        ->limit(10);
                },
                'inventories.product:' . $productQueries->getColumnsForReservedInventoryReports(),
                'storeManagers:' . $storeManagerQueries->getEmployeeIdColumnNames(),
                'storeManagers.employee:' . $employeeQueries->getBasicColumnNames(),
                'warehouseManagers:' . $warehouseManagerQueries->getEmployeeIdColumnNames(),
                'warehouseManagers.employee:' . $employeeQueries->getBasicColumnNames(),
            ])
            ->where('company_id', $automatedNotification->company_id)
            ->get();
    }

    public function getInventoryForLowStockNotificationCompanyOfWarehouse(
        AutomatedNotification $automatedNotification,
        array $excludeProductIds = []
    ): Collection {
        $warehouseManagerQueries = resolve(WarehouseManagerQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $productQueries = resolve(ProductQueries::class);

        return Location::select('id', 'name', 'company_id')
            ->withCount([
                'inventories as total_record_count' => function ($query) use (
                    $automatedNotification,
                    $excludeProductIds
                ): void {
                    $query->where('stock', '>', 0)
                        ->whereNotIn('product_id', $excludeProductIds)
                        ->whereHas('product', function ($productQuery) use ($automatedNotification): void {
                            $productQuery->where('company_id', $automatedNotification->company_id)
                                ->where('status', Statuses::ACTIVE->value)
                                ->where('is_non_inventory', false);
                        })
                        ->where('inventories.stock', '<=', $automatedNotification->low_stock_alert_threshold)
                        ->whereNotIn('inventories.id', function ($query) use ($automatedNotification): void {
                            $query->select('inventories.id')
                                ->from('inventories')
                                ->join('automated_notification_products as anp', function ($join): void {
                                    $join->on('anp.location_id', '=', 'inventories.location_id')
                                        ->on('anp.product_id', '=', 'inventories.product_id');
                                })
                                ->where('anp.automated_notification_id', $automatedNotification->id);
                        })
                        ->whereNotIn('inventories.id', function ($query) use ($automatedNotification): void {
                            $query->select('inventories.id')
                                ->from('inventories')
                                ->join('automated_notification_stores as ans', function ($join): void {
                                    $join->on('ans.location_id', '=', 'inventories.location_id');
                                })
                                ->whereNotIn('inventories.id', function ($query) use (
                                    $automatedNotification
                                ): void {
                                    $query->select('inventories.id')
                                        ->from('inventories')
                                        ->join(
                                            'automated_notification_products as anp',
                                            function ($join): void {
                                                $join->on('anp.location_id', '=', 'inventories.location_id')
                                                    ->on('anp.product_id', '=', 'inventories.product_id');
                                            }
                                        )
                                        ->where('anp.automated_notification_id', $automatedNotification->id);
                                });
                        });
                },
            ])
            ->with([
                'inventories' => function ($query) use ($automatedNotification, $excludeProductIds): void {
                    $query->where('stock', '>', 0)
                        ->whereNotIn('product_id', $excludeProductIds)
                        ->whereHas('product', function ($productQuery) use ($automatedNotification): void {
                            $productQuery->where('company_id', $automatedNotification->company_id)
                                ->where('status', Statuses::ACTIVE->value)
                                ->where('is_non_inventory', false);
                        })
                        ->where('inventories.stock', '<=', $automatedNotification->low_stock_alert_threshold)
                        ->whereNotIn('inventories.id', function ($query) use ($automatedNotification): void {
                            $query->select('inventories.id')
                                ->from('inventories')
                                ->join('automated_notification_products as anp', function ($join): void {
                                    $join->on('anp.location_id', '=', 'inventories.location_id')
                                        ->on('anp.product_id', '=', 'inventories.product_id');
                                })
                                ->where('anp.automated_notification_id', $automatedNotification->id);
                        })
                        ->whereNotIn('inventories.id', function ($query) use ($automatedNotification): void {
                            $query->select('inventories.id')
                                ->from('inventories')
                                ->join('automated_notification_stores as ans', function ($join): void {
                                    $join->on('ans.location_id', '=', 'inventories.location_id');
                                })
                                ->where('ans.automated_notification_id', $automatedNotification->id)
                                ->whereNotIn('inventories.id', function ($query) use ($automatedNotification): void {
                                    $query->select('inventories.id')
                                        ->from('inventories')
                                        ->join('automated_notification_products as anp', function ($join): void {
                                            $join->on('anp.location_id', '=', 'inventories.location_id')
                                                ->on('anp.product_id', '=', 'inventories.product_id');
                                        })
                                        ->where('anp.automated_notification_id', $automatedNotification->id);
                                });
                        })
                        ->limit(10);
                },
                'inventories.product:' . $productQueries->getColumnsForReservedInventoryReports(),
                'warehouseManagers:' . $warehouseManagerQueries->getEmployeeIdColumnNames(),
                'warehouseManagers.employee:' . $employeeQueries->getBasicColumnNames(),
            ])
            ->where('company_id', $automatedNotification->company_id)
            ->where('type_id', LocationTypes::STORE->value)
            ->get();
    }

    public function getWarehousesForCustomReports(int $companyId): Collection
    {
        return Location::select('id', 'name')
            ->where('company_id', $companyId)
            ->where('type_id', LocationTypes::WAREHOUSE->value)
            ->get();
    }

    public function doesWarehouseExist(int $companyId, int $locationId): bool
    {
        return Location::where('id', $locationId)->where('company_id', $companyId)->where(
            'type_id',
            LocationTypes::WAREHOUSE->value
        )->exists();
    }

    public function doAllWarehousesExist(int $companyId, array $locationIds): bool
    {
        $totalRecords = Location::whereIntegerInRaw('id', $locationIds)->where('company_id', $companyId)->where(
            'type_id',
            LocationTypes::WAREHOUSE->value
        )->count();

        return count($locationIds) === $totalRecords;
    }

    public function getWarehousesWithExternalInventories(int $companyId): Collection
    {
        return Location::select('id', 'name')
            ->where('company_id', $companyId)
            ->where('share_inventory_to_external_companies', true)
            ->where('type_id', LocationTypes::WAREHOUSE->value)
            ->get();
    }

    public function update(LocationData $locationData, int $locationId, int $companyId): void
    {
        $googleService = resolve(GoogleGeocodingService::class);
        $data = $locationData->all();
        unset($data['brand_ids']);
        unset($data['sale_channel_ids']);

        $data = $googleService->getCoordinatesForLocation($data);

        $location = Location::where('company_id', $companyId)->findOrFail($locationId);
        $location->update($data);
        if ($locationData->brand_ids) {
            $location->brands()->sync($locationData->brand_ids);
        }

        if (array_key_exists('sale_channel_ids', $locationData->all())) {
            $location->saleChannels()->sync((array) $locationData->sale_channel_ids);
        }
    }

    public function getBasicColumnNames(): string
    {
        return 'id,company_id,name,code,loyalty_point_expiration_days,receipt_footer,disclaimer,city_id,address_line_1,address_line_2,phone,fax';
    }

    public function getLocationsExport(array $filterData, int $companyId): Collection
    {
        return $this->locationQuery($filterData, $companyId)->get();
    }

    public function getStoreIOICityMallConfiguration(int $locationId, int $companyId): Location
    {
        return Location::select('ioi_city_mall_machine_id', 'enable_ioi_city_mall_data_sharing')
            ->where('company_id', $companyId)
            ->where('type_id', LocationTypes::STORE->value)
            ->findOrFail($locationId);
    }

    public function getStoreTRXMallConfiguration(int $locationId, int $companyId): Location
    {
        return Location::select('trx_mall_machine_id', 'enable_trx_mall_data_sharing')
            ->where('company_id', $companyId)
            ->where('type_id', LocationTypes::STORE->value)
            ->findOrFail($locationId);
    }

    public function updateIOICityMallConfiguration(
        array $storeIOICityMallConfiguration,
        int $locationId,
        int $companyId
    ): void {
        $location = Location::select('id', 'ioi_city_mall_machine_id', 'enable_ioi_city_mall_data_sharing')
            ->where('company_id', $companyId)
            ->where('type_id', LocationTypes::STORE->value)
            ->find($locationId);

        if (! $location instanceof Location) {
            return;
        }

        $location->update($storeIOICityMallConfiguration);
    }

    public function updateTRXMallConfiguration(array $storeTRXMallConfiguration, int $locationId, int $companyId): void
    {
        $location = Location::select('id', 'trx_mall_machine_id', 'enable_trx_mall_data_sharing')
            ->where('company_id', $companyId)
            ->where('type_id', LocationTypes::STORE->value)
            ->find($locationId);

        if (! $location instanceof Location) {
            return;
        }

        $location->update($storeTRXMallConfiguration);
    }

    public function getLocationTypeStoreById(int $locationId): ?Location
    {
        return Location::select('id', 'uuid')
            ->where('type_id', LocationTypes::STORE->value)
            ->find($locationId);
    }

    public function getNameColumnName(): string
    {
        return 'id,company_id,name,code,type_id,city_id';
    }

    public function filterByStore(): Closure
    {
        return fn ($query) => $query->select('id')
            ->where('type_id', LocationTypes::STORE->value);
    }

    public function filterByWarehouse(): Closure
    {
        return fn ($query) => $query->select('id')
            ->where('type_id', LocationTypes::WAREHOUSE->value);
    }

    public function getStoreWithBasicColumns(int $companyId): Collection
    {
        return Location::select('id', 'name', 'code', 'company_id', 'region_id')
            ->where('company_id', $companyId)
            ->where('type_id', LocationTypes::STORE->value)
            ->get();
    }

    public function doAllStoresExist(int $companyId, array $locationIds): bool
    {
        $totalRecords = Location::whereIntegerInRaw('id', $locationIds)->where('company_id', $companyId)->where(
            'type_id',
            LocationTypes::STORE->value
        )->count();

        return count($locationIds) === $totalRecords;
    }

    public function getBasicColumnNamesForStoreListAPI(): string
    {
        return 'id,name,code,email,phone,mobile,address_line_1,address_line_2,area_code,sales_tax_percentage,sales_return_days_limit,receipt_footer,disclaimer,city_id,uuid';
    }

    public function searchByName(string $searchText): Closure
    {
        return fn ($query) => $query->select('id')->where('type_id', LocationTypes::STORE->value)->where(
            'name',
            'like',
            '%' . $searchText . '%'
        );
    }

    public function getIdOnlyByName(string $storeName, int $companyId): ?Location
    {
        return Location::select('id')
            ->whereCaseSensitive('name', $storeName)
            ->where('company_id', $companyId)
            ->where('type_id', LocationTypes::STORE->value)
            ->first();
    }

    public function getIdByName(string $storeName, int $companyId): int
    {
        return Location::select('id', 'name')
            ->whereCaseSensitive('name', $storeName)
            ->where('company_id', $companyId)
            ->where('type_id', LocationTypes::STORE->value)
            ->firstOrFail()
            ->id;
    }

    public function getBasicColumnNamesForStoreConfiguration(): string
    {
        return 'id,company_id,registration_number,sst_number,sales_tax_percentage,sales_return_days_limit,receipt_footer,disclaimer,credit_note_expiration_days,loyalty_point_expiration_days,cash_out_limit_info,cash_out_limit_warning,cash_out_limit_restrict,uuid';
    }

    public function getBasicColumnNamesForAdminSaleReports(): string
    {
        return 'id,company_id,name,code,address_line_1,address_line_2,city_id,area_code,receipt_footer,disclaimer,phone';
    }

    public function getLocationCompanyId(): string
    {
        return 'id,company_id';
    }

    public function getColumnsForPriceFallDownCalculation(): string
    {
        return 'id,company_id,price_fall_down_percentage';
    }

    public function getLocationByCountersCounterUpdateId(int $counterUpdateId): Location
    {
        $counterQueries = resolve(CounterQueries::class);

        return Location::select(
            'id',
            'name',
            'code',
            'company_id',
            'sales_tax_percentage',
            'sales_return_days_limit',
            'credit_note_expiration_days',
            'loyalty_point_expiration_days',
        )
            ->whereHas('counters', $counterQueries->filterByCounterUpdateId($counterUpdateId))
            ->where('type_id', LocationTypes::STORE->value)
            ->firstOrFail();
    }

    public function doStoreNamesExists(array $locationNames, int $companyId): bool
    {
        if ([] === $locationNames) {
            return false;
        }

        $filteredStoreNames = array_unique(array_filter($locationNames));
        if ([] === $filteredStoreNames) {
            return false;
        }

        $totalRecords = Location::whereInCaseSensitive('name', $filteredStoreNames)
            ->where('company_id', $companyId)
            ->where('type_id', LocationTypes::STORE->value)
            ->count();

        return count($filteredStoreNames) === $totalRecords;
    }

    public function getIdAndNameByNames(array $locationNames, int $companyId): Collection
    {
        return Location::select('id', 'name')
            ->whereInCaseSensitive('name', $locationNames)
            ->where('company_id', $companyId)
            ->where('type_id', LocationTypes::STORE->value)
            ->get();
    }

    public function getIdAndNameByName(string $locationName, int $companyId, int $typeId): ?Location
    {
        return Location::select('id', 'name')
            ->where('name', $locationName)
            ->where('company_id', $companyId)
            ->where('type_id', $typeId)
            ->first();
    }

    public function getBasicColumnNamesForEcommerceLocationConfiguration(): string
    {
        return 'id,company_id,name,registration_number,sst_number,sales_tax_percentage,sales_return_days_limit,phone,email,address_line_1,address_line_2,city_id,area_code';
    }

    public function searchStoreByName(string $searchText): Closure
    {
        return fn ($query) => $query->select('id')->where('type_id', LocationTypes::STORE->value)->where(
            'name',
            'like',
            '%' . $searchText . '%'
        );
    }

    public function getById(
        int $locationId,
        int $companyId,
        int $typeId,
        array $columnNames = ['id', 'name', 'code'],
    ): Location {
        return Location::select(...$columnNames)
            ->where('company_id', $companyId)
            ->where('type_id', $typeId)
            ->findOrFail($locationId);
    }

    public function getByIds(
        array $locationIds,
        int $typeId,
        array $columnNames = ['id', 'name', 'code'],
    ): Collection {
        return Location::select(...$columnNames)
            ->where('type_id', $typeId)
            ->whereIn('id', $locationIds)
            ->get();
    }

    public function getByIdsWithNameAndCode(int $companyId, array $locationIds): Collection
    {
        return Location::select('id', 'name', 'code')
            ->whereIntegerInRaw('id', $locationIds)
            ->where('company_id', $companyId)
            ->get();
    }

    public function getWarehouseWithBasicColumns(int $companyId): Collection
    {
        return Location::select('id', 'name', 'company_id')
            ->where('company_id', $companyId)
            ->where('type_id', LocationTypes::WAREHOUSE->value)
            ->get();
    }

    public function filterByStoreIdAndCompanyId(int $locationId, int $companyId): Closure
    {
        return fn ($query) => $query->select('id')
            ->when($locationId > 0, function ($query) use ($locationId): void {
                $query->where('id', $locationId)
                    ->where('type_id', LocationTypes::STORE->value);
            })
            ->where('company_id', $companyId);
    }

    public function getYesterdayCreatedLocationsIds(int $typeId): array
    {
        $yesterdayDate = Carbon::yesterday()->format('Y-m-d');

        return Location::query()
            ->select('id')
            ->where('created_at', $yesterdayDate)
            ->where('type_id', $typeId)
            ->pluck('id')
            ->toArray();
    }

    public function getAllLocationsIds(int $typeId): array
    {
        return Location::query()
            ->select('id')
            ->where('type_id', $typeId)
            ->pluck('id')
            ->toArray();
    }

    public function getByIdWithNameAndCode(int $companyId, int $locationId): Location
    {
        return Location::select('id', 'name', 'code')
            ->where('company_id', $companyId)
            ->findOrFail($locationId);
    }

    public function getNameAndCodeWithCompanyById(string $locationId): Location
    {
        $companyQueries = resolve(CompanyQueries::class);

        return Location::select('id', 'name', 'code', 'company_id')
            ->with('company:' . $companyQueries->getBasicColumnNamesWithCode())
            ->findOrFail($locationId);
    }

    public function getLocationSalesTaxPercentage(int $locationId): Location
    {
        $companyQueries = resolve(CompanyQueries::class);

        return Location::select('id', 'sales_tax_percentage', 'company_id')
            ->with('company:' . $companyQueries->getBasicColumnForOrders())
            ->findOrFail($locationId);
    }

    public function existsByName(string $name, int $companyId): bool
    {
        return Location::whereCaseSensitive('name', $name)->where('company_id', $companyId)->where(
            'type_id',
            LocationTypes::STORE->value
        )->exists();
    }

    public function existsByPhone(string $phone, int $companyId): bool
    {
        return Location::whereCaseSensitive('phone', $phone)->where('company_id', $companyId)->where(
            'type_id',
            LocationTypes::STORE->value
        )->exists();
    }

    public function existsByNameAndTypeId(string $name, int $typeId, int $companyId): bool
    {
        return Location::whereCaseSensitive('name', $name)->where('company_id', $companyId)->where(
            'type_id',
            $typeId
        )->exists();
    }

    public function existsByCodeAndTypeId(string $code, int $typeId, int $companyId): bool
    {
        return Location::whereCaseSensitive('code', $code)->where('company_id', $companyId)->where(
            'type_id',
            $typeId
        )->exists();
    }

    public function existsByPhoneAndTypeId(string $phone, int $typeId, int $companyId): bool
    {
        return Location::whereCaseSensitive('phone', $phone)->where('company_id', $companyId)->where(
            'type_id',
            $typeId
        )->exists();
    }

    public function isLocationNameTakenByAnother(string $name, string $phone, int $typeId, int $companyId): bool
    {
        return Location::whereNot('phone', $phone)
            ->whereCaseSensitive('name', $name)
            ->where('company_id', $companyId)
            ->where('type_id', $typeId)
            ->exists();
    }

    public function isLocationCodeTakenByAnother(string $code, string $phone, int $typeId, int $companyId): bool
    {
        return Location::whereNot('phone', $phone)
            ->whereCaseSensitive('code', $code)
            ->where('company_id', $companyId)
            ->where('type_id', $typeId)
            ->exists();
    }

    public function updateByPhone(array $locationData, string $mobileNumber, int $typeId, int $companyId): void
    {
        $brandIds = $locationData['brand_ids'];
        unset($locationData['brand_ids'], $locationData['sale_channel_ids']);

        $location = Location::select('id')
            ->where('phone', $mobileNumber)
            ->where('type_id', $typeId)
            ->where('company_id', $companyId)
            ->first();

        if ($location instanceof Location) {
            $location->update($locationData);
            $location->brands()->sync($brandIds);
        }
    }

    public function getStoreNamesWithCodesByIds(int $companyId, array $locationIds): ?Location
    {
        $column = count($locationIds) >= config(
            'app.location_name_code_limit'
        ) ? 'code' : "CONCAT(name, '(', code, ')')";

        $query = Location::selectRaw(sprintf("GROUP_CONCAT(%s SEPARATOR ', ') AS getNamesWithCodes", $column));

        return $query->whereIntegerInRaw('id', $locationIds)
            ->where('company_id', $companyId)
            ->where('type_id', LocationTypes::STORE->value)
            ->first();
    }

    public function getLocationNamesWithCodesByIds(int $companyId, array $locationIds): ?Location
    {
        $column = count($locationIds) >= config(
            'app.location_name_code_limit'
        ) ? 'code' : "CONCAT(name, '(', code, ')')";

        $query = Location::selectRaw(sprintf("GROUP_CONCAT(%s SEPARATOR ', ') AS getNamesWithCodes", $column));

        return $query->whereIntegerInRaw('id', $locationIds)
            ->where('company_id', $companyId)
            ->first();
    }

    public function getWarehouseNamesWithCodesByIds(int $companyId, array $locationIds): ?Location
    {
        $column = count($locationIds) >= config(
            'app.location_name_code_limit'
        ) ? 'code' : "CONCAT(name, '(', code, ')')";

        $query = Location::selectRaw(sprintf("GROUP_CONCAT(%s SEPARATOR ', ') AS getNamesWithCodes", $column));

        return $query->whereIntegerInRaw('id', $locationIds)
            ->where('company_id', $companyId)
            ->where('type_id', LocationTypes::WAREHOUSE->value)
            ->first();
    }

    public function getStoreByCounters(int $counterId): Location
    {
        $counterQueries = resolve(CounterQueries::class);

        return Location::select(
            'id',
            'name',
            'company_id',
            'sales_tax_percentage',
            'sales_return_days_limit',
            'credit_note_expiration_days',
            'loyalty_point_expiration_days',
        )
            ->whereHas('counters', $counterQueries->filterById($counterId))
            ->where('type_id', LocationTypes::STORE->value)
            ->firstOrFail();
    }

    public function getStoresForCustomReports(int $companyId): Collection
    {
        return Location::select('id', 'name')
            ->where('company_id', $companyId)
            ->where('type_id', LocationTypes::STORE->value)
            ->get();
    }

    public function getLoyaltyPointExpirationDaysById(int $locationId, int $companyId): Location
    {
        return Location::select('id', 'name', 'loyalty_point_expiration_days')
            ->where('company_id', $companyId)
            ->where('type_id', LocationTypes::STORE->value)
            ->findOrFail($locationId);
    }

    public function doStoreNameExist(string $locationName, int $companyId): bool
    {
        return Location::whereCaseSensitive('name', $locationName)
            ->where('company_id', $companyId)
            ->where('type_id', LocationTypes::STORE->value)
            ->exists();
    }

    public function getRegionColumnNames(): string
    {
        return 'id,company_id,name,region_id,type_id';
    }

    public function getNameColumnNameForSaleSeasons(): string
    {
        return 'id,company_id,name,code,region_id';
    }

    public function getCompanyIdOfStore(int $locationId): int
    {
        return Location::select('company_id')
            ->where('type_id', LocationTypes::STORE->value)
            ->findOrFail($locationId)->company_id;
    }

    public function getIdByRefIdAndRefType(int|string $locationId): int
    {
        return Location::select('id', 'type_id')
            ->when(is_int($locationId) || strlen($locationId) < 4, function ($query) use ($locationId): void {
                $query->where('ref_id', $locationId);
            }, function ($query) use ($locationId): void {
                $query->where('uuid', $locationId);
            })
            ->firstOrFail()
            ->id;
    }

    public function getIdByRefIdAndRef(int|string $locationId): Location
    {
        return Location::select('id', 'type_id', 'company_id')
            ->where(function ($query) use ($locationId): void {
                $query->where('type_id', LocationTypes::STORE->value)
                    ->where('uuid', $locationId);
            })
            ->firstOrFail();
    }

    public function findByIdWithReceiptFooterDisclaimerAndCreatedAt(int $locationId): ?Location
    {
        return Location::select('id', 'receipt_footer', 'disclaimer', 'created_at')->where(
            'type_id',
            LocationTypes::STORE->value
        )->find($locationId);
    }

    public function getWithAutomaticDayCloseTimeAndName(): Collection
    {
        return Location::query()
            ->select('id', 'automatic_day_close_time', 'name', 'created_at')
            ->where('is_automatic_day_close', true)
            ->whereNotNull('automatic_day_close_time')
            ->where('type_id', LocationTypes::STORE->value)
            ->get();
    }

    public function getCachedStoresSalesForChart(
        int $companyId,
        ?int $locationId,
        ?int $brandId,
        string|array $date,
        bool $refresh = false
    ): Collection {
        if (is_array($date)) {
            $cacheKey = null !== $locationId ? 'cache-stores-sales-' . $locationId . $brandId . $date[0] . $date[1] : 'cache-stores-sales-' . $date[0] . $date[1] . $brandId;
        } else {
            $cacheKey = null !== $locationId ? 'cache-stores-sales-' . $locationId . $brandId . $date : 'cache-stores-sales-' . $date . $brandId;
        }

        if ($refresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember(
            $cacheKey,
            900,
            fn (): Collection => Location::query()
                ->select(
                    'locations.id',
                    'locations.name',
                    'locations.code',
                    'store_sale_total.sales_count',
                    DB::raw(
                        '(COALESCE(store_sale_total.total_paid_amount, 0) - COALESCE(store_return_total.return_amount, 0)) as total_sales'
                    ),
                    DB::raw(
                        '(COALESCE(store_sale_total.units_sold, 0) - COALESCE(store_return_total.return_units, 0)) as total_units_sold'
                    ),
                )
                ->leftJoinSub(
                    DB::table('sales')
                        ->join('counter_updates', 'sales.counter_update_id', '=', 'counter_updates.id')
                        ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
                        ->join('products', 'products.id', '=', 'sale_items.product_id')
                        ->where('locations.company_id', $companyId)
                        ->when((int) $locationId > 0, function ($query) use ($locationId): void {
                            $query->where('counters.location_id', $locationId);
                        })
                        ->when((int) $brandId > 0, function ($query) use ($brandId): void {
                            $query->where('products.brand_id', $brandId);
                        })
                        ->when($date, function ($query) use ($date): void {
                            if (is_array($date)) {
                                $query->where(
                                    'counter_updates.opened_by_pos_at',
                                    '>=',
                                    CommonFunctions::addStartTime($date[0])
                                )
                                    ->where(
                                        'counter_updates.opened_by_pos_at',
                                        '<=',
                                        CommonFunctions::addEndTime($date[1])
                                    );
                            }

                            if (is_string($date)) {
                                $query->where(
                                    'counter_updates.opened_by_pos_at',
                                    '>=',
                                    CommonFunctions::addStartTime($date)
                                )
                                    ->where(
                                        'counter_updates.opened_by_pos_at',
                                        '<=',
                                        CommonFunctions::addEndTime($date)
                                    );
                            }
                        })
                        ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
                        ->select(
                            'locations.id as location_id',
                            DB::raw('SUM(sale_items.total_price_paid) as total_paid_amount'),
                            DB::raw('SUM(sale_items.quantity) as units_sold'),
                            DB::raw('COUNT(sales.id) as sales_count'),
                        )
                        ->groupBy('counters.location_id'),
                    'store_sale_total',
                    'store_sale_total.location_id',
                    '=',
                    'locations.id'
                )
                ->leftJoinSub(
                    DB::table('sale_returns')
                        ->join('counter_updates', 'sale_returns.counter_update_id', '=', 'counter_updates.id')
                        ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->join('sale_return_items', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                        ->join('products', 'products.id', '=', 'sale_return_items.product_id')
                        ->where('locations.company_id', $companyId)
                        ->when((int) $locationId > 0, function ($query) use ($locationId): void {
                            $query->where('counters.location_id', $locationId);
                        })
                        ->when((int) $brandId > 0, function ($query) use ($brandId): void {
                            $query->where('products.brand_id', $brandId);
                        })
                        ->when($date, function ($query) use ($date): void {
                            if (is_array($date)) {
                                $query->where(
                                    'counter_updates.opened_by_pos_at',
                                    '>=',
                                    CommonFunctions::addStartTime($date[0])
                                )
                                    ->where(
                                        'counter_updates.opened_by_pos_at',
                                        '<=',
                                        CommonFunctions::addEndTime($date[1])
                                    );
                            }

                            if (is_string($date)) {
                                $query->where(
                                    'counter_updates.opened_by_pos_at',
                                    '>=',
                                    CommonFunctions::addStartTime($date)
                                )
                                    ->where(
                                        'counter_updates.opened_by_pos_at',
                                        '<=',
                                        CommonFunctions::addEndTime($date)
                                    );
                            }
                        })
                        ->select(
                            'locations.id as location_id',
                            DB::raw('SUM(sale_return_items.total_price_paid) as return_amount'),
                            DB::raw('SUM(sale_return_items.quantity) as return_units'),
                        )
                        ->groupBy('counters.location_id'),
                    'store_return_total',
                    'store_return_total.location_id',
                    '=',
                    'locations.id'
                )
                ->whereNotNull('store_sale_total.total_paid_amount')
                ->orWhereNotNull('store_sale_total.units_sold')
                ->orWhereNotNull('store_sale_total.sales_count')
                ->orWhereNotNull('store_return_total.return_amount')
                ->orWhereNotNull('store_return_total.return_units')
                ->having('total_sales', '>', 0)
                ->having('total_units_sold', '>', 0)
                ->orderByDesc('total_sales')
                ->get()
        );
    }

    public function getIdAndNameByIds(array $ids, int $companyId): Collection
    {
        return Location::select('id', 'name')
            ->where('company_id', $companyId)
            ->whereIntegerInRaw('id', $ids)
            ->where('type_id', LocationTypes::STORE->value)
            ->get();
    }

    public function getWithExternalInventoriesByType(int $companyId, int $typeId): Collection
    {
        return Location::select('id', 'name')
            ->where('company_id', $companyId)
            ->where('share_inventory_to_external_companies', true)
            ->where('type_id', $typeId)
            ->get();
    }

    public function getStoreWithCompanyByCountersCounterUpdateId(int $counterUpdateId): Location
    {
        $counterService = resolve(CounterQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $countryQueries = resolve(CountryQueries::class);
        $currencyQueries = resolve(CurrencyQueries::class);
        $currencyRateQueries = resolve(CurrencyRateQueries::class);
        $companySettingQueries = resolve(CompanySettingQueries::class);

        return Location::select(
            'id',
            'name',
            'code',
            'company_id',
            'sales_tax_percentage',
            'sales_return_days_limit',
            'credit_note_expiration_days',
            'loyalty_point_expiration_days',
        )
            ->with([
                'company:' . $companyQueries->getColumnForBookingPayment(),
                'company.countries:' . $countryQueries->getBasicColumnNames(),
                'company.countries.currency:' . $currencyQueries->getBasicColumnNames(),
                'company.countries.currency.currencyRate:' . $currencyRateQueries->getBasicColumnNames(),
                'company.companySetting:' . $companySettingQueries->getNameColumnName(),
            ])
            ->whereHas('counters', $counterService->filterByCounterUpdateId($counterUpdateId))
            ->where('type_id', LocationTypes::STORE->value)
            ->firstOrFail();
    }

    public function getCompanyOfStore(int $locationId): Location
    {
        $companyQueries = resolve(CompanyQueries::class);

        return Location::select('company_id')
            ->with('company:' . $companyQueries->getBasicColumnsForEInvoice())
            ->findOrFail($locationId);
    }

    public function getCompanyLogoOfStore(int $locationId): Location
    {
        $mediaQueries = resolve(MediaQueries::class);

        return Location::select('id', 'company_id')
            ->with(['company', 'company.media:' . $mediaQueries->getBasicColumnNames()])
            ->where('type_id', LocationTypes::STORE->value)
            ->findOrFail($locationId);
    }

    public function getCompanyLogoOfStoreForRegisterMember(int|string $locationId): Location
    {
        $mediaQueries = resolve(MediaQueries::class);

        return Location::select('id', 'company_id')
            ->with(['company', 'company.media:' . $mediaQueries->getBasicColumnNames()])
            ->when(is_int($locationId) || strlen($locationId) < 4, function ($query) use ($locationId): void {
                $query->where('ref_id', $locationId);
            }, function ($query) use ($locationId): void {
                $query->where('uuid', $locationId);
            })
            ->firstOrFail();
    }

    public function getByCodesAndCompanyId(array $codes, int $companyId, int $typeId): Collection
    {
        return Location::select('id', 'name', 'code')
            ->where('company_id', $companyId)
            ->whereInCaseSensitive('code', $codes)
            ->where('type_id', $typeId)
            ->get();
    }

    public function getLocationsOfRegions(int $regionId, int $companyId, int $typeId): Collection
    {
        return Location::query()
            ->select('id', 'name')
            ->where('region_id', $regionId)
            ->where('company_id', $companyId)
            ->where('type_id', $typeId)
            ->get();
    }

    public function getLocationsOfLocationsName(array $names, int $companyId, int $typeId): Collection
    {
        return Location::query()
            ->select('id', 'name')
            ->whereInCaseSensitive('name', $names)
            ->where('company_id', $companyId)
            ->where('type_id', $typeId)
            ->get();
    }

    public function getLocationSalesSummary(array $filterData, int $companyId): Collection
    {
        return Location::query()
            ->select(
                'locations.id',
                'locations.name',
                DB::raw(
                    '(COALESCE(store_sale_total.total_paid_amount, 0) - COALESCE(store_return_total.return_amount, 0)) as total_sales'
                ),
                DB::raw(
                    '(COALESCE(store_sale_total.units_sold, 0) - COALESCE(store_return_total.return_units, 0)) as total_units_sold'
                )
            )
            ->leftJoinSub(
                DB::table('sales')
                    ->join('counter_updates', 'sales.counter_update_id', '=', 'counter_updates.id')
                    ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                    ->join('locations', 'counters.location_id', '=', 'locations.id')
                    ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
                    ->join('products', 'sale_items.product_id', '=', 'products.id')
                    ->when(
                        $filterData['type'] === StoreRevenueDashboardTableFilterTypes::CATEGORIES->value,
                        function ($query) use ($filterData): void {
                            $query->join('category_product', 'products.id', '=', 'category_product.product_id')
                                ->where('category_product.category_id', $filterData['id']);
                        }
                    )
                    ->when(
                        $filterData['type'] === StoreRevenueDashboardTableFilterTypes::COLORS->value,
                        function ($query) use ($filterData): void {
                            $query->where('products.color_id', $filterData['id']);
                        }
                    )
                    ->when(
                        $filterData['type'] === StoreRevenueDashboardTableFilterTypes::BRANDS->value,
                        function ($query) use ($filterData): void {
                            $query->where('products.brand_id', $filterData['id']);
                        }
                    )
                    ->when(
                        $filterData['type'] === StoreRevenueDashboardTableFilterTypes::DEPARTMENTS->value,
                        function ($query) use ($filterData): void {
                            $query->where('products.department_id', $filterData['id']);
                        }
                    )
                    ->when(
                        $filterData['type'] === StoreRevenueDashboardTableFilterTypes::COLOR_GROUPS->value,
                        function ($query) use ($filterData): void {
                            $query->join('colors', 'products.color_id', '=', 'colors.id')
                                ->where('colors.group_id', $filterData['id']);
                        }
                    )
                    ->when(
                        $filterData['type'] === StoreRevenueDashboardTableFilterTypes::SIZES->value,
                        function ($query) use ($filterData): void {
                            $query->where('products.size_id', $filterData['id']);
                        }
                    )
                    ->when(
                        $filterData['type'] === StoreRevenueDashboardTableFilterTypes::STYLES->value,
                        function ($query) use ($filterData): void {
                            $query->where('products.style_id', $filterData['id']);
                        }
                    )
                    ->where('locations.company_id', $companyId)
                    ->where(
                        'counter_updates.opened_by_pos_at',
                        '>=',
                        CommonFunctions::addStartTime($filterData['date'])
                    )
                    ->where(
                        'counter_updates.opened_by_pos_at',
                        '<=',
                        CommonFunctions::addEndTime($filterData['date'])
                    )
                    ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
                    ->select(
                        'locations.id as location_id',
                        DB::raw('SUM(sale_items.total_price_paid) as total_paid_amount'),
                        DB::raw('SUM(sale_items.quantity) as units_sold')
                    )
                    ->groupBy('location_id'),
                'store_sale_total',
                'store_sale_total.location_id',
                '=',
                'locations.id'
            )
            ->leftJoinSub(
                DB::table('sale_returns')
                    ->join('counter_updates', 'sale_returns.counter_update_id', '=', 'counter_updates.id')
                    ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                    ->join('locations', 'counters.location_id', '=', 'locations.id')
                    ->join('sale_return_items', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                    ->join('products', 'sale_return_items.product_id', '=', 'products.id')
                    ->when(
                        $filterData['type'] === StoreRevenueDashboardTableFilterTypes::CATEGORIES->value,
                        function ($query) use ($filterData): void {
                            $query->join('category_product', 'products.id', '=', 'category_product.product_id')
                                ->where('category_product.category_id', $filterData['id']);
                        }
                    )
                    ->when(
                        $filterData['type'] === StoreRevenueDashboardTableFilterTypes::COLORS->value,
                        function ($query) use ($filterData): void {
                            $query->where('products.color_id', $filterData['id']);
                        }
                    )
                    ->when(
                        $filterData['type'] === StoreRevenueDashboardTableFilterTypes::BRANDS->value,
                        function ($query) use ($filterData): void {
                            $query->where('products.brand_id', $filterData['id']);
                        }
                    )
                    ->when(
                        $filterData['type'] === StoreRevenueDashboardTableFilterTypes::DEPARTMENTS->value,
                        function ($query) use ($filterData): void {
                            $query->where('products.department_id', $filterData['id']);
                        }
                    )
                    ->when(
                        $filterData['type'] === StoreRevenueDashboardTableFilterTypes::COLOR_GROUPS->value,
                        function ($query) use ($filterData): void {
                            $query->join('colors', 'products.color_id', '=', 'colors.id')
                                ->where('colors.group_id', $filterData['id']);
                        }
                    )
                    ->when(
                        $filterData['type'] === StoreRevenueDashboardTableFilterTypes::SIZES->value,
                        function ($query) use ($filterData): void {
                            $query->where('products.size_id', $filterData['id']);
                        }
                    )
                    ->when(
                        $filterData['type'] === StoreRevenueDashboardTableFilterTypes::STYLES->value,
                        function ($query) use ($filterData): void {
                            $query->where('products.style_id', $filterData['id']);
                        }
                    )
                    ->where('locations.company_id', $companyId)
                    ->where(
                        'counter_updates.opened_by_pos_at',
                        '>=',
                        CommonFunctions::addStartTime($filterData['date'])
                    )
                    ->where(
                        'counter_updates.opened_by_pos_at',
                        '<=',
                        CommonFunctions::addEndTime($filterData['date'])
                    )
                    ->select(
                        'locations.id as location_id',
                        DB::raw('SUM(sale_return_items.total_price_paid) as return_amount'),
                        DB::raw('SUM(sale_return_items.quantity) as return_units')
                    )
                    ->groupBy('location_id'),
                'store_return_total',
                'store_return_total.location_id',
                '=',
                'locations.id'
            )
            ->whereNotNull('store_sale_total.total_paid_amount')
            ->orWhereNotNull('store_sale_total.units_sold')
            ->orWhereNotNull('store_return_total.return_amount')
            ->orWhereNotNull('store_return_total.return_units')
            ->orderByDesc('total_sales')
            ->get();
    }

    public function getCachedLocationsSalesForChart(
        int $companyId,
        ?int $locationId,
        ?int $brandId,
        string|array $date,
        bool $refresh = false
    ): Collection {
        if (is_array($date)) {
            $cacheKey = null !== $locationId ? 'cache-locations-sales-' . $locationId . $brandId . $date[0] . $date[1] : 'cache-locations-sales-' . $date[0] . $date[1] . $brandId;
        } else {
            $cacheKey = null !== $locationId ? 'cache-locations-sales-' . $locationId . $brandId . $date : 'cache-locations-sales-' . $date . $brandId;
        }

        if ($refresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember(
            $cacheKey,
            900,
            fn (): Collection => Location::query()
                ->select(
                    'locations.id',
                    'locations.name',
                    'locations.code',
                    'store_sale_total.sales_count',
                    DB::raw(
                        '(COALESCE(store_sale_total.total_paid_amount, 0) - COALESCE(store_return_total.return_amount, 0)) as total_sales'
                    ),
                    DB::raw(
                        '(COALESCE(store_sale_total.units_sold, 0) - COALESCE(store_return_total.return_units, 0)) as total_units_sold'
                    ),
                )
                ->leftJoinSub(
                    DB::table('sales')
                        ->join('counter_updates', 'sales.counter_update_id', '=', 'counter_updates.id')
                        ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
                        ->join('products', 'products.id', '=', 'sale_items.product_id')
                        ->where('locations.company_id', $companyId)
                        ->when((int) $locationId > 0, function ($query) use ($locationId): void {
                            $query->where('counters.location_id', $locationId);
                        })
                        ->when((int) $brandId > 0, function ($query) use ($brandId): void {
                            $query->where('products.brand_id', $brandId);
                        })
                        ->when($date, function ($query) use ($date): void {
                            if (is_array($date)) {
                                $query->where(
                                    'counter_updates.opened_by_pos_at',
                                    '>=',
                                    CommonFunctions::addStartTime($date[0])
                                )
                                    ->where(
                                        'counter_updates.opened_by_pos_at',
                                        '<=',
                                        CommonFunctions::addEndTime($date[1])
                                    );
                            }

                            if (is_string($date)) {
                                $query->where(
                                    'counter_updates.opened_by_pos_at',
                                    '>=',
                                    CommonFunctions::addStartTime($date)
                                )
                                    ->where(
                                        'counter_updates.opened_by_pos_at',
                                        '<=',
                                        CommonFunctions::addEndTime($date)
                                    );
                            }
                        })
                        ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
                        ->select(
                            'locations.id as location_id',
                            DB::raw('SUM(sale_items.total_price_paid) as total_paid_amount'),
                            DB::raw('SUM(sale_items.quantity) as units_sold'),
                            DB::raw('COUNT(sales.id) as sales_count'),
                        )
                        ->groupBy('counters.location_id'),
                    'store_sale_total',
                    'store_sale_total.location_id',
                    '=',
                    'locations.id'
                )
                ->leftJoinSub(
                    DB::table('sale_returns')
                        ->join('counter_updates', 'sale_returns.counter_update_id', '=', 'counter_updates.id')
                        ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->join('sale_return_items', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                        ->join('products', 'products.id', '=', 'sale_return_items.product_id')
                        ->where('locations.company_id', $companyId)
                        ->when((int) $locationId > 0, function ($query) use ($locationId): void {
                            $query->where('counters.location_id', $locationId);
                        })
                        ->when((int) $brandId > 0, function ($query) use ($brandId): void {
                            $query->where('products.brand_id', $brandId);
                        })
                        ->when($date, function ($query) use ($date): void {
                            if (is_array($date)) {
                                $query->where(
                                    'counter_updates.opened_by_pos_at',
                                    '>=',
                                    CommonFunctions::addStartTime($date[0])
                                )
                                    ->where(
                                        'counter_updates.opened_by_pos_at',
                                        '<=',
                                        CommonFunctions::addEndTime($date[1])
                                    );
                            }

                            if (is_string($date)) {
                                $query->where(
                                    'counter_updates.opened_by_pos_at',
                                    '>=',
                                    CommonFunctions::addStartTime($date)
                                )
                                    ->where(
                                        'counter_updates.opened_by_pos_at',
                                        '<=',
                                        CommonFunctions::addEndTime($date)
                                    );
                            }
                        })
                        ->select(
                            'locations.id as location_id',
                            DB::raw('SUM(sale_return_items.total_price_paid) as return_amount'),
                            DB::raw('SUM(sale_return_items.quantity) as return_units'),
                        )
                        ->groupBy('counters.location_id'),
                    'store_return_total',
                    'store_return_total.location_id',
                    '=',
                    'locations.id'
                )
                ->whereNotNull('store_sale_total.total_paid_amount')
                ->orWhereNotNull('store_sale_total.units_sold')
                ->orWhereNotNull('store_sale_total.sales_count')
                ->orWhereNotNull('store_return_total.return_amount')
                ->orWhereNotNull('store_return_total.return_units')
                ->orderByDesc('total_sales')
                ->get()
        );
    }

    public function cacheSaleByRegionId(int $regionId, int $companyId, int $brandId): Collection
    {
        $cacheKey = 'cache-top-ten-locations-by-region-id' . $regionId . '-' . $companyId . '-' . $brandId;

        return Cache::remember(
            $cacheKey,
            900,
            fn (): Collection => Location::query()
                ->select(
                    'locations.id',
                    'locations.name',
                    DB::raw(
                        '(COALESCE(store_sale_total.total_paid_amount, 0) - COALESCE(store_return_total.return_amount, 0)) as total_sales'
                    ),
                )
                ->leftJoinSub(
                    DB::table('sales')
                        ->join('counter_updates', 'sales.counter_update_id', '=', 'counter_updates.id')
                        ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
                        ->join('products', 'products.id', '=', 'sale_items.product_id')
                        ->where('locations.company_id', $companyId)
                        ->where('locations.region_id', $regionId)
                        ->when(0 !== $brandId, function ($query) use ($brandId): void {
                            $query->where('products.brand_id', $brandId);
                        })
                        ->where(
                            'counter_updates.opened_by_pos_at',
                            '>=',
                            now()->startOfDay()->format('Y-m-d H:i:s')
                        )
                        ->where('counter_updates.opened_by_pos_at', '<=', now()->endOfDay()->format('Y-m-d H:i:s'))
                        ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
                        ->select(
                            'locations.id as location_id',
                            DB::raw('SUM(sale_items.total_price_paid) as total_paid_amount'),
                            DB::raw('SUM(sale_items.quantity) as units_sold'),
                            DB::raw('COUNT(sales.id) as sales_count'),
                        )
                        ->groupBy('counters.location_id'),
                    'store_sale_total',
                    'store_sale_total.location_id',
                    '=',
                    'locations.id'
                )
                ->leftJoinSub(
                    DB::table('sale_returns')
                        ->join('counter_updates', 'sale_returns.counter_update_id', '=', 'counter_updates.id')
                        ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->join('sale_return_items', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                        ->join('products', 'products.id', '=', 'sale_return_items.product_id')
                        ->where('locations.company_id', $companyId)
                        ->where('locations.region_id', $regionId)
                        ->when(0 !== $brandId, function ($query) use ($brandId): void {
                            $query->where('products.brand_id', $brandId);
                        })
                        ->where(
                            'counter_updates.opened_by_pos_at',
                            '>=',
                            now()->startOfDay()->format('Y-m-d H:i:s')
                        )
                        ->where('counter_updates.opened_by_pos_at', '<=', now()->endOfDay()->format('Y-m-d H:i:s'))
                        ->select(
                            'locations.id as location_id',
                            DB::raw('SUM(sale_return_items.total_price_paid) as return_amount'),
                            DB::raw('SUM(sale_return_items.quantity) as return_units'),
                        )
                        ->groupBy('counters.location_id'),
                    'store_return_total',
                    'store_return_total.location_id',
                    '=',
                    'locations.id'
                )
                ->whereNotNull('store_sale_total.total_paid_amount')
                ->orWhereNotNull('store_sale_total.units_sold')
                ->orWhereNotNull('store_sale_total.sales_count')
                ->orWhereNotNull('store_return_total.return_amount')
                ->orWhereNotNull('store_return_total.return_units')
                ->orderByDesc('total_sales')
                ->get()
        );
    }

    public function getByIdWithReceiptFooterDisclaimerAndCreatedAt(int $locationId): Location
    {
        return Location::select('id', 'receipt_footer', 'disclaimer', 'created_at')->where(
            'type_id',
            LocationTypes::STORE->value
        )->findOrFail($locationId);
    }

    public function hasBrands(int $companyId, array $brandIds): bool
    {
        $brandQueries = new BrandQueries();

        return Location::where('company_id', $companyId)
            ->whereHas('brands', $brandQueries->filterByIds($brandIds))
            ->where('type_id', LocationTypes::STORE->value)
            ->exists();
    }

    public function getStoresWhereAllowIOICityMallDataSharingIsTrue(int $companyId): Collection
    {
        return Location::select(
            'id',
            'name',
            'enable_ioi_city_mall_data_sharing',
            'ioi_city_mall_machine_id',
            'sales_tax_percentage'
        )
            ->where('company_id', $companyId)
            ->whereNotNull('ioi_city_mall_machine_id')
            ->where('enable_ioi_city_mall_data_sharing', true)
            ->where('type_id', LocationTypes::STORE->value)
            ->get();
    }

    public function getDetailsByNameForIOICityMall(string $locationName): ?Location
    {
        $companyQueries = resolve(CompanyQueries::class);

        return Location::select(
            'id',
            'name',
            'company_id',
            'enable_ioi_city_mall_data_sharing',
            'ioi_city_mall_machine_id',
            'sales_tax_percentage'
        )
            ->with('company:' . $companyQueries->getEnableIoiCityMallIntegrationColumn())
            ->where('name', $locationName)
            ->whereNotNull('ioi_city_mall_machine_id')
            ->where('enable_ioi_city_mall_data_sharing', true)
            ->where('type_id', LocationTypes::STORE->value)
            ->first();
    }

    public function getStoresWhereAllowTRXMallDataSharingIsTrue(int $companyId): Collection
    {
        return Location::select(
            'id',
            'name',
            'enable_trx_mall_data_sharing',
            'trx_mall_machine_id',
            'sales_tax_percentage'
        )
            ->where('company_id', $companyId)
            ->whereNotNull('trx_mall_machine_id')
            ->where('enable_trx_mall_data_sharing', true)
            ->where('type_id', LocationTypes::STORE->value)
            ->get();
    }

    public function getIdByNameForIOICityMall(string $locationName): ?Location
    {
        return Location::select('id')
            ->where('name', $locationName)
            ->whereNotNull('ioi_city_mall_machine_id')
            ->where('enable_ioi_city_mall_data_sharing', true)
            ->where('type_id', LocationTypes::STORE->value)
            ->first();
    }

    public function getIdByNameForTRXMall(string $locationName): ?Location
    {
        return Location::select('id')
            ->where('name', $locationName)
            ->whereNotNull('trx_mall_machine_id')
            ->where('enable_trx_mall_data_sharing', true)
            ->where('type_id', LocationTypes::STORE->value)
            ->first();
    }

    public function getDetailsByNameForTRXMall(string $locationName): ?Location
    {
        $companyQueries = resolve(CompanyQueries::class);

        return Location::select(
            'id',
            'name',
            'company_id',
            'trx_mall_machine_id',
            'enable_trx_mall_data_sharing',
            'sales_tax_percentage'
        )
            ->with('company:' . $companyQueries->getEnableTRXMallIntegrationColumn())
            ->where('name', $locationName)
            ->whereNotNull('trx_mall_machine_id')
            ->where('enable_trx_mall_data_sharing', true)
            ->where('type_id', LocationTypes::STORE->value)
            ->first();
    }

    public function getStoresByCompanyIdForEcommerce(int $companyId, array $filterData): LengthAwarePaginator
    {
        $countryQueries = resolve(CountryQueries::class);
        $cityQueries = resolve(CityQueries::class);

        return Location::select(
            'id',
            'name',
            'address_line_1',
            'address_line_2',
            'city_id',
            'area_code',
            'country_id',
            'updated_at',
            'created_at'
        )
            ->with([
                'country:' . $countryQueries->getBasicColumnNames(),
                'city:' . $cityQueries->getBasicColumnNames(),
            ])
            ->where('company_id', $companyId)
            ->when($filterData['after_updated_at'], function ($query) use ($filterData): void {
                $query->where('updated_at', '>=', $filterData['after_updated_at']);
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->where('type_id', LocationTypes::STORE->value)
            ->paginate($filterData['per_page']);
    }

    public function getByCompanyIdAndTypeId(int $companyId, int $typeId): Collection
    {
        return Location::select(
            'id',
            'name',
            'code',
            'email',
            'phone',
            'address_line_1',
            'address_line_2',
            'area_code',
            'fax'
        )
            ->where('company_id', $companyId)
            ->where('type_id', $typeId)
            ->orderBy('id', 'asc')
            ->get();
    }

    public function getNameAndCodeById(int $locationId, int $companyId): Location
    {
        return Location::select('id', 'name', 'code')
            ->where('company_id', $companyId)
            ->where('type_id', LocationTypes::STORE->value)
            ->findOrFail($locationId);
    }

    public function getIdById(int $locationId, int $companyId): ?Location
    {
        return Location::select('id')
            ->where('company_id', $companyId)
            ->find($locationId);
    }

    public function getStoreNameById(int $locationId, int $companyId): string
    {
        return Location::select('name')
            ->where('company_id', $companyId)
            ->where('type_id', LocationTypes::STORE->value)
            ->findOrFail($locationId)
            ->name;
    }

    public function checkNameExists(int $companyId, string $name, int $typeId): bool
    {
        return Location::where('name', $name)
            ->where('company_id', $companyId)
            ->where('type_id', $typeId)
            ->exists();
    }

    public function getNameAndCodeByIds(array $locationIds, int $typeId): Collection
    {
        return Location::select('id', 'name', 'code')
            ->whereIntegerInRaw('id', $locationIds)
            ->where('type_id', $typeId)
            ->get();
    }

    public function getNameByIds(array $locationIds): string
    {
        $locations = Location::whereIntegerInRaw('id', $locationIds)
            ->pluck('name')->toArray();

        return implode(', ', $locations);
    }

    public function getWarehouseNamesByIds(int $companyId, array $locationIds): ?Location
    {
        $column = count($locationIds) >= config(
            'app.location_name_code_limit'
        ) ? 'code' : "CONCAT(name, '(', code, ')')";

        $query = Location::selectRaw(sprintf("GROUP_CONCAT(%s SEPARATOR ', ') AS names", $column));

        return $query->whereIntegerInRaw('id', $locationIds)
            ->where('company_id', $companyId)
            ->where('type_id', LocationTypes::WAREHOUSE->value)
            ->first();
    }

    public function getWarehouseNameById(int $locationId, int $companyId): string
    {
        return Location::select('name')
            ->where('company_id', $companyId)
            ->where('type_id', LocationTypes::WAREHOUSE->value)
            ->findOrFail($locationId)
            ->name;
    }

    public function getBasicColumnNamesOfWarehouse(): string
    {
        return 'id,company_id,name';
    }

    public function getBasicColumnNamesOfReport(): string
    {
        return 'id,company_id,name';
    }

    public function getCompanyIdOfWarehouse(int $locationId): int
    {
        return Location::select('company_id')->where('type_id', LocationTypes::WAREHOUSE->value)->findOrFail(
            $locationId
        )->company_id;
    }

    public function getByCompanyId(int $companyId): Collection
    {
        $cityQueries = resolve(CityQueries::class);

        return Location::select(
            'id',
            'name',
            'code',
            'email',
            'phone',
            'address_line_1',
            'address_line_2',
            'city_id',
            'area_code',
            'fax',
            'ref_id',
            'type_id',
        )
            ->with('city:' . $cityQueries->getBasicColumnNames())
            ->where('company_id', $companyId)
            ->orderBy('id', 'asc')
            ->get();
    }

    public function doesLocationExist(int $companyId, int $locationId): bool
    {
        return Location::where('id', $locationId)->where('company_id', $companyId)->exists();
    }

    public function getColumnsForShipment(): string
    {
        return 'id,name,code,company_id,country_id,state_id,city_id,email,phone,mobile,fax,address_line_1,address_line_2,area_code';
    }

    public function getLocationNameById(int $locationId, int $companyId): string
    {
        return Location::select('name')
            ->where('company_id', $companyId)
            ->findOrFail($locationId)
            ->name;
    }

    public function doesStoreExist(int $locationId): bool
    {
        return Location::where('id', $locationId)->where('type_id', LocationTypes::STORE->value)->exists();
    }

    public function getByCodes(array $codes, int $companyId): Collection
    {
        return Location::select('id', 'name', 'code')
            ->where('company_id', $companyId)
            ->whereInCaseSensitive('code', $codes)
            ->get();
    }

    private function locationQuery(array $filterData, int $companyId): Builder
    {
        $regionQueries = resolve(RegionQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $countryQueries = resolve(CountryQueries::class);
        $stateQueries = resolve(StateQueries::class);
        $cityQueries = resolve(CityQueries::class);

        return Location::query()
            ->select(...$this->getColumnNames())
            ->with([
                'region:' . $regionQueries->getBasicColumnNames(),
                'brands:' . $brandQueries->getBasicColumnNames(),
                'country:' . $countryQueries->getBasicColumnNames(),
                'state:' . $stateQueries->getBasicColumnNames(),
                'city:' . $cityQueries->getBasicColumnNames(),
            ])
            ->leftJoin('cities', 'cities.id', '=', 'locations.city_id')
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query
                        ->where('locations.name', 'LIKE', '%' . $filterData['search_text'] . '%')
                        ->orWhere('locations.code', 'LIKE', '%' . $filterData['search_text'] . '%')
                        ->orWhere('locations.email', 'LIKE', '%' . $filterData['search_text'] . '%')
                        ->orWhere('locations.phone', 'LIKE', '%' . $filterData['search_text'] . '%');

                    $query->orWhereHas('city', function ($query) use ($filterData): void {
                        $query->where('cities.name', 'LIKE', '%' . $filterData['search_text'] . '%');
                    });
                });
            })
            ->where('company_id', $companyId)
            ->when($filterData['type_id'], function ($query) use ($filterData): void {
                $query->where('locations.type_id', $filterData['type_id']);
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                if ('city' === $filterData['sort_by']) {
                    $query->orderBy('cities.name', $filterData['sort_direction']);
                } else {
                    $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
                }
            }, function ($query): void {
                $query->orderBy('locations.id', 'desc');
            });
    }

    private function getColumnNames(): array
    {
        return [
            'locations.id',
            'locations.name',
            'locations.type_id',
            'locations.code',
            'locations.company_id',
            'locations.region_id',
            'locations.country_id',
            'locations.state_id',
            'locations.city_id',
            'locations.registration_number',
            'locations.sst_number',
            'locations.email',
            'locations.phone',
            'locations.mobile',
            'locations.fax',
            'locations.address_line_1',
            'locations.address_line_2',
            'locations.area_code',
            'locations.web_site',
            'locations.sales_tax_percentage',
            'locations.sales_return_days_limit',
            'locations.credit_note_expiration_days',
            'locations.loyalty_point_expiration_days',
            'locations.receipt_footer',
            'locations.disclaimer',
            'locations.open_time',
            'locations.close_time',
            'locations.cash_out_limit_info',
            'locations.cash_out_limit_warning',
            'locations.cash_out_limit_restrict',
            'locations.price_fall_down_percentage',
            'locations.is_email_verified',
        ];
    }

    public function getLocationForFilter(array $locationIds): string
    {
        $locationData = [];
        $location = Location::select('name')
            ->whereIntegerInRaw('id', values: $locationIds)
            ->get();

        if ($location->isNotEmpty()) {
            $locationData = $location->pluck('name')->toArray();
        }

        return implode(', ', $locationData);
    }

    public function getBasicColumnForPurchasePlan(): string
    {
        return 'id,company_id,name,registration_number,phone,email,address_line_1,address_line_2,city_id,area_code,fax';
    }

    public function topTenSellingLocation(
        array $dateRange,
        ?int $targetId,
        ?int $companyId,
        array $saleTargetIds
    ): Collection {
        return Location::select(
            'locations.id',
            'locations.name',
            DB::raw('SUM(sale_items.total_price_paid) as total_sales'),
        )
            ->join('counters', 'locations.id', '=', 'counters.location_id')
            ->join('counter_updates', 'counter_updates.counter_id', '=', 'counters.id')
            ->join('sales', 'counter_updates.id', '=', 'sales.counter_update_id')
            ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
            ->where('locations.company_id', $companyId)
            ->when($targetId && 0 > $targetId && [] === $saleTargetIds, function ($query) use ($targetId): void {
                $query->join('sale_targets', 'locations.company_id', '=', 'sale_targets.company_id')
                    ->where('sale_targets.id', $targetId);
            })
            ->when([] !== $saleTargetIds && ! $targetId || 0 === $targetId, function ($query) use (
                $saleTargetIds
            ): void {
                $query->join('sale_targets', 'locations.company_id', '=', 'sale_targets.company_id')
                    ->whereIn('sale_targets.id', $saleTargetIds);
            })
            ->whereBetween('sales.happened_at', [$dateRange[0], $dateRange[1]])
            ->groupBy('locations.id')
            ->orderByDesc('total_sales')
            ->limit(10)
            ->get();
    }

    public function worstTenSellingLocation(
        array $dataRange,
        ?int $targetId,
        ?int $companyId,
        array $saleTargetIds
    ): Collection {
        return Location::select(
            'locations.id',
            'locations.name',
            DB::raw('SUM(sale_items.total_price_paid) as total_sales'),
        )
            ->join('counters', 'locations.id', '=', 'counters.location_id')
            ->join('counter_updates', 'counter_updates.counter_id', '=', 'counters.id')
            ->join('sales', 'counter_updates.id', '=', 'sales.counter_update_id')
            ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
            ->where('locations.company_id', $companyId)
            ->when($targetId && 0 > $targetId && [] === $saleTargetIds, function ($query) use ($targetId): void {
                $query->join('sale_targets', 'locations.company_id', '=', 'sale_targets.company_id')
                    ->where('sale_targets.id', $targetId);
            })
            ->when([] !== $saleTargetIds && ! $targetId || 0 === $targetId, function ($query) use (
                $saleTargetIds
            ): void {
                $query->join('sale_targets', 'locations.company_id', '=', 'sale_targets.company_id')
                    ->whereIn('sale_targets.id', $saleTargetIds);
            })
            ->whereBetween('sales.happened_at', [$dataRange[0], $dataRange[1]])
            ->groupBy('locations.id')
            ->orderBy('total_sales', 'asc')
            ->limit(10)
            ->get();
    }

    public function getByIdForEmailVerification(int $locationId, int $companyId): Location
    {
        return Location::select('id', 'email')
            ->where('company_id', $companyId)
            ->findOrFail($locationId);
    }

    public function getAllStoreLocationByCompanyIdWithRelation(int $companyId): Collection
    {
        return Location::select(
            'id',
            'company_id',
            'name',
            'code',
            'email',
            'phone',
            'address_line_1',
            'address_line_2',
            'area_code',
            'country_id',
            'state_id',
            'city_id',
            'region_id'
        )
            ->with('brands')
            ->where('company_id', $companyId)
            ->where('type_id', LocationTypes::STORE->value)
            ->get();
    }

    public function getByIdWithRelation(int $locationId): Location
    {
        return Location::select(
            'id',
            'company_id',
            'name',
            'code',
            'email',
            'phone',
            'address_line_1',
            'address_line_2',
            'area_code',
            'country_id',
            'state_id',
            'city_id',
            'region_id'
        )
            ->with('brands')
            ->findOrFail($locationId);
    }
}
