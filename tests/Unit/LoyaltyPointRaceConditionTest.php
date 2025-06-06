<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domains\LoyaltyPoint\LoyaltyPointQueries;
use App\Domains\LoyaltyPoint\Services\LoyaltyPointService;
use App\Domains\LoyaltyPointUpdate\Jobs\LoyaltyPointExpirationJob;
use App\Models\LoyaltyPoint;
use App\Models\Member;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LoyaltyPointRaceConditionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test race condition between point redemption and expiration
     * This test demonstrates the issue where concurrent operations can lead to negative balances
     */
    public function test_race_condition_between_redemption_and_expiration(): void
    {
        // Create a member with loyalty points
        $member = Member::factory()->create(['loyalty_points' => 100]);
        
        // Create a loyalty point record that's about to expire
        $loyaltyPoint = LoyaltyPoint::factory()->create([
            'member_id' => $member->id,
            'points' => 100,
            'available_points' => 100,
            'expiry_date' => Carbon::now()->subDay(), // Already expired
        ]);

        // Simulate concurrent operations
        $loyaltyPointQueries = resolve(LoyaltyPointQueries::class);
        $loyaltyPointService = resolve(LoyaltyPointService::class);

        // Start a transaction to simulate the redemption process
        DB::beginTransaction();
        
        try {
            // Simulate redemption process reading the loyalty point
            $loyaltyPointForRedemption = LoyaltyPoint::find($loyaltyPoint->id);
            $this->assertEquals(100, $loyaltyPointForRedemption->available_points);
            
            // Simulate expiration job running concurrently (in another transaction)
            // This would normally happen in a separate process/queue worker
            $this->simulateExpirationJob($loyaltyPoint->id);
            
            // Now continue with redemption - this should detect the race condition
            // The available_points should now be 0 due to expiration, but the redemption
            // process still thinks it has 100 points available
            $loyaltyPointService->decreaseLoyaltyPointsByFirstExpiryFirstOut(
                $member,
                100, // user's current loyalty points
                50,  // points to redeem
                1,   // type_id
                1,   // affected_by_id
                'MEMBER',
                Carbon::now()->format('Y-m-d H:i:s'),
                'Test redemption'
            );
            
            DB::commit();
            
            // Check final state - this might show inconsistent data
            $finalLoyaltyPoint = LoyaltyPoint::find($loyaltyPoint->id);
            $finalMember = Member::find($member->id);
            
            // This assertion might fail due to race condition
            // The available_points could be negative or inconsistent
            $this->assertGreaterThanOrEqual(0, $finalLoyaltyPoint->available_points, 
                'Available points should never be negative due to race condition');
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Simulate the expiration job running concurrently
     */
    private function simulateExpirationJob(int $loyaltyPointId): void
    {
        // This simulates what happens in LoyaltyPointExpirationJob
        // In a real scenario, this would run in a separate transaction/process
        $loyaltyPoint = LoyaltyPoint::find($loyaltyPointId);
        
        if ($loyaltyPoint && $loyaltyPoint->available_points > 0) {
            // This is the problematic operation - it doesn't check for concurrent modifications
            $loyaltyPoint->available_points = 0;
            $loyaltyPoint->save();
            
            // Also update member's total loyalty points
            $member = Member::find($loyaltyPoint->member_id);
            $member->loyalty_points -= $loyaltyPoint->available_points;
            $member->save();
        }
    }

    /**
     * Test that demonstrates the specific race condition in decreasePoints method
     */
    public function test_decrease_points_race_condition(): void
    {
        $loyaltyPoint = LoyaltyPoint::factory()->create([
            'points' => 100,
            'available_points' => 100,
        ]);

        $loyaltyPointQueries = resolve(LoyaltyPointQueries::class);

        // Simulate two concurrent operations trying to decrease points
        $originalPoints = $loyaltyPoint->available_points;
        
        // First operation reads the current value
        $loyaltyPoint1 = LoyaltyPoint::find($loyaltyPoint->id);
        $this->assertEquals(100, $loyaltyPoint1->available_points);
        
        // Second operation also reads the same value (race condition)
        $loyaltyPoint2 = LoyaltyPoint::find($loyaltyPoint->id);
        $this->assertEquals(100, $loyaltyPoint2->available_points);
        
        // Both operations modify and save
        $loyaltyPointQueries->decreasePoints($loyaltyPoint1, 60); // Should result in 40
        $loyaltyPointQueries->decreasePoints($loyaltyPoint2, 50); // Should result in 50, but will overwrite
        
        // Check final state - this demonstrates the lost update problem
        $finalLoyaltyPoint = LoyaltyPoint::find($loyaltyPoint->id);
        
        // The final result should be 100 - 60 - 50 = -10 or 100 - 50 = 50
        // But due to race condition, it might be 50 (lost update) or -10 (negative balance)
        $this->assertNotEquals($originalPoints - 60 - 50, $finalLoyaltyPoint->available_points,
            'Race condition causes lost updates in decreasePoints method');
    }
}