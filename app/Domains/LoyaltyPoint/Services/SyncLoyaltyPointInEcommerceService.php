<?php

declare(strict_types=1);

namespace App\Domains\LoyaltyPoint\Services;

use App\Domains\MemberChannelReference\MemberChannelReferenceQueries;
use App\Domains\OrderChannelReference\OrderChannelReferenceQueries;
use App\Models\LoyaltyPoint;
use App\Models\SaleChannel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncLoyaltyPointInEcommerceService
{
    public function addUpdateDetails(LoyaltyPoint $loyaltyPoint, SaleChannel $saleChannel): void
    {
        Log::channel('e_commerce')->info('Start creating or updating the loyalty point eCommerce.', [
            'Start time for master product creation or updating' => Carbon::now()->format('Y-m-d H:i:s'),
            'loyalty point id: ' . $loyaltyPoint->getKey(),
        ]);

        foreach ($saleChannel->saleChannelWebhookUrls as $saleChannelWebhookUrl) {
            $url = $saleChannelWebhookUrl->url;

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $saleChannel->secret,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(config('services.http_time_out'))->post($url, [
                'loyaltyPoint' => $this->preparedRecords($loyaltyPoint, $saleChannel),
            ]);

            if ($response->successful()) {
                Log::channel('e_commerce')->info('Response: loyalty point synchronized in E-Commerce');
            } else {
                Log::channel('e_commerce')->info('Response: Error on loyalty point in E-Commerce', [
                    'status_code' => $response->status(),
                    'response_body' => $response->body() ?: 'No response body provided',
                    'request_data' => [
                        'loyalty_point_id' => $loyaltyPoint->getKey(),
                        'saleChannel_id' => $saleChannel->getKey(),
                    ],
                ]);
            }
        }
    }

    private function preparedRecords(LoyaltyPoint $loyaltyPoint, SaleChannel $saleChannel): array
    {
        $memberId = null;
        if ($loyaltyPoint->member_id) {
            $memberChannelReferenceQueries = resolve(MemberChannelReferenceQueries::class);
            $memberChannelReference = $memberChannelReferenceQueries->getByMemberIdAndSaleChannelId(
                $loyaltyPoint->member_id,
                $saleChannel->id
            );
            if ($memberChannelReference) {
                $memberId = $memberChannelReference->external_member_id;
            }
        }

        $orderReceiptNumber = null;
        if ($loyaltyPoint->order_id) {
            $orderChannelReferenceQueries = resolve(OrderChannelReferenceQueries::class);
            $orderChannelReference = $orderChannelReferenceQueries->getExternalOrderIdByOrderIdAndSaleChannelId(
                $loyaltyPoint->order_id,
                $saleChannel->id
            );
            if ($orderChannelReference) {
                $orderReceiptNumber = $orderChannelReference->external_order_id;
            }
        }

        return [
            'external_id' => $loyaltyPoint->getKey(),
            'customer_id' => $memberId,
            'loyalty_campaign_id' => $loyaltyPoint->loyalty_campaign_id,
            'expiry_date' => $loyaltyPoint->expiry_date,
            'points' => $loyaltyPoint->points,
            'available_points' => $loyaltyPoint->available_points,
            'minimum_spend_amount' => $loyaltyPoint->minimum_spend_amount,
            'order_receipt_number' => $orderReceiptNumber,
        ];
    }
}
