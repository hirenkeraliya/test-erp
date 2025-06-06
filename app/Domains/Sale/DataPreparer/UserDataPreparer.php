<?php

declare(strict_types=1);

namespace App\Domains\Sale\DataPreparer;

use App\Domains\Common\Enums\ModelMapping;
use App\Models\CreditNote;
use App\Models\HoldSaleDetail;
use App\Models\Member;
use App\Models\MemberAddress;
use App\Models\Sale;
use App\Models\SaleReturn;
use Illuminate\Support\Collection;

class UserDataPreparer
{
    public function getUserDetails(
        ?Member $member,
        Collection $loyaltyPointUpdates,
        Collection $saleItems
    ): ?array {
        if (! $member instanceof Member) {
            return null;
        }

        $accumulatedPoints = $member->loyalty_points;
        $earnedPoints = $this->getEarnedPoints($loyaltyPointUpdates);
        $redeemPoints = $this->getRedeemPoints($loyaltyPointUpdates);
        $redeemPointsToProduct = $this->getRedeemPointsToProduct($saleItems);

        return [
            'first_name' => $member->first_name,
            'last_name' => $member->last_name,
            'email' => $member->email,
            'mobile_number' => $member->mobile_number,
            'employee_id' => $member->employee_id,
            'membership_id' => $member->membership_id,
            'previous_points' => $accumulatedPoints - $earnedPoints + $redeemPoints + $redeemPointsToProduct,
            'earned_points' => $earnedPoints,
            'redeem_points_to_product' => $redeemPointsToProduct,
            'redeem_points' => $redeemPoints,
            'current_sale_points' => ($earnedPoints - ($redeemPoints + $redeemPointsToProduct)),
            'accumulated_points' => $accumulatedPoints,
        ];
    }

    public function getRedeemPointsToProduct(Collection $saleItems): int
    {
        return $saleItems->sum(fn ($saleItem): int|float => abs($saleItem->loyaltyPointUpdates->sum('points')));
    }

    public function getEarnedPoints(Collection $loyaltyPointUpdates): int
    {
        return $loyaltyPointUpdates->filter(fn ($loyaltyPointUpdate): bool => $loyaltyPointUpdate['points'] > 0)->sum(
            'points'
        );
    }

    public function getRedeemPoints(Collection $loyaltyPointUpdates): int
    {
        $redeemPoints = $loyaltyPointUpdates->filter(
            fn ($loyaltyPointUpdate): bool => $loyaltyPointUpdate['points'] < 0
        )->sum('points');

        return (int) abs($redeemPoints);
    }

    public function getUserType(Sale|SaleReturn|CreditNote|HoldSaleDetail $saleOrReturn): ?string
    {
        if (! $saleOrReturn->member_id) {
            return null;
        }

        /** @var Member $member */
        $member = $saleOrReturn->member;
        if ($member->employee_id) {
            return ModelMapping::getFormattedCaseName(ModelMapping::EMPLOYEE->value);
        }

        return ModelMapping::getFormattedCaseName(ModelMapping::MEMBER->value);
    }

    public function getBasicUserDetails(?Member $member): ?array
    {
        if (! $member instanceof Member) {
            return null;
        }

        return [
            'first_name' => $member->first_name,
            'last_name' => $member->last_name,
            'email' => $member->email,
            'mobile_number' => $member->mobile_number,
            'employee_id' => $member->employee_id,
            'card_number' => $member->card_number,
        ];
    }

    public function getMemberNameAndAddressDetails(?Member $member): ?array
    {
        /** @var MemberAddress|null $memberAddress */
        $memberAddress = $member?->primaryMemberAddress;

        return [
            'name' => $member instanceof Member ? $member->getFullName() : '',
            'address_line_1' => $memberAddress ? $memberAddress->address_line_1 : '',
            'address_line_2' => $memberAddress ? $memberAddress->address_line_2 : '',
            'city' => $memberAddress ? $memberAddress->city_name : '',
            'area_code' => $memberAddress ? $memberAddress->area_code : '',
        ];
    }

    public function getMemberAddressDetails(Collection $memberAddresses): Collection
    {
        return $memberAddresses->map(fn ($memberAddress): array => [
            'id' => $memberAddress->id,
            'name' => $memberAddress->name,
            'contact_mobile_number' => $memberAddress->contact_mobile_number,
            'contact_email' => $memberAddress->contact_email,
            'address_line_1' => $memberAddress->address_line_1,
            'address_line_2' => $memberAddress->address_line_2,
            'city' => $memberAddress->city_name,
            'area_code' => $memberAddress->area_code,
            'is_primary' => $memberAddress->is_primary,
        ]);
    }
}
