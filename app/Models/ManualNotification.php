<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ManualNotification extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'message',
        'status',
        'type_id',
        'member_filter_type_id',
        'promoter_filter_type_id',
        'company_id',
    ];

    public function promoters(): BelongsToMany
    {
        return $this->belongsToMany(Promoter::class);
    }

    public function promoterGroups(): BelongsToMany
    {
        return $this->belongsToMany(PromoterGroup::class);
    }

    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class);
    }

    public function memberGroups(): BelongsToMany
    {
        return $this->belongsToMany(MemberGroup::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Member::class);
    }

    public function memberTypes(): HasMany
    {
        return $this->hasMany(ManualNotificationMemberTypes::class);
    }
}
