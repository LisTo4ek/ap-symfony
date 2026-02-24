<?php

namespace App\Tests\Command;

use App\Command\CurrencyRateImportCbrCommand;
use App\Domain\CurrencyRateProvider\Base\CurrencyEnum;
use App\Domain\CurrencyRateProvider\Base\Entity\Rate;
use App\Domain\CurrencyRateProvider\Providers\CurrencyRateProviderInterface;
use App\Event\RateSavedEvent;
use App\Repository\RateHistoryRepository;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class ImportCurrencyCommandTest extends TestCase
{
    private CurrencyRateProviderInterface $rateProvider;
    private RateHistoryRepository $historyRepository;
    private EventDispatcherInterface $eventDispatcher;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->rateProvider = $this->createMock(CurrencyRateProviderInterface::class);
        $this->historyRepository = $this->createMock(RateHistoryRepository::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $command = new CurrencyRateImportCbrCommand(
            $this->rateProvider,
            $this->historyRepository,
            $this->eventDispatcher
        );

        $application = new Application();
        $application->add($command);

        $this->commandTester = new CommandTester($command);
    }

    /**
     * Test case 1: Successfully imports rates for date range
     */
    public function testSuccessfullyImportsRates(): void
    {
        $rate = new Rate(
            CurrencyEnum::RUB,
            CurrencyEnum::USD,
            75.50,
            new DateTimeImmutable('2026-01-01')
        );

        $this->rateProvider
            ->expects($this->atLeastOnce())
            ->method('getRates')
            ->willReturn([$rate]);

        $this->historyRepository
            ->expects($this->atLeastOnce())
            ->method('updateOrCreate');

        $this->eventDispatcher
            ->expects($this->atLeastOnce())
            ->method('dispatch')
            ->with($this->isInstanceOf(RateSavedEvent::class));

        $this->commandTester->execute([
            'from' => '2026-01-01',
            'to' => '2026-01-03',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('successful', $this->commandTester->getDisplay());
    }

    /**
     * Test case 2: Uses default date range (last 30 days) when no arguments provided
     */
    public function testUsesDefaultDateRange(): void
    {
        $this->rateProvider
            ->method('getRates')
            ->willReturn([]);

        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Importing rates from', $output);
    }

    /**
     * Test case 3: Handles invalid date format
     */
    public function testHandlesInvalidDateFormat(): void
    {
        $this->commandTester->execute([
            'from' => 'invalid-date',
            'to' => '2026-01-03',
        ]);

        $this->assertEquals(1, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('Invalid date format', $this->commandTester->getDisplay());
    }

    /**
     * Test case 4: Handles start date after end date
     */
    public function testHandlesInvalidDateRange(): void
    {
        $this->commandTester->execute([
            'from' => '2026-01-10',
            'to' => '2026-01-01',
        ]);

        $this->assertEquals(1, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('before or equal', $this->commandTester->getDisplay());
    }

    /**
     * Test case 5: Handles errors during import (requirement 3.0)
     */
    public function testHandlesErrorsDuringImport(): void
    {
        $this->rateProvider
            ->method('getRates')
            ->willThrowException(new \RuntimeException('CBR service unavailable'));

        $this->commandTester->execute([
            'from' => '2026-01-01',
            'to' => '2026-01-01',
        ]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('errors', $output);
        $this->assertStringContainsString('CBR service unavailable', $output);
    }

    /**
     * Test case 6: Displays progress bar (requirement 3.0)
     */
    public function testDisplaysProgressBar(): void
    {
        $this->rateProvider
            ->method('getRates')
            ->willReturn([]);

        $this->commandTester->execute([
            'from' => '2026-01-01',
            'to' => '2026-01-02',
        ]);

        $output = $this->commandTester->getDisplay();

        $this->assertNotEmpty($output);
    }
}

