<?php

declare(strict_types=1);

namespace App\Domains\StoreManagerAuthorizationCode;

use App\Domains\StoreManagerAuthorizationCode\Enum\StoreManagerAuthorizationCodeStatuses;
use App\Models\StoreManagerAuthorizationCode;
use Illuminate\Support\Collection;

class StoreManagerAuthorizationCodeQueries
{
    public function getWithStoreManager(int $storeManagerId): ?StoreManagerAuthorizationCode
    {
        return StoreManagerAuthorizationCode::query()
            ->select(...$this->getBasicColumns())
            ->where('store_manager_id', $storeManagerId)
            ->latest()
            ->first();
    }

    public function getOnlyActiveStoreManagerAuthorizationCodes(): Collection
    {
        return StoreManagerAuthorizationCode::query()
            ->select(...$this->getBasicColumns())
            ->where('status', StoreManagerAuthorizationCodeStatuses::ACTIVE)
            ->get();
    }

    public function getById(int $storeManagerAuthorizationCodeId): ?StoreManagerAuthorizationCode
    {
        return StoreManagerAuthorizationCode::query()
            ->select(...$this->getBasicColumns())
            ->findOrFail($storeManagerAuthorizationCodeId);
    }

    public function getByCode(string $code): ?StoreManagerAuthorizationCode
    {
        return StoreManagerAuthorizationCode::query()
            ->select(...$this->getBasicColumns())
            ->where('code', $code)
            ->first();
    }

    public function cancelTheAuthorizationCode(int $storeManagerAuthorizationCodeId): void
    {
        $storeManagerAuthorizationCode = $this->getById($storeManagerAuthorizationCodeId);

        if (! $storeManagerAuthorizationCode instanceof StoreManagerAuthorizationCode) {
            return;
        }

        $storeManagerAuthorizationCode->status = StoreManagerAuthorizationCodeStatuses::CANCELLED;
        $storeManagerAuthorizationCode->save();
    }

    public function getBasicColumns(): array
    {
        return ['id', 'store_manager_id', 'code', 'expiry_date', 'status', 'created_at'];
    }

    public function addNew(array $storeManagerAuthorizationCodeData): void
    {
        StoreManagerAuthorizationCode::create($storeManagerAuthorizationCodeData);
    }

    public function markStatusAsExpired(int $storeManagerAuthorizationCodeId): void
    {
        /** @var StoreManagerAuthorizationCode $storeManagerAuthorizationCode */
        $storeManagerAuthorizationCode = $this->getById($storeManagerAuthorizationCodeId);

        $storeManagerAuthorizationCode->status = StoreManagerAuthorizationCodeStatuses::EXPIRED;
        $storeManagerAuthorizationCode->save();
    }
}
