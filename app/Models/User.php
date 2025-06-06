<?php

declare(strict_types=1);

namespace App\Models;

use App\Notifications\ResetPassword;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\HasApiTokens;

class User extends Model
{
    use HasFactory;
    use HasApiTokens;
    use Notifiable;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['employee_id', 'username', 'type_id', 'password'];

    /**
     * @var array<int, string>
     */
    protected $hidden = ['password', 'remember_token'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Send a password reset notification to the user.
     */
    public function sendPasswordResetNotification(string $token): bool
    {
        /** @var Employee $employee */
        $employee = $this->employee;

        if (null !== $employee->email) {
            $url = route('admin.user_reset_password', [
                'token' => $token,
            ]);

            Notification::route('mail', [
                $employee->email => $employee->getFullName(),
            ])->notify(new ResetPassword($url));

            return true;
        }

        return false;
    }

    public function revokeCurrentToken(int $tokenId): void
    {
        $this->tokens()->where('id', $tokenId)->delete();
    }
}
