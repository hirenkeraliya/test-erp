<?php

declare(strict_types=1);

namespace App\Domains\GenuineReceiptVerification\Recourses;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReceiptVerificationReportListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $genuineReceiptVerification = $this->resource;

        return [
            'name' => $genuineReceiptVerification->name ?? 'N/A',
            'mobile_number' => $genuineReceiptVerification->mobile_number ?? 'N/A',
            'email' => $genuineReceiptVerification->email ?? 'N/A',
            'is_genuine' => $genuineReceiptVerification->is_genuine ? 'Genuine' : 'Fake',
            'receipt_number' => $genuineReceiptVerification->receipt_number,
            'created_at' => $genuineReceiptVerification->created_at->format('d-m-Y D h:s:i A'),
            'remarks' => $genuineReceiptVerification->remarks,
        ];
    }
}
