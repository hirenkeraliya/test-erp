<?php

declare(strict_types=1);

namespace App\Domains\SaleChannel;

use App\Domains\City\CityQueries;
use App\Domains\Company\CompanyQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Media\MediaQueries;
use App\Domains\SaleChannel\DataObjects\SaleChannelData;
use App\Domains\SaleChannel\Enums\SaleChannelTypes;
use App\Domains\SaleChannelInventoryRollbackOrderStatus\SaleChannelInventoryRollbackOrderStatusQueries;
use App\Domains\SaleChannelWebhookUrl\SaleChannelWebhookUrlQueries;
use App\Models\SaleChannel;
use Closure;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class SaleChannelQueries
{
    public function getBasicColumns(): array
    {
        return [
            'id',
            'name',
            'code',
            'company_id',
            'default_location_id',
            'type_id',
            'inventory_deduct_order_status',
            'url',
            'secret',
            'status',
            'display_variants',
            'display_dynamic_menus',
            'round_off_configuration',
        ];
    }

    public function getBasicColumnsInString(): string
    {
        return implode(',', $this->getBasicColumns());
    }

    public function listQuery(array $filterData): LengthAwarePaginator
    {
        return SaleChannel::query()
            ->select($this->getBasicColumns())
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->where('name', 'like', '%' . $filterData['search_text'] . '%');
                    $query->orWhere('code', 'like', '%' . $filterData['search_text'] . '%');
                });
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->where('status', true)
            ->paginate($filterData['per_page']);
    }

    public function addNew(SaleChannelData $saleChannelData): string
    {
        $data = $saleChannelData->all();
        unset($data['inventory_rollback_order_status']);
        unset($data['webhook_urls']);

        $saleChannel = SaleChannel::create($data);
        $this->updateRelationDetails($saleChannelData, $saleChannel);

        $newAccessToken = $saleChannel->createToken('ecommerce-application');

        return $newAccessToken->plainTextToken;
    }

    public function getById(int $saleChannelId): SaleChannel
    {
        $saleChannelInventoryRollbackOrderStatusQueries = new SaleChannelInventoryRollbackOrderStatusQueries();
        $saleChannelWebhookUrlQueries = new SaleChannelWebhookUrlQueries();

        return SaleChannel::select($this->getBasicColumns())
            ->with([
                'saleChannelInventoryRollbackOrderStatus:' . $saleChannelInventoryRollbackOrderStatusQueries->getBasicColumns(),
                'saleChannelWebhookUrls:' . $saleChannelWebhookUrlQueries->getBasicColumnsInString(),
            ])
            ->findOrFail($saleChannelId);
    }

    public function getByIdAndStatus(int $saleChannelId): SaleChannel
    {
        $saleChannelInventoryRollbackOrderStatusQueries = new SaleChannelInventoryRollbackOrderStatusQueries();
        $saleChannelWebhookUrlQueries = new SaleChannelWebhookUrlQueries();

        return SaleChannel::select($this->getBasicColumns())
            ->with([
                'saleChannelInventoryRollbackOrderStatus:' . $saleChannelInventoryRollbackOrderStatusQueries->getBasicColumns(),
                'saleChannelWebhookUrls:' . $saleChannelWebhookUrlQueries->getBasicColumnsInString(),
            ])
            ->where('status', true)
            ->findOrFail($saleChannelId);
    }

    public function update(SaleChannelData $saleChannelData, SaleChannel $saleChannel): void
    {
        $saleChannelDetails = $saleChannelData->toArray();
        unset($saleChannelDetails['inventory_rollback_order_status']);
        unset($saleChannelDetails['webhook_urls']);

        $saleChannel->update($saleChannelDetails);
        $this->updateRelationDetails($saleChannelData, $saleChannel);
    }

    public function getAllByCompanyId(int $companyId): Collection
    {
        return SaleChannel::query()
            ->select('id', 'name', 'type_id', 'default_location_id')
            ->where('company_id', $companyId)
            ->where('status', true)
            ->get();
    }

    public function getAll(): Collection
    {
        return SaleChannel::query()
            ->select('id', 'name', 'type_id', 'default_location_id')
            ->where('status', true)
            ->get();
    }

    public function getAllByCompanyIdWithRelation(int $companyId, int $typeId): Collection
    {
        return SaleChannel::query()
            ->select('id', 'name', 'type_id', 'company_id')
            ->with([
                'syncTransactions' => function ($query) use ($typeId): void {
                    $query->select('updated_at', 'sale_channel_id')
                        ->where('type_id', $typeId);
                },
            ])
            ->where('company_id', $companyId)
            ->where('status', true)
            ->get();
    }

    public function getAllWithRelation(int $typeId): Collection
    {
        return SaleChannel::query()
            ->select('id', 'name', 'type_id', 'company_id')
            ->with([
                'syncTransactions' => function ($query) use ($typeId): void {
                    $query->select('updated_at', 'sale_channel_id')
                        ->where('type_id', $typeId);
                },
            ])
            ->where('status', true)
            ->get();
    }

    public function refreshToken(int $saleChannelId): string
    {
        $saleChannel = $this->getById($saleChannelId);

        $saleChannel->tokens()->delete();

        $newAccessToken = $saleChannel->createToken('ecommerce-application');

        return $newAccessToken->plainTextToken;
    }

    public function doAllSaleChannelExist(int $companyId, array $saleChannelIds): bool
    {
        $totalRecords = SaleChannel::whereIntegerInRaw('id', $saleChannelIds)
            ->where('company_id', $companyId)
            ->count();

        return count($saleChannelIds) === $totalRecords;
    }

    private function updateRelationDetails(SaleChannelData $saleChannelData, SaleChannel $saleChannel): void
    {
        $saleChannelInventoryRollbackOrderStatusQueries = resolve(
            SaleChannelInventoryRollbackOrderStatusQueries::class
        );
        $saleChannelInventoryRollbackOrderStatusQueries->deleteInventoryRollbackOrder($saleChannel);

        $saleChannelWebhookUrlQueries = resolve(SaleChannelWebhookUrlQueries::class);
        $saleChannelWebhookUrlQueries->deleteSaleChannelWebhookUrl($saleChannel);

        foreach ($saleChannelData->inventory_rollback_order_status as $orderStatus) {
            $saleChannelInventoryRollbackOrderStatusQueries->addNew([
                'sale_channel_id' => $saleChannel->id,
                'order_status' => $orderStatus,
            ]);
        }

        foreach ($saleChannelData->webhook_urls as $webhookUrl) {
            $saleChannelWebhookUrlQueries->addNew([
                'sale_channel_id' => $saleChannel->id,
                'webhook_url_type_id' => $webhookUrl['webhook_url_type_id'],
                'url' => $webhookUrl['url'],
            ]);
        }
    }

    public function loadWithLocationsAndCompany(SaleChannel $saleChannel): SaleChannel
    {
        $locationQueries = resolve(LocationQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $mediaQueries = resolve(MediaQueries::class);

        return $saleChannel->load([
            'location:' . $locationQueries->getBasicColumnNamesForEcommerceLocationConfiguration(),
            'company:' . $companyQueries->getBasicColumnNamesForEcommerceLocationConfiguration(),
            'company.media:' . $mediaQueries->getBasicColumnNames(),
        ]);
    }

    public function loadWithLocationsAndCompanyEcommerce(SaleChannel $saleChannel): SaleChannel
    {
        $locationQueries = resolve(LocationQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $cityQueries = resolve(CityQueries::class);

        return $saleChannel->load([
            'location:' . $locationQueries->getBasicColumnNamesForEcommerceLocationConfiguration(),
            'location.city:' . $cityQueries->getBasicColumnNames(),
            'company:' . $companyQueries->getBasicColumnNamesForEcommerceLocationConfiguration(),
        ]);
    }

    public function getSaleChannels(array $webhookUrls, int $locationId): Collection
    {
        $saleChannelWebhookUrlQueries = new SaleChannelWebhookUrlQueries();

        return SaleChannel::query()
            ->withWhereHas(
                'saleChannelWebhookUrls',
                function ($query) use ($saleChannelWebhookUrlQueries, $webhookUrls): void {
                    $query->select($saleChannelWebhookUrlQueries->getBasicColumns())
                        ->whereIn('webhook_url_type_id', $webhookUrls);
                }
            )
            ->select($this->getBasicColumns())
            ->where('default_location_id', $locationId)
            ->get();
    }

    public function loadWebhookUrls(SaleChannel $saleChannel): SaleChannel
    {
        $saleChannelWebhookUrlQueries = resolve(SaleChannelWebhookUrlQueries::class);

        return $saleChannel->load([
            'saleChannelWebhookUrls:' . $saleChannelWebhookUrlQueries->getBasicColumnsInString(),
        ]);
    }

    public function getSaleChannelsByCompany(array $webhookUrls, ?int $companyId): Collection
    {
        $saleChannelWebhookUrlQueries = new SaleChannelWebhookUrlQueries();

        return SaleChannel::query()
            ->withWhereHas(
                'saleChannelWebhookUrls',
                function ($query) use ($saleChannelWebhookUrlQueries, $webhookUrls): void {
                    $query->select($saleChannelWebhookUrlQueries->getBasicColumns())
                        ->whereIn('webhook_url_type_id', $webhookUrls);
                }
            )
            ->select($this->getBasicColumns())
            ->where('company_id', $companyId)
            ->where('status', true)
            ->get();
    }

    public function getSaleChannelsByCompanyAndTypeId(array $webhookUrls, int $companyId, int $typeId): Collection
    {
        $saleChannelWebhookUrlQueries = new SaleChannelWebhookUrlQueries();

        return SaleChannel::query()
            ->withWhereHas(
                'saleChannelWebhookUrls',
                function ($query) use ($saleChannelWebhookUrlQueries, $webhookUrls): void {
                    $query->select($saleChannelWebhookUrlQueries->getBasicColumns())
                        ->whereIn('webhook_url_type_id', $webhookUrls);
                }
            )
            ->select($this->getBasicColumns())
            ->where('company_id', $companyId)
            ->where('type_id', $typeId)
            ->where('status', true)
            ->get();
    }

    public function getSaleChannelsByWebhookUrls(array $webhookUrls): Collection
    {
        $saleChannelWebhookUrlQueries = new SaleChannelWebhookUrlQueries();

        return SaleChannel::query()
            ->withWhereHas(
                'saleChannelWebhookUrls',
                function ($query) use ($saleChannelWebhookUrlQueries, $webhookUrls): void {
                    $query->select($saleChannelWebhookUrlQueries->getBasicColumns())
                        ->whereIn('webhook_url_type_id', $webhookUrls);
                }
            )
            ->select($this->getBasicColumns())
            ->get();
    }

    public function getSpecificTypeOfSaleChannelWithWebHooks(SaleChannelTypes $saleChannelType): Collection
    {
        $saleChannelWebhookUrlQueries = new SaleChannelWebhookUrlQueries();

        return SaleChannel::query()
            ->with(['saleChannelWebhookUrls:' . $saleChannelWebhookUrlQueries->getBasicColumnsInString()])
            ->where('type_id', $saleChannelType)
            ->select($this->getBasicColumns())
            ->get();
    }

    public function isAvailable(int $companyId): int
    {
        return SaleChannel::query()
            ->select('id', 'name')
            ->where('company_id', $companyId)
            ->where('status', true)
            ->count();
    }

    public function filterByTypeId(int $typeId, string $operator = '='): Closure
    {
        return fn ($query) => $query->where('type_id', $operator, $typeId);
    }

    public function existsByNames(array $names, int $companyId): array
    {
        $existingSaleChannelIds = collect([]);

        foreach ($names as $name) {
            $newSaleChannel = SaleChannel::select('id')
                ->where('name', $name)
                ->where('company_id', $companyId)
                ->firstOrFail();

            $existingSaleChannelIds->push($newSaleChannel->id);
        }

        return $existingSaleChannelIds->toArray();
    }

    public function doSaleChannelNamesExists(array $saleChannelNames, int $companyId): bool
    {
        if ([] === $saleChannelNames) {
            return false;
        }

        $filteredSaleChannelNames = array_unique(array_filter($saleChannelNames));

        if ([] === $filteredSaleChannelNames) {
            return false;
        }

        $totalRecords = SaleChannel::whereIn('name', $filteredSaleChannelNames)
            ->where('company_id', $companyId)
            ->count();

        return count($filteredSaleChannelNames) === $totalRecords;
    }

    public function updateStatus(int $saleChannelId, bool $status): void
    {
        $saleChannel = SaleChannel::query()
            ->select('id', 'status')
            ->findOrFail($saleChannelId);

        $saleChannel->status = $status;
        $saleChannel->save();
    }

    public function getBaseUrlColumn(): string
    {
        return 'id,url';
    }

    public function getAllByCompanyIdAndTypeId(int $companyId, int $typeId): Collection
    {
        return SaleChannel::query()
            ->select('id', 'name', 'type_id', 'url', 'secret')
            ->where('company_id', $companyId)
            ->where('type_id', $typeId)
            ->where('status', true)
            ->get();
    }

    public function setArchiveCompanyInactive(int $companyId, bool $status): void
    {
        SaleChannel::query()
            ->where('company_id', $companyId)
            ->update([
                'status' => $status,
            ]);
    }

    public function setRestoreCompanyActive(int $companyId, bool $status): void
    {
        SaleChannel::query()
            ->where('company_id', $companyId)
            ->update([
                'status' => $status,
            ]);
    }

    public function getWebspertSaleChannel(): ?SaleChannel
    {
        return SaleChannel::query()
            ->select('id', 'company_id', 'name', 'type_id', 'url', 'secret')
            ->where('type_id', SaleChannelTypes::WEBSPERT_ECOMMERCE->value)
            ->where('status', true)
            ->first();
    }

    public function getECommerceSaleChannel(): ?SaleChannel
    {
        return SaleChannel::query()
            ->select('id', 'url', 'secret')
            ->where('type_id', SaleChannelTypes::ECOMMERCE->value)
            ->where('status', true)
            ->first();
    }

    public function getEcommerceSaleChannelsByTypeIdAndWebhookUrls(array $webhookUrls, int $typeId): Collection
    {
        $saleChannelWebhookUrlQueries = new SaleChannelWebhookUrlQueries();

        return SaleChannel::query()
            ->withWhereHas(
                'saleChannelWebhookUrls',
                function ($query) use ($saleChannelWebhookUrlQueries, $webhookUrls): void {
                    $query->select($saleChannelWebhookUrlQueries->getBasicColumns())
                        ->whereIn('webhook_url_type_id', $webhookUrls);
                }
            )
            ->select($this->getBasicColumns())
            ->where('type_id', $typeId)
            ->where('status', true)
            ->get();
    }

    public function isEcommerceEnabled(): bool
    {
        return SaleChannel::query()
            ->select('id')
            ->where('type_id', SaleChannelTypes::ECOMMERCE->value)
            ->where('status', true)
            ->exists();
    }
}
