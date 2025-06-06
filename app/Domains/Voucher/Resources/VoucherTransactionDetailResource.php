<?php

declare(strict_types=1);

namespace App\Domains\Voucher\Resources;

use App\Domains\Sale\Enums\SaleStatus;
use App\Domains\VoucherTransaction\Enums\VoucherTransactionActionTypes;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class VoucherTransactionDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return mixed[]
     */
    public function toArray($request): array
    {
        /** @var Voucher $voucher */
        $voucher = $this;

        /** @var Collection $voucherTransactions */
        $voucherTransactions = $voucher->voucherTransactions;

        $voucherDetails = $voucherTransactions->map(function (VoucherTransaction $voucherTransaction): array {
            $voucherLocation = $voucherTransaction->location;
            $voucherSale = $voucherTransaction->sale;
            $voucherOrder = $voucherTransaction->order;
            $offlineId = 'N/A';

            if ($voucherSale) {
                $offlineId = $voucherSale->offline_sale_id . ' (' . SaleStatus::getFormattedCaseName(
                    $voucherSale->status
                ) . ')';
            }

            if ($voucherOrder) {
                $offlineId = $voucherOrder->receipt_number. ' (' . $voucherOrder->status?->name . ')';
            }

            /** @var Carbon $date */
            $date = Carbon::createFromFormat('Y-m-d H:i:s', $voucherTransaction->happened_at);

            return [
                'offline_sale_id' => $offlineId,
                'action_type' => VoucherTransactionActionTypes::getFormattedCaseName(
                    $voucherTransaction->action_type_id
                ),
                'location' => $voucherLocation ? $voucherLocation->name . ' (' . $voucherLocation->code . ')' : 'N/A',
                'date' => $date->format('d-m-Y h:i:s A'),
            ];
        });

        return $voucherDetails->toArray();
    }
}
