<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ManualNotificationMemberTypes extends Model
{
    use HasFactory;

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['manual_notification_id', 'member_type_id'];

    public function getTypeId(): int
    {
        return $this->member_type_id;
    }
}
