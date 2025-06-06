<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Common\Enums\ModelMapping;
use App\Models\Admin;
use App\Models\Member;
use App\Models\MergeMemberTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MergeMemberTransaction>
 */
class MergeMemberTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'user_id' => fn () => Admin::factory()->create()->id,
            'user_type' => ModelMapping::ADMIN->name,
            'old_member_id' => fn () => Member::factory()->create()->id,
            'new_member_id' => fn () => Member::factory()->create()->id,
        ];
    }
}
