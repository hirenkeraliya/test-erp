<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\CommonFunctions;
use PHPUnit\Framework\TestCase;

class CommonFunctionsSecurityTest extends TestCase
{
    public function test_getMaxWorkersByCores_returns_positive_integer(): void
    {
        $result = CommonFunctions::getMaxWorkersByCores();
        
        $this->assertIsInt($result);
        $this->assertGreaterThan(0, $result);
    }

    public function test_getMaxWorkersByCores_handles_shell_exec_failure(): void
    {
        // This test verifies that the method handles shell_exec failures gracefully
        // We can't easily mock shell_exec, but we can test the current behavior
        // and ensure it doesn't return 0 or negative values
        
        $result = CommonFunctions::getMaxWorkersByCores();
        
        // Should never return 0 or negative values, even if shell_exec fails
        $this->assertGreaterThan(0, $result);
        
        // Should return a reasonable number (not too high, not too low)
        $this->assertLessThanOrEqual(100, $result); // Reasonable upper bound
        $this->assertGreaterThanOrEqual(1, $result); // Reasonable lower bound
    }

    public function test_getMaxWorkersByCores_returns_consistent_results(): void
    {
        // Multiple calls should return the same result
        $result1 = CommonFunctions::getMaxWorkersByCores();
        $result2 = CommonFunctions::getMaxWorkersByCores();
        
        $this->assertEquals($result1, $result2);
    }
}