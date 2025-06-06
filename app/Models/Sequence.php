<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\Sequence\Enums\SequenceTypes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sequence extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['location_id', 'type_id', 'number'];

    // can be Store OR Warehouse
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function getCompleteNumber(): string
    {
        /** @var Location $location */
        $location = $this->location;

        return $location->code . SequenceTypes::getCaseNameByValue($this->type_id) . $this->number;
    }

    public function getNumber(): string
    {
        return SequenceTypes::getCaseNameByValue($this->type_id) . $this->number;
    }
}
