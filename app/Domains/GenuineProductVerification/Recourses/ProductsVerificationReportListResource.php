<?php

declare(strict_types=1);

namespace App\Domains\GenuineProductVerification\Recourses;

use App\Domains\Product\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductsVerificationReportListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $genuineProductVerification = $this->resource;

        $product = $genuineProductVerification->product;

        $productService = resolve(ProductService::class);

        return [
            'name' => $genuineProductVerification->name,
            'mobile_number' => $genuineProductVerification->mobile_number,
            'email' => $genuineProductVerification->email,
            'product_name' => $product ? $product->name : 'N/A',
            'upc' => $product ? $product->upc : 'N/A',
            'color' => config('app.product_variant') ? 'N/A' : $product->color?->name ?? 'N/A',
            'size' => config('app.product_variant') ? 'N/A' : $product->size?->name ?? 'N/A',
            'is_genuine' => $genuineProductVerification->is_genuine ? 'Genuine' : 'Fake',
            'qr_code' => $genuineProductVerification->qr_code,
            'receipt_number' => $genuineProductVerification->receipt_number,
            'created_at' => $genuineProductVerification->created_at->format('d-m-Y D h:s:i A'),
            'remarks' => $genuineProductVerification->remarks,
            'attributes' => $productService->getAttributesWithNameAndValueKey($product),
        ];
    }
}
