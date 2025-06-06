<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Common\Enums\ModelMapping;
use App\Models\AttachedTemplate;
use App\Models\Product;
use App\Models\Template;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttachedTemplate>
 */
class AttachedTemplateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'model_id' => Product::factory(),
            'model_type' => ModelMapping::PRODUCT->name,
            'template_id' => fn () => Template::factory()->create()->id,
        ];
    }
}
