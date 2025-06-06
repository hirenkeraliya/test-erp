<?php

declare(strict_types=1);

namespace App\Domains\HoldSale\Services;

use App\Domains\HoldSale\DataObjects\HoldSaleData;
use App\Domains\HoldSale\HoldSaleQueries;
use App\Domains\Member\MemberQueries;
use App\Domains\Sale\SaleQueries;
use App\Domains\SaleReturn\SaleReturnQueries;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Domains\StoreManagerAuthorizationCodeUsage\Services\StoreManagerAuthorizationCodeUsageService;
use App\Domains\UnitOfMeasureDerivative\UnitOfMeasureDerivativeQueries;
use App\Models\HoldSale;
use App\Models\Member;
use App\Models\Sale;
use App\Models\SaleReturn;
use Illuminate\Support\Collection;

class CheckHoldSaleDetailsService
{
    public HoldSaleData $holdSaleData;

    public Collection $products;

    public Collection $cartItems;

    public Collection $derivatives;

    public HoldSaleReturnService $holdSaleReturnService;

    public ?HoldSale $holdSale = null;

    public ?Sale $sale = null;

    public ?SaleReturn $saleReturn = null;

    public int $companyId;

    public ?Member $member = null;

    public Collection $saleMismatches;

    public function setDetails(
        HoldSaleData $holdSaleData,
        Collection $products,
        Collection $cartItems,
        int $companyId
    ): void {
        $this->holdSaleData = $holdSaleData;
        $this->products = $products;
        $this->cartItems = $cartItems;
        $this->companyId = $companyId;
        $this->saleMismatches = collect([]);

        $this->derivatives = collect([]);
        $derivativeIds = $this->cartItems->pluck('derivative_id')->unique()->filter();
        if (0 !== $derivativeIds->count()) {
            $unitOfMeasureDerivativeQueries = resolve(UnitOfMeasureDerivativeQueries::class);
            $this->derivatives = $unitOfMeasureDerivativeQueries->getByIds($derivativeIds->toArray());
        }

        $this->holdSaleReturnService = resolve(HoldSaleReturnService::class);
        $this->holdSaleReturnService->setDetails($this);

        $this->member = $this->getMember();
    }

    public function getMember(): ?Member
    {
        if ($this->holdSaleData->employee_id) {
            return $this->getEmployeeMember($this->holdSaleData->employee_id);
        }

        if (! $this->holdSaleData->member_id) {
            return null;
        }

        $memberQueries = resolve(MemberQueries::class);

        return $memberQueries->memberExistsById($this->companyId, $this->holdSaleData->member_id);
    }

    public function getEmployeeMember(int $employeeId): ?Member
    {
        $memberQueries = resolve(MemberQueries::class);

        return $memberQueries->getByEmployeeIdWithEmployee($this->companyId, $employeeId);
    }

    public function checkRequestDetails(): void
    {
        if (! $this->hasCartItems() && ! $this->holdSaleReturnService->hasReturnItems()) {
            return;
        }

        $this->checkMemberExists();

        $this->checkEmployeeExists();

        $this->checkProducts();

        if ($this->holdSaleReturnService->hasReturnItems()) {
            $this->holdSaleReturnService->checkReturnItems();
        }

        $this->checkHoldSaleCancelled();
        $this->checkHoldSaleCompleted();
        $this->checkHoldSaleReleased();
        $this->checkHoldSale();

        foreach ($this->cartItems as $cartItem) {
            $this->checkDerivativeDetails($cartItem);
        }
    }

    public function checkHoldSale(): void
    {
        $holdSaleQueries = resolve(HoldSaleQueries::class);
        if ($this->holdSaleData->cancelled_at) {
            $this->holdSale = $holdSaleQueries->getNotCancelByOfflineId($this->holdSaleData->offline_id);

            if (null === $this->holdSale) {
                abort(412, 'The offline ID you chose could not be located.');
            }

            return;
        }

        if ($this->holdSaleData->complete_at && $this->holdSaleData->complete_offline_id) {
            $saleQueries = resolve(SaleQueries::class);
            $this->sale = $saleQueries->getByOfflineId($this->holdSaleData->complete_offline_id, $this->companyId);

            $saleReturnQueries = resolve(SaleReturnQueries::class);
            $this->saleReturn = $saleReturnQueries->getByOfflineId(
                $this->holdSaleData->complete_offline_id,
                $this->companyId,
            );

            $this->holdSale = $holdSaleQueries->getNotCompleteByOfflineId($this->holdSaleData->offline_id);

            if (null === $this->holdSale) {
                abort(412, 'The offline ID you chose could not be located.');
            }

            return;
        }

        if ($this->holdSaleData->released_at) {
            $this->holdSale = $holdSaleQueries->getNotCompleteAndNotCancelByOfflineId(
                $this->holdSaleData->offline_id,
            );

            if (null === $this->holdSale) {
                abort(412, 'The offline ID you chose could not be located.');
            }

            return;
        }

        $this->checkOfflineId();
    }

    public function checkOfflineId(): void
    {
        $holdSaleQueries = resolve(HoldSaleQueries::class);
        if ($holdSaleQueries->doesOfflineIdExist($this->holdSaleData->offline_id)) {
            abort(412, 'The selected offline ID is already in use.');
        }
    }

    public function checkMemberExists(): void
    {
        if (! $this->holdSaleData->member_id) {
            return;
        }

        if ($this->member instanceof Member) {
            return;
        }

        abort(412, 'The selected member id is invalid.');
    }

    public function isMemberAttached(): bool
    {
        return $this->holdSaleData->member_id && null !== $this->holdSaleData->member_id;
    }

    public function checkEmployeeExists(): void
    {
        if (! $this->holdSaleData->employee_id) {
            return;
        }

        if ($this->member instanceof Member) {
            return;
        }

        abort(412, 'The selected employee id is invalid.');
    }

    public function checkProducts(): void
    {
        if ($this->products->count() !== $this->cartItems->pluck('id')->unique()->count()) {
            abort(412, 'Some of the products are not in our records.');
        }
    }

    public function checkDerivativeDetails(array $cartItem): void
    {
        if (! $this->hasDerivativeAttached($cartItem)) {
            return;
        }

        $derivative = $this->derivatives->firstWhere('id', $cartItem['derivative_id']);

        if (! $derivative) {
            abort(412, 'Specified derivative id is not available in our records.');
        }
    }

    public function hasDerivativeAttached(array $cartItem): bool
    {
        return array_key_exists('derivative_id', $cartItem) && $cartItem['derivative_id'];
    }

    public function hasCartItems(): bool
    {
        return $this->cartItems->isNotEmpty();
    }

    public function checkHoldSaleCancelled(): void
    {
        if (! $this->holdSaleData->cancelled_at) {
            return;
        }

        if (! $this->holdSaleData->reason) {
            abort(412, 'reason is required.');
        }

        if (! $this->holdSaleData->store_manager_id) {
            abort(412, 'store_manager_id is required.');
        }

        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $storeManager = $storeManagerQueries->getByIdWithEmployee(
            $this->holdSaleData->store_manager_id,
            $this->companyId
        );

        if (! $storeManager) {
            abort(412, 'Specified Store Manager does not correspond with our records.');
        }

        if ($storeManager->passcode !== $this->holdSaleData->store_manager_passcode) {
            abort(412, 'The Store Manager provided passcode for authorization does not correspond with our records.');
        }

        $this->checkStoreManagerAuthorizationCode();
        $this->checkHoldSaleCompletedOrCancelled();
    }

    public function checkStoreManagerAuthorizationCode(): void
    {
        $storeManagerAuthorizationCodeUsageService = resolve(StoreManagerAuthorizationCodeUsageService::class);
        $storeManagerAuthorizationCodeUsageService->checkStoreManagerAuthorizationCode(
            $this->saleMismatches,
            (int) $this->holdSaleData->store_manager_id,
            $this->holdSaleData->store_manager_authorization_code,
            $this->holdSaleData->happened_at
        );
    }

    public function checkHoldSaleCompleted(): void
    {
        if (! $this->holdSaleData->complete_at) {
            return;
        }

        $this->checkHoldSaleCompletedOrCancelled();

        $this->checkCompleteOfflineId();
    }

    public function checkHoldSaleReleased(): void
    {
        if (! $this->holdSaleData->released_at) {
            return;
        }

        $this->checkHoldSaleCompletedOrCancelled();
    }

    public function checkHoldSaleCompletedOrCancelled(): void
    {
        $holdSaleQueries = resolve(HoldSaleQueries::class);
        if ($holdSaleQueries->isCompletedHoldSale($this->holdSaleData->offline_id)) {
            abort(412, 'Specified hold sale: ' . $this->holdSaleData->offline_id . ' is already Completed.');
        }

        if ($holdSaleQueries->isCancelledHoldSale($this->holdSaleData->offline_id)) {
            abort(412, 'Specified hold sale: ' . $this->holdSaleData->offline_id . ' is already cancelled.');
        }
    }

    public function checkCompleteOfflineId(): void
    {
        if (! $this->holdSaleData->complete_offline_id) {
            abort(412, 'complete_offline_id is required.');
        }

        $saleQueries = resolve(SaleQueries::class);
        if ($saleQueries->doesOfflineSaleIdExist($this->holdSaleData->complete_offline_id, $this->companyId)) {
            return;
        }

        $saleReturnQueries = resolve(SaleReturnQueries::class);
        if ($saleReturnQueries->doesOfflineSaleReturnIdExist(
            $this->holdSaleData->complete_offline_id,
            $this->companyId
        )) {
            return;
        }

        abort(412, 'The complete offline id is not in our records.');
    }
}
