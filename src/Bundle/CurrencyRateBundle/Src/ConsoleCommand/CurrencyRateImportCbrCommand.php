<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Src\ConsoleCommand;

use App\Bundle\CurrencyRateBundle\Src\Service\CurrencyRateHistoryCbrProcessorService;
use DateInterval;
use DateMalformedPeriodStringException;
use DateMalformedStringException;
use DatePeriod;
use DateTime;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Throwable;

use function count;
use function iterator_count;
use function sprintf;

/**
 * Console command that imports currency exchange rates from the Central Bank of Russia (CBR)
 * for a specified date range.
 *
 * Usage: app:import:currency-rates:cbr [from] [to] [--force]
 *
 * Iterates day-by-day over the given range, fetching and persisting rates.
 * Displays a progress bar and summary of successes/errors on completion.
 */
#[AsCommand(
    name: 'app:import:currency-rates:cbr',
    description: 'Import currency rates from CBR for a date range',
)]
class CurrencyRateImportCbrCommand extends Command
{
    /**
     * @param CurrencyRateHistoryCbrProcessorService $processor Service that fetches and persists rates for a single
     *        date
     * @param LoggerInterface $logger Logger for the currency_rate_bundle channel
     */
    public function __construct(
        private readonly CurrencyRateHistoryCbrProcessorService $processor,
        #[Target('monolog.logger.currency_rate_bundle')]
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    /**
     * Configures the command arguments and options.
     *
     * Arguments:
     * - from (optional): Start date in Y-m-d format (defaults to today)
     * - to   (optional): End date in Y-m-d format (defaults to today)
     *
     * Options:
     * - --force / -f: Reserved for forcing re-import (not yet implemented)
     */
    protected function configure(): void
    {
        $this
            ->addArgument('from', InputArgument::OPTIONAL, 'Start date (Y-m-d)')
            ->addArgument('to', InputArgument::OPTIONAL, 'End date (Y-m-d)')
            ->addOption('force', 'f', InputOption::VALUE_NONE);
    }

    /**
     * Executes the import command: parses date arguments, iterates over each day in the range,
     * fetches rates via the processor service, and reports progress and errors.
     *
     * @param InputInterface $input The console input (arguments: from, to; option: force)
     * @param OutputInterface $output The console output for progress bar and messages
     *
     * @return int Command::SUCCESS on full success, Command::FAILURE on date errors or any import errors
     *
     * @throws DateMalformedStringException
     * @throws DateMalformedPeriodStringException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->logger->info('Currency rates import started', [
            'from' => $input->getArgument('from'),
            'to' => $input->getArgument('to'),
            'force' => $input->getOption('force'),
        ]);

        try {
            $fromArg = $input->getArgument('from')
                ?? new DateTime()->format('Y-m-d');
            $toArg = $input->getArgument('to')
                ?? new DateTime()->format('Y-m-d');

            if (!is_string($fromArg) || !is_string($toArg)) {
                throw new DateMalformedStringException('Arguments must be strings');
            }

            $from = new DateTimeImmutable($fromArg);
            $to = new DateTimeImmutable($toArg);
        } catch (Throwable $e) {
            $this->logger->error('Invalid date format provided', [
                'from' => $input->getArgument('from'),
                'to' => $input->getArgument('to'),
                'exception' => $e->getMessage(),
            ]);
            $io->error('Invalid date format. Use Y-m-d format.');
            return Command::FAILURE;
        }

        if ($from > $to) {
            $this->logger->error('Invalid date range: from date is after to date', [
                'from' => $from->format('Y-m-d'),
                'to' => $to->format('Y-m-d'),
            ]);
            $io->error('Start date must be before or equal to end date.');
            return Command::FAILURE;
        }

        $io->title('Currency Rates Import from CBR');
        $io->info(sprintf(
            'Importing rates from %s to %s',
            $from->format('Y-m-d'),
            $to->format('Y-m-d')
        ));

        $this->logger->info('Import process started', [
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
        ]);

        $interval = new DateInterval('P1D');
        $period = new DatePeriod($from, $interval, $to->modify('+1 day'));
        $totalDays = iterator_count($period->getIterator());

        $progressBar = new ProgressBar($output, $totalDays);
        $progressBar->setFormat('verbose');
        $progressBar->start();

        $successCount = 0;
        $errors = [];

        foreach ($period as $date) {
            $progressBar->setMessage($date->format('Y-m-d'));

            try {
                $successCount += $this->processor->process($date);
            } catch (Throwable $e) {
                $errorMessage = sprintf('[%s] %s', $date->format('Y-m-d'), $e->getMessage());
                $errors[] = $errorMessage;

                $this->logger->error('Error importing rates for date', [
                    'date' => $date->format('Y-m-d'),
                    'error_message' => $e->getMessage(),
                    'error_code' => $e->getCode(),
                    'exception_class' => $e::class,
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $io->newLine(2);

        $errorCount = count($errors);
        if ($errorCount > 0) {
            $io->warning(sprintf(
                'Import completed with errors: %d rates imported, %d errors',
                $successCount,
                $errorCount
            ));
            $io->listing($errors);

            $this->logger->warning('Import process completed with errors', [
                'success_count' => $successCount,
                'error_count' => $errorCount,
                'total_days_processed' => $totalDays,
            ]);

            return Command::FAILURE;
        } else {
            $io->success(sprintf('Import completed: %d rates imported', $successCount));

            $this->logger->info('Import process completed', [
                'success_count' => $successCount,
                'total_days_processed' => $totalDays,
            ]);
        }

        return Command::SUCCESS;
    }
}
