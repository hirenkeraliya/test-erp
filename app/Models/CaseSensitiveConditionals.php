<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

trait CaseSensitiveConditionals
{
    public function scopeWhereCaseSensitive(Builder $query, string $column, string $record): void
    {
        $query->where(DB::raw('BINARY ' . $column), $record);
    }

    public function scopeWhereInCaseSensitive(Builder $query, string $column, array $records): void
    {
        $query->whereIn(DB::raw('BINARY ' . $column), $records);
    }

    public function scopeWhereNotCaseSensitive(Builder $query, string $column, string $record): void
    {
        $query->whereNot(DB::raw('BINARY ' . $column), $record);
    }
}
