<?php

declare(strict_types=1);

namespace App\Domains\Voucher\Services;

use App\Domains\MemberChannelReference\MemberChannelReferenceQueries;
use App\Domains\OrderChannelReference\OrderChannelReferenceQueries;
use App\Domains\VoucherConfigurationChannelReference\VoucherConfigurationChannelReferenceQueries;
use App\Models\MemberChannelReference;
use App\Models\SaleChannel;
use App\Models\Voucher;
use App\Models\VoucherConfigurationChannelReference;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VoucherSyncInEcommerce
{
    public function addOrUpdateVoucherInEcommerce(SaleChannel $saleChannel, Voucher $voucher, string $url): void
    {
        Log::channel('e_commerce')->info('Start add or update vouchers in eCommerce', [
            'Start time for voucher addition' => Carbon::now()->format('Y-m-d H:i:s'),
            'voucher id: ' . $voucher->getKey(),
        ]);

        $memberId = null;
        if ($voucher->member_id) {
            $memberChannelReferenceQueries = resolve(MemberChannelReferenceQueries::class);
            $memberChannelReference = $memberChannelReferenceQueries->getByMemberIdAndSaleChannelId(
                $voucher->member_id,
                $saleChannel->id
            );
            if ($memberChannelReference instanceof MemberChannelReference) {
                $memberId = $memberChannelReference->external_member_id;
            }
        }

        if (null === $memberId) {
            Log::channel('e_commerce')->info('Member is not exist in eCommerce', [
                'Start time for voucher addition' => Carbon::now()->format('Y-m-d H:i:s'),
                'voucher id: ' . $voucher->getKey(),
                'member id: ' . $voucher->member_id,
            ]);

            return;
        }

        $voucherConfigurationId = null;
        $voucherConfigurationChannelReferenceQueries = resolve(VoucherConfigurationChannelReferenceQueries::class);
        $voucherConfigurationChannelReference = $voucherConfigurationChannelReferenceQueries->getByVoucherConfigurationIdAndSaleChannelId(
            $voucher->voucher_configuration_id,
            $saleChannel->id
        );

        if ($voucherConfigurationChannelReference instanceof VoucherConfigurationChannelReference) {
            $voucherConfigurationId = $voucherConfigurationChannelReference->external_voucher_configuration_id;
        }

        if (null === $voucherConfigurationId) {
            Log::channel('e_commerce')->info('Voucher configuration is not exist in eCommerce', [
                'Start time for voucher addition' => Carbon::now()->format('Y-m-d H:i:s'),
                'voucher id: ' . $voucher->getKey(),
                'voucher configuration id' . $voucher->voucher_configuration_id,
            ]);

            return;
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $saleChannel->secret,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post($url, [
            'voucher' => $this->preparedRecords($voucher, $saleChannel, $memberId, $voucherConfigurationId),
        ]);

        if ($response->successful()) {
            $responseData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

            Log::channel('e_commerce')->info('Response:voucher in E-Commerce', [
                'response' => $responseData,
            ]);
        } else {
            Log::channel('e_commerce')->info('Response: Error on voucher in E-Commerce', [
                'status_code' => $response->status(),
                'response_body' => $response->body() ?: 'No response body provided',
                'request_data' => [
                    'voucher_id' => $voucher->getKey(),
                    'saleChannel_id' => $saleChannel->getKey(),
                ],
            ]);
        }

        Log::channel('e_commerce')->info('End voucher add or update in eCommerce', [
            'Completion time for voucher addition' => Carbon::now()->format('Y-m-d H:i:s'),
            'voucher id: ' . $voucher->getKey(),
        ]);
    }

    private function preparedRecords(
        Voucher $voucher,
        SaleChannel $saleChannel,
        int $memberId,
        int $voucherConfigurationId
    ): array {
        $generatedByOrderId = null;
        if ($voucher->generated_by_order_id) {
            $orderChannelReferenceQueries = resolve(OrderChannelReferenceQueries::class);
            $orderChannelReference = $orderChannelReferenceQueries->getExternalOrderIdByOrderIdAndSaleChannelId(
                $voucher->generated_by_order_id,
                $saleChannel->id
            );
            if ($orderChannelReference) {
                $generatedByOrderId = $orderChannelReference->external_order_id;
            }
        }

        return [
            'id' => $voucher->id,
            'voucher_configuration_id' => $voucherConfigurationId,
            'customer_id' => $memberId,
            'discount_type' => $voucher->discount_type,
            'number' => $voucher->number,
            'minimum_spend_amount' => $voucher->minimum_spend_amount,
            'percentage' => $voucher->percentage,
            'flat_amount' => $voucher->flat_amount,
            'used_at' => $voucher->used_at,
            'expiry_date' => $voucher->expiry_date,
            'cancelled_at' => $voucher->cancelled_at,
            'dream_price_applicable' => $voucher->dream_price_applicable,
            'cart_wide_promotion_applicable' => $voucher->cart_wide_promotion_applicable,
            'status' => $voucher->status,
            'generated_by_order_id' => $generatedByOrderId,
        ];
    }
}
