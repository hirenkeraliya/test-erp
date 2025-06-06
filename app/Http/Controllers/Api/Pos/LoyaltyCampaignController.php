<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Pos;

use App\CommonFunctions;
use App\Domains\LoyaltyCampaign\LoyaltyCampaignQueries;
use App\Domains\LoyaltyCampaign\Resources\PosLoyaltyCampaignListResource;
use App\Http\Controllers\Controller;
use App\Models\Cashier;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LoyaltyCampaignController extends Controller
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

        $companyId = CommonFunctions::getCashierCompanyId($cashier);

        $loyaltyCampaignQueries = resolve(LoyaltyCampaignQueries::class);
        $loyaltyCampaignList = $loyaltyCampaignQueries->getActiveLoyaltyCampaignsByCompanyId(
            $companyId,
            $afterUpdatedAt
        );

        return [
            'loyalty_campaigns' => PosLoyaltyCampaignListResource::collection($loyaltyCampaignList),
        ];
    }
}
