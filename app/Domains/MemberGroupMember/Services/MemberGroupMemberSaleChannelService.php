<?php

declare(strict_types=1);

namespace App\Domains\MemberGroupMember\Services;

use App\Domains\Common\Enums\WebhookUrls;
use App\Domains\Member\Enums\Genders;
use App\Domains\MemberChannelReference\MemberChannelReferenceQueries;
use App\Domains\MemberGroupChannelReference\MemberGroupChannelReferenceQueries;
use App\Domains\MemberGroupMember\MemberGroupMemberQueries;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Models\Member;
use App\Models\MemberChannelReference;
use App\Models\MemberGroupChannelReference;
use App\Models\MemberGroupMember;
use App\Models\SaleChannel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class MemberGroupMemberSaleChannelService
{
    public function updateMemberGroup(MemberGroupMember $memberGroupMember): void
    {
        Log::channel('e_commerce')->info('Start update the members member group member options in eCommerce.', [
            'Start time for member group member creation' => Carbon::now()->format('Y-m-d H:i:s'),
            'member group id: ' . $memberGroupMember->getKey(),
        ]);

        $memberGroupMemberQueries = resolve(MemberGroupMemberQueries::class);
        $memberGroupMember = $memberGroupMemberQueries->refresh($memberGroupMember);
        $memberGroupMember = $memberGroupMemberQueries->getMemberAndMemberGroupById($memberGroupMember->id);

        if (! $memberGroupMember instanceof MemberGroupMember) {
            Log::channel('e_commerce')->info(
                'update the members member group : return when member group member is empty.',
                [
                    'Start time for member group member creation' => Carbon::now()->format('Y-m-d H:i:s'),
                ]
            );

            return;
        }

        /** @var Member $member */
        $member = $memberGroupMember->member;

        if (! $member instanceof Member) {
            Log::channel('e_commerce')->info('update the members member group : return when member is empty.', [
                'Start time for member group member creation' => Carbon::now()->format('Y-m-d H:i:s'),
                'member id: ' . $memberGroupMember->getKey(),
            ]);

            return;
        }

        $memberGroupId = $memberGroupMemberQueries->getMemberGroupIdByMemberId($member->id);

        if (! $memberGroupId) {
            Log::channel('e_commerce')->info('update the members member group : return when member group is empty.', [
                'Start time for member group member creation' => Carbon::now()->format('Y-m-d H:i:s'),
                'member group id: ' . $memberGroupId,
            ]);

            return;
        }

        $saleChannelQueries = resolve(SaleChannelQueries::class);

        $webhookUrls = [WebhookUrls::MEMBER_UPDATE->value];

        $saleChannels = $saleChannelQueries->getSaleChannelsByCompany($webhookUrls, $member->company_id);

        if ($saleChannels->isEmpty()) {
            Log::channel('e_commerce')->info('update the members member group : return when sale channels is empty.', [
                'Start time for member creation' => Carbon::now()->format('Y-m-d H:i:s'),
                'member id: ' . $member->getKey(),
            ]);

            return;
        }

        try {
            foreach ($saleChannels as $saleChannel) {
                $memberAndMemberGroupId = $this->checkMemberAndMemberGroup($saleChannel, $member, $memberGroupId);
                if ([] === $memberAndMemberGroupId) {
                    continue;
                }

                $this->updateMembersMemberGroup($saleChannel, $member, $memberAndMemberGroupId);
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

    public function checkMemberAndMemberGroup(SaleChannel $saleChannel, Member $member, int $memberGroupId): array
    {
        $memberChannelReferenceQueries = resolve(MemberChannelReferenceQueries::class);
        $memberGroupChannelReferenceQueries = resolve(MemberGroupChannelReferenceQueries::class);

        $memberChannelReference = $memberChannelReferenceQueries->getByMemberIdAndSaleChannelId(
            $member->id,
            $saleChannel->id
        );

        if (! $memberChannelReference instanceof MemberChannelReference) {
            return [];
        }

        $memberGroupChannelReference = $memberGroupChannelReferenceQueries->getByMemberGroupIdAndSaleChannelId(
            $memberGroupId,
            $saleChannel->id
        );

        if (! $memberGroupChannelReference instanceof MemberGroupChannelReference) {
            return [];
        }

        return [
            'external_member_id' => $memberChannelReference->external_member_id,
            'external_member_group_id' => $memberGroupChannelReference->external_member_group_id,
        ];
    }

    public function updateMembersMemberGroup(
        SaleChannel $saleChannel,
        Member $member,
        array $memberAndMemberGroupId
    ): void {
        Log::channel('e_commerce')->info('Start update members member group in eCommerce', [
            'Start time for member addition' => Carbon::now()->format('Y-m-d H:i:s'),
            'member and member group id: ' . $memberAndMemberGroupId['external_member_id'] . ' ' . $memberAndMemberGroupId['external_member_group_id'],
        ]);

        $saleChannelWebhookUrls = $saleChannel->saleChannelWebhookUrls->where(
            'webhook_url_type_id',
            WebhookUrls::MEMBER_UPDATE->value
        );

        foreach ($saleChannelWebhookUrls as $saleChannelWebhookUrl) {
            $url = $saleChannelWebhookUrl->url;

            $requestData = [
                'secretkey' => $saleChannel->secret,
                'id' => $member->id,
                'external_member_id' => $memberAndMemberGroupId['external_member_id'],
                'first_name' => $member->first_name,
                'last_name' => $member->last_name,
                'phone' => $member->mobile_number,
                'email' => $member->email,
                'gender' => $member->gender_id ? Genders::getFormattedCaseName($member->gender_id) : null,
                'date_of_birth' => $member->date_of_birth,
                'image' => $member->getDiskBasedFirstMediaUrl('photo'),
                'customer_group_id' => $memberAndMemberGroupId['external_member_group_id'],
            ];

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(config('services.http_time_out'))->post($url, $requestData);

            if ($response->successful()) {
                $responseData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

                Log::channel('e_commerce')->info('Response: update members member group in E-Commerce', [
                    'response' => $responseData,
                ]);
            } else {
                Log::channel('e_commerce')->info('Response: Error on update members member group E-Commerce', [
                    'status_code' => $response->status(),
                    'response_body' => $response->body() ?: 'No response body provided',
                    'request_data' => $memberAndMemberGroupId['external_member_id'].' '. $memberAndMemberGroupId['external_member_group_id'],
                ]);
            }
        }

        Log::channel('e_commerce')->info('End update members member group in eCommerce', [
            'Completion time for member addition' => Carbon::now()->format('Y-m-d H:i:s'),
            'member and member group id: ' . $memberAndMemberGroupId['external_member_id'] . ' ' . $memberAndMemberGroupId['external_member_group_id'],
        ]);
    }
}
