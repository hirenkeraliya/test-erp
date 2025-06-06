<?php

declare(strict_types=1);

namespace App\Domains\ExternalProduct\Resources;

use App\Domains\ExternalProduct\Enums\ExternalProductStatuses;
use App\Models\ExternalProduct;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminExternalProductListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var ExternalProduct $externalProduct */
        $externalProduct = $this;

        /** @var Carbon $createdAt */
        $createdAt = $externalProduct->created_at;

        /** @var array $productDetails */
        $productDetails = $externalProduct->product_details;
        $senderCompanyName = isset($productDetails['sender_company']) && null !== $productDetails['sender_company'] ? $productDetails['sender_company']['name'] : 'N/A';

        return [
            'id' => $externalProduct->id,
            'product_name' => $externalProduct->product_name,
            'product_details' => $externalProduct->product_details,
            'upc' => $externalProduct->upc,
            'sender_company_name' => $senderCompanyName,
            'status' => ExternalProductStatuses::getFormattedCaseName($externalProduct->status),
            'status_id' => $externalProduct->status,
            'created_at' => $createdAt->format('d-m-Y h:i:s A'),
        ];
    }
}
