<?php

declare(strict_types=1);

namespace App\Domains\GoodsReceivedNote\Exports;

use App\Domains\Location\Enums\LocationTypes;
use App\Models\GoodsReceivedNote;
use App\Models\Location;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class GoodsReceivedNoteExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected Collection $goodsReceivedNotes
    ) {
    }

    public function collection(): Collection
    {
        return $this->goodsReceivedNotes->map(function (GoodsReceivedNote $goodsReceivedNote): array {
            /** @var Vendor $vendor */
            $vendor = $goodsReceivedNote->vendor;
            /** @var Location $location */
            $location = $goodsReceivedNote->location;
            /** @var Carbon|string $createdAt */
            $createdAt = 'N/A';
            if ($goodsReceivedNote->created_at) {
                /** @var Carbon $createdAt */
                $createdAt = Carbon::createFromFormat('Y-m-d H:i:s', (string) $goodsReceivedNote->created_at);
                $createdAt = $createdAt->format('d-m-Y h:i:s A');
            }

            return [
                'date' => $createdAt,
                'location' => $location->name . ' (' . LocationTypes::getFormattedCaseName(
                    $location->type_id
                ) . ')',
                'vendor' => $vendor->name ?? 'N/A',
                'grn_reference' => $goodsReceivedNote->grn_reference,
                'purchase_order_reference' => $goodsReceivedNote->purchase_order_reference,
                'delivery_order_reference' => $goodsReceivedNote->delivery_order_reference,
            ];
        });
    }

    public function headings(): array
    {
        return ['Date', 'Location', 'Vendor', 'Grn Reference', 'Purchase Order Reference', 'Delivery Order Reference'];
    }
}
