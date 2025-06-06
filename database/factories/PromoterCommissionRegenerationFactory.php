<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Admin;
use App\Models\PromoterCommissionRegeneration;
use App\Models\SuperAdmin;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PromoterCommissionRegeneration>
 */
class PromoterCommissionRegenerationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'period' => Carbon::now()->toDateString(),
            'admin_id' => fn () => Admin::factory()->create()->id,
            'super_admin_id' => fn () => SuperAdmin::factory()->create()->id,
            'started_at' => now(),
            'completed_at' => now(),
        ];
    }
}
