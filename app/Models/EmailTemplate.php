<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmailTemplate extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'usage', 'clicks', 'revenue', 'conversion', 'template_json', 'html'];
}
