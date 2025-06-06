<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\City;
use App\Models\MemberAddress;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateMemberAddressLocations extends Command
{
    protected $signature = 'member-addresses:migrate-locations';

    protected $description = 'Migrate existing member addresses to use new location fields';

    public function handle(): void
    {
        $this->info('Starting member address location migration...');

        $addresses = MemberAddress::whereNull('city_id')->get();
        $totalUpdated = 0;

        DB::beginTransaction();
        try {
            foreach ($addresses as $address) {
                if (! $address->city_name) {
                    continue;
                }

                // Try to find matching city
                $city = City::where('name', 'like', '%' . $address->city_name . '%')
                    ->first();

                if ($city) {
                    $address->update([
                        'city_id' => $city->id,
                        'state_id' => $city->state_id,
                        'country_id' => $city->country_id,
                    ]);
                    $totalUpdated++;
                }
            }

            DB::commit();
            $this->info(sprintf('Successfully migrated %d addresses', $totalUpdated));
        } catch (Exception $exception) {
            DB::rollBack();
            $this->error('Error during migration: ' . $exception->getMessage());
        }
    }
}
