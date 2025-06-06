<?php

declare(strict_types=1);

namespace App\Domains\MemberAddress\Services;

use App\Domains\Common\Enums\WebhookUrls;
use App\Domains\CountryChannelReference\CountryChannelReferenceQueries;
use App\Domains\Member\MemberQueries;
use App\Domains\MemberAddress\MemberAddressQueries;
use App\Domains\MemberAddressChannelReference\MemberAddressChannelReferenceQueries;
use App\Domains\MemberChannelReference\MemberChannelReferenceQueries;
use App\Domains\SaleChannel\Enums\SaleChannelTypes;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Domains\StateChannelReference\StateChannelReferenceQueries;
use App\Models\MemberAddress;
use App\Models\MemberAddressChannelReference;
use App\Models\MemberChannelReference;
use App\Models\SaleChannel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class MemberAddressSaleChannelService
{
    public function createMemberAddress(MemberAddress $memberAddress): void
    {
        Log::channel('e_commerce')->info('Start creating the member address options in eCommerce.', [
            'Start time for member address creation' => Carbon::now()->format('Y-m-d H:i:s'),
            'member address id: ' . $memberAddress->getKey(),
        ]);

        $memberAddressQueries = resolve(MemberAddressQueries::class);
        $memberQueries = resolve(MemberQueries::class);

        $memberAddress = $memberAddressQueries->getByOnlyId($memberAddress->id);
        $companyId = $memberQueries->getCompanyId($memberAddress->member_id);

        $saleChannelQueries = resolve(SaleChannelQueries::class);

        $webhookUrls = [WebhookUrls::MEMBER_ADDRESS_CREATE->value];

        $saleChannels = $saleChannelQueries->getSaleChannelsByCompany($webhookUrls, $companyId);

        if ($saleChannels->isEmpty()) {
            Log::channel('e_commerce')->info('creating member address : sale channels is empty.', [
                'Start time for member address creation' => Carbon::now()->format('Y-m-d H:i:s'),
                'member address id: ' . $memberAddress->getKey(),
            ]);

            return;
        }

        try {
            foreach ($saleChannels as $saleChannel) {
                $this->addMemberAddress($saleChannel, $memberAddress);
            }
        } catch (Throwable $throwable) {
            Log::channel('e_commerce')->error('e-commerce webhook member address create failed', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }

        Log::channel('e_commerce')->info('Start creating the member address options in eCommerce.', [
            'Start time for member address creation' => Carbon::now()->format('Y-m-d H:i:s'),
            'member address id: ' . $memberAddress->getKey(),
        ]);
    }

    public function addMemberAddress(SaleChannel $saleChannel, MemberAddress $memberAddress): void
    {
        Log::channel('e_commerce')->info('Start adding member address in eCommerce', [
            'Start time for member address addition' => Carbon::now()->format('Y-m-d H:i:s'),
            'member address id: ' . $memberAddress->getKey(),
        ]);

        $memberChannelReferenceQueries = resolve(MemberChannelReferenceQueries::class);
        $memberChannelReference = $memberChannelReferenceQueries->getByMemberIdAndSaleChannelId(
            $memberAddress->member_id,
            $saleChannel->id
        );

        if (! $memberChannelReference instanceof MemberChannelReference) {
            Log::channel('e_commerce')->info('adding member address : return when member channel reference not found', [
                'Start time for member address addition' => Carbon::now()->format('Y-m-d H:i:s'),
                'member address id: ' . $memberAddress->getKey(),
            ]);

            return;
        }

        $memberAddressChannelReferenceQueries = resolve(MemberAddressChannelReferenceQueries::class);
        $saleChannelWebhookUrls = $saleChannel->saleChannelWebhookUrls->where(
            'webhook_url_type_id',
            WebhookUrls::MEMBER_ADDRESS_CREATE->value
        );

        foreach ($saleChannelWebhookUrls as $saleChannelWebhookUrl) {
            $memberAddressChannelReference = $memberAddressChannelReferenceQueries->getByMemberAddressIdAndSaleChannelId(
                $memberAddress->id,
                $saleChannel->id
            );

            if ($memberAddressChannelReference instanceof MemberAddressChannelReference) {
                Log::channel('e_commerce')->info('adding member address : call update member address detail', [
                    'Start time for member address addition' => Carbon::now()->format('Y-m-d H:i:s'),
                    'member address id: ' . $memberAddress->getKey(),
                ]);

                $this->updateMemberAddressDetails($saleChannel, $memberAddress);
                continue;
            }

            $url = $saleChannelWebhookUrl->url;

            $requestData = [];
            $response = null;

            if (SaleChannelTypes::WEBSPERT_ECOMMERCE === $saleChannel->type_id) {
                $requestData = [
                    'secretkey' => $saleChannel->secret,
                    'id' => $memberAddress->id,
                    'external_member_id' => $memberChannelReference->external_member_id,
                    'external_member_address_id' => null,
                    'first_name' => $memberAddress->first_name,
                    'last_name' => $memberAddress->last_name,
                    'contact_mobile_number' => $memberAddress->contact_mobile_number,
                    'contact_email' => $memberAddress->contact_email,
                    'address_line_1' => $memberAddress->address_line_1,
                    'address_line_2' => $memberAddress->address_line_2,
                    'city' => $memberAddress->city_name,
                    'area_code' => $memberAddress->area_code,
                    'is_primary' => $memberAddress->is_primary,
                ];

                $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])->timeout(config('services.http_time_out'))->post($url, $requestData);

                Log::channel('e_commerce')->info('creating or updating the member address : inside webspert .', [
                    'Start time for member address creation or updation' => Carbon::now()->format('Y-m-d H:i:s'),
                    'member address id: ' . $memberAddress->getKey(),
                ]);
            }

            if (SaleChannelTypes::ECOMMERCE === $saleChannel->type_id) {
                $requestData = [
                    'customer_address' => $this->preparedRecords(
                        $memberAddress,
                        $memberAddressChannelReference,
                        $memberChannelReference,
                        $saleChannel->id,
                    ),
                ];

                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $saleChannel->secret,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])->timeout(config('services.http_time_out'))->post($url, $requestData);

                Log::channel('e_commerce')->info('creating or updating the member address : inside eCommerce .', [
                    'Start time for member address creation or updation' => Carbon::now()->format('Y-m-d H:i:s'),
                    'member address id: ' . $memberAddress->getKey(),
                ]);
            }

            if ($response?->successful()) {
                $responseData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

                Log::channel('e_commerce')->info('Response: Add member Address in E-Commerce', [
                    'response' => $responseData,
                ]);

                if (array_key_exists('member_address_id', $responseData)) {
                    $memberAddressChannelReferenceQueries = resolve(MemberAddressChannelReferenceQueries::class);
                    $memberAddressChannelReferenceQueries->addNew([
                        'sale_channel_id' => $saleChannel->getKey(),
                        'member_address_id' => $memberAddress->id,
                        'external_member_address_id' => $responseData['member_address_id'],
                    ]);
                }
            } else {
                Log::channel('e_commerce')->info('Response: Error on Add member Address in E-Commerce', [
                    'status_code' => $response?->status(),
                    'response_body' => $response?->body() ?: 'No response body provided',
                    'request_data' => [
                        'memberAddress_id' => $memberAddress->getKey(),
                        'saleChannel_id' => $saleChannel->getKey(),
                    ],
                ]);
            }
        }

        Log::channel('e_commerce')->info('End member address addition in eCommerce', [
            'Completion time for member address addition' => Carbon::now()->format('Y-m-d H:i:s'),
            'member address id: ' . $memberAddress->getKey(),
        ]);
    }

    public function updateMemberAddress(MemberAddress $memberAddress): void
    {
        Log::channel('e_commerce')->info('Start updating member address in eCommerce', [
            'Start time for member address update' => Carbon::now()->format('Y-m-d H:i:s'),
            'member address id: ' . $memberAddress->getKey(),
        ]);

        $memberAddressQueries = resolve(MemberAddressQueries::class);
        $memberAddress = $memberAddressQueries->getByOnlyId($memberAddress->id);

        $memberQueries = resolve(MemberQueries::class);
        $companyId = $memberQueries->getCompanyId($memberAddress->member_id);

        $saleChannelQueries = resolve(SaleChannelQueries::class);

        $webhookUrls = [WebhookUrls::MEMBER_ADDRESS_UPDATE->value];

        $saleChannels = $saleChannelQueries->getSaleChannelsByCompany($webhookUrls, $companyId);

        if ($saleChannels->isEmpty()) {
            Log::channel('e_commerce')->info('updating member : return when sales channel is empty', [
                'Start time for member address update' => Carbon::now()->format('Y-m-d H:i:s'),
                'member address id: ' . $memberAddress->getKey(),
            ]);

            return;
        }

        try {
            foreach ($saleChannels as $saleChannel) {
                $this->updateMemberAddressDetails($saleChannel, $memberAddress);
            }
        } catch (Throwable $throwable) {
            Log::channel('e_commerce')->error('e-commerce webhook member address update details failed', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }

        Log::channel('e_commerce')->info('End member address update in eCommerce', [
            'Completion time for member address update' => Carbon::now()->format('Y-m-d H:i:s'),
            'member address id: ' . $memberAddress->getKey(),
        ]);
    }

    public function deleteMemberAddress(MemberAddress $memberAddress): void
    {
        Log::channel('e_commerce')->info('Start Delete member address in eCommerce', [
            'Start time for member address delete' => Carbon::now()->format('Y-m-d H:i:s'),
            'member address id: ' . $memberAddress->getKey(),
        ]);

        $memberAddressQueries = resolve(MemberAddressQueries::class);
        $memberAddress = $memberAddressQueries->refresh($memberAddress);

        $memberQueries = resolve(MemberQueries::class);
        $companyId = $memberQueries->getCompanyId($memberAddress->member_id);

        $saleChannelQueries = resolve(SaleChannelQueries::class);

        $webhookUrls = [WebhookUrls::MEMBER_ADDRESS_DELETE->value];

        $saleChannels = $saleChannelQueries->getSaleChannelsByCompany($webhookUrls, $companyId);

        if ($saleChannels->isEmpty()) {
            Log::channel('e_commerce')->info('Delete member address : sale channels is empty', [
                'Start time for member address delete' => Carbon::now()->format('Y-m-d H:i:s'),
                'member address id: ' . $memberAddress->getKey(),
            ]);

            return;
        }

        try {
            foreach ($saleChannels as $saleChannel) {
                $this->deleteMemberAddressDetails($saleChannel, $memberAddress);
            }
        } catch (Throwable $throwable) {
            Log::channel('e_commerce')->error('e-commerce webhook member address update details failed', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }

        Log::channel('e_commerce')->info('End Delete member address in eCommerce', [
            'End time for member address delete' => Carbon::now()->format('Y-m-d H:i:s'),
            'member address id: ' . $memberAddress->getKey(),
        ]);
    }

    private function updateMemberAddressDetails(SaleChannel $saleChannel, MemberAddress $memberAddress): void
    {
        Log::channel('e_commerce')->info('Start updating member address details in eCommerce.', [
            'Start time for updating member address details' => Carbon::now()->format('Y-m-d H:i:s'),
            'member address id: ' . $memberAddress->getKey(),
        ]);
        $memberAddressChannelReferenceQueries = resolve(MemberAddressChannelReferenceQueries::class);

        $memberAddressChannelReference = $memberAddressChannelReferenceQueries->getByMemberAddressIdAndSaleChannelId(
            $memberAddress->id,
            $saleChannel->id
        );

        if (! $memberAddressChannelReference instanceof MemberAddressChannelReference) {
            $saleChannelQueries = resolve(SaleChannelQueries::class);
            $saleChannel = $saleChannelQueries->loadWebhookUrls($saleChannel);

            Log::channel('e_commerce')->info('updating member address : call add member address.', [
                'Start time for updating member address details' => Carbon::now()->format('Y-m-d H:i:s'),
                'member address id: ' . $memberAddress->getKey(),
            ]);

            $this->addMemberAddress($saleChannel, $memberAddress);

            return;
        }

        $memberChannelReferenceQueries = resolve(MemberChannelReferenceQueries::class);
        $memberChannelReference = $memberChannelReferenceQueries->getByMemberIdAndSaleChannelId(
            $memberAddress->member_id,
            $saleChannel->id
        );

        if (! $memberChannelReference instanceof MemberChannelReference) {
            Log::channel('e_commerce')->info(
                'updating member address : return when member channel reference not found.',
                [
                    'Start time for updating member address details' => Carbon::now()->format('Y-m-d H:i:s'),
                    'member address id: ' . $memberAddress->getKey(),
                ]
            );

            return;
        }

        $saleChannelWebhookUrls = $saleChannel->saleChannelWebhookUrls->where(
            'webhook_url_type_id',
            WebhookUrls::MEMBER_ADDRESS_UPDATE->value
        );

        foreach ($saleChannelWebhookUrls as $saleChannelWebhookUrl) {
            $url = $saleChannelWebhookUrl->url;

            $memberAddressChannelReference = $memberAddressChannelReferenceQueries->getByMemberAddressIdAndSaleChannelId(
                $memberAddress->id,
                $saleChannel->id
            );

            $requestData = [];

            if (SaleChannelTypes::WEBSPERT_ECOMMERCE === $saleChannel->type_id) {
                $requestData = [
                    'secretkey' => $saleChannel->secret,
                    'id' => $memberAddress->id,
                    'external_member_id' => $memberChannelReference->external_member_id,
                    'external_member_address_id' => null,
                    'first_name' => $memberAddress->first_name,
                    'last_name' => $memberAddress->last_name,
                    'contact_mobile_number' => $memberAddress->contact_mobile_number,
                    'contact_email' => $memberAddress->contact_email,
                    'address_line_1' => $memberAddress->address_line_1,
                    'address_line_2' => $memberAddress->address_line_2,
                    'city' => $memberAddress->city_name,
                    'area_code' => $memberAddress->area_code,
                    'is_primary' => $memberAddress->is_primary,
                ];

                Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])->timeout(config('services.http_time_out'))->post($url, $requestData);

                Log::channel('e_commerce')->info('creating or updating the member address : inside webspert .', [
                    'Start time for member address creation or updation' => Carbon::now()->format('Y-m-d H:i:s'),
                    'member address id: ' . $memberAddress->getKey(),
                ]);
            }

            if (SaleChannelTypes::ECOMMERCE === $saleChannel->type_id) {
                $requestData = [
                    'customer_address' => $this->preparedRecords(
                        $memberAddress,
                        $memberAddressChannelReference,
                        $memberChannelReference,
                        $saleChannel->id,
                    ),
                ];

                Http::withHeaders([
                    'Authorization' => 'Bearer ' . $saleChannel->secret,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])->timeout(config('services.http_time_out'))->post($url, $requestData);

                Log::channel('e_commerce')->info('creating or updating the member address : inside eCommerce .', [
                    'Start time for member address creation or updation' => Carbon::now()->format('Y-m-d H:i:s'),
                    'member address id: ' . $memberAddress->getKey(),
                ]);
            }
        }

        Log::channel('e_commerce')->info('End updating member address details in eCommerce', [
            'Completion time for updating member address details' => Carbon::now()->format('Y-m-d H:i:s'),
            'member address id: ' . $memberAddress->getKey(),
        ]);
    }

    private function deleteMemberAddressDetails(SaleChannel $saleChannel, MemberAddress $memberAddress): void
    {
        Log::channel('e_commerce')->info('Start delete member address details in eCommerce.', [
            'Start time for deleting member address details' => Carbon::now()->format('Y-m-d H:i:s'),
            'member address id: ' . $memberAddress->getKey(),
        ]);

        $memberAddressChannelReferenceQueries = resolve(MemberAddressChannelReferenceQueries::class);

        $memberAddressChannelReference = $memberAddressChannelReferenceQueries->getByMemberAddressIdAndSaleChannelId(
            $memberAddress->id,
            $saleChannel->id
        );

        if (! $memberAddressChannelReference instanceof MemberAddressChannelReference) {
            Log::channel('e_commerce')->info('delete member address : member address channel reference not found.', [
                'Start time for deleting member address details' => Carbon::now()->format('Y-m-d H:i:s'),
                'member address id: ' . $memberAddress->getKey(),
            ]);

            return;
        }

        $memberChannelReferenceQueries = resolve(MemberChannelReferenceQueries::class);
        $memberChannelReference = $memberChannelReferenceQueries->getByMemberIdAndSaleChannelId(
            $memberAddress->member_id,
            $saleChannel->id
        );

        if (! $memberChannelReference instanceof MemberChannelReference) {
            Log::channel('e_commerce')->info('delete member address  : member channel reference not found.', [
                'Start time for deleting member address details' => Carbon::now()->format('Y-m-d H:i:s'),
                'member address id: ' . $memberAddress->getKey(),
            ]);

            return;
        }

        $saleChannelWebhookUrls = $saleChannel->saleChannelWebhookUrls->where(
            'webhook_url_type_id',
            WebhookUrls::MEMBER_ADDRESS_DELETE->value
        );

        foreach ($saleChannelWebhookUrls as $saleChannelWebhookUrl) {
            $url = $saleChannelWebhookUrl->url;
            $response = null;

            if (SaleChannelTypes::WEBSPERT_ECOMMERCE === $saleChannel->type_id) {
                $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])->timeout(config('services.http_time_out'))->post($url, [
                    'secretkey' => $saleChannel->secret,
                    'id' => $memberAddress->id,
                    'external_member_address_id' => $memberAddressChannelReference->external_member_address_id,
                    'external_member_id' => $memberChannelReference->external_member_id,
                ]);
            }

            if (SaleChannelTypes::ECOMMERCE === $saleChannel->type_id) {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $saleChannel->secret,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])->timeout(config('services.http_time_out'))->post($url, [
                    'id' => $memberAddress->id,
                    'external_member_address_id' => $memberAddressChannelReference->external_member_address_id,
                    'external_member_id' => $memberChannelReference->external_member_id,
                ]);
            }

            if ($response->successful()) {
                $responseData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

                Log::channel('e_commerce')->info('Response: Delete a member address in E-Commerce', [
                    'response' => $responseData,
                ]);

                if (array_key_exists('member_address_id', $responseData)) {
                    $memberAddressChannelReferenceQueries = resolve(MemberAddressChannelReferenceQueries::class);
                    $memberAddressChannelReferenceQueries->deleteById($responseData['member_address_id']);
                }
            } else {
                Log::channel('e_commerce')->info('Response: Error on Delete a member address in E-Commerce', [
                    'status_code' => $response->status(),
                    'response_body' => $response->body() ?: 'No response body provided',
                    'request_data' => [
                        'memberAddress_id' => $memberAddress->getKey(),
                        'saleChannel_id' => $saleChannel->getKey(),
                    ],
                ]);
            }
        }

        Log::channel('e_commerce')->info('End delete member address details in eCommerce.', [
            'End time for deleting member address details' => Carbon::now()->format('Y-m-d H:i:s'),
            'member address id: ' . $memberAddress->getKey(),
        ]);
    }

    private function preparedRecords(
        MemberAddress $memberAddress,
        ?MemberAddressChannelReference $MemberAddressChannelReference,
        ?MemberChannelReference $memberChannelReference,
        int $saleChannelId
    ): array {
        $countryChannelReferenceQueries = resolve(CountryChannelReferenceQueries::class);
        $stateChannelReferenceQueries = resolve(StateChannelReferenceQueries::class);

        $countryChannelReference = null;
        if (null !== $memberAddress->country_id) {
            $countryChannelReference = $countryChannelReferenceQueries->getByCountryIdAndSaleChannelId(
                $memberAddress->country_id,
                $saleChannelId
            );
        }

        $stateChannelReference = null;
        if (null !== $memberAddress->state_id) {
            $stateChannelReference = $stateChannelReferenceQueries->getByStateIdAndSaleChannelId(
                $memberAddress->state_id,
                $saleChannelId
            );
        }

        return [
            'existing_id' => $MemberAddressChannelReference?->external_member_address_id,
            'customer_id' => $memberChannelReference?->external_member_id,
            'external_member_address_id' => $MemberAddressChannelReference?->external_member_address_id,
            'name' => $memberAddress->name,
            'first_name' => $memberAddress->first_name,
            'last_name' => $memberAddress->last_name,
            'contact_mobile_number' => $memberAddress->contact_mobile_number,
            'contact_email' => $memberAddress->contact_email,
            'address_line_1' => $memberAddress->address_line_1,
            'address_line_2' => $memberAddress->address_line_2,
            'city' => $memberAddress->city_name,
            'country_id' => $countryChannelReference ? $countryChannelReference->external_country_id : $memberAddress->country_id,
            'state_id' => $stateChannelReference ? $stateChannelReference->external_state_id : $memberAddress->state_id,
            'area_code' => $memberAddress->area_code,
            'is_primary' => $memberAddress->is_primary,
        ];
    }
}
