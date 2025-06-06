<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Pos;

use App\CommonFunctions;
use App\Domains\Membership\MembershipQueries;
use App\Domains\Membership\Resources\PosMemberShipListResource;
use App\Http\Controllers\Controller;
use App\Models\Cashier;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MembershipController extends Controller
{
    /**
     * @return array<string, AnonymousResourceCollection>
     */
    public function getList(Request $request): array
    {
        $validatedData = $request->validate([
            'after_updated_at' => ['sometimes', 'nullable', 'string', 'date_format:Y-m-d H:i:s'],
        ]);

        $afterUpdatedAt = $validatedData['after_updated_at'] ?? null;

        /** @var Cashier $cashier */
        $cashier = $request->user();

        if (! $cashier->getCounterUpdateId()) {
            abort(412, 'The counter has not been opened yet.');
        }

        $companyId = CommonFunctions::getCashierCompanyId($cashier);
        $membershipQueries = resolve(MembershipQueries::class);
        $memberships = $membershipQueries->getByCompanyIdSortByMinimumSpendAmount($companyId, $afterUpdatedAt);

        return [
            'memberships' => PosMemberShipListResource::collection($memberships),
        ];
    }
}
