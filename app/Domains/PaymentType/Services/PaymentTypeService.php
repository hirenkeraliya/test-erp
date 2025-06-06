<?php

declare(strict_types=1);

namespace App\Domains\PaymentType\Services;

use App\Domains\Common\Enums\WebhookUrls;
use App\Domains\PaymentType\DataObjects\PaymentTypeData;
use App\Domains\PaymentType\Enums\PaymentTypeImages;
use App\Domains\PaymentType\PaymentTypeQueries;
use App\Domains\SaleChannel\Enums\SaleChannelTypes;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Domains\ShippingZoneChannelReference\ShippingZoneChannelReferenceQueries;
use App\Models\PaymentType;
use App\Models\SaleChannel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentTypeService
{
    public function getPaymentTypeData(array $paymentTypeDetails): PaymentTypeData
    {
        return new PaymentTypeData(
            name: (string) $paymentTypeDetails['name'],
            is_member_required: 'Yes' === $paymentTypeDetails['is_member_required'],
            is_available_for_refund: 'Yes' === $paymentTypeDetails['is_available_for_refund'],
            trigger_card_payment_machine : false,
            trigger_qr_code_payment_machine : false,
            trigger_card_affin_payment_machine : false,
            status: true,
            image_name: PaymentTypeImages::E_WALLET->value,
            payment_terminal_key : (string) $paymentTypeDetails['payment_terminal_key'] ?: null,
            is_card_payment: false,
            trigger_card_bank_rakyat_terminal: false,
        );
    }

    public function addUpdateDetails(PaymentType $paymentType, SaleChannel $saleChannel): void
    {
        Log::channel('e_commerce')->info('Start creating or updating the payment type in eCommerce.', [
            'Start time for payment type creation or updation' => Carbon::now()->format('Y-m-d H:i:s'),
            'payment type id: ' . $paymentType->getKey(),
        ]);

        $paymentTypeQueries = resolve(PaymentTypeQueries::class);

        foreach ($saleChannel->saleChannelWebhookUrls as $saleChannelWebhookUrl) {
            $url = $saleChannelWebhookUrl->url;

            $productSaleChannelMatch = $paymentTypeQueries->validatePaymentTypeSaleChannelMatch(
                $paymentType,
                $saleChannel
            );

            if (SaleChannelTypes::ECOMMERCE === $saleChannel->type_id
                && $productSaleChannelMatch
            ) {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $saleChannel->secret,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])->post($url, [
                    'payment_type' => $this->preparedRecords($paymentType, $saleChannel->id),
                ]);

                if ($response->successful()) {
                    $responseData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

                    Log::channel('e_commerce')->info('Response: Payment Type in E-Commerce', [
                        'response' => $responseData,
                    ]);
                } else {
                    Log::channel('e_commerce')->info('Response: Error on Payment Type in E-Commerce', [
                        'status_code' => $response->status(),
                        'response_body' => $response->body() ?: 'No response body provided',
                        'request_data' => [
                            'payment_type_id' => $paymentType->getKey(),
                            'saleChannel_id' => $saleChannel->getKey(),
                        ],
                    ]);
                }
            }

            if (! $productSaleChannelMatch) {
                $this->unAvailablePaymentTypeInCommerce($paymentType);
            }
        }

        Log::channel('e_commerce')->info('End creating or updating the payment type in eCommerce.', [
            'End time for payment tType creation or updation' => Carbon::now()->format('Y-m-d H:i:s'),
            'payment type id: ' . $paymentType->getKey(),
        ]);
    }

    public function unAvailablePaymentTypeInCommerce(PaymentType $paymentType): void
    {
        $webhookUrls = [WebhookUrls::PAYMENT_TYPE_UNAVAILABLE->value];

        $saleChannelQueries = resolve(SaleChannelQueries::class);
        $saleChannels = $saleChannelQueries->getSaleChannelsByCompany($webhookUrls, $paymentType->company_id);

        if ($saleChannels->isEmpty()) {
            Log::channel('e_commerce')->info(
                'Unavailable payment type : return when sale channels is empty',
                [
                    'start time of the webhook call for the payment type unavailable' => Carbon::now()->format(
                        'Y-m-d H:i:s'
                    ),
                    'payment type id: ' . $paymentType->getKey(),
                ]
            );

            return;
        }

        Log::channel('e_commerce')->info('e-commerce webhook payment type unavailable details started', [
            'start time of the webhook call for the payment type unavailable' => Carbon::now()->format('Y-m-d H:i:s'),
            'payment type id: ' . $paymentType->getKey(),
        ]);

        foreach ($saleChannels as $saleChannel) {
            if ($saleChannel->type_id === SaleChannelTypes::WEBSPERT_ECOMMERCE->value) {
                continue;
            }

            $saleChannelWebhookUrl = $saleChannel->saleChannelWebhookUrls
                ->firstWhere('webhook_url_type_id', WebhookUrls::PAYMENT_TYPE_UNAVAILABLE->value);

            $url = $saleChannelWebhookUrl->url;

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $saleChannel->secret,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(config('services.http_time_out'))->post($url, [
                'existing_id' => $paymentType->getKey(),
            ]);

            if ($response->successful()) {
                Log::channel('e_commerce')->info('Response: success on payment type in E-Commerce', [
                    'status_code' => $response->status(),
                    'response_body' => 'payment type is unavailable in E-Commerce',
                    'request_data' => [
                        'payment_type_id' => $paymentType->getKey(),
                        'saleChannel_id' => $saleChannel->getKey(),
                    ],
                ]);
            } else {
                Log::channel('e_commerce')->info('Response: Error on payment type in E-Commerce', [
                    'status_code' => $response->status(),
                    'response_body' => $response->body() ?: 'No response body provided',
                    'request_data' => [
                        'payment_type_id' => $paymentType->getKey(),
                        'saleChannel_id' => $saleChannel->getKey(),
                    ],
                ]);
            }
        }

        Log::channel('e_commerce')->info('e-commerce webhook payment type unavailable details ended', [
            'end time of the webhook call for the payment type unavailable' => Carbon::now()->format('Y-m-d H:i:s'),
            'payment type id: ' . $paymentType->getKey(),
        ]);
    }

    private function preparedRecords(PaymentType $paymentType, int $saleChannelId): array
    {
        $shippingZoneChannelReferenceQueries = resolve(ShippingZoneChannelReferenceQueries::class);

        $externalShippingZoneIds = $shippingZoneChannelReferenceQueries->getByShippingZoneIds(
            $paymentType->shippingZones()->pluck('shipping_zone_id')->toArray(),
            $saleChannelId
        );

        return [
            'external_id' => $paymentType->getKey(),
            'name' => $paymentType->name,
            'image_name' => $paymentType->image_name,
            'site_key' => $paymentType->site_key,
            'secret_key' => $paymentType->secret_key,
            'url' => $paymentType->url,
            'status' => $paymentType->status,
            'restrict_by_zone' => $paymentType->restrict_by_zone,
            'restriction_type' => $paymentType->restriction_type,
            'shipping_zone_ids' => $externalShippingZoneIds,
        ];
    }
}
