<?php

declare(strict_types=1);

namespace App\Domains\ProductCollection\Resources;

use App\Domains\ImportRecord\Enums\Status;
use App\Models\ImportRecord;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class ProductCollectionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $productCollection = $this->resource;

        /** @var ?ImportRecord $importRecord */
        $importRecord = $productCollection->importRecord;

        /** @var Collection $productCollectionProducts */
        $productCollectionProducts = $productCollection->productCollectionProducts;

        return [
            'id' => $productCollection->id,
            'name' => $productCollection->name,
            'items' => $productCollectionProducts->where('is_synced', true)->count(),
            'pending_products' => $productCollectionProducts->where('is_synced', false)->count(),
            'logical_connector_type' => $productCollection->logical_connector_type_id->name,
            'last_sync' => $productCollection->last_sync_at,
            'status' => $productCollection->status,
            'created_by' => $productCollection->createdBy->employee->getFullName(),
            'upload_status' => $importRecord instanceof ImportRecord ? Status::getFormattedCaseName(
                $importRecord->status
            ) : 'N/A',
            'total_records' => $importRecord instanceof ImportRecord ? $importRecord->records_in_file : null,
            'total_records_imported' => $importRecord instanceof ImportRecord ? $importRecord->records_imported : null,
            'total_records_failed' => $importRecord instanceof ImportRecord ? $importRecord->records_failed : null,
        ];
    }
}
