<?php

declare(strict_types=1);

namespace App\Domain\Action\CurrencyRateProvider;

use App\Bundle\CurrencyRateProviderBundle\Src\Base\Rate;
use App\Bundle\CurrencyRateProviderBundle\Src\Providers\CbrProvider\CbrProvider;
use App\Bundle\CurrencyRateProviderBundle\Src\Providers\CurrencyRateProviderInterface;
use App\Entity\RateHistory;
use App\Event\RateSavedEvent;
use App\Repository\RateHistoryRepository;
use DateTimeImmutable;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class SaveCbrCurrencyRateHistoryAction
{
    private const int CHUNK_SIZE = 1000;

    public function __construct(
        #[Autowire(service: CbrProvider::class)]
        private readonly CurrencyRateProviderInterface $provider,
        private readonly RateHistoryRepository $historyRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function __invoke(DateTimeImmutable $date): int
    {
        $today = new DateTimeImmutable('today');
        $count = 0;
        /** @var array<Rate> $chunk */
        foreach ($this->provider->getRates($date, self::CHUNK_SIZE) as $chunk) {
            $historyEntities = \array_map(
                static fn(Rate $rate) => new RateHistory(
                    $rate->baseCurrency,
                    $rate->targetCurrency,
                    (string) $rate->rate,
                    $rate->date
                ),
                $chunk
            );

            $this->historyRepository->saveBatch($historyEntities);

            $count += \count($chunk);

            /** @var Rate $rate */
            foreach ($chunk as $rate) {
                if ($rate->date->diff($today)->days === 0) {
                    continue;
                }

                $this->eventDispatcher->dispatch(new RateSavedEvent($rate));
            }
        }

        return $count;
    }
}
