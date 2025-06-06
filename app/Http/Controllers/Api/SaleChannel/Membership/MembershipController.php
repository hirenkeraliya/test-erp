<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\SaleChannel\Membership;

use App\Domains\Membership\MembershipQueries;
use App\Domains\Membership\Resources\EcommerceMemberShipListResource;
use App\Http\Controllers\Controller;
use App\Models\SaleChannel;
use Illuminate\Http\Request;

class MembershipController extends Controller
{
    public function getMembership(Request $request): array
    {
        $saleChannel = $request->user();

        if (! $saleChannel instanceof SaleChannel) {
            abort(401, 'You are not authenticated.');
        }

        $validatedData = $request->validate([
            'after_updated_at' => ['sometimes', 'nullable', 'string', 'date_format:Y-m-d H:i:s'],
        ]);

        $afterUpdatedAt = $validatedData['after_updated_at'] ?? null;

        $membershipQueries = resolve(MembershipQueries::class);
        $memberships = $membershipQueries->getByCompanyIdSortByMinimumSpendAmount(
            $saleChannel->getCompanyId(),
            $afterUpdatedAt
        );

        return [
            'memberships' => EcommerceMemberShipListResource::collection($memberships),
        ];
    }
}
