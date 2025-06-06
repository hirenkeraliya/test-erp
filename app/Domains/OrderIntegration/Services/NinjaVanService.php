<?php

declare(strict_types=1);

namespace App\Domains\OrderIntegration\Services;

use App\Domains\Common\Enums\CourierWebhookUrls;
use App\Domains\CourierAccessToken\CourierAccessTokenQueries;
use App\Domains\Order\OrderQueries;
use App\Domains\OrderIntegration\Enums\IntegrationStatuses;
use App\Domains\OrderIntegration\OrderIntegrationQueries;
use App\Models\Courier;
use App\Models\Location;
use App\Models\Member;
use App\Models\OrderAddress;
use App\Models\OrderIntegration;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class NinjaVanService
{
    public function requestAccessToken(Courier $courier): string
    {
        /** @var Collection $courierWebhookUrls */
        $courierWebhookUrls = $courier->courierWebhookUrls;

        $url = $courierWebhookUrls->where(
            'webhook_url_type_id',
            CourierWebhookUrls::ACCESS_TOKEN->value
        )->first()->url;

        try {
            $payload = [
                'client_id' => $courier->client_id,
                'client_secret' => $courier->client_secret,
                'grant_type' => 'client_credentials',
            ];

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(config('services.http_time_out'))->post($url, $payload);

            if ($response->successful()) {
                $courierAccessTokenQueries = resolve(CourierAccessTokenQueries::class);

                $response = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

                return $courierAccessTokenQueries->addNew($courier->id, $response['access_token']);
            }

            Log::channel('ninja_van_service')->error('Failed to retrieve access token', [
                'Response status' => $response->status(),
                'Response body' => $response->body(),
            ]);
        } catch (Throwable $throwable) {
            Log::channel('ninja_van_service')->error('NinjaVan access token request failed', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'Stack trace' => $throwable->getTraceAsString(),
            ]);
        }

        return '';
    }

    public function createOrder(int $orderId, OrderIntegration $orderIntegration): void
    {
        /** @var Courier $courier */
        $courier = $orderIntegration->courier;

        /** @var Collection $courierWebhookUrls */
        $courierWebhookUrls = $courier->courierWebhookUrls;

        $url = $courierWebhookUrls->where(
            'webhook_url_type_id',
            CourierWebhookUrls::CREATE_ORDER->value
        )->first()->url;

        $orderQueries = resolve(OrderQueries::class);
        $orderIntegrationQueries = resolve(OrderIntegrationQueries::class);
        $courierAccessTokenQueries = resolve(CourierAccessTokenQueries::class);

        $order = $orderQueries->getOrderForOrderIntegration($orderId);

        $accessToken = $courierAccessTokenQueries->getByCourierId($orderIntegration->courier_id)?->access_token
        ?? $this->requestAccessToken($courier);

        $tomorrow = Carbon::today()->addDay()->format('Y-m-d');

        $orderItems = [];
        foreach ($order->orderItems as $orderItem) {
            /** @var Product $product */
            $product = $orderItem->product;

            $orderItems[] = [
                'item_description' => $product->name,
                'quantity' => (float) $orderItem->quantity,
                'is_dangerous_good' => false,
            ];
        }

        $randomString = Str::random(random_int(1, 9));

        /** @var Location $location */
        $location = $order->location;

        /** @var Member $member */
        $member = $order->member;

        /** @var OrderAddress $shippingAddress */
        $shippingAddress = $order->shippingAddress;

        $payload = [
            'service_type' => 'Parcel',
            'service_level' => 'Standard',
            'requested_tracking_number' => $randomString,
            'reference' => [
                'merchant_order_number' => $order->receipt_number,
            ],
            'from' => [
                'name' => $location->name,
                'phone_number' => $location->phone,
                'email' => $location->email,
                'address' => [
                    'address1' => $location->address_line_1,
                    'address2' => $location->address_line_2,
                    'area' => null,
                    'city' => $location->city ? $location->city->name : '',
                    'state' => $location->state ? $location->state->name : '',
                    'address_type' => 'office',
                    'country' => 'MY',
                    'postcode' => '51200',
                ],
            ],
            'to' => [
                'name' => $member->first_name,
                'phone_number' => $member->mobile_number,
                'email' => $member->email,
                'address' => [
                    'address1' => $shippingAddress->address_line_1,
                    'address2' => $shippingAddress->address_line_2,
                    'area' => null,
                    'city' => $shippingAddress->city ? $shippingAddress->city->name : null,
                    'state' => $shippingAddress->state ? $shippingAddress->state->name : null,
                    'address_type' => 'home',
                    'country' => 'MY',
                    'postcode' => '51200',
                ],
            ],
            'parcel_job' => [
                'is_pickup_required' => true,
                'pickup_service_type' => 'Scheduled',
                'pickup_service_level' => 'Standard',
                'pickup_address_id' => '98989012',
                'pickup_date' => $tomorrow,
                'pickup_timeslot' => [
                    'start_time' => '09:00',
                    'end_time' => '12:00',
                    'timezone' => 'Asia/Kuala_Lumpur',
                ],
                'pickup_instructions' => 'Pickup with care!',
                'delivery_instructions' => '',
                'allow_weekend_delivery' => true,
                'delivery_start_date' => $tomorrow,
                'delivery_timeslot' => [
                    'start_time' => '09:00',
                    'end_time' => '12:00',
                    'timezone' => 'Asia/Kuala_Lumpur',
                ],
                'dimensions' => [
                    'weight' => 0,
                ],
                'items' => $orderItems,
            ],
        ];

        try {
            $response = Http::withToken($accessToken)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])->timeout(config('services.http_time_out'))->post($url, $payload);

            if ($response->status() === 401) {
                $accessToken = $this->requestAccessToken($courier);
                $this->createOrder($orderId, $orderIntegration);
            }

            if ($response->successful()) {
                $reposeData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

                $orderIntegration = $orderIntegrationQueries->updateStatusAndTrackingNumber(
                    $orderIntegration,
                    IntegrationStatuses::GENERATE_WAY_BILL->value,
                    $reposeData
                );

                return;
            }

            Log::channel('ninja_van_service')->error('Failed to create order', [
                'Response status' => $response->status(),
                'Response body' => $response->body(),
            ]);
        } catch (Throwable $throwable) {
            Log::channel('ninja_van_service')->error('ninja_van create order failed', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }
    }
}
