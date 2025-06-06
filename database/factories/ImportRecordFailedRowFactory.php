<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ImportRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

class ImportRecordFailedRowFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'import_record_id' => fn () => ImportRecord::factory()->create()->id,
            'row_data' => [],
            'fail_reasons' => [],
        ];
    }
}
