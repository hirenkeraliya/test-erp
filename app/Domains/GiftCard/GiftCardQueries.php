<?php

declare(strict_types=1);

namespace App\Domains\GiftCard;

use App\CommonFunctions;
use App\Domains\GiftCard\Enums\GiftCardStatuses;
use App\Domains\GiftCard\Enums\GiftCardTypes;
use App\Models\GiftCard;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class GiftCardQueries
{
    public function listQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->getGiftCardLists($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function getGiftCardsForExport(array $filterData, int $companyId): Collection
    {
        return $this->getGiftCardLists($filterData, $companyId)->get();
    }

    public function getByIds(array $giftCardIds, int $companyId): Collection
    {
        return GiftCard::query()
            ->select(
                'id',
                'company_id',
                'type_id',
                'number',
                'expiry_date',
                'total_amount',
                'available_amount',
                'status'
            )
            ->where('company_id', $companyId)
            ->whereIntegerInRaw('id', $giftCardIds)
            ->get();
    }

    public function getById(int $giftCardId, int $companyId): ?GiftCard
    {
        return GiftCard::query()
            ->select(
                'id',
                'company_id',
                'type_id',
                'number',
                'expiry_date',
                'total_amount',
                'available_amount',
                'status'
            )
            ->where('company_id', $companyId)
            ->find($giftCardId);
    }

    public function decreaseAvailableAmountAndMarkAsUsed(GiftCard $giftCard, float $amount): void
    {
        $giftCard->available_amount -= $amount;

        if ($giftCard->available_amount <= 0 || $giftCard->type_id === GiftCardTypes::SINGLE_USE_ONLY->value) {
            $giftCard->status = GiftCardStatuses::USED->value;
        }

        $giftCard->save();
    }

    public function getPaginatedList(array $filterData, int $companyId): LengthAwarePaginator
    {
        return GiftCard::select(
            'id',
            'company_id',
            'type_id',
            'number',
            'expiry_date',
            'total_amount',
            'available_amount',
            'status',
            'created_at',
        )
            ->where('company_id', $companyId)
            ->when($filterData['after_updated_at'], function ($query) use ($filterData): void {
                $query->where('updated_at', '>=', $filterData['after_updated_at']);
            })
            ->paginate($filterData['per_page']);
    }

    public function createMany(array $giftCards): void
    {
        foreach ($giftCards as $giftCard) {
            unset($giftCard['amount']);
            GiftCard::create($giftCard);
        }
    }

    public function filterByCompany(int $companyId): Closure
    {
        return fn ($query) => $query->where('company_id', $companyId);
    }

    public function checkExistingNumbers(array $numbers, int $companyId): bool
    {
        return GiftCard::where('company_id', $companyId)->whereIn('number', $numbers)->exists();
    }

    public function incrementAvailableAmountAndActivate(int $giftCardId, float $amount): void
    {
        $giftCard = GiftCard::findOrFail($giftCardId);

        $giftCard->available_amount += $amount;
        $giftCard->status = GiftCardStatuses::ACTIVE->value;

        $giftCard->save();
    }

    public function markGiftCardsAsExpired(): int
    {
        $giftCards = GiftCard::where('status', GiftCardStatuses::ACTIVE->value)
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<', now()->format('Y-m-d'))->get();

        $giftCardsCounts = $giftCards->count();

        foreach ($giftCards as $giftCard) {
            $giftCard->status = GiftCardStatuses::EXPIRED->value;
            $giftCard->save();
        }

        return $giftCardsCounts;
    }

    private function getGiftCardLists(array $filterData, int $companyId): Builder
    {
        return GiftCard::query()
            ->select(
                'id',
                'type_id',
                'number',
                'expiry_date',
                'total_amount',
                'available_amount',
                'status',
                'created_at'
            )
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query
                        ->whereAny(
                            ['number', 'total_amount', 'available_amount'],
                            'LIKE',
                            '%' . $filterData['search_text'] . '%'
                        );
                });
            })
            ->where('company_id', $companyId)
            ->when($filterData['expiry_date'], function ($query) use ($filterData): void {
                $query->where('expiry_date', '>=', $filterData['expiry_date'][0])
                    ->where('expiry_date', '<=', $filterData['expiry_date'][1]);
            })
            ->when($filterData['created_date'], function ($query) use ($filterData): void {
                $query->where('created_at', '>=', CommonFunctions::addStartTime($filterData['created_date'][0]))
                    ->where('created_at', '<=', CommonFunctions::addEndTime($filterData['created_date'][1]));
            })
            ->when((int) $filterData['status'] === GiftCardStatuses::ACTIVE->value, function ($query): void {
                $query->where('status', GiftCardStatuses::ACTIVE->value);
            })
            ->when((int) $filterData['status'] === GiftCardStatuses::USED->value, function ($query): void {
                $query->where('status', GiftCardStatuses::USED->value);
            })
            ->when((int) $filterData['status'] === GiftCardStatuses::EXPIRED->value, function ($query): void {
                $query->where('status', GiftCardStatuses::EXPIRED->value);
            })
            ->when((int) $filterData['type'] === GiftCardTypes::SINGLE_USE_ONLY->value, function ($query): void {
                $query->where('type_id', GiftCardTypes::SINGLE_USE_ONLY->value);
            })
            ->when((int) $filterData['type'] === GiftCardTypes::MULTIPLE_USES->value, function ($query): void {
                $query->where('type_id', GiftCardTypes::MULTIPLE_USES->value);
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }
}
