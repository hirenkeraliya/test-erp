<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmployeeTransaction extends Model
{
    use HasFactory;

    protected $fillable = ['employee_id', 'status', 'user_id', 'user_type'];
}
