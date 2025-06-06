<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class CreditNoteVoidUse extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['credit_note_id', 'credit_note_uses_id', 'void_sale_id', 'amount'];
}
