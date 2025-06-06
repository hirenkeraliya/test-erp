<?php

declare(strict_types=1);

namespace App\Domains\Media;

use App\Domains\Common\Enums\ModelMapping;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaQueries
{
    public static function getBasicColumnNames(): string
    {
        return 'id,model_type,model_id,uuid,collection_name,name,file_name,mime_type,disk,generated_conversions,order_column';
    }

    public function updateMemberMedia(int $oldMemberId, int $newMemberId): void
    {
        $media = Media::query()
            ->select('id', 'model_id', 'model_type')
            ->where('model_id', $oldMemberId)
            ->where('model_type', ModelMapping::MEMBER->name)
            ->first();

        if (! $media) {
            return;
        }

        $media->model_id = $newMemberId;
        $media->save();
    }

    public function updateProductIdWithMedia(int $oldProductId, int $newProductId): void
    {
        $medias = Media::query()
            ->select('id', 'model_id', 'model_type')
            ->where('model_id', $oldProductId)
            ->where('model_type', ModelMapping::PRODUCT->name)
            ->get();

        if ($medias->isEmpty()) {
            return;
        }

        foreach ($medias as $media) {
            $media->model_id = $newProductId;
            $media->save();
        }
    }
}
