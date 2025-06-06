<?php

declare(strict_types=1);

namespace App\Domains\Membership\Services;

use App\Domains\MembershipChannelReference\MembershipChannelReferenceQueries;
use App\Domains\SaleChannel\Enums\SaleChannelTypes;
use App\Models\Membership;
use App\Models\MembershipChannelReference;
use App\Models\SaleChannel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MembershipService
{
    public function addUpdateDetails(Membership $membership, SaleChannel $saleChannel): void
    {
        Log::channel('e_commerce')->info('Start creating or updating the membership in eCommerce.', [
            'Start time for membership creation or updation' => Carbon::now()->format('Y-m-d H:i:s'),
            'membership id: ' . $membership->getKey(),
        ]);

        $membershipChannelReferenceQueries = resolve(MembershipChannelReferenceQueries::class);

        foreach ($saleChannel->saleChannelWebhookUrls as $saleChannelWebhookUrl) {
            $url = $saleChannelWebhookUrl->url;

            $membershipChannelReference = $membershipChannelReferenceQueries->getByMembershipIdAndSaleChannelId(
                $membership->id,
                $saleChannel->id
            );

            $response = null;

            if (SaleChannelTypes::WEBSPERT_ECOMMERCE === $saleChannel->type_id) {
                $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])->timeout(config('services.http_time_out'))->post($url, [
                    'membership' => $this->preparedRecords($membership, $membershipChannelReference),
                ]);
            }

            if (SaleChannelTypes::ECOMMERCE === $saleChannel->type_id) {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $saleChannel->secret,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])->timeout(config('services.http_time_out'))->post($url, [
                    'membership' => $this->preparedRecords($membership, $membershipChannelReference),
                ]);
            }

            if ($response->successful()) {
                $responseData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

                Log::channel('e_commerce')->info('Response: Membership in E-Commerce', [
                    'response' => $responseData,
                ]);
                if (array_key_exists('membership_id', $responseData) && ! $membershipChannelReference) {
                    $membershipChannelReferenceQueries = resolve(MembershipChannelReferenceQueries::class);
                    $membershipChannelReferenceQueries->addNew([
                        'sale_channel_id' => $saleChannel->id,
                        'membership_id' => $membership->id,
                        'external_membership_id' => $responseData['membership_id'],
                    ]);
                }
            } else {
                Log::channel('e_commerce')->info('Response: Error on Membership in E-Commerce', [
                    'status_code' => $response->status(),
                    'response_body' => $response->body() ?: 'No response body provided',
                    'request_data' => [
                        'membership_id' => $membership->getKey(),
                        'saleChannel_id' => $saleChannel->getKey(),
                    ],
                ]);
            }
        }

        Log::channel('e_commerce')->info('End creating or updating the membership in eCommerce.', [
            'End time for membership creation or updation' => Carbon::now()->format('Y-m-d H:i:s'),
            'membership id: ' . $membership->getKey(),
        ]);
    }

    private function preparedRecords(
        Membership $membership,
        ?MembershipChannelReference $membershipChannelReference
    ): array {
        return [
            'existing_id' => $membershipChannelReference?->external_membership_id,
            'id' => $membership->id,
            'name' => $membership->name,
            'minimum_lifetime_spend_amount' => $membership->lifetime_value,
            'lifetime_value' => $membership->lifetime_value,
            'loyalty_points_per_ringgit' => $membership->loyalty_points_per_currency_unit,
            'loyalty_points_per_currency_unit' => $membership->loyalty_points_per_currency_unit,
            'min_loyalty_points_for_redemption' => $membership->min_loyalty_points_for_redemption,
            'max_loyalty_points_for_redemption' => $membership->max_loyalty_points_for_redemption,
        ];
    }
}
