<?php

declare(strict_types=1);

namespace App\Domains\Member\Services;

use App\Domains\Common\Enums\WebhookUrls;
use App\Domains\Member\Enums\Genders;
use App\Domains\Member\Enums\Status;
use App\Domains\Member\MemberQueries;
use App\Domains\MemberChannelReference\MemberChannelReferenceQueries;
use App\Domains\MemberGroup\MemberGroupQueries;
use App\Domains\MemberGroup\Services\MemberGroupSaleChannelService;
use App\Domains\MemberGroupChannelReference\MemberGroupChannelReferenceQueries;
use App\Domains\MembershipChannelReference\MembershipChannelReferenceQueries;
use App\Domains\SaleChannel\Enums\SaleChannelTypes;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Models\Member;
use App\Models\MemberChannelReference;
use App\Models\SaleChannel;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class MemberSaleChannelService
{
    public function createMember(Member $member): void
    {
        Log::channel('e_commerce')->info('Start creating the member options in eCommerce.', [
            'Start time for member creation' => Carbon::now()->format('Y-m-d H:i:s'),
            'member id: ' . $member->getKey(),
        ]);

        $memberQueries = resolve(MemberQueries::class);
        $member = $memberQueries->getByOnlyId($member->id);

        $saleChannelQueries = resolve(SaleChannelQueries::class);

        $webhookUrls = [WebhookUrls::MEMBER_CREATE->value, WebhookUrls::MEMBER_GROUP_UPDATE->value];

        $saleChannels = $saleChannelQueries->getSaleChannelsByCompany($webhookUrls, $member->company_id);

        if ($saleChannels->isEmpty()) {
            Log::channel('e_commerce')->info('creating member : return when sale channels is empty.', [
                'Start time for member creation' => Carbon::now()->format('Y-m-d H:i:s'),
                'member id: ' . $member->getKey(),
            ]);

            return;
        }

        try {
            foreach ($saleChannels as $saleChannel) {
                $this->addMember($saleChannel, $member);
            }
        } catch (Throwable $throwable) {
            Log::channel('e_commerce')->error('e-commerce webhook member create failed', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }

        Log::channel('e_commerce')->info('Complete the member creation process in eCommerce.', [
            'End time for member creation' => Carbon::now()->format('Y-m-d H:i:s'),
            'member id: ' . $member->getKey(),
        ]);
    }

    public function addMember(SaleChannel $saleChannel, Member $member): void
    {
        Log::channel('e_commerce')->info('Start adding member in eCommerce', [
            'Start time for member addition' => Carbon::now()->format('Y-m-d H:i:s'),
            'member id: ' . $member->getKey(),
        ]);

        $memberChannelReferenceQueries = resolve(MemberChannelReferenceQueries::class);
        $memberGroupSaleChannelService = resolve(MemberGroupSaleChannelService::class);
        $memberGroupQueries = resolve(MemberGroupQueries::class);

        $saleChannelWebhookUrls = $saleChannel->saleChannelWebhookUrls->where(
            'webhook_url_type_id',
            WebhookUrls::MEMBER_CREATE->value
        );

        foreach ($saleChannelWebhookUrls as $saleChannelWebhookUrl) {
            $memberChannelReference = $memberChannelReferenceQueries->getByMemberIdAndSaleChannelId(
                $member->id,
                $saleChannel->id
            );

            $memberGroupChannelReferenceQueries = resolve(MemberGroupChannelReferenceQueries::class);

            if ($member->memberGroupMembers->first()) {
                $memberGroup = $memberGroupQueries->getByOnlyId($member->memberGroupMembers->first()->member_group_id);

                Log::channel('e_commerce')->info('adding member : add member group call', [
                    'Start time for member addition' => Carbon::now()->format('Y-m-d H:i:s'),
                    'member id: ' . $member->getKey(),
                ]);

                $memberGroupSaleChannelService->addMemberGroup($saleChannel, $memberGroup);

                $memberGroupChannelReferenceQueries->getByMemberGroupIdAndSaleChannelId(
                    $member->memberGroupMembers->first()->member_group_id,
                    $saleChannel->id
                );
            }

            if ($memberChannelReference instanceof MemberChannelReference) {
                Log::channel('e_commerce')->info('adding member : update member details call', [
                    'Start time for member addition' => Carbon::now()->format('Y-m-d H:i:s'),
                    'member id: ' . $member->getKey(),
                ]);

                $this->updateMemberDetails($saleChannel, $member);
                continue;
            }

            $url = $saleChannelWebhookUrl->url;

            $requestData = [
                'customer' => $this->preparedRecords($member, $memberChannelReference, $saleChannel),
            ];

            $response = null;

            if (SaleChannelTypes::WEBSPERT_ECOMMERCE === $saleChannel->type_id) {
                $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])->timeout(config('services.http_time_out'))->post($url, $requestData);
            }

            if (SaleChannelTypes::ECOMMERCE === $saleChannel->type_id) {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $saleChannel->secret,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])->timeout(config('services.http_time_out'))->post($url, $requestData);
            }

            if ($response->successful()) {
                $responseData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

                Log::channel('e_commerce')->info('Response: Add member in E-Commerce', [
                    'response' => $responseData,
                ]);

                if (array_key_exists('member_id', $responseData)) {
                    $memberChannelReferenceQueries = resolve(MemberChannelReferenceQueries::class);
                    $memberChannelReferenceQueries->addNew([
                        'sale_channel_id' => $saleChannel->getKey(),
                        'member_id' => $member->id,
                        'external_member_id' => $responseData['member_id'],
                    ]);
                }
            } else {
                Log::channel('e_commerce')->info('Response: Error on Add member in E-Commerce', [
                    'status_code' => $response->status(),
                    'response_body' => $response->body() ?: 'No response body provided',
                    'request_data' => [
                        'member_id' => $member->getKey(),
                        'saleChannel_id' => $saleChannel->getKey(),
                    ],
                ]);
            }
        }

        Log::channel('e_commerce')->info('End member addition in eCommerce', [
            'Completion time for member addition' => Carbon::now()->format('Y-m-d H:i:s'),
            'member id: ' . $member->getKey(),
        ]);
    }

    public function updateMember(Member $member): void
    {
        Log::channel('e_commerce')->info('Start updating members in eCommerce', [
            'Start time for member update' => Carbon::now()->format('Y-m-d H:i:s'),
            'member id: ' . $member->getKey(),
        ]);

        $saleChannelQueries = resolve(SaleChannelQueries::class);

        $webhookUrls = [WebhookUrls::MEMBER_UPDATE->value, WebhookUrls::MEMBER_GROUP_UPDATE->value];

        $saleChannels = $saleChannelQueries->getSaleChannelsByCompany($webhookUrls, $member->company_id);

        if ($saleChannels->isEmpty()) {
            Log::channel('e_commerce')->info('updating members : sale channels is empty', [
                'Start time for member update' => Carbon::now()->format('Y-m-d H:i:s'),
                'member id: ' . $member->getKey(),
            ]);

            return;
        }

        try {
            foreach ($saleChannels as $saleChannel) {
                $this->updateMemberDetails($saleChannel, $member);
            }
        } catch (Throwable $throwable) {
            Log::channel('e_commerce')->error('e-commerce webhook member update details failed', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }

        Log::channel('e_commerce')->info('End updating members in eCommerce', [
            'End time for member update' => Carbon::now()->format('Y-m-d H:i:s'),
            'member id: ' . $member->getKey(),
        ]);
    }

    public function deleteMember(int $memberId, int $companyId): void
    {
        Log::channel('e_commerce')->info('Start deleting members in eCommerce', [
            'Start time for member delete' => Carbon::now()->format('Y-m-d H:i:s'),
            'member id: ' . $memberId,
        ]);

        $saleChannelQueries = resolve(SaleChannelQueries::class);

        $webhookUrls = [WebhookUrls::MEMBER_DELETE->value];

        $saleChannels = $saleChannelQueries->getSaleChannelsByCompanyAndTypeId(
            $webhookUrls,
            $companyId,
            SaleChannelTypes::ECOMMERCE->value
        );

        if ($saleChannels->isEmpty()) {
            Log::channel('e_commerce')->info('deleting member : sale channels is empty', [
                'Start time for member delete' => Carbon::now()->format('Y-m-d H:i:s'),
                'member id: ' . $memberId,
            ]);

            return;
        }

        try {
            $memberChannelReferenceQueries = resolve(MemberChannelReferenceQueries::class);

            foreach ($saleChannels as $saleChannel) {
                $memberChannelReference = $memberChannelReferenceQueries->getByMemberIdAndSaleChannelId(
                    $memberId,
                    $saleChannel->id
                );

                if (! $memberChannelReference) {
                    continue;
                }

                foreach ($saleChannel->saleChannelWebhookUrls as $saleChannelWebhookUrl) {
                    $response = Http::withHeaders([
                        'Authorization' => 'Bearer ' . $saleChannel->secret,
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ])->timeout(config('services.http_time_out'))->post($saleChannelWebhookUrl->url, [
                        'id' => $memberChannelReference->external_member_id,
                    ]);

                    if ($response->successful()) {
                        $responseData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

                        Log::channel('e_commerce')->info('Response: delete member id: ' . $memberId, [
                            'response' => $responseData,
                        ]);
                    }

                    Log::channel('e_commerce')->info('Response: Error on Member delete in E-Commerce', [
                        'status_code' => $response->status(),
                        'response_body' => $response->body() ?: 'No response body provided',
                        'request_data' => [
                            'member_id' => $memberId,
                        ],
                    ]);
                }
            }
        } catch (Throwable $throwable) {
            Log::channel('e_commerce')->error('e-commerce webhook member delete details failed', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }

        Log::channel('e_commerce')->info('End deleting members in eCommerce', [
            'End time for member delete' => Carbon::now()->format('Y-m-d H:i:s'),
            'member id: ' . $memberId,
        ]);
    }

    public function mergeMember(
        int $oldMemberId,
        int $newMemberId,
        int $companyId,
        Collection $oldMemberChannelReferences,
        Collection $newMemberChannelReferences,
    ): void {
        Log::channel('e_commerce')->info('Start merge members in eCommerce', [
            'Start time for member merge' => Carbon::now()->format('Y-m-d H:i:s'),
            'old member id: ' . $oldMemberId,
            'new member id: ' . $newMemberId,
        ]);

        $saleChannelQueries = resolve(SaleChannelQueries::class);
        $memberChannelReferenceQueries = resolve(MemberChannelReferenceQueries::class);

        $webhookUrls = [WebhookUrls::MEMBER_MERGE->value];

        $saleChannels = $saleChannelQueries->getSaleChannelsByCompanyAndTypeId(
            $webhookUrls,
            $companyId,
            SaleChannelTypes::ECOMMERCE->value
        );

        if ($saleChannels->isEmpty()) {
            Log::channel('e_commerce')->info('merging member : sale channels is empty', [
                'old member id: ' . $oldMemberId,
                'new member id: ' . $newMemberId,
            ]);

            return;
        }

        try {
            foreach ($saleChannels as $saleChannel) {
                $oldMemberChannelReference = $oldMemberChannelReferences
                    ->where('member_id', $oldMemberId)
                    ->firstWhere('sale_channel_id', $saleChannel->id);

                $newMemberChannelReference = $newMemberChannelReferences
                    ->where('member_id', $newMemberId)
                    ->firstWhere('sale_channel_id', $saleChannel->id);

                if (! ($oldMemberChannelReference && $newMemberChannelReference)) {
                    Log::channel('e_commerce')->info('merging member : external member not found', [
                        'old member id: ' . $oldMemberId,
                        'new member id: ' . $newMemberId,
                    ]);

                    continue;
                }

                if ($oldMemberChannelReference instanceof MemberChannelReference) {
                    $payLoad = [
                        'old_customer_id' => $oldMemberChannelReference->external_member_id,
                        'new_customer_id' => $newMemberChannelReference->external_member_id,
                    ];

                    foreach ($saleChannel->saleChannelWebhookUrls as $saleChannelWebhookUrl) {
                        $response = Http::withHeaders([
                            'Authorization' => 'Bearer ' . $saleChannel->secret,
                            'Content-Type' => 'application/json',
                            'Accept' => 'application/json',
                        ])->timeout(config('services.http_time_out'))->post($saleChannelWebhookUrl->url, $payLoad);

                        if ($response->successful()) {
                            $memberChannelReferenceQueries->deleteOldMemberForMerge($oldMemberId);
                            $responseData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

                            Log::channel('e_commerce')->info('Response: merge old member id: ' . $oldMemberId, [
                                'response' => $responseData,
                            ]);
                        }

                        if ($response->failed()) {
                            Log::channel('e_commerce')->info('Response: Error on Member merge in E-Commerce', [
                                'status_code' => $response->status(),
                                'response_body' => $response->body() ?: 'No response body provided',
                                'request_data' => $payLoad,
                            ]);
                        }
                    }
                }
            }
        } catch (Throwable $throwable) {
            Log::channel('e_commerce')->error('e-commerce webhook member merge details failed', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }
    }

    private function updateMemberDetails(SaleChannel $saleChannel, Member $member): void
    {
        Log::channel('e_commerce')->info('Start updating member details in eCommerce.', [
            'Start time for updating member details' => Carbon::now()->format('Y-m-d H:i:s'),
            'member id: ' . $member->getKey(),
        ]);

        $memberChannelReferenceQueries = resolve(MemberChannelReferenceQueries::class);
        $memberGroupSaleChannelService = resolve(MemberGroupSaleChannelService::class);
        $memberGroupQueries = resolve(MemberGroupQueries::class);

        $memberChannelReference = $memberChannelReferenceQueries->getByMemberIdAndSaleChannelId(
            $member->id,
            $saleChannel->id
        );

        if (! $memberChannelReference instanceof MemberChannelReference) {
            $saleChannelQueries = resolve(SaleChannelQueries::class);
            $saleChannel = $saleChannelQueries->loadWebhookUrls($saleChannel);

            Log::channel('e_commerce')->info('updating member : add member call.', [
                'Start time for updating member details' => Carbon::now()->format('Y-m-d H:i:s'),
                'member id: ' . $member->getKey(),
            ]);

            $this->addMember($saleChannel, $member);

            return;
        }

        $saleChannelWebhookUrls = $saleChannel->saleChannelWebhookUrls->where(
            'webhook_url_type_id',
            WebhookUrls::MEMBER_UPDATE->value
        );

        $memberGroupChannelReferenceQueries = resolve(MemberGroupChannelReferenceQueries::class);
        if ($member->memberGroupMembers->first()) {
            $memberGroup = $memberGroupQueries->getByOnlyId($member->memberGroupMembers->first()->member_group_id);

            Log::channel('e_commerce')->info('adding member : add member group call', [
                'Start time for member addition' => Carbon::now()->format('Y-m-d H:i:s'),
                'member id: ' . $member->getKey(),
            ]);

            $memberGroupSaleChannelService->addMemberGroup($saleChannel, $memberGroup);

            $memberGroupChannelReferenceQueries->getByMemberGroupIdAndSaleChannelId(
                $member->memberGroupMembers->first()->member_group_id,
                $saleChannel->id
            );
        }

        foreach ($saleChannelWebhookUrls as $saleChannelWebhookUrl) {
            $url = $saleChannelWebhookUrl->url;

            $requestData = [
                'customer' => $this->preparedRecords($member, $memberChannelReference, $saleChannel),
            ];

            if (SaleChannelTypes::WEBSPERT_ECOMMERCE === $saleChannel->type_id) {
                Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])->timeout(config('services.http_time_out'))->post($url, $requestData);
            }

            if (SaleChannelTypes::ECOMMERCE === $saleChannel->type_id) {
                Http::withHeaders([
                    'Authorization' => 'Bearer ' . $saleChannel->secret,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])->timeout(config('services.http_time_out'))->post($url, $requestData);
            }
        }

        Log::channel('e_commerce')->info('End updating member details in eCommerce', [
            'Completion time for updating member details' => Carbon::now()->format('Y-m-d H:i:s'),
            'member id: ' . $member->getKey(),
        ]);
    }

    private function preparedRecords(
        Member $member,
        ?MemberChannelReference $memberChannelReference,
        SaleChannel $saleChannel
    ): array {
        $status = $member->status === Status::ACTIVE->value;

        return [
            'secretkey' => $saleChannel->secret,
            'existing_id' => $memberChannelReference?->external_member_id,
            'external_member_id' => $memberChannelReference?->external_member_id,
            'title_id' => $member->title_id,
            'first_name' => $member->first_name,
            'last_name' => $member->last_name,
            'gender_id' => $member->gender_id,
            'date_of_birth' => $member->date_of_birth,
            'mobile_number' => $member->mobile_number,
            'email' => $member->email,
            'company_name' => $member->company_name,
            'notes' => $member->notes,
            'membership_id' => $member->membership_id ? $this->getMemberShipChannelReferenceId(
                $member->membership_id,
                $saleChannel->id,
            ) : null,
            'card_number' => $member->card_number,
            'otp' => $member->otp,
            'otp_expire_date' => $member->otp_expire_date,
            'status' => (int) $status,
            'id' => $member->id,
            'phone' => $member->mobile_number,
            'loyalty_points' => $member->loyalty_points,
            'gender' => $member->gender_id ? Genders::getFormattedCaseName($member->gender_id) : null,
            'image' => $member->getDiskBasedFirstMediaUrl('photo'),
        ];
    }

    private function getMemberShipChannelReferenceId(int $memberShipId, int $saleChannelId): ?int
    {
        $membershipChannelReferenceQueries = resolve(MembershipChannelReferenceQueries::class);

        $membershipChannelReference = $membershipChannelReferenceQueries->getByMembershipIdAndSaleChannelId(
            $memberShipId,
            $saleChannelId
        );

        if (! $membershipChannelReference) {
            Log::channel('e_commerce')->info('Add/Update member : Membership not found', [
                'Start time for member addition/update' => Carbon::now()->format('Y-m-d H:i:s'),
                'membership id: ' . $memberShipId,
            ]);
        }

        return $membershipChannelReference?->external_membership_id;
    }
}
