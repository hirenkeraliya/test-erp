<?php

declare(strict_types=1);

namespace App\Domains\OnlineSalesCharges;

use App\Domains\OnlineSalesCharges\DataObjects\OnlineSalesChargesData;
use App\Domains\OnlineSalesCharges\Events\OnlineSaleChargeCreateEvent;
use App\Domains\OnlineSalesCharges\Events\OnlineSaleChargeUpdateEvent;
use App\Domains\OnlineSalesChargeTier\OnlineSalesChargeTierQueries;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Domains\ShippingZone\ShippingZoneQueries;
use App\Models\OnlineSalesCharges;
use App\Models\SaleChannel;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class OnlineSalesChargesQueries
{
    public function listQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->getOnlineSalesChargesCommonQuery($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function addNew(OnlineSalesChargesData $onlineSalesChargesData, int $companyId): void
    {
        $data = $onlineSalesChargesData->all();
        unset($data['sale_channel_ids']);
        unset($data['online_sales_charge_tiers']);

        $data['company_id'] = $companyId;
        $data['status'] = true;
        $onlineSalesCharges = OnlineSalesCharges::create($data);
        $this->updateSaleChannels($onlineSalesCharges, $onlineSalesChargesData);
        $this->addWeightTiers($onlineSalesCharges, $onlineSalesChargesData);
        event(new OnlineSaleChargeCreateEvent($onlineSalesCharges));
    }

    public function getOnlineSalesChargesByIdForEcommerce(int $onlineSaleChargeId): OnlineSalesCharges
    {
        return OnlineSalesCharges::select('id', 'company_id')
            ->findOrFail($onlineSaleChargeId);
    }

    public function getById(int $onlineSalesChargeId, int $companyId): OnlineSalesCharges
    {
        $saleChannelQueries = resolve(SaleChannelQueries::class);

        return OnlineSalesCharges::select(
            'id',
            'company_id',
            'name',
            'shipping_zone_id',
            'minimum_value',
            'maximum_value',
            'amount',
            'status',
            'is_available_in_ecommerce',
            'shipping_charge_type_id',
        )
            ->with(['saleChannels:' . $saleChannelQueries->getBasicColumnsInString(), 'onlineSalesChargeTiers'])
            ->where('company_id', $companyId)
            ->findOrFail($onlineSalesChargeId);
    }

    public function update(
        OnlineSalesChargesData $onlineSalesChargesData,
        int $onlineSalesChargeId,
        int $companyId
    ): void {
        $onlineSalesCharge = $this->getById($onlineSalesChargeId, $companyId);

        $data = $onlineSalesChargesData->all();
        unset($data['sale_channel_ids']);
        unset($data['online_sales_charge_tiers']);

        $onlineSalesCharge->update($data);

        $this->updateSaleChannels($onlineSalesCharge, $onlineSalesChargesData);
        $this->removeWeightTiers($onlineSalesCharge);
        $this->addWeightTiers($onlineSalesCharge, $onlineSalesChargesData);

        event(new OnlineSaleChargeUpdateEvent($onlineSalesCharge));
    }

    public function filterByCompany(int $companyId): Closure
    {
        return fn ($query) => $query->where('company_id', $companyId);
    }

    public function delete(int $onlineSalesChargeId, int $companyId): void
    {
        $onlineSalesCharge = $this->getById($onlineSalesChargeId, $companyId);
        $this->removeWeightTiers($onlineSalesCharge);
        $onlineSalesCharge->delete();
    }

    public function toggleStatus(int $onlineSalesChargeId, int $companyId): void
    {
        $onlineSalesCharge = OnlineSalesCharges::query()
            ->select('id', 'status')
            ->where('company_id', $companyId)
            ->findOrFail($onlineSalesChargeId);

        $onlineSalesCharge->status = ! $onlineSalesCharge->status;
        $onlineSalesCharge->save();

        event(new OnlineSaleChargeUpdateEvent($onlineSalesCharge));
    }

    public function onlineSalesChargesForEcommerce(int $companyId): Collection
    {
        return OnlineSalesCharges::select(
            'id',
            'name',
            'minimum_value',
            'maximum_value',
            'amount',
            'status',
            'created_at',
            'updated_at'
        )
            ->where('company_id', $companyId)
            ->get();
    }

    public function validateOnlineSalesChargeSaleChannelMatch(
        OnlineSalesCharges $onlineSalesCharges,
        SaleChannel $saleChannel
    ): bool {
        return $onlineSalesCharges->saleChannels()
            ->wherePivot('sale_channel_id', $saleChannel->id)
            ->exists();
    }

    private function removeWeightTiers(OnlineSalesCharges $onlineSalesCharge): void
    {
        $onlineSalesChargeTierQueries = resolve(OnlineSalesChargeTierQueries::class);
        $onlineSalesChargeTierQueries->remove($onlineSalesCharge);
    }

    private function addWeightTiers(
        OnlineSalesCharges $onlineSalesCharges,
        OnlineSalesChargesData $onlineSalesChargesData
    ): void {
        $onlineSalesChargeTierQueries = resolve(OnlineSalesChargeTierQueries::class);
        foreach ($onlineSalesChargesData->online_sales_charge_tiers as $tier) {
            $onlineSalesChargeTierQueries->addNew([
                'online_sales_charges_id' => $onlineSalesCharges->id,
                'min_weight' => $tier['min_weight'],
                'max_weight' => $tier['max_weight'],
                'amount' => $tier['amount'],
            ]);
        }
    }

    private function updateSaleChannels(
        OnlineSalesCharges $onlineSalesCharges,
        OnlineSalesChargesData $onlineSalesChargesData
    ): void {
        if (! array_key_exists('sale_channel_ids', $onlineSalesChargesData->all())) {
            return;
        }

        if (null === $onlineSalesChargesData->sale_channel_ids) {
            return;
        }

        $onlineSalesCharges->saleChannels()->sync($onlineSalesChargesData->sale_channel_ids);
    }

    private function getOnlineSalesChargesCommonQuery(array $filterData, int $companyId): Builder
    {
        $shippingZoneQueries = resolve(ShippingZoneQueries::class);

        return OnlineSalesCharges::query()
            ->select('id', 'name', 'shipping_zone_id', 'shipping_charge_type_id', 'status')
            ->with(['shippingZone:' . $shippingZoneQueries->getBasicColumns()])
            ->where('company_id', $companyId)
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->where('name', 'like', '%' . $filterData['search_text'] . '%');
                });
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }
}
