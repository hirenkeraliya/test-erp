<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Currency extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'country_id', 'name', 'code', 'precision', 'symbol', 'symbol_native', 'symbol_first', 'decimal_mark', 'thousands_separator',
    ];

    public function getSymbol(): string
    {
        return $this->symbol;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function currencyRate(): HasOne
    {
        return $this->hasOne(CurrencyRate::class);
    }
}
