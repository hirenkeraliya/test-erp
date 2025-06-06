<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class MembershipAssignment extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['membership_id', 'member_id', 'happened_at'];
}
