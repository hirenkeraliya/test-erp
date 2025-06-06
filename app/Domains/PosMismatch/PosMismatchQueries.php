<?php

declare(strict_types=1);

namespace App\Domains\PosMismatch;

use App\Domains\Common\Enums\ModelMapping;
use App\Models\Model;
use App\Models\PosMismatch;

class PosMismatchQueries
{
    public function addNew(Model $model, string $mismatchMessage): void
    {
        PosMismatch::create([
            'module_id' => $model->getKey(),
            'module_type' => ModelMapping::getCaseName($model::class),
            'message' => $mismatchMessage,
        ]);
    }

    public function getBasicColumnNames(): string
    {
        return 'id,module_id,module_type,message';
    }
}
