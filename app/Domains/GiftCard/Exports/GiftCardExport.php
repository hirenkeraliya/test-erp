<?php

declare(strict_types=1);

namespace App\Domains\GiftCard\Exports;

use App\CommonFunctions;
use App\Domains\GiftCard\Enums\GiftCardStatuses;
use App\Domains\GiftCard\Enums\GiftCardTypes;
use App\Models\GiftCard;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class GiftCardExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected Collection $giftCards
    ) {
    }

    public function collection(): Collection
    {
        return $this->giftCards->map(function (GiftCard $giftCard): array {
            $expiryDate = null;

            if ($giftCard->expiry_date) {
                /** @var Carbon $expiryDate */
                $expiryDate = Carbon::createFromFormat('Y-m-d', $giftCard->expiry_date);
                $expiryDate = $expiryDate->format('d-m-Y');
            }

            return [
                'type_id' => GiftCardTypes::getCaseNameByValue($giftCard->type_id),
                'number' => $giftCard->number,
                'expiry_date' => $expiryDate ?? 'N/A',
                'total_amount' => CommonFunctions::currencyFormat((float) $giftCard->total_amount),
                'available_amount' => CommonFunctions::currencyFormat((float) $giftCard->available_amount),
                'status' => GiftCardStatuses::getCaseNameByValue($giftCard->status),
            ];
        });
    }

    public function headings(): array
    {
        return ['TypeId', 'Number', 'Expiry Date', 'Amount', 'Available', 'Status'];
    }
}
