<?php

declare(strict_types=1);

namespace App\Domains\Order\Services;

use App\CommonFunctions;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\LoyaltyCampaign\LoyaltyCampaignQueries;
use App\Domains\LoyaltyPoint\LoyaltyPointQueries;
use App\Domains\LoyaltyPointUpdate\Enums\LoyaltyPointUpdateTypes;
use App\Domains\LoyaltyPointUpdate\LoyaltyPointUpdateQueries;
use App\Domains\Member\MemberQueries;
use App\Models\LoyaltyCampaign;
use App\Models\Member;
use App\Models\Order;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class GenerateEcommerceLoyaltyPointsService
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
        CheckOrderEcommerceDetailsService $checkOrderEcommerceDetailsService,
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
                $checkOrderEcommerceDetailsService,
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

    public function getFinalAmountExcludeByBrandsForOffline(
        float $totalAmount,
        float $saleFinalAmount,
        array $itemTotals,
        LoyaltyCampaign $loyaltyCampaign,
        CheckOrderEcommerceDetailsService $checkOrderEcommerceDetailsService,
    ): float {
        if ($loyaltyCampaign->excludedBrands->isEmpty()) {
            return $saleFinalAmount;
        }

        if ($totalAmount <= 0) {
            return $saleFinalAmount;
        }

        $saleFinalAmountExcludeByBrands = 0.0;
        foreach ($checkOrderEcommerceDetailsService->orderItems as $orderItem) {
            $product = $checkOrderEcommerceDetailsService->products->firstWhere('id', $orderItem['id']);
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
        Order $order,
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
                    $order,
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
        Order $order,
        string $happenedAt,
        int $closingLoyaltyPointsBalance,
        ?int $userLoyaltyPoints = 0,
    ): void {
        $loyaltyPointQueries = resolve(LoyaltyPointQueries::class);
        $loyaltyPointUpdateQueries = resolve(LoyaltyPointUpdateQueries::class);
        $loyaltyPoint = $loyaltyPointQueries->addNew([
            'member_id' => $member->id,
            'order_id' => $order->getKey(),
            'loyalty_campaign_id' => $loyaltyPoint['loyalty_campaign_id'],
            'expiry_date' => $loyaltyPoint['expired_at'] ?? null,
            'points' => $loyaltyPoint['points'],
            'available_points' => $loyaltyPoint['points'],
            'minimum_spend_amount' => $loyaltyPoint['minimum_spend_amount'],
        ]);

        $loyaltyPointUpdateQueries->addNew([
            'member_id' => $member->id,
            'loyalty_point_id' => $loyaltyPoint->id,
            'affected_by_id' => $order->getKey(),
            'affected_by_type' => ModelMapping::ORDER->name,
            'type_id' => LoyaltyPointUpdateTypes::ORDER->value,
            'points' => $loyaltyPoint['points'],
            'closing_loyalty_points_balance' => $closingLoyaltyPointsBalance + $userLoyaltyPoints,
            'happened_at' => $happenedAt,
        ]);
    }

    public function saveGenerateLoyaltyPoints(
        CheckOrderEcommerceDetailsService $checkOrderEcommerceDetailsService,
        Order $order,
        ?int $memberId
    ): void {
        if (! $checkOrderEcommerceDetailsService->hasGenerateLoyaltyPoints()) {
            return;
        }

        if (! $memberId) {
            return;
        }

        $happenedAtFormat = $checkOrderEcommerceDetailsService->getHappenedAtFormat();

        $this->updateUserLoyaltyPointsForOffline(
            $memberId,
            $checkOrderEcommerceDetailsService->companyId,
            $happenedAtFormat->format('Y-m-d H:i:s'),
            $order,
        );
    }
}
