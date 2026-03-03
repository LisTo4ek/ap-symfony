<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateProviderBundle\Tests\Helper;

use App\Bundle\CurrencyRateProviderBundle\Src\Helper\DurationCalculator;
use PHPUnit\Framework\TestCase;

class DurationCalculatorTest extends TestCase
{
    /**
     * Test start() returns a float timestamp
     */
    public function testStartReturnsFloatTimestamp(): void
    {
        $start = DurationCalculator::start();

        $this->assertIsFloat($start);
        $this->assertGreaterThan(0, $start);
    }

    /**
     * Test elapsed() returns milliseconds as integer
     */
    public function testElapsedReturnsMilliseconds(): void
    {
        $start = DurationCalculator::start();
        usleep(100000); // Sleep 100ms
        $elapsed = DurationCalculator::elapsed($start);

        $this->assertIsInt($elapsed);
        $this->assertGreaterThanOrEqual(100, $elapsed);
    }

    /**
     * Test elapsed time is accurate
     */
    public function testElapsedTimeAccuracy(): void
    {
        $start = DurationCalculator::start();
        usleep(500000); // Sleep 500ms
        $elapsed = DurationCalculator::elapsed($start);

        // Allow some margin for timing variance
        $this->assertGreaterThanOrEqual(450, $elapsed);
        $this->assertLessThan(700, $elapsed);
    }

    /**
     * Test measure() returns both result and duration
     */
    public function testMeasureReturnsResultAndDuration(): void
    {
        $result = DurationCalculator::measure(function () {
            usleep(100000); // Sleep 100ms
            return 'test_result';
        });

        $this->assertIsArray($result);
        $this->assertArrayHasKey('result', $result);
        $this->assertArrayHasKey('duration_ms', $result);

        $this->assertEquals('test_result', $result['result']);
        $this->assertIsInt($result['duration_ms']);
        $this->assertGreaterThanOrEqual(100, $result['duration_ms']);
    }

    /**
     * Test measure() with callable returning value
     */
    public function testMeasureWithCallableValue(): void
    {
        $callable = fn() => 42;

        $result = DurationCalculator::measure($callable);

        $this->assertEquals(42, $result['result']);
        $this->assertIsInt($result['duration_ms']);
    }

    /**
     * Test measure() captures execution exceptions
     */
    public function testMeasureWithExceptionThrowingCallable(): void
    {
        $callable = function () {
            throw new \RuntimeException('Test error');
        };

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Test error');

        DurationCalculator::measure($callable);
    }

    /**
     * Test multiple measurements are independent
     */
    public function testMultipleMeasurementsIndependent(): void
    {
        $duration1 = DurationCalculator::measure(function () {
            usleep(50000); // 50ms
            return 1;
        });

        $duration2 = DurationCalculator::measure(function () {
            usleep(150000); // 150ms
            return 2;
        });

        // Second should be longer
        $this->assertLessThan($duration2['duration_ms'], $duration1['duration_ms']);
    }

    /**
     * Test elapsed with very short duration
     */
    public function testElapsedWithVeryShortDuration(): void
    {
        $start = DurationCalculator::start();
        $elapsed = DurationCalculator::elapsed($start);

        // Should be at least 0ms
        $this->assertGreaterThanOrEqual(0, $elapsed);
        $this->assertLessThan(50, $elapsed);
    }

    /**
     * Test elapsed is zero or positive
     */
    public function testElapsedAlwaysNonNegative(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $start = DurationCalculator::start();
            $elapsed = DurationCalculator::elapsed($start);

            $this->assertGreaterThanOrEqual(0, $elapsed);
        }
    }
}

