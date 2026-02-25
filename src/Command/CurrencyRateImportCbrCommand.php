<?php

declare(strict_types=1);

namespace App\Command;

use App\Domain\Action\SaveCbrCurrencyRateHistoryAction;
use DateMalformedPeriodStringException;
use DateMalformedStringException;
use DateTimeImmutable;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import:currency-rates:cbr',
    description: 'Import currency rates from CBR for a date range',
)]
class CurrencyRateImportCbrCommand extends Command
{

    public function __construct(
        private readonly SaveCbrCurrencyRateHistoryAction $saveCurrencyRateHistoryAction,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'from',
                InputArgument::OPTIONAL,
                'Start date (Y-m-d)',
                (new \DateTime())->format('Y-m-d')
            )
            ->addArgument(
                'to',
                InputArgument::OPTIONAL,
                'End date (Y-m-d)',
                (new \DateTime())->format('Y-m-d')
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Force re-import existing data'
            );
    }

    /**
     * @throws DateMalformedStringException
     * @throws DateMalformedPeriodStringException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $from = new DateTimeImmutable($input->getArgument('from'));
            $to = new DateTimeImmutable($input->getArgument('to'));
        } catch (\Exception $e) {
            $io->error('Invalid date format. Use Y-m-d format.');
            return Command::FAILURE;
        }

        if ($from > $to) {
            $io->error('Start date must be before or equal to end date.');
            return Command::FAILURE;
        }

        $io->title('Currency Rates Import from CBR');
        $io->info(sprintf(
            'Importing rates from %s to %s',
            $from->format('Y-m-d'), $to->format('Y-m-d')
        ));

        $interval = new \DateInterval('P1D');
        $period = new \DatePeriod($from, $interval, $to->modify('+1 day'));
        $totalDays = iterator_count($period);

        // Reset the iterator
        $period = new \DatePeriod($from, $interval, $to->modify('+1 day'));

        $progressBar = new ProgressBar($output, $totalDays);
        $progressBar->setFormat('verbose');
        $progressBar->start();

        $successCount = 0;
        $errors = [];

        foreach ($period as $date) {
            $progressBar->setMessage($date->format('Y-m-d'));

            try {
                $successCount += ($this->saveCurrencyRateHistoryAction)($date) ?? 0;
            } catch (\Exception $e) {
                $errors[] = sprintf('[%s] %s', $date->format('Y-m-d'), $e->getMessage());
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $io->newLine(2);

        $io->success(sprintf('Import completed: %d successful, %d errors', $successCount, count($errors)));

        if (count($errors) > 0) {
            $io->warning('Errors occurred during import:');
            $io->listing($errors);
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
