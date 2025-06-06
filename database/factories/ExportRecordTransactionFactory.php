<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Common\Enums\ModelMapping;
use App\Models\Admin;
use App\Models\ExportRecordTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExportRecordTransaction>
 */
class ExportRecordTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'downloaded_by_type' => ModelMapping::ADMIN->name,
            'downloaded_by_id' => fn () => Admin::factory()->create()->id,
        ];
    }
}
