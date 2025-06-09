<?php

declare(strict_types=1);

namespace App\Domains\Courier;

use App\Domains\Courier\DataObjects\CourierData;
use App\Domains\CourierWebhookUrl\CourierWebhookUrlQueries;
use App\Models\Courier;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CourierQueries
{
    public function getBasicColumns(): array
    {
        return ['id', 'name', 'code', 'type_id', 'url', 'client_secret', 'client_id'];
    }

    public function getBasicColumnsInString(): string
    {
        return implode(',', $this->getBasicColumns());
    }

    public function listQuery(array $filterData): LengthAwarePaginator
    {
        return Courier::query()
            ->select($this->getBasicColumns())
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->whereAny(['name', 'code'], 'LIKE', '%' . $filterData['search_text'] . '%');
                });
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->paginate($filterData['per_page']);
    }

    public function addNew(CourierData $courierData): void
    {
        $data = $courierData->all();
        unset($data['webhook_urls']);

        $courier = Courier::create($data);
        $this->updateRelationDetails($courierData, $courier);
    }

    public function getById(int $courierId): Courier
    {
        $courierWebhookUrlQueries = resolve(CourierWebhookUrlQueries::class);

        return Courier::select($this->getBasicColumns())
            ->with(['courierWebhookUrls:' . $courierWebhookUrlQueries->getBasicColumnsInString()])
            ->findOrFail($courierId);
    }

    public function getByTypeId(int $typeId): ?Courier
    {
        return Courier::select($this->getBasicColumns())
            ->where('type_id', $typeId)
            ->first();
    }

    public function update(CourierData $courierData, Courier $courier): void
    {
        $courierDetails = $courierData->toArray();
        unset($courierDetails['webhook_urls']);

        $courier->update($courierDetails);
        $this->updateRelationDetails($courierData, $courier);
    }

    private function updateRelationDetails(CourierData $courierData, Courier $courier): void
    {
        DB::transaction(function () use ($courierData, $courier) {
            $courierWebhookUrlQueries = resolve(CourierWebhookUrlQueries::class);
            $courierWebhookUrlQueries->deleteCourierWebhookUrl($courier);

            foreach ($courierData->webhook_urls as $webhookUrl) {
                $courierWebhookUrlQueries->addNew([
                    'courier_id' => $courier->id,
                    'webhook_url_type_id' => $webhookUrl['webhook_url_type_id'],
                    'url' => $webhookUrl['url'],
                ]);
            }
        });
    }
}
