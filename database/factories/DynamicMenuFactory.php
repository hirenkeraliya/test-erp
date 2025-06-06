<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\DynamicMenus\Enums\DynamicMenuTypesEnum;
use App\Models\Company;
use App\Models\DynamicMenu;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DynamicMenu>
 */
class DynamicMenuFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'company_id' => fn () => Company::factory()->create()->id,
            'parent_id' => null,
            'title' => fake()->name(),
            'slug' => fake()->slug(),
            'type' => DynamicMenuTypesEnum::BRAND->value,
            'module_id' => 1,
            'content' => 'content',
        ];
    }
}
