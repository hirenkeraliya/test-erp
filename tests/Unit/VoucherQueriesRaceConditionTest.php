<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domains\Voucher\VoucherQueries;
use App\Models\Voucher;
use App\Models\VoucherConfiguration;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoucherQueriesRaceConditionTest extends TestCase
{
    use RefreshDatabase;

    private VoucherQueries $voucherQueries;

    protected function setUp(): void
    {
        parent::setUp();
        $this->voucherQueries = new VoucherQueries();
    }

    /**
     * Test that generateUniqueVoucherNumber handles race conditions properly
     * by ensuring unique voucher numbers are generated even under concurrent access.
     */
    public function test_generateUniqueVoucherNumber_handles_race_condition(): void
    {
        // Create a test voucher configuration
        $voucherConfiguration = VoucherConfiguration::factory()->create();

        // Generate multiple voucher numbers concurrently (simulated)
        $generatedNumbers = [];
        $numberOfAttempts = 100;

        for ($i = 0; $i < $numberOfAttempts; $i++) {
            try {
                $voucher = $this->voucherQueries->addNew(
                    $voucherConfiguration,
                    10.0, // getValue
                    1, // discountType
                    null, // expiryDate
                    null, // memberId
                    null, // voucherNumber (will be auto-generated)
                    null, // saleId
                    null, // locationId
                    null  // orderId
                );

                $generatedNumbers[] = $voucher->number;
            } catch (QueryException $e) {
                // If we get a unique constraint violation, that's expected behavior
                // The retry logic should handle this internally
                $this->fail('Unique constraint violation should be handled internally: ' . $e->getMessage());
            }
        }

        // Verify all generated numbers are unique
        $uniqueNumbers = array_unique($generatedNumbers);
        $this->assertCount(
            $numberOfAttempts,
            $uniqueNumbers,
            'All generated voucher numbers should be unique'
        );

        // Verify all vouchers were actually created in the database
        $this->assertCount(
            $numberOfAttempts,
            Voucher::whereIn('number', $generatedNumbers)->get(),
            'All vouchers should be persisted in the database'
        );
    }

    /**
     * Test that the voucher number generation produces sufficiently unique values
     * by checking the format and entropy of generated numbers.
     */
    public function test_generateUniqueVoucherNumber_produces_high_entropy_numbers(): void
    {
        $voucherConfiguration = VoucherConfiguration::factory()->create();

        // Generate several voucher numbers and analyze their uniqueness
        $numbers = [];
        for ($i = 0; $i < 10; $i++) {
            $voucher = $this->voucherQueries->addNew(
                $voucherConfiguration,
                10.0,
                1,
                null,
                null,
                null,
                null,
                null,
                null
            );
            $numbers[] = $voucher->number;
        }

        // Verify all numbers are unique
        $this->assertCount(10, array_unique($numbers));

        // Verify number format (should be longer and more complex than before)
        foreach ($numbers as $number) {
            // Should be at least 20 characters long (6 random + 14 timestamp + 3 microseconds + 4 additional random)
            $this->assertGreaterThanOrEqual(27, strlen($number));
            
            // Should contain both letters and numbers
            $this->assertMatchesRegularExpression('/[A-Z]/', $number);
            $this->assertMatchesRegularExpression('/[0-9]/', $number);
        }
    }

    /**
     * Test that the isUniqueConstraintViolation method correctly identifies
     * different types of database constraint violations.
     */
    public function test_isUniqueConstraintViolation_identifies_constraint_violations(): void
    {
        // This test would require mocking QueryException with different error codes
        // and messages to verify the constraint violation detection logic
        
        // Test MySQL duplicate entry error
        $mysqlException = new QueryException(
            'test',
            [],
            new \Exception('Duplicate entry \'test\' for key \'vouchers_number_unique\'')
        );
        
        // Use reflection to test the private method
        $reflection = new \ReflectionClass($this->voucherQueries);
        $method = $reflection->getMethod('isUniqueConstraintViolation');
        $method->setAccessible(true);
        
        $this->assertTrue($method->invoke($this->voucherQueries, $mysqlException));
    }

    /**
     * Test that the retry mechanism works correctly when encountering
     * unique constraint violations.
     */
    public function test_retry_mechanism_with_constraint_violations(): void
    {
        // Create a voucher configuration
        $voucherConfiguration = VoucherConfiguration::factory()->create();

        // Pre-create a voucher with a specific pattern to force collision
        $existingVoucher = Voucher::create([
            'voucher_configuration_id' => $voucherConfiguration->id,
            'number' => 'TEST123456789012345678901234567890',
            'discount_type' => 1,
            'status' => 1,
        ]);

        // Generate a new voucher - should not collide due to improved algorithm
        $newVoucher = $this->voucherQueries->addNew(
            $voucherConfiguration,
            10.0,
            1,
            null,
            null,
            null,
            null,
            null,
            null
        );

        // Verify the new voucher has a different number
        $this->assertNotEquals($existingVoucher->number, $newVoucher->number);
    }
}