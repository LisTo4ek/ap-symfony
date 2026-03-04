<?php

declare(strict_types=1);

namespace App\Domain\Action\CurrencyRate;

use App\Bundle\CurrencyRateProviderBundle\Src\Base\Rate;
use App\Bundle\CurrencyRateProviderBundle\Src\Helper\DateCompare;
use App\Bundle\CurrencyRateProviderBundle\Src\Providers\CbrProvider\CbrProvider;
use App\Bundle\CurrencyRateProviderBundle\Src\Providers\CurrencyRateProviderContract;
use App\Domain\Contracts\CurrencyRate\RateHistoryStorageContract;
use App\Entity\RateHistory;
use App\Event\RateSavedEvent;
use DateTimeImmutable;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use function array_map;
use function count;

class ProcessCbrCurrencyRateHistoryAction
{
    private const int CHUNK_SIZE = 1000;

    public function __construct(
        #[Autowire(service: CbrProvider::class)]
        private readonly CurrencyRateProviderContract $provider,
        private readonly RateHistoryStorageContract $rateHistoryStorage,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function __invoke(DateTimeImmutable $date): int
    {
        $today = new DateTimeImmutable('today');
        $count = 0;
        /** @var array<Rate> $chunk */
        foreach ($this->provider->getRates($date, self::CHUNK_SIZE) as $chunk) {
            $historyEntities = array_map(
                static fn(Rate $rate) => new RateHistory(
                    $rate->baseCurrency,
                    $rate->targetCurrency,
                    (string) $rate->rate,
                    $rate->date
                ),
                $chunk
            );

            $this->rateHistoryStorage->saveBatch($historyEntities);

            $count += count($chunk);

            /** @var Rate $rate */
            foreach ($chunk as $rate) {
                if (!DateCompare::eq($rate->date, $today)) {
                    continue;
                }

                $this->eventDispatcher->dispatch(new RateSavedEvent($rate));
            }
        }

        return $count;
    }
}
