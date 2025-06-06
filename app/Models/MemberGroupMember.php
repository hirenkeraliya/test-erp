<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\MemberGroupMember\Events\MemberGroupMemberCreateEvent;
use App\Domains\MemberGroupMember\Events\MemberGroupMemberDeleteEvent;
use App\Domains\MemberGroupMember\Events\MemberGroupMemberUpdateEvent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberGroupMember extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['member_id', 'member_group_id', 'is_synced'];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function memberGroup(): BelongsTo
    {
        return $this->belongsTo(MemberGroup::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::created(function ($memberGroupMember): void {
            event(new MemberGroupMemberCreateEvent($memberGroupMember));
        });

        static::updated(function ($memberGroupMember): void {
            event(new MemberGroupMemberUpdateEvent($memberGroupMember));
        });

        static::deleted(function ($memberGroupMember): void {
            event(new MemberGroupMemberDeleteEvent($memberGroupMember));
        });
    }
}
