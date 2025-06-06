<?php

declare(strict_types=1);

namespace App\Domains\Sale\Services;

use App\CommonFunctions;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\LoyaltyCampaign\LoyaltyCampaignQueries;
use App\Domains\LoyaltyPoint\LoyaltyPointQueries;
use App\Domains\LoyaltyPointUpdate\Enums\LoyaltyPointUpdateTypes;
use App\Domains\LoyaltyPointUpdate\LoyaltyPointUpdateQueries;
use App\Domains\Member\MemberQueries;
use App\Domains\Sale\DataObjects\CompleteCreditSaleData;
use App\Domains\Sale\DataObjects\CompleteLayawaySaleData;
use App\Domains\Sale\Enums\SaleStatus;
use App\Domains\Sale\SaleQueries;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\LoyaltyCampaign;
use App\Models\Member;
use App\Models\Product;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class GenerateLoyaltyPointsService
{
    public Collection $loyaltyCampaigns;

    public Collection $loyaltyPoints;

    public Collection $loyaltyPointsMismatches;

    public function setDetails(?array $loyaltyPoints, int $companyId): void
    {
        $this->loyaltyPoints = collect($loyaltyPoints);
        $loyaltyCampaignIds = $this->loyaltyPoints->pluck('loyalty_campaign_id')->unique()->filter()->toArray();
        $this->loyaltyCampaigns = $this->getLoyaltyCampaigns($loyaltyCampaignIds, $companyId);
        $this->loyaltyPointsMismatches = collect([]);
    }

    public function getLoyaltyCampaigns(array $loyaltyCampaignIds, int $companyId): Collection
    {
        $loyaltyCampaignQueries = resolve(LoyaltyCampaignQueries::class);

        return $loyaltyCampaignQueries->getByIds($loyaltyCampaignIds, $companyId);
    }

    public function checkLoyaltyPoints(
        array $itemTotals,
        CheckSaleDetailsService $checkSaleDetailsService,
        float $cartSubtotal,
        float $saleFinalAmount,
        ?int $memberId,
        string $happenedAt
    ): Collection {
        $this->checkUserDetails($memberId);

        $this->checkLoyaltyCampaigns();

        foreach ($this->loyaltyPoints as $loyaltyPoint) {
            $loyaltyCampaign = $this->getLoyaltyCampaign((int) $loyaltyPoint['loyalty_campaign_id']);

            $this->checkMinimumSpendAmount(
                (float) $loyaltyPoint['minimum_spend_amount'],
                (float) $loyaltyCampaign->minimum_spend_amount
            );

            $this->checkDateRange($happenedAt, $loyaltyCampaign);
            if (array_key_exists('expired_at', $loyaltyPoint) && $loyaltyPoint['expired_at']) {
                $this->checkExpireDate(
                    $loyaltyPoint['expired_at'],
                    $happenedAt,
                    $loyaltyCampaign->loyalty_point_expiration_days
                );
            }

            $saleAmountExcludeByBrands = $this->getFinalAmountExcludeByBrandsForOffline(
                $cartSubtotal,
                $saleFinalAmount,
                $itemTotals,
                $loyaltyCampaign,
                $checkSaleDetailsService,
            );

            $applicableLoyaltyPoints = $this->getTotalApplicableLoyaltyPoints(
                $saleAmountExcludeByBrands,
                $loyaltyCampaign
            );

            $this->checkApplicableLoyaltyPoints($applicableLoyaltyPoints, (int) $loyaltyPoint['points']);
        }

        return $this->loyaltyPointsMismatches;
    }

    public function getLoyaltyCampaign(int $loyaltyCampaignId): LoyaltyCampaign
    {
        return $this->loyaltyCampaigns->firstWhere('id', $loyaltyCampaignId);
    }

    public function checkApplicableLoyaltyPoints(int $applicableLoyaltyPoints, int $specifiedLoyaltyPoints): void
    {
        if ($applicableLoyaltyPoints === $specifiedLoyaltyPoints) {
            return;
        }

        $saleMismatchMessage = 'Specified loyalty points does not match with our calculations. Calculated loyalty points are ' . $applicableLoyaltyPoints . ' and, the specified loyalty points is ' . $specifiedLoyaltyPoints . '.';

        CommonFunctions::addMismatchOrAbort($this->loyaltyPointsMismatches, $saleMismatchMessage);
    }

    public function checkMinimumSpendAmount(
        float $loyaltyPointMinimumSpendAmount,
        float $loyaltyCampaignMinimumSpendAmount
    ): void {
        if ($loyaltyPointMinimumSpendAmount === $loyaltyCampaignMinimumSpendAmount) {
            return;
        }

        $saleMismatchMessage = 'The specified minimum spend amount does not match the loyalty campaign minimum spend amount. The loyalty campaign minimum spend amount is ' . $loyaltyCampaignMinimumSpendAmount . '. But the specified minimum spend amount is ' . $loyaltyPointMinimumSpendAmount . '.';
        CommonFunctions::addMismatchOrAbort($this->loyaltyPointsMismatches, $saleMismatchMessage);
    }

    public function checkLoyaltyCampaigns(): void
    {
        if (
            $this->loyaltyCampaigns->count()
            === $this->loyaltyPoints->pluck('loyalty_campaign_id')->unique()->count()
        ) {
            return;
        }

        abort(412, 'Some of the loyalty campaigns are not in our records.');
    }

    public function checkUserDetails(?int $memberId): void
    {
        if ($memberId) {
            return;
        }

        abort(412, 'User is compulsory when generate loyalty point');
    }

    public function checkDateRange(string $happenedAt, LoyaltyCampaign $loyaltyCampaign): void
    {
        $happenedAt = Carbon::createFromFormat('Y-m-d H:i:s', $happenedAt);
        if ($happenedAt) {
            $happenedAt = $happenedAt->format('Y-m-d');
        }

        if ($loyaltyCampaign->start_date > $happenedAt || $loyaltyCampaign->end_date < $happenedAt) {
            $saleMismatchMessage = 'Specified loyalty campaign is available between ' . $loyaltyCampaign->start_date . ' and ' . $loyaltyCampaign->end_date . '. only. But the specified sale date is ' . $happenedAt . '.';
            CommonFunctions::addMismatchOrAbort($this->loyaltyPointsMismatches, $saleMismatchMessage);
        }
    }

    public function checkExpireDate(
        ?string $specifiedExpireDate,
        string $happenedAt,
        ?int $loyaltyExpiryLimit
    ): void {
        $expireDate = null;
        if ($loyaltyExpiryLimit > 0 && Carbon::createFromFormat('Y-m-d H:i:s', $happenedAt)) {
            $expireDate = Carbon::createFromFormat('Y-m-d H:i:s', $happenedAt)->addDays(
                $loyaltyExpiryLimit
            )->format('Y-m-d');
        }

        if ($specifiedExpireDate === $expireDate) {
            return;
        }

        $saleMismatchMessage = 'Specified expire date does not match with our calculations. The actual expire date is ' . $expireDate . ' and requested expire date is ' . $specifiedExpireDate . '.';
        CommonFunctions::addMismatchOrAbort($this->loyaltyPointsMismatches, $saleMismatchMessage);
    }

    public function generateLoyaltyPoints(
        float $saleFinalAmount,
        ?int $member_id,
        int $companyId,
        Sale $sale,
        string $happenedAt
    ): void {
        if (! $member_id) {
            return;
        }

        $memberQueries = resolve(MemberQueries::class);

        $member = $memberQueries->getByIdWithMembershipAndLoyaltyPoints($companyId, $member_id);

        $this->updateUserLoyaltyPoints($companyId, $member, $happenedAt, $sale, $saleFinalAmount);
    }

    public function getTotalApplicableLoyaltyPoints(
        float $saleFinalAmount,
        LoyaltyCampaign $loyaltyCampaign,
        int $applicableLoyaltyPoints = 0,
    ): int {
        if ($saleFinalAmount < (float) $loyaltyCampaign->minimum_spend_amount) {
            return $applicableLoyaltyPoints;
        }

        $saleFinalAmount -= $loyaltyCampaign->minimum_spend_amount;
        $applicableLoyaltyPoints += $loyaltyCampaign->loyalty_points;

        return $this->getTotalApplicableLoyaltyPoints($saleFinalAmount, $loyaltyCampaign, $applicableLoyaltyPoints);
    }

    public function getFinalAmountExcludeByBrands(
        float $saleFinalAmount,
        LoyaltyCampaign $loyaltyCampaign,
        Sale $sale,
    ): float {
        if ($loyaltyCampaign->excludedBrands->isEmpty()) {
            return $saleFinalAmount;
        }

        $saleFinalAmountExcludeByBrands = 0.0;
        $totalAmount = $sale->total_amount_paid + $sale->layaway_pending_amount;

        if ($totalAmount <= 0) {
            return $saleFinalAmount;
        }

        foreach ($sale->saleItems as $saleItem) {
            /** @var Product $product */
            $product = $saleItem->product;

            if ($this->checkExcludedBrands($product, $loyaltyCampaign)) {
                continue;
            }

            $itemSubtotal = $saleItem->price_paid_per_unit * $saleItem->quantity;

            $saleFinalAmountExcludeByBrands += CommonFunctions::numberFormat(
                $saleFinalAmount * $itemSubtotal / $totalAmount
            );
        }

        return $saleFinalAmountExcludeByBrands;
    }

    public function getFinalAmountExcludeByBrandsForOffline(
        float $totalAmount,
        float $saleFinalAmount,
        array $itemTotals,
        LoyaltyCampaign $loyaltyCampaign,
        CheckSaleDetailsService $checkSaleDetailsService,
    ): float {
        if ($loyaltyCampaign->excludedBrands->isEmpty()) {
            return $saleFinalAmount;
        }

        if ($totalAmount <= 0) {
            return $saleFinalAmount;
        }

        $saleFinalAmountExcludeByBrands = 0.0;
        foreach ($checkSaleDetailsService->cartItems as $cartItem) {
            $product = $checkSaleDetailsService->products->firstWhere('id', $cartItem['id']);
            if ($this->checkExcludedBrands($product, $loyaltyCampaign)) {
                continue;
            }

            $itemSubtotal = $itemTotals[$product->id];

            $saleFinalAmountExcludeByBrands += CommonFunctions::numberFormat(
                $saleFinalAmount * $itemSubtotal / $totalAmount
            );
        }

        return $saleFinalAmountExcludeByBrands;
    }

    public function checkExcludedBrands(Product $product, LoyaltyCampaign $loyaltyCampaign): bool
    {
        if (! $product->brand) {
            return false;
        }

        return $loyaltyCampaign->excludedBrands->where('id', $product->brand->id)->isNotEmpty();
    }

    public function updateUserLoyaltyPointsForOffline(
        ?int $memberId,
        int $companyId,
        string $happenedAt,
        Sale $sale,
    ): void {
        if (! $memberId) {
            return;
        }

        $memberQueries = resolve(MemberQueries::class);

        $member = $memberQueries->getByIdWithMembershipAndLoyaltyPoints($companyId, $memberId);

        $closingLoyaltyPointsBalance = 0;
        /** @var ?int $userLoyaltyPoints */
        $userLoyaltyPoints = $member->loyalty_points;

        foreach ($this->loyaltyPoints as $loyaltyPoint) {
            $applicableLoyaltyPoints = $loyaltyPoint['points'];
            $loyaltyCampaign = $this->getLoyaltyCampaign((int) $loyaltyPoint['loyalty_campaign_id']);

            if ($loyaltyCampaign->loyalty_point_expiration_days <= 0) {
                $loyaltyPoint['expired_at'] = null;
            }

            if ($applicableLoyaltyPoints > 0) {
                $closingLoyaltyPointsBalance += $applicableLoyaltyPoints;
                $this->saveLoyaltyPoints(
                    $loyaltyPoint,
                    $member,
                    $sale,
                    $happenedAt,
                    $closingLoyaltyPointsBalance,
                    $userLoyaltyPoints,
                );
            }
        }

        $memberQueries->increaseLoyaltyPoints($member, $closingLoyaltyPointsBalance);
    }

    public function saveLoyaltyPoints(
        array $loyaltyPoint,
        Member $member,
        Sale $sale,
        string $happenedAt,
        int $closingLoyaltyPointsBalance,
        ?int $userLoyaltyPoints = 0,
    ): void {
        $loyaltyPointQueries = resolve(LoyaltyPointQueries::class);
        $loyaltyPointUpdateQueries = resolve(LoyaltyPointUpdateQueries::class);
        $loyaltyPoint = $loyaltyPointQueries->addNew([
            'member_id' => $member->id,
            'sale_id' => $sale->getKey(),
            'loyalty_campaign_id' => $loyaltyPoint['loyalty_campaign_id'],
            'expiry_date' => $loyaltyPoint['expired_at'] ?? null,
            'points' => $loyaltyPoint['points'],
            'available_points' => $loyaltyPoint['points'],
            'minimum_spend_amount' => $loyaltyPoint['minimum_spend_amount'],
        ]);

        $loyaltyPointUpdateQueries->addNew([
            'member_id' => $member->id,
            'loyalty_point_id' => $loyaltyPoint->id,
            'affected_by_id' => $sale->getKey(),
            'affected_by_type' => ModelMapping::SALE->name,
            'type_id' => LoyaltyPointUpdateTypes::SALE->value,
            'points' => $loyaltyPoint['points'],
            'closing_loyalty_points_balance' => $closingLoyaltyPointsBalance + $userLoyaltyPoints,
            'happened_at' => $happenedAt,
        ]);
    }

    public function checkLayawaySaleLoyaltyPoints(
        float $saleFinalAmount,
        ?int $memberId,
        Sale $sale,
        string $happenedAt
    ): Collection {
        $this->checkUserDetails($memberId);

        $this->checkLoyaltyCampaigns();

        foreach ($this->loyaltyPoints as $loyaltyPoint) {
            $loyaltyCampaign = $this->getLoyaltyCampaign((int) $loyaltyPoint['loyalty_campaign_id']);

            $this->checkMinimumSpendAmount(
                (float) $loyaltyPoint['minimum_spend_amount'],
                (float) $loyaltyCampaign->minimum_spend_amount
            );

            $this->checkDateRange($happenedAt, $loyaltyCampaign);
            if (array_key_exists('expired_at', $loyaltyPoint) && $loyaltyPoint['expired_at']) {
                $this->checkExpireDate(
                    $loyaltyPoint['expired_at'],
                    $happenedAt,
                    $loyaltyCampaign->loyalty_point_expiration_days
                );
            }

            $saleFinalAmountExcludeByBrands = $this->getLayawaySaleFinalAmountExcludeByBrands(
                $saleFinalAmount,
                $loyaltyCampaign,
                $sale
            );

            $applicableLoyaltyPoints = $this->getTotalApplicableLoyaltyPoints(
                $saleFinalAmountExcludeByBrands,
                $loyaltyCampaign
            );

            $this->checkApplicableLoyaltyPoints($applicableLoyaltyPoints, (int) $loyaltyPoint['points']);
        }

        return $this->loyaltyPointsMismatches;
    }

    public function getLayawaySaleFinalAmountExcludeByBrands(
        float $saleFinalAmount,
        LoyaltyCampaign $loyaltyCampaign,
        Sale $sale,
    ): float {
        if ($loyaltyCampaign->excludedBrands->isEmpty()) {
            return $saleFinalAmount;
        }

        $saleFinalAmountExcludeByBrands = 0.0;
        $totalAmount = $sale->total_amount_paid + $sale->layaway_pending_amount;
        foreach ($sale->saleItems as $saleItem) {
            /** @var Product $product */
            $product = $saleItem->product;

            if ($this->checkExcludedBrands($product, $loyaltyCampaign)) {
                continue;
            }

            $itemSubtotal = $saleItem->price_paid_per_unit * $saleItem->quantity;

            $saleFinalAmountExcludeByBrands += CommonFunctions::numberFormat(
                $saleFinalAmount * $itemSubtotal / $totalAmount
            );
        }

        return $saleFinalAmountExcludeByBrands;
    }

    public function saveGenerateLoyaltyPoints(
        CheckSaleDetailsService $checkSaleDetailsService,
        Sale $sale,
        ?int $memberId
    ): void {
        if ($checkSaleDetailsService->hasGenerateLoyaltyPoints()) {
            $this->updateUserLoyaltyPointsForOffline(
                $memberId,
                $checkSaleDetailsService->companyId,
                $checkSaleDetailsService->saleData->happened_at,
                $sale,
            );

            return;
        }

        /** @var Company $company */
        $company = $checkSaleDetailsService->company;

        /** @var CompanySetting $companySetting */
        $companySetting = $company->companySetting;

        if (
            $sale->status === SaleStatus::PENDING_LAYAWAY_SALE->value
            && ! $companySetting->layaway_sale_earn_loyalty_points
        ) {
            return;
        }

        if (
            $sale->status === SaleStatus::COMPLETE_LAYAWAY_SALE->value
            && ! $companySetting->layaway_sale_earn_loyalty_points
        ) {
            return;
        }

        if (
            $sale->status === SaleStatus::PENDING_CREDIT_SALE->value
            && ! $companySetting->credit_sale_earn_loyalty_points
        ) {
            return;
        }

        if (
            $sale->status === SaleStatus::COMPLETE_CREDIT_SALE->value
            && ! $companySetting->credit_sale_earn_loyalty_points
        ) {
            return;
        }

        /** @var float $saleFinalAmount */
        $saleFinalAmount = $sale->total_amount_paid;

        $saleFinalAmount -= $checkSaleDetailsService->saleReturnService->getExchangeItemsTotal();

        $this->generateLoyaltyPoints(
            $saleFinalAmount,
            $memberId,
            $checkSaleDetailsService->companyId,
            $sale,
            $checkSaleDetailsService->saleData->happened_at
        );
    }

    public function hasGenerateLoyaltyPointsForLayawaySale(
        CompleteLayawaySaleData $completeLayawaySaleDataRequest
    ): bool {
        return collect($completeLayawaySaleDataRequest->loyalty_points)->isNotEmpty();
    }

    public function generateLoyaltyPointsForLayawaySale(
        CompleteLayawaySaleData $completeLayawaySaleDataRequest,
        Sale $sale,
        CompanySetting $companySetting,
        int $companyId,
        float $saleFinalAmount,
        int $memberId,
    ): void {
        $happenedAt = $completeLayawaySaleDataRequest->happened_at ?? now()->format('Y-m-d H:i:s');
        if ($this->hasGenerateLoyaltyPointsForLayawaySale($completeLayawaySaleDataRequest)) {
            $this->updateUserLoyaltyPointsForOffline($memberId, $companyId, $happenedAt, $sale);

            return;
        }

        if (! $companySetting->layaway_sale_earn_loyalty_points) {
            return;
        }

        $this->generateLoyaltyPoints($saleFinalAmount, $memberId, $companyId, $sale, $happenedAt);
    }

    public function hasGenerateLoyaltyPointsForCreditSale(CompleteCreditSaleData $completeCreditSaleData): bool
    {
        return collect($completeCreditSaleData->loyalty_points)->isNotEmpty();
    }

    public function checkCreditSaleLoyaltyPoints(
        float $saleFinalAmount,
        ?int $memberId,
        Sale $sale,
        string $happenedAt
    ): Collection {
        $this->checkUserDetails($memberId);

        $this->checkLoyaltyCampaigns();

        foreach ($this->loyaltyPoints as $loyaltyPoint) {
            $loyaltyCampaign = $this->getLoyaltyCampaign((int) $loyaltyPoint['loyalty_campaign_id']);

            $this->checkMinimumSpendAmount(
                (float) $loyaltyPoint['minimum_spend_amount'],
                (float) $loyaltyCampaign->minimum_spend_amount
            );

            $this->checkDateRange($happenedAt, $loyaltyCampaign);
            if (array_key_exists('expired_at', $loyaltyPoint) && $loyaltyPoint['expired_at']) {
                $this->checkExpireDate(
                    $loyaltyPoint['expired_at'],
                    $happenedAt,
                    $loyaltyCampaign->loyalty_point_expiration_days
                );
            }

            $saleFinalAmountExcludeByBrands = $this->getCreditSaleFinalAmountExcludeByBrands(
                $saleFinalAmount,
                $loyaltyCampaign,
                $sale
            );

            $applicableLoyaltyPoints = $this->getTotalApplicableLoyaltyPoints(
                $saleFinalAmountExcludeByBrands,
                $loyaltyCampaign
            );

            $this->checkApplicableLoyaltyPoints($applicableLoyaltyPoints, (int) $loyaltyPoint['points']);
        }

        return $this->loyaltyPointsMismatches;
    }

    public function getCreditSaleFinalAmountExcludeByBrands(
        float $saleFinalAmount,
        LoyaltyCampaign $loyaltyCampaign,
        Sale $sale,
    ): float {
        if ($loyaltyCampaign->excludedBrands->isEmpty()) {
            return $saleFinalAmount;
        }

        $saleFinalAmountExcludeByBrands = 0.0;
        $totalAmount = $sale->total_amount_paid + $sale->credit_pending_amount;
        foreach ($sale->saleItems as $saleItem) {
            /** @var Product $product */
            $product = $saleItem->product;

            if ($this->checkExcludedBrands($product, $loyaltyCampaign)) {
                continue;
            }

            $itemSubtotal = $saleItem->price_paid_per_unit * $saleItem->quantity;

            $saleFinalAmountExcludeByBrands += CommonFunctions::numberFormat(
                $saleFinalAmount * $itemSubtotal / $totalAmount
            );
        }

        return $saleFinalAmountExcludeByBrands;
    }

    public function generateLoyaltyPointsForCreditSale(
        CompleteCreditSaleData $completeCreditSaleData,
        Sale $sale,
        CompanySetting $companySetting,
        int $companyId,
        float $saleFinalAmount,
        ?int $memberId,
    ): void {
        $happenedAt = $completeCreditSaleData->happened_at ?? now()->format('Y-m-d H:i:s');
        if ($this->hasGenerateLoyaltyPointsForCreditSale($completeCreditSaleData)) {
            $this->updateUserLoyaltyPointsForOffline($memberId, $companyId, $happenedAt, $sale);

            return;
        }

        if (! $companySetting->credit_sale_earn_loyalty_points) {
            return;
        }

        $this->generateLoyaltyPoints($saleFinalAmount, $memberId, $companyId, $sale, $happenedAt);
    }

    private function updateUserLoyaltyPoints(
        int $companyId,
        Member $member,
        string $happenedAt,
        Sale $sale,
        float $saleFinalAmount
    ): void {
        $saleQueries = resolve(SaleQueries::class);
        $sale = $saleQueries->loadSaleItemsProductAndBrand($sale);
        $loyaltyCampaignQueries = resolve(LoyaltyCampaignQueries::class);
        $loyaltyCampaigns = $loyaltyCampaignQueries->getActiveLoyaltyCampaignsByCompanyId($companyId);
        $closingLoyaltyPointsBalance = 0;
        /** @var ?int $userLoyaltyPoints */
        $userLoyaltyPoints = $member->loyalty_points;

        foreach ($loyaltyCampaigns as $loyaltyCampaign) {
            $saleFinalAmountExcludeByBrands = $this->getFinalAmountExcludeByBrands(
                $saleFinalAmount,
                $loyaltyCampaign,
                $sale
            );
            $applicableLoyaltyPoints = $this->getTotalApplicableLoyaltyPoints(
                $saleFinalAmountExcludeByBrands,
                $loyaltyCampaign
            );

            if ($applicableLoyaltyPoints > 0) {
                $closingLoyaltyPointsBalance += $applicableLoyaltyPoints;
                $loyaltyPoint = [
                    'loyalty_campaign_id' => $loyaltyCampaign->id,
                    'expired_at' => $loyaltyCampaign->loyalty_point_expiration_days > 0 ? Carbon::now()->addDays(
                        $loyaltyCampaign->loyalty_point_expiration_days
                    ) : null,
                    'points' => $applicableLoyaltyPoints,
                    'minimum_spend_amount' => $loyaltyCampaign->minimum_spend_amount,
                ];

                $this->saveLoyaltyPoints(
                    $loyaltyPoint,
                    $member,
                    $sale,
                    $happenedAt,
                    $closingLoyaltyPointsBalance,
                    $userLoyaltyPoints,
                );
            }
        }

        $memberQueries = resolve(MemberQueries::class);
        $memberQueries->increaseLoyaltyPoints($member, $closingLoyaltyPointsBalance);
    }
}
