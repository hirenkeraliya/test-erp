<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\SaleChannel\Promotion;

use App\Domains\Promotion\PromotionQueries;
use App\Domains\Promotion\Resources\EcommercePromotionListResource;
use App\Http\Controllers\Controller;
use App\Models\SaleChannel;
use Illuminate\Http\Request;

class PromotionController extends Controller
{
    public function __construct(
        protected PromotionQueries $promotionQueries
    ) {
    }

    public function getPromotions(Request $request): array
    {
        $saleChannel = $request->user();

        if (! $saleChannel instanceof SaleChannel) {
            abort(401, 'You are not authenticated.');
        }

        $promotionsList = $this->promotionQueries->getListForEcommerceAsPerTimeFrameWithRelatedData(
            $saleChannel->getCompanyId(),
            $saleChannel->getDefaultLocationId()
        );

        return [
            'promotions' => EcommercePromotionListResource::collection($promotionsList),
        ];
    }
}
