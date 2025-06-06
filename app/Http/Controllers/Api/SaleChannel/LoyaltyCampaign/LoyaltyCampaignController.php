<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\SaleChannel\LoyaltyCampaign;

use App\Domains\LoyaltyCampaign\LoyaltyCampaignQueries;
use App\Domains\LoyaltyCampaign\Resources\EcommerceLoyaltyCampaignListResource;
use App\Http\Controllers\Controller;
use App\Models\SaleChannel;
use Illuminate\Http\Request;

class LoyaltyCampaignController extends Controller
{
    public function getLoyaltyCampaignConfigurations(Request $request): array
    {
        $saleChannel = $request->user();

        if (! $saleChannel instanceof SaleChannel) {
            abort(401, 'You are not authenticated.');
        }

        $validatedData = $request->validate([
            'after_updated_at' => ['sometimes', 'nullable', 'string', 'date_format:Y-m-d H:i:s'],
        ]);

        $afterUpdatedAt = $validatedData['after_updated_at'] ?? null;

        $loyaltyCampaignQueries = resolve(LoyaltyCampaignQueries::class);
        $loyaltyCampaignList = $loyaltyCampaignQueries->getActiveLoyaltyCampaignsByCompanyId(
            $saleChannel->getCompanyId(),
            $afterUpdatedAt
        );

        return [
            'loyalty_campaigns' => EcommerceLoyaltyCampaignListResource::collection($loyaltyCampaignList),
        ];
    }
}
