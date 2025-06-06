<?php

declare(strict_types=1);

namespace App\Domains\PromoterCommission\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PromoterCommissionListApiCollectionResource extends ResourceCollection
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
            fn ($item): array => (new PromoterCommissionListApiResource(
                $item,
                $this->currencySymbol
            ))->toArray($request)
        )->all();
    }
}
