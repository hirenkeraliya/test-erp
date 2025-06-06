<?php

declare(strict_types=1);

namespace App\Domains\GoodsReceivedNote\Resources;

use App\Domains\Location\Enums\LocationTypes;
use App\Models\GoodsReceivedNote;
use App\Models\Location;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GoodsReceivedNoteListApiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var GoodsReceivedNote $goodsReceivedNote */
        $goodsReceivedNote = $this;

        /** @var Vendor $vendor */
        $vendor = $goodsReceivedNote->getVendor();

        /** @var Location $location */
        $location = $goodsReceivedNote->getLocation();

        /** @var Carbon $date */
        $date = $goodsReceivedNote->created_at;

        $locationType = LocationTypes::getFormattedCaseName($location->type_id);

        return [
            'id' => $goodsReceivedNote->id,
            'grn_reference' => $goodsReceivedNote->grn_reference,
            'purchase_order_reference' => $goodsReceivedNote->purchase_order_reference,
            'delivery_order_reference' => $goodsReceivedNote->delivery_order_reference,
            'notes' => $goodsReceivedNote->notes,
            'created_at' => $date->format('d-m-Y h:i:s A'),
            'vendor' => $vendor->name ?? 'N/A',
            'location' => $location->name . ' (' . $locationType . ')',
        ];
    }
}
