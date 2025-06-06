<?php

declare(strict_types=1);

namespace App\Domains\Vendor\Resources;

use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VendorListApiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Vendor $vendor */
        $vendor = $this;

        return [
            'id' => $vendor->id,
            'name' => $vendor->name,
        ];
    }
}
