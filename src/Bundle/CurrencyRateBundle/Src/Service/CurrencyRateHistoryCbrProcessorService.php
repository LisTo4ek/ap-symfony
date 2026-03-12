<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Src\Service;

use App\Bundle\CurrencyRateBundle\Src\Config\ConstantsConfig;
use App\Bundle\CurrencyRateBundle\Src\Config\CurrencyRateSavedEvent;
use App\Bundle\CurrencyRateBundle\Src\Container\RateContainer;
use App\Bundle\CurrencyRateBundle\Src\Entity\RateHistory;
use App\Bundle\CurrencyRateBundle\Src\Exception\ProcessorException;
use App\Bundle\CurrencyRateBundle\Src\Exception\ProviderException;
use App\Bundle\CurrencyRateBundle\Src\Storage\RateHistoryStorageInterface;
use DateTimeImmutable;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Throwable;

use function array_map;
use function count;

/**
 * Orchestrates the import of CBR currency rates for a single date.
 *
 * Fetches rates from the CBR provider in chunks, persists them as RateHistory entities,
 * and dispatches CurrencyRateSavedEvent for each rate whose date matches today
 * (triggering current-rate synchronization).
 *
 * @property CurrencyRateProviderServiceInterface $provider The CBR rate provider service
 * @property RateHistoryStorageInterface $rateHistoryStorage Storage for persisting rate history entities
 * @property EventDispatcherInterface $eventDispatcher Dispatcher for CurrencyRateSavedEvent events
 * @property LoggerInterface $logger Logger for the currency_rate_bundle channel
 */
class CurrencyRateHistoryCbrProcessorService
{
    public function __construct(
        #[Autowire(service: CurrencyRateProviderCbrService::class)]
        private readonly CurrencyRateProviderServiceInterface $provider,
        private readonly RateHistoryStorageInterface $rateHistoryStorage,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly BundleLoggerServiceInterface $logger,
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
     * @throws ProcessorException If there is an error fetching rates from the provider
     * @todo: n+1 problem when dispatching the event but we can live with it for now, because we are
     *        going process rates once per day I think
     */
    public function process(DateTimeImmutable $date): int
    {
        $today = new DateTimeImmutable('today');
        $count = 0;
        try {
            /** @var array<RateContainer> $chunk */
            foreach ($this->provider->getRates($date, ConstantsConfig::CHUNK_SIZE) as $chunk) {
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
                /** @var RateContainer $rate */
                foreach ($chunk as $rate) {
                    if (!DateCompareService::eq($rate->date, $today)) {
                        continue;
                    }

                    $this->eventDispatcher->dispatch(new CurrencyRateSavedEvent($rate));
                }
            }
        } catch (ProviderException $e) {
            $this->logger->error('Provider error', [
                'date' => $date->format('Y-m-d'),
                'exception' => $this->logger->getExceptionContext($e),
            ]);

            throw new ProcessorException('Error processing rates', previous: $e);
        }

        return $count;
    }
}
