<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Src\Service;

use function microtime;

/**
 * DurationCalculator provides utilities for measuring elapsed time in milliseconds
 */
class DurationCalculatorService
{
    /**
     * Get the current timestamp in microseconds
     *
     * @return float Current time as returned by microtime(true)
     */
    public static function start(): float
    {
        return microtime(true);
    }

    /**
     * Calculate the elapsed time in milliseconds from a start timestamp
     *
     * @param float $startTime The start timestamp from start()
     * @return int Elapsed time in milliseconds
     */
    public static function elapsed(float $startTime): int
    {
        return (int)((microtime(true) - $startTime) * 1000);
    }

    /**
     * Measure the duration of a callable and return both the result and duration
     *
     * @template T
     * @param callable(): T $callable The callable to measure
     * @return array{result: T, duration_ms: int} Array with 'result' and 'duration_ms' keys
     */
    public static function measure(callable $callable): array
    {
        $startTime = self::start();
        $result = $callable();
        $duration = self::elapsed($startTime);

        return [
            'result' => $result,
            'duration_ms' => $duration,
        ];
    }
}
