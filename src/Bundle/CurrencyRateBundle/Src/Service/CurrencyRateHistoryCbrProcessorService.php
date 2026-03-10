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
use Symfony\Component\DependencyInjection\Attribute\Autowire;

use function array_map;
use function count;

class CurrencyRateHistoryCbrProcessorService
{
    private const int CHUNK_SIZE = 1000;

    public function __construct(
        #[Autowire(service: CurrencyRateProviderCbrService::class)]
        private readonly CurrencyRateProviderServiceInterface $provider,
        private readonly RateHistoryStorageInterface $rateHistoryStorage,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function process(DateTimeImmutable $date): int
    {
        $today = new DateTimeImmutable('today');
        $count = 0;
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

        return $count;
    }
}
