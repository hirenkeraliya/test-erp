<?php

declare(strict_types=1);

namespace App\Domains\MemberGroup\Services;

use App\Domains\Common\Enums\WebhookUrls;
use App\Domains\MemberGroup\MemberGroupQueries;
use App\Domains\MemberGroupChannelReference\MemberGroupChannelReferenceQueries;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Models\MemberGroup;
use App\Models\MemberGroupChannelReference;
use App\Models\SaleChannel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class MemberGroupSaleChannelService
{
    public function updateMemberGroup(MemberGroup $memberGroup): void
    {
        Log::channel('e_commerce')->info('Start update the memberGroup options in eCommerce.', [
            'Start time for memberGroup creation' => Carbon::now()->format('Y-m-d H:i:s'),
            'memberGroup id: ' . $memberGroup->getKey(),
        ]);

        $memberGroupQueries = resolve(MemberGroupQueries::class);
        $memberGroup = $memberGroupQueries->getByOnlyId($memberGroup->id);

        $saleChannelQueries = resolve(SaleChannelQueries::class);

        $webhookUrls = [WebhookUrls::MEMBER_GROUP_UPDATE->value];

        $saleChannels = $saleChannelQueries->getSaleChannelsByCompany($webhookUrls, $memberGroup->company_id);

        if ($saleChannels->isEmpty()) {
            Log::channel('e_commerce')->info('update memberGroup :  sale channels is empty.', [
                'Start time for memberGroup creation' => Carbon::now()->format('Y-m-d H:i:s'),
                'memberGroup id: ' . $memberGroup->getKey(),
            ]);

            return;
        }

        try {
            foreach ($saleChannels as $saleChannel) {
                $this->updateMemberGroupDetails($saleChannel, $memberGroup);
            }
        } catch (Throwable $throwable) {
            Log::channel('e_commerce')->error('e-commerce webhook member group update details failed', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }

        Log::channel('e_commerce')->info('Complete update the memberGroup options in eCommerce.', [
            'Start time for memberGroup creation' => Carbon::now()->format('Y-m-d H:i:s'),
            'memberGroup id: ' . $memberGroup->getKey(),
        ]);
    }

    public function updateMemberGroupDetails(SaleChannel $saleChannel, MemberGroup $memberGroup): void
    {
        Log::channel('e_commerce')->info('Start updating memberGroup details in eCommerce.', [
            'Start time for updating memberGroup details' => Carbon::now()->format('Y-m-d H:i:s'),
            'memberGroup id: ' . $memberGroup->getKey(),
        ]);

        $memberGroupChannelReferenceQueries = resolve(MemberGroupChannelReferenceQueries::class);

        $memberGroupChannelReference = $memberGroupChannelReferenceQueries->getByMemberGroupIdAndSaleChannelId(
            $memberGroup->id,
            $saleChannel->id
        );

        if (! $memberGroupChannelReference instanceof MemberGroupChannelReference) {
            $saleChannelQueries = resolve(SaleChannelQueries::class);
            $saleChannel = $saleChannelQueries->loadWebhookUrls($saleChannel);

            Log::channel('e_commerce')->info('updating memberGroup : add member group call.', [
                'Start time for updating memberGroup details' => Carbon::now()->format('Y-m-d H:i:s'),
                'memberGroup id: ' . $memberGroup->getKey(),
            ]);

            $this->addMemberGroup($saleChannel, $memberGroup);

            return;
        }

        $saleChannelWebhookUrls = $saleChannel->saleChannelWebhookUrls->where(
            'webhook_url_type_id',
            WebhookUrls::MEMBER_GROUP_UPDATE->value
        );

        foreach ($saleChannelWebhookUrls as $saleChannelWebhookUrl) {
            $url = $saleChannelWebhookUrl->url;

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(config('services.http_time_out'))->post($url, [
                'secretkey' => $saleChannel->secret,
                'id' => $memberGroup->id,
                'external_member_group_id' => $memberGroupChannelReference->external_member_group_id,
                'name' => $memberGroup->name,
                'code' => $memberGroup->code,
            ]);

            if ($response->successful()) {
                $responseData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

                Log::channel('e_commerce')->info('Response: update a new member group in E-Commerce', [
                    'response' => $responseData,
                ]);

                if (array_key_exists('external_member_group_id', $responseData)) {
                    $memberGroupChannelReferenceQueries = resolve(MemberGroupChannelReferenceQueries::class);
                    $memberGroupChannelReferenceQueries->updateOrCreate(
                        $saleChannel->id,
                        $memberGroup->id,
                        $responseData['external_member_group_id']
                    );
                }
            } else {
                Log::channel('e_commerce')->info('Response: Error on update a new member group in E-Commerce', [
                    'status_code' => $response->status(),
                    'response_body' => $response->body() ?: 'No response body provided',
                    'request_data' => [
                        'memberGroup_id' => $memberGroup->getKey(),
                        'saleChannel_id' => $saleChannel->getKey(),
                    ],
                ]);
            }
        }

        Log::channel('e_commerce')->info('End updating memberGroup details in eCommerce', [
            'Completion time for updating memberGroup details' => Carbon::now()->format('Y-m-d H:i:s'),
            'memberGroup id: ' . $memberGroup->getKey(),
        ]);
    }

    public function addMemberGroup(SaleChannel $saleChannel, MemberGroup $memberGroup): void
    {
        Log::channel('e_commerce')->info('Start adding memberGroups in eCommerce', [
            'Start time for memberGroup addition' => Carbon::now()->format('Y-m-d H:i:s'),
            'memberGroup id: ' . $memberGroup->getKey(),
        ]);

        $memberGroupChannelReferenceQueries = resolve(MemberGroupChannelReferenceQueries::class);
        $saleChannelWebhookUrls = $saleChannel->saleChannelWebhookUrls->where(
            'webhook_url_type_id',
            WebhookUrls::MEMBER_GROUP_UPDATE->value
        );

        foreach ($saleChannelWebhookUrls as $saleChannelWebhookUrl) {
            $memberGroupChannelReference = $memberGroupChannelReferenceQueries->getByMemberGroupIdAndSaleChannelId(
                $memberGroup->id,
                $saleChannel->id
            );

            if ($memberGroupChannelReference instanceof MemberGroupChannelReference) {
                Log::channel('e_commerce')->info('adding memberGroups : update member group details', [
                    'Start time for memberGroup addition' => Carbon::now()->format('Y-m-d H:i:s'),
                    'memberGroup id: ' . $memberGroup->getKey(),
                ]);

                $this->updateMemberGroupDetails($saleChannel, $memberGroup);
                continue;
            }

            $url = $saleChannelWebhookUrl->url;

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(config('services.http_time_out'))->post($url, [
                'secretkey' => $saleChannel->secret,
                'id' => $memberGroup->id,
                'external_member_group_id' => null,
                'name' => $memberGroup->name,
                'code' => $memberGroup->code,
            ]);

            if ($response->successful()) {
                $responseData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

                Log::channel('e_commerce')->info('Response: Add a new member group in E-Commerce', [
                    'response' => $responseData,
                ]);

                if (array_key_exists('external_member_group_id', $responseData)) {
                    $memberGroupChannelReferenceQueries = resolve(MemberGroupChannelReferenceQueries::class);
                    $memberGroupChannelReferenceQueries->updateOrCreate(
                        $saleChannel->id,
                        $memberGroup->id,
                        $responseData['external_member_group_id']
                    );
                }
            } else {
                Log::channel('e_commerce')->info('Response: Add a new member group in E-Commerce', [
                    'status_code' => $response->status(),
                    'response_body' => $response->body() ?: 'No response body provided',
                    'request_data' => [
                        'memberGroup_id' => $memberGroup->getKey(),
                        'saleChannel_id' => $saleChannel->getKey(),
                    ],
                ]);
            }
        }

        Log::channel('e_commerce')->info('End  memberGroup  addition in eCommerce', [
            'Completion time for memberGroup addition' => Carbon::now()->format('Y-m-d H:i:s'),
            'memberGroup id: ' . $memberGroup->getKey(),
        ]);
    }
}
