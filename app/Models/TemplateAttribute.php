<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class TemplateAttribute extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['template_id', 'attribute_id'];
}
