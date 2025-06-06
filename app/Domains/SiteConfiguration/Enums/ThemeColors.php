<?php

declare(strict_types=1);

namespace App\Domains\SiteConfiguration\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum ThemeColors: string
{
    use PrepareEnumDataMethods;
    case LIGHT_PURPLE = 'bg-indigo-500';
    case DARK_PURPLE = 'bg-indigo-900';
    case PINK = 'bg-pink-700';
    case BLUE = 'bg-blue-800';
    case DARK_BLUE = 'bg-blue-900';
    case EMERALD = 'bg-emerald-900';
    case FUCHSIA = 'bg-fuchsia-900';
    case BLACK = 'bg-slate-900';
    case AMARANTH_DEEP_PURPLE = 'bg-amaranth-deep-purple';
    case RUDDY_BROWN = 'bg-ruddy-brown';

    public static function getHexColor(string $value): string
    {
        $colorMapping = [
            'bg-indigo-500' => '#6166F0',
            'bg-indigo-900' => '#312E81',
            'bg-pink-700' => '#BB1A5F',
            'bg-blue-800' => '#1E42B0',
            'bg-blue-900' => '#1E3B8A',
            'bg-emerald-900' => '#064F3E',
            'bg-slate-900' => '#0F192F',
            'bg-fuchsia-900' => '#6E1C76',
            'bg-amaranth-deep-purple' => '#ae2573',
            'bg-ruddy-brown' => '#c7642d',
        ];

        return $colorMapping[$value] ?? '';
    }
}
