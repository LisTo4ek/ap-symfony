<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Src\Service;

use App\Bundle\CurrencyRateBundle\Src\Config\CurrencyRateSavedEvent;
use App\Bundle\CurrencyRateBundle\Src\Container\RateContainer;
use App\Bundle\CurrencyRateBundle\Src\Entity\RateHistory;
use App\Bundle\CurrencyRateBundle\Src\Helper\DateCompare;
use App\Bundle\CurrencyRateBundle\Src\Storage\RateHistoryStorageInterface;
use DateTimeImmutable;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Throwable;

use function array_map;
use function count;

/**
 * Orchestrates the import of CBR currency rates for a single date.
 *
 * Fetches rates from the CBR provider in chunks, persists them as RateHistory entities,
 * and dispatches CurrencyRateSavedEvent for each rate whose date matches today
 * (triggering current-rate synchronization).
 */
class CurrencyRateHistoryCbrProcessorService
{
    /** @var int Maximum number of rate containers per processing chunk */
    private const int CHUNK_SIZE = 1000;

    /**
     * @param CurrencyRateProviderServiceInterface  $provider           The CBR rate provider service
     * @param RateHistoryStorageInterface           $rateHistoryStorage Storage for persisting rate history entities
     * @param EventDispatcherInterface              $eventDispatcher    Dispatcher for CurrencyRateSavedEvent events
     * @param LoggerInterface                       $logger             Logger for the currency_rate_bundle channel
     */
    public function __construct(
        #[Autowire(service: CurrencyRateProviderCbrService::class)]
        private readonly CurrencyRateProviderServiceInterface $provider,
        private readonly RateHistoryStorageInterface $rateHistoryStorage,
        private readonly EventDispatcherInterface $eventDispatcher,
        #[Target('monolog.logger.currency_rate_bundle')]
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Fetches, persists, and dispatches events for all currency rates on the given date.
     *
     * Rates are fetched in chunks from the provider, mapped to RateHistory entities,
     * batch-saved, and — if the date is today — each rate triggers a CurrencyRateSavedEvent.
     *
     * @param DateTimeImmutable $date The date to import rates for
     *
     * @return int The total number of rate records processed and saved
     *
     * @throws Throwable Re-throws any exception after logging it
     */
    public function process(DateTimeImmutable $date): int
    {
        $today = new DateTimeImmutable('today');
        $count = 0;

        try {
            /** @var array<RateContainer> $chunk */
            foreach ($this->provider->getRates($date, self::CHUNK_SIZE) as $chunk) {
                $historyEntities = array_map(
                    static fn(RateContainer $rate) => new RateHistory(
                        $rate->baseCurrency,
                        $rate->targetCurrency,
                        $rate->rate,
                        $rate->date
                    ),
                    $chunk
                );

                $this->rateHistoryStorage->saveBatch($historyEntities);

                $count += count($chunk);

                // todo: n+1 problem,
                // todo: but we can live with it for now, because we are going process rates once per day I think
                /** @var RateContainer $rate */
                foreach ($chunk as $rate) {
                    if (!DateCompare::eq($rate->date, $today)) {
                        continue;
                    }

                    $this->eventDispatcher->dispatch(new CurrencyRateSavedEvent($rate));
                }
            }
        } catch (Throwable $e) {
            $this->logger->error('Failed to process rates', [
                'date' => $date->format('Y-m-d'),
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }

        return $count;
    }
}
