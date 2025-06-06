<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\SaleChannel\Banner;

use App\Domains\Banner\BannerQueries;
use App\Domains\Banner\Enums\ActionTypes;
use App\Domains\Banner\Resources\BannerListResource;
use App\Http\Controllers\Controller;
use App\Models\SaleChannel;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    public function __construct(
        protected BannerQueries $bannerQueries
    ) {
    }

    public function getList(Request $request): array
    {
        $saleChannel = $request->user();

        if (! $saleChannel instanceof SaleChannel) {
            abort(401, 'You are not authenticated.');
        }

        $filterData = [
            'search_text' => $request->get('search_text'),
        ];

        $bannerList = $this->bannerQueries->getListInEcommerce($filterData, $saleChannel->getCompanyId());

        return [
            'banners' => BannerListResource::collection($bannerList),
        ];
    }

    public function getActionTypes(Request $request): array
    {
        $saleChannel = $request->user();

        if (! $saleChannel instanceof SaleChannel) {
            abort(401, 'You are not authenticated.');
        }

        return [
            'action_types' => ActionTypes::getList(),
        ];
    }
}
