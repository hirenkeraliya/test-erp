<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\SaleChannel\MemberAddress;

use App\Domains\MemberAddress\DataObjects\EcommerceMemberAddressData;
use App\Domains\MemberAddress\MemberAddressQueries;
use App\Domains\MemberAddress\Resources\MemberAddressResource;
use App\Domains\MemberAddressChannelReference\MemberAddressChannelReferenceQueries;
use App\Domains\MemberChannelReference\MemberChannelReferenceQueries;
use App\Domains\SaleChannel\Enums\SaleChannelTypes;
use App\Http\Controllers\Controller;
use App\Models\MemberAddress;
use App\Models\SaleChannel;
use Illuminate\Http\Request;

class MemberAddressController extends Controller
{
    public function __construct(
        protected MemberAddressQueries $memberAddressQueries
    ) {
    }

    public function store(EcommerceMemberAddressData $ecommerceMemberAddressData, Request $request): array
    {
        $saleChannel = $request->user();

        if (! $saleChannel instanceof SaleChannel) {
            abort(401, 'You are not authenticated.');
        }

        $memberChannelReferenceQueries = resolve(MemberChannelReferenceQueries::class);
        $memberId = $memberChannelReferenceQueries->getByMemberId(
            $ecommerceMemberAddressData->external_member_id,
            $saleChannel->id
        );

        if (! $memberId) {
            return [];
        }

        $memberAddress = $this->memberAddressQueries->isPrimary($memberId);

        if ($memberAddress instanceof MemberAddress && $ecommerceMemberAddressData->is_primary) {
            $this->memberAddressQueries->updatePrimaryKey($memberAddress->id, $memberId);
        }

        $memberAddress = $this->memberAddressQueries->addAddressForEcommerce($ecommerceMemberAddressData, $memberId);

        $memberAddressChannelReferenceQueries = resolve(MemberAddressChannelReferenceQueries::class);
        $memberAddressChannelReferenceQueries->firstOrCreate([
            'sale_channel_id' => $saleChannel->id,
            'member_address_id' => $memberAddress->id,
            'external_member_address_id' => $ecommerceMemberAddressData->external_member_address_id,
        ]);

        return [
            'member_address' => new MemberAddressResource($memberAddress),
        ];
    }

    public function update(
        EcommerceMemberAddressData $ecommerceMemberAddressData,
        Request $request,
        int $externalMemberAddressId
    ): void {
        $saleChannel = $request->user();

        if (! $saleChannel instanceof SaleChannel) {
            abort(401, 'You are not authenticated.');
        }

        $memberChannelReferenceQueries = resolve(MemberChannelReferenceQueries::class);
        $memberId = $memberChannelReferenceQueries->getByMemberId(
            $ecommerceMemberAddressData->external_member_id,
            $saleChannel->id
        );

        if (! $memberId) {
            return;
        }

        $memberAddress = $this->memberAddressQueries->isPrimary($memberId);

        if ($memberAddress instanceof MemberAddress && $ecommerceMemberAddressData->is_primary) {
            $this->memberAddressQueries->updatePrimaryKey($memberAddress->id, $memberId);
        }

        $memberAddressChannelReferenceQueries = resolve(MemberAddressChannelReferenceQueries::class);

        /** @var int $memberAddressId */
        $memberAddressId = $memberAddressChannelReferenceQueries->getByMemberAddressId(
            $externalMemberAddressId,
            $saleChannel->id
        );

        $this->memberAddressQueries->updateForEcommerce($ecommerceMemberAddressData, $memberAddressId);
    }

    public function removeAddress(Request $request, ?int $memberAddressId): void
    {
        $saleChannel = $request->user();

        if (! $saleChannel instanceof SaleChannel) {
            abort(401, 'You are not authenticated.');
        }

        if (SaleChannelTypes::ECOMMERCE === $saleChannel->type_id) {
            $memberAddressChannelReferenceQueries = resolve(MemberAddressChannelReferenceQueries::class);
            if ($memberAddressId) {
                $memberAddressId = $memberAddressChannelReferenceQueries->getByMemberAddressId(
                    $memberAddressId,
                    $saleChannel->id
                );
            }
        }

        if ($memberAddressId) {
            $this->memberAddressQueries->delete($memberAddressId);
        }
    }

    public function getList(Request $request, int $memberId): array
    {
        $saleChannel = $request->user();

        if (! $saleChannel instanceof SaleChannel) {
            abort(401, 'You are not authenticated.');
        }

        $memberAddresses = $this->memberAddressQueries->getMemberAddressDetails($memberId);

        return [
            'member_addresses' => MemberAddressResource::collection($memberAddresses),
        ];
    }
}
