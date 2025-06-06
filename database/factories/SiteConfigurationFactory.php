<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\SiteConfiguration\Enums\SiteConfigurationTypes;
use App\Domains\SiteConfiguration\Enums\ThemeColors;
use App\Models\SiteConfiguration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SiteConfiguration>
 */
class SiteConfigurationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'type_id' => SiteConfigurationTypes::getValues(),
            'value' => ThemeColors::getValues(),
        ];
    }
}
