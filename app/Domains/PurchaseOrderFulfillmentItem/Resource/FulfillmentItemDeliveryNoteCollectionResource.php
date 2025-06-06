<?php

declare(strict_types=1);

namespace App\Domains\PurchaseOrderFulfillmentItem\Resource;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Collection;

class FulfillmentItemDeliveryNoteCollectionResource extends ResourceCollection
{
    public function __construct(
        $resource,
        protected Collection $externalLocationProductStocks
    ) {
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return $this->collection->map(
            fn ($item): array => (new FulfillmentItemDeliveryNoteResource(
                $item,
                $this->externalLocationProductStocks
            ))->toArray($request)
        )->all();
    }
}
