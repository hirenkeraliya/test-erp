<?php

declare(strict_types=1);

namespace App\Domains\Product\Services;

use App\Domains\PosModules\Services\PosModuleZipService;
use App\Domains\Product\Resources\PosProductListResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;

class PosProductExportZipService
{
    public function productExportWithJson(Collection $productCollections, int $index): void
    {
        $resourceConverter = fn (Collection $collection): AnonymousResourceCollection => PosProductListResource::collection(
            $collection
        );

        $posModuleZipService = resolve(PosModuleZipService::class);
        $posModuleZipService->exportWithJsonGroupByCompanyId(
            $productCollections,
            $index,
            'products',
            $resourceConverter
        );
    }
}
