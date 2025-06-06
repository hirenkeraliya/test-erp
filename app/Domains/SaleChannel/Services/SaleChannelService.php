<?php

declare(strict_types=1);

namespace App\Domains\SaleChannel\Services;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Domains\SyncTransaction\Enums\SyncTransactionStatuses;
use App\Domains\SyncTransaction\SyncTransactionQueries;
use App\Models\Admin;
use App\Models\SuperAdmin;
use App\Models\SyncTransaction;
use Illuminate\Support\Collection;

class SaleChannelService
{
    public function getModifySaleChannels(int $typeId, ?int $companyId): Collection
    {
        $saleChannelQueries = resolve(SaleChannelQueries::class);

        if ($companyId) {
            $saleChannels = $saleChannelQueries->getAllByCompanyIdWithRelation($companyId, $typeId);
        } else {
            $saleChannels = $saleChannelQueries->getAllWithRelation($typeId);
        }

        $modifiedSaleChannels = $saleChannels->map(function ($saleChannel): array {
            $updatedAt = $saleChannel->syncTransactions->first()->updated_at ?? null;

            return [
                'id' => $saleChannel->id,
                'name' => $saleChannel->name,
                'type_id' => $saleChannel->type_id,
                'company_id' => $saleChannel->company_id,
                'updated_at' => $updatedAt?->format('Y-m-d H:i:s'),
            ];
        });

        $customEntry = [
            'id' => 0,
            'name' => 'All',
            'type_id' => null,
            'company_id' => null,
            'updated_at' => null,
        ];

        return $modifiedSaleChannels->prepend($customEntry);
    }

    public function updateSyncData(int $saleChannelId, int $typeId, Admin|SuperAdmin $user, ?int $companyId): void
    {
        $saleChannels = $this->getSaleChannels($saleChannelId, $companyId);

        foreach ($saleChannels as $saleChannel) {
            $this->addOrUpdateSyncTransactions($saleChannel->id, $typeId, $user);
        }
    }

    public function getSaleChannels(int $saleChannelId, ?int $companyId): Collection
    {
        $saleChannelQueries = resolve(SaleChannelQueries::class);
        if (0 > $saleChannelId) {
            $saleChannel = $saleChannelQueries->getByIdAndStatus($saleChannelId);

            return collect([$saleChannel]);
        }

        if ($companyId) {
            return $saleChannelQueries->getAllByCompanyId($companyId);
        }

        return $saleChannelQueries->getAll();
    }

    public function addOrUpdateSyncTransactions(int $saleChannelId, int $typeId, Admin|SuperAdmin $user): void
    {
        $syncTransactionQueries = resolve(SyncTransactionQueries::class);

        $syncTransaction = $syncTransactionQueries->getBySaleChannelIdAndType($saleChannelId, $typeId);

        if ($syncTransaction instanceof SyncTransaction) {
            $syncTransaction->touch();

            $syncTransactionQueries->updateStatus($saleChannelId, $typeId, SyncTransactionStatuses::IN_PROGRESS->value);

            return;
        }

        $syncTransactionData = [
            'sale_channel_id' => $saleChannelId,
            'type_id' => $typeId,
            'user_id' => $user->id,
            'status' => SyncTransactionStatuses::IN_PROGRESS->value,
            'user_type' => ModelMapping::getCaseName($user::class),
        ];

        $syncTransactionQueries->addNew($syncTransactionData);
    }
}
