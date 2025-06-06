<?php

declare(strict_types=1);

namespace App\Domains\LoyaltyPointUpdate\Services;

use App\CommonFunctions;
use App\Domains\LoyaltyPointUpdate\Enums\LoyaltyPointUpdateTypes;
use App\Domains\LoyaltyPointUpdate\LoyaltyPointUpdateQueries;
use App\Domains\MemberChannelReference\MemberChannelReferenceQueries;
use App\Domains\OrderChannelReference\OrderChannelReferenceQueries;
use App\Models\Admin;
use App\Models\Employee;
use App\Models\LoyaltyPointUpdate;
use App\Models\Member;
use App\Models\Order;
use App\Models\Sale;
use App\Models\SaleChannel;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\VoidSale;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncLoyaltyPointUpdateInEcommerceService
{
    public function addUpdateDetails(LoyaltyPointUpdate $loyaltyPointUpdate, SaleChannel $saleChannel): void
    {
        Log::channel('e_commerce')->info('Start creating or updating the loyalty point updates eCommerce.', [
            'Start time for master product creation or updating' => Carbon::now()->format('Y-m-d H:i:s'),
            'loyalty point updates id: ' . $loyaltyPointUpdate->getKey(),
        ]);

        foreach ($saleChannel->saleChannelWebhookUrls as $saleChannelWebhookUrl) {
            $url = $saleChannelWebhookUrl->url;

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $saleChannel->secret,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(config('services.http_time_out'))->post($url, [
                'loyaltyPointUpdate' => $this->preparedRecords($loyaltyPointUpdate, $saleChannel),
            ]);

            if ($response->successful()) {
                Log::channel('e_commerce')->info('Response: loyalty point updates synchronized in E-Commerce');
            } else {
                Log::channel('e_commerce')->info('Response: Error on loyalty point updates in E-Commerce', [
                    'status_code' => $response->status(),
                    'response_body' => $response->body() ?: 'No response body provided',
                    'request_data' => [
                        'loyalty_point_updates_id' => $loyaltyPointUpdate->getKey(),
                        'saleChannel_id' => $saleChannel->getKey(),
                    ],
                ]);
            }
        }
    }

    public function preparedRecords(LoyaltyPointUpdate $loyaltyPointUpdate, SaleChannel $saleChannel): array
    {
        $loyaltyPointsUpdateQueries = resolve(LoyaltyPointUpdateQueries::class);
        $memberLoyaltyPointUpdateDetails = $loyaltyPointsUpdateQueries->getLoyaltyPointDetailsForEcommerceSyncByIdAndCompanyId(
            $loyaltyPointUpdate->id,
            $saleChannel->company_id,
        );

        /** @var Admin|Sale|SaleReturn|VoidSale|Member|SaleItem|SaleReturnItem|Order $affectedBy */
        $affectedBy = $memberLoyaltyPointUpdateDetails->affectedBy;

        $description = 'Expired';

        if ($loyaltyPointUpdate->type_id === LoyaltyPointUpdateTypes::MANUAL_UPDATE->value) {
            $description = 'Added By Admin';
        }

        if ($affectedBy instanceof Admin) {
            /** @var Employee $employee */
            $employee = $affectedBy->employee;
            $description = CommonFunctions::stringTitleLowerCase($employee->getFullName());
        }

        if ($affectedBy instanceof Member) {
            $description = 'Welcome Benefits';
        }

        if ($affectedBy instanceof Sale) {
            $description = $affectedBy->offline_sale_id;
        }

        if ($affectedBy instanceof SaleItem) {
            $description = $affectedBy->sale?->offline_sale_id;
        }

        if ($affectedBy instanceof SaleReturn) {
            $description = $affectedBy->offline_sale_return_id;
        }

        if ($affectedBy instanceof SaleReturnItem) {
            $description = $affectedBy->saleReturn?->offline_sale_return_id;
        }

        if ($affectedBy instanceof VoidSale) {
            $description = $affectedBy->sale_id . ' (' . $affectedBy->void_sale_number . ')';
        }

        if ($affectedBy instanceof Order) {
            $orderChannelReferenceQueries = resolve(OrderChannelReferenceQueries::class);
            $orderChannelReference = $orderChannelReferenceQueries->getExternalOrderIdByOrderId($affectedBy->id);

            if ($orderChannelReference) {
                $description = 'Order: '.$orderChannelReference->external_order_id;
            }
        }

        $module = 'System Generated';
        if (null !== $loyaltyPointUpdate->affected_by_type) {
            $module = CommonFunctions::stringTitleLowerCase($loyaltyPointUpdate->affected_by_type);
        }

        $happenedAt = null;
        if ($loyaltyPointUpdate->happened_at) {
            $happenedAt = $loyaltyPointUpdate->happened_at;
        }

        $memberId = null;
        if ($loyaltyPointUpdate->member_id) {
            $memberChannelReferenceQueries = resolve(MemberChannelReferenceQueries::class);
            $memberChannelReference = $memberChannelReferenceQueries->getByMemberIdAndSaleChannelId(
                $loyaltyPointUpdate->member_id,
                $saleChannel->id
            );
            if ($memberChannelReference) {
                $memberId = $memberChannelReference->external_member_id;
            }
        }

        return [
            'external_id' => $loyaltyPointUpdate->getKey(),
            'customer_id' => $memberId,
            'module' => $module,
            'type_id' => $loyaltyPointUpdate->type_id,
            'description' => $description,
            'points' => $loyaltyPointUpdate->points,
            'closing_loyalty_points_balance' => $loyaltyPointUpdate->closing_loyalty_points_balance,
            'remarks' => $loyaltyPointUpdate->remarks,
            'happened_at' => $happenedAt,
        ];
    }
}
