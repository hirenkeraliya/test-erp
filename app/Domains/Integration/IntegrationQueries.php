<?php

declare(strict_types=1);

namespace App\Domains\Integration;

use App\CommonFunctions;
use App\Domains\Integration\DataObjects\IntegrationData;
use App\Domains\Integration\Enums\IntegrationConnections;
use App\Domains\IntegrationWebhookUrl\IntegrationWebhookUrlQueries;
use App\Models\Integration;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class IntegrationQueries
{
    public function getBasicColumns(): array
    {
        return ['id', 'name', 'company_id', 'url', 'connection_type', 'secret', 'status'];
    }

    public function getBasicColumnsInString(): string
    {
        return implode(',', $this->getBasicColumns());
    }

    public function listQuery(array $filterData): LengthAwarePaginator
    {
        return Integration::query()
            ->select($this->getBasicColumns())
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->where('name', 'like', '%' . $filterData['search_text'] . '%');
                });
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->paginate($filterData['per_page']);
    }

    public function addNew(IntegrationData $integrationData): string
    {
        DB::beginTransaction();
        try {
            $data = $integrationData->all();
            unset($data['webhook_urls']);

            $integration = Integration::create($data);

            $this->updateRelationDetails($integrationData, $integration);

            $newAccessToken = $integration->createToken('third-party-integration');

            DB::commit();

            return $newAccessToken->plainTextToken;
        } catch (Throwable $throwable) {
            DB::rollBack();

            CommonFunctions::logErrorDetails($throwable, 'Integration create error:');

            throw $throwable;
        }
    }

    private function updateRelationDetails(IntegrationData $integrationData, Integration $integration): void
    {
        $integrationWebhookUrlQueries = resolve(IntegrationWebhookUrlQueries::class);
        $integrationWebhookUrlQueries->deleteIntegrationWebhookUrl($integration);

        if ($integrationData->connection_type === IntegrationConnections::RETAIL_PLANNING->value) {
            foreach ($integrationData->webhook_urls as $webhookUrl) {
                $integrationWebhookUrlQueries->addNew([
                    'integration_id' => $integration->id,
                    'webhook_url_type_id' => $webhookUrl['webhook_url_type_id'],
                    'url' => $webhookUrl['url'],
                ]);
            }
        }
    }

    public function getById(int $integrationId): Integration
    {
        $integrationWebhookUrlQueries = resolve(IntegrationWebhookUrlQueries::class);

        return Integration::select($this->getBasicColumns())
            ->with(['integrationWebhookUrls:' . $integrationWebhookUrlQueries->getBasicColumnsInString()])
            ->findOrFail($integrationId);
    }

    public function getByIdAndStatus(int $integrationId): Integration
    {
        return Integration::select($this->getBasicColumns())
            ->where('status', true)
            ->findOrFail($integrationId);
    }

    public function update(IntegrationData $integrationData, Integration $integration): void
    {
        DB::beginTransaction();
        try {
            $integrationDetails = $integrationData->toArray();
            unset($integrationDetails['webhook_urls']);

            $integration->update($integrationDetails);
            $this->updateRelationDetails($integrationData, $integration);

            DB::commit();
        } catch (Throwable $throwable) {
            DB::rollBack();

            CommonFunctions::logErrorDetails($throwable, 'Integration update error:');

            throw $throwable;
        }
    }

    public function refreshToken(int $integrationId): string
    {
        $integration = $this->getById($integrationId);

        $integration->tokens()->delete();

        $newAccessToken = $integration->createToken('third-party-integration');

        return $newAccessToken->plainTextToken;
    }

    public function updateStatus(int $integrationId, bool $status): void
    {
        $integration = $this->getById($integrationId);

        $integration->status = $status;
        $integration->save();
    }

    public function getIntegrationsByWebhookUrl(int $webhookUrl, int $integrationConnectionId): Collection
    {
        $integrationWebhookUrlQueries = new IntegrationWebhookUrlQueries();

        return Integration::query()
            ->withWhereHas(
                'integrationWebhookUrls',
                function ($query) use ($integrationWebhookUrlQueries, $webhookUrl): void {
                    $query->select($integrationWebhookUrlQueries->getBasicColumns())
                        ->where('webhook_url_type_id', $webhookUrl);
                }
            )
            ->where('connection_type', $integrationConnectionId)
            ->select($this->getBasicColumns())
            ->get();
    }

    public function getIntegrationsByConnectionId(IntegrationConnections $integrationConnection): Collection
    {
        return Integration::query()
            ->select($this->getBasicColumns())
            ->where('connection_type', $integrationConnection)
            ->where('status', true)
            ->get();
    }
}
