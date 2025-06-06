<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class MemberGroup extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'name',
        'code',
        'type_id',
        'smart_group_type_id',
        'date_condition_type_id',
        'element_condition_type_id',
        'number_condition_type_id',
        'date',
        'max_date',
        'value',
        'max_value',
        'members_count',
        'created_by_id',
        'created_by_type',
    ];

    public function memberGroupMembers(): HasMany
    {
        return $this->hasMany(MemberGroupMember::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function importRecord(): MorphOne
    {
        return $this->morphOne(ImportRecord::class, 'module')->latest();
    }

    public function memberGroupChannelReferences(): HasMany
    {
        return $this->hasMany(MemberGroupChannelReference::class);
    }
}
