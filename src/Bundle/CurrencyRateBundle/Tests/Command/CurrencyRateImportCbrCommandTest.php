<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Tests\Command;

use App\Bundle\CurrencyRateBundle\Src\ConsoleCommand\CurrencyRateImportCbrCommand;
use App\Bundle\CurrencyRateBundle\Src\Exception\ProcessorException;
use App\Bundle\CurrencyRateBundle\Src\Service\BundleLoggerService;
use App\Bundle\CurrencyRateBundle\Src\Service\CurrencyRateHistoryCbrProcessorService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class CurrencyRateImportCbrCommandTest extends TestCase
{
    private CurrencyRateHistoryCbrProcessorService&MockObject $processor;
    private BundleLoggerService&MockObject $logger;
    private CurrencyRateImportCbrCommand $command;
    private CommandTester $tester;

    protected function setUp(): void
    {
        $this->processor = $this->createMock(CurrencyRateHistoryCbrProcessorService::class);
        $this->logger = $this->createMock(BundleLoggerService::class);
        $this->command = new CurrencyRateImportCbrCommand($this->processor, $this->logger);
        $this->tester = new CommandTester($this->command);
    }

    public function testSuccessfulImportForSingleDay(): void
    {
        $this->processor
            ->expects($this->once())
            ->method('process')
            ->willReturn(10);
        $this->tester->execute(['from' => '2026-03-06', 'to' => '2026-03-06']);
        $this->assertSame(Command::SUCCESS, $this->tester->getStatusCode());
        $this->assertStringContainsString('Import completed', $this->tester->getDisplay());
    }

    public function testSuccessfulImportForDateRange(): void
    {
        $this->processor
            ->expects($this->exactly(3))
            ->method('process')
            ->willReturn(5);
        $this->tester->execute(['from' => '2026-03-01', 'to' => '2026-03-03']);
        $this->assertSame(Command::SUCCESS, $this->tester->getStatusCode());
    }

    public function testFailsWhenFromDateAfterToDate(): void
    {
        $this->processor->expects($this->never())->method('process');
        $this->tester->execute(['from' => '2026-03-10', 'to' => '2026-03-01']);
        $this->assertSame(Command::FAILURE, $this->tester->getStatusCode());
        $this->assertStringContainsString('Start date must be before', $this->tester->getDisplay());
    }

    public function testFailsWithInvalidDateFormat(): void
    {
        $this->processor->expects($this->never())->method('process');
        $this->tester->execute(['from' => 'not-a-date', 'to' => '2026-03-01']);
        $this->assertSame(Command::FAILURE, $this->tester->getStatusCode());
        $this->assertStringContainsString('Invalid date format', $this->tester->getDisplay());
    }

    public function testProcessorExceptionResultsInFailure(): void
    {
        $this->processor
            ->method('process')
            ->willThrowException(new ProcessorException('API down'));
        $this->tester->execute(['from' => '2026-03-06', 'to' => '2026-03-06']);
        $this->assertSame(Command::FAILURE, $this->tester->getStatusCode());
        $this->assertStringContainsString('API down', $this->tester->getDisplay());
    }

    public function testCommandNameIsRegistered(): void
    {
        $this->assertSame('app:import:currency-rates:cbr', $this->command->getName());
    }

    public function testDefaultDatesAreToday(): void
    {
        $this->processor->method('process')->willReturn(0);
        $this->tester->execute([]);
        $this->assertSame(Command::SUCCESS, $this->tester->getStatusCode());
    }
}
