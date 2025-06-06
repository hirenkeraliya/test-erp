<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Country;
use App\Models\State;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Nnjeim\World\Actions\SeedAction;

class WorldSeeder extends Seeder
{
    public function run(): void
    {
        // In SeedAction class run then first truncate countries table that's why we add this code
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        $this->call([SeedAction::class]);

        $countries = Country::query()->whereDoesntHave('states')->get();

        foreach ($countries as $country) {
            $state = State::query()->create([
                'name' => 'No State',
                'country_id' => $country->id,
                'country_code' => $country->iso2,
            ]);

            City::query()->create([
                'name' => 'No City',
                'state_id' => $state->id,
                'country_id' => $country->id,
                'country_code' => $country->iso2,
            ]);
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
