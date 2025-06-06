<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\ImportRecord\Enums\ImportTypes;
use App\Domains\ImportRecord\Enums\Status;
use App\Models\Admin;
use App\Models\Company;
use App\Models\ImportRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ImportRecord>
 */
class ImportRecordFactory extends Factory
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
            'type_id' => array_rand(array_flip(array_column(ImportTypes::cases(), 'value'))),
            'header_columns' => [fake()->word],
            'status' => array_rand(array_flip(array_column(Status::cases(), 'value'))),
            'records_in_file' => random_int(0, 100),
            'records_imported' => random_int(0, 100),
            'records_failed' => random_int(0, 100),
            'created_by_id' => fn () => Admin::factory()->create()->id,
            'created_by_type' => ModelMapping::ADMIN->name,
            'module_id' => null,
            'module_type' => null,
        ];
    }
}
