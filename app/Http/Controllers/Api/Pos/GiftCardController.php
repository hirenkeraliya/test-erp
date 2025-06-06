<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Pos;

use App\CommonFunctions;
use App\Domains\GiftCard\DataObjects\PaginatedGiftCardListDataForPos;
use App\Domains\GiftCard\Enums\GiftCardStatuses;
use App\Domains\GiftCard\Enums\GiftCardTypes;
use App\Domains\GiftCard\GiftCardQueries;
use App\Domains\GiftCard\Resources\PosGiftCardListResource;
use App\Http\Controllers\Controller;
use App\Models\Cashier;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class GiftCardController extends Controller
{
    /**
     * @return array<string, AnonymousResourceCollection>|array<string, int>
     */
    public function getPaginatedList(
        Request $request,
        PaginatedGiftCardListDataForPos $paginatedGiftCardListDataForPos
    ): array {
        /** @var Cashier $cashier */
        $cashier = $request->user();

        if (! $cashier->counter_update_id) {
            abort(412, 'The counter has not been opened yet.');
        }

        $companyId = CommonFunctions::getCashierCompanyId($cashier);

        $filterData = [
            'per_page' => $paginatedGiftCardListDataForPos->per_page,
            'after_updated_at' => $paginatedGiftCardListDataForPos->after_updated_at,
        ];

        $giftCardQueries = resolve(GiftCardQueries::class);
        $giftCardList = $giftCardQueries->getPaginatedList($filterData, $companyId);

        return [
            'gift_cards' => PosGiftCardListResource::collection($giftCardList),
            'total_records' => $giftCardList->total(),
            'last_page' => $giftCardList->lastPage(),
            'current_page' => $giftCardList->currentPage(),
            'per_page' => $giftCardList->perPage(),
        ];
    }

    /**
     * @return array<string, mixed[]>
     */
    public function getStaticDetails(): array
    {
        return [
            'statuses' => GiftCardStatuses::getList(),
            'types' => GiftCardTypes::getList(),
        ];
    }
}
