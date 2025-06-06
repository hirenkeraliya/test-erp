<?php

declare(strict_types=1);

namespace App\Domains\LoyaltyPointUpdate\Jobs;

use App\Domains\LoyaltyPoint\LoyaltyPointQueries;
use App\Domains\LoyaltyPointUpdate\Enums\LoyaltyPointUpdateTypes;
use App\Domains\LoyaltyPointUpdate\LoyaltyPointUpdateQueries;
use App\Domains\Member\MemberQueries;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class LoyaltyPointExpirationJob implements ShouldQueueAfterCommit
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::channel('loyalty_point_expiration')->info('loyalty_point_expiration', [
            'Start time of the Loyalty Point Expiration job.: ' . Carbon::now()->format('Y-m-d H:i:s'),
        ]);

        DB::beginTransaction();

        try {
            $now = Carbon::now()->format('Y-m-d');

            $loyaltyPointUpdateQueries = resolve(LoyaltyPointUpdateQueries::class);
            $loyaltyPointQueries = resolve(LoyaltyPointQueries::class);
            $loyaltyPoints = $loyaltyPointQueries->getLoyaltyPointsDueForExpiry($now);

            foreach ($loyaltyPoints as $loyaltyPoint) {
                $availablePoints = $loyaltyPoint->available_points;

                $userLoyaltyPoints = 0;

                if (! $loyaltyPoint->member_id) {
                    continue;
                }

                $memberQueries = resolve(MemberQueries::class);
                $member = $memberQueries->getLoyaltyPointsById($loyaltyPoint->member_id);
                $userLoyaltyPoints = $member->loyalty_points;
                $memberQueries->decreaseExpiredLoyaltyPoints($member, $availablePoints);

                $loyaltyPointUpdateQueries->addNew([
                    'member_id' => $loyaltyPoint->member_id,
                    'loyalty_point_id' => $loyaltyPoint->id,
                    'affected_by_id' => null,
                    'affected_by_type' => null,
                    'type_id' => LoyaltyPointUpdateTypes::EXPIRED->value,
                    'points' => (int) ('-' . $availablePoints),
                    'closing_loyalty_points_balance' => (int) ($userLoyaltyPoints - $availablePoints),
                    'happened_at' => Carbon::now()->format('Y-m-d H:i:s'),
                ]);

                $loyaltyPointQueries->decreaseLoyaltyPointsToZero($loyaltyPoint);
            }

            DB::commit();

            Log::channel('loyalty_point_expiration')->info('loyalty_point_expiration', [
                $loyaltyPoints->count() . ' loyalty point expiration entries have been added in the loyalty point updates..',
            ]);
        } catch (Throwable $throwable) {
            DB::rollBack();

            Log::error('Loyalty point expiration job error', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            $this->fail($throwable);
        }

        Log::channel('loyalty_point_expiration')->info('loyalty_point_expiration', [
            'End time of the Loyalty Point Expiration job: ' . Carbon::now()->format('Y-m-d H:i:s'),
        ]);
    }
}
