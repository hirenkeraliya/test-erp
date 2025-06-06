<?php

declare(strict_types=1);

namespace App\Domains\Promoter\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PromoterSalesListCollectionResource extends ResourceCollection
{
    public function __construct(
        $resource,
        protected string $currencySymbol
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
            fn ($item): array => (new PromoterSalesListResource($item, $this->currencySymbol))->toArray($request)
        )->all();
    }
}
