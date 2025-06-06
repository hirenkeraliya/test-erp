<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\Member;
use App\Models\MemberProductReview;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MemberProductReview>
 */
class MemberProductReviewFactory extends Factory
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
            'member_id' => fn () => Member::factory()->create()->id,
            'product_id' => fn () => Product::factory()->create()->id,
            'rating' => fake()->numberBetween(1, 5),
            'review' => fake()->text(),
        ];
    }
}
