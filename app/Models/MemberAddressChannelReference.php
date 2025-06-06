<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class MemberAddressChannelReference extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['sale_channel_id', 'member_address_id', 'external_member_address_id'];
}
