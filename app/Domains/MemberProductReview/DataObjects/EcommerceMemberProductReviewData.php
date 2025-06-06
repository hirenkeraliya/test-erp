<?php

declare(strict_types=1);

namespace App\Domains\MemberProductReview\DataObjects;

use Spatie\LaravelData\Data;

class EcommerceMemberProductReviewData extends Data
{
    public function __construct(
        public int $product_id,
        public int $customer_id,
        public string $review,
        public int $rating,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'product_id' => ['required', 'integer'],
            'customer_id' => ['required', 'integer'],
            'review' => ['required', 'string', 'max:1000'],
            'rating' => ['required', 'integer', 'between:1,5'],
        ];
    }
}
