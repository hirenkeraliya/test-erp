<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Common\Enums\ModelMapping;
use App\Models\Admin;
use App\Models\Company;
use App\Models\Notification;
use App\Models\StoreManager;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Notification>
 */
class NotificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => fn () => Company::factory()->create()->id,
            'from_user_id' => fn () => StoreManager::factory()->create()->id,
            'from_user_type' => ModelMapping::STORE_MANAGER->name,
            'to_user_id' => fn () => Admin::factory()->create()->id,
            'to_user_type' => ModelMapping::ADMIN->name,
            'message' => fake()->text(),
            'title' => fake()->sentence(),
        ];
    }

    public function markAsRead(): static
    {
        return $this->state(fn (): array => [
            'mark_as_read_at' => fake()->dateTime(),
            'mark_as_read_by_id' => Admin::factory()->create()->id,
            'mark_as_read_by_type' => ModelMapping::ADMIN->name,
        ]);
    }
}
