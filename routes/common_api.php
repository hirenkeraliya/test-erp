<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Common\CityController;
use App\Http\Controllers\Api\Common\CountryController;
use App\Http\Controllers\Api\Common\CurrencyController;
use App\Http\Controllers\Api\Common\MemberController;
use App\Http\Controllers\Api\Common\SiteConfigurationController;
use App\Http\Controllers\Api\Common\StateController;
use Illuminate\Support\Facades\Route;

Route::controller(MemberController::class)->group(function (): void {
    Route::get('get-mobile-number-regex', 'getMobileNumberRegex');
});

Route::get('get-current-date-time', fn (): string => now()->format('Y-m-d H:i:s'))
    ->name('get_current_date_time');

Route::controller(SiteConfigurationController::class)->group(function (): void {
    Route::get('get-site-configuration', 'getSiteConfiguration');
});

Route::controller(CurrencyController::class)->group(function (): void {
    Route::get('get-currency-symbol', 'getCurrencySymbol');
});

Route::controller(CountryController::class)->group(function (): void {
    Route::get('get-all-countries', 'getAllCountries');
});

Route::controller(StateController::class)->group(function (): void {
    Route::get('get-all-states', 'getAllStates');
});

Route::controller(CityController::class)->group(function (): void {
    Route::get('get-all-cities', 'getAllCities');
});
