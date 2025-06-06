<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AttachedTemplate extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['template_id', 'model_id', 'model_type'];

    public function product(): MorphTo
    {
        return $this->morphTo();
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }
}
