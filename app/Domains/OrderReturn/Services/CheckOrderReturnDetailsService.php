<?php

declare(strict_types=1);

namespace App\Domains\OrderReturn\Services;

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Member\MemberQueries;
use App\Domains\OrderReturn\DataObjects\OrderReturnData;
use App\Models\Company;
use App\Models\Location;
use App\Models\Member;
use App\Models\StoreManager;
use Illuminate\Support\Collection;

class CheckOrderReturnDetailsService
{
    public StoreManager $storeManager;

    public OrderReturnData $orderReturnData;

    public Collection $orderReturnItems;

    public Company $company;

    public int $companyId;

    public Location $location;

    public int $locationId;

    public ?Member $member = null;

    public OrderReturnService $orderReturnService;

    public function setDetails(
        StoreManager $storeManager,
        OrderReturnData $orderReturnData,
        Collection $orderReturnItems,
        int $companyId,
        int $locationId,
    ): void {
        $this->storeManager = $storeManager;
        $this->orderReturnData = $orderReturnData;
        $this->orderReturnItems = $orderReturnItems;
        $this->companyId = $companyId;
        $this->locationId = $locationId;

        $this->member = $this->getMemberDetails($companyId, $this->orderReturnData->member_id);

        $this->location = $this->getCurrentLocation($locationId, $companyId);

        $this->orderReturnService = resolve(OrderReturnService::class);
        $this->orderReturnService->setDetails($this);
    }

    public function checkRequestDetails(): void
    {
        if ($this->orderReturnItems->isEmpty()) {
            return;
        }

        $this->orderReturnService->checkReturnItems();
    }

    public function getCurrentLocation(int $locationId, int $companyId): Location
    {
        $locationQueries = resolve(LocationQueries::class);

        return $locationQueries->getById(
            $locationId,
            $companyId,
            (int) LocationTypes::STORE->value,
            [
                'id',
                'name',
                'code',
                'sales_tax_percentage',
                'sales_return_days_limit',
                'credit_note_expiration_days',
                'loyalty_point_expiration_days',
            ],
        );
    }

    public function getMemberDetails(int $companyId, ?int $memberId): ?Member
    {
        if (null === $memberId) {
            return null;
        }

        $memberQueries = resolve(MemberQueries::class);

        return $memberQueries->getById($memberId, $companyId);
    }
}
