<?php

declare(strict_types=1);

namespace App\Domains\Sequence;

use App\Models\Sequence;
use Illuminate\Support\Facades\DB;

class SequenceQueries
{
    public function addNew(?int $locationId, int $typeId): Sequence
    {
        $sequence = Sequence::updateOrCreate(
            [
                'location_id' => $locationId ?? null,
                'type_id' => $typeId,
            ],
            [
                'number' => DB::raw('number + 1'),
            ]
        );

        return $sequence->refresh()->load('location:' . $this->getCodeColumnName());
    }

    private function getCodeColumnName(): string
    {
        return 'id,code';
    }
}
