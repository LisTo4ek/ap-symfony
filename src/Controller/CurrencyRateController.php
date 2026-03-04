<?php

declare(strict_types=1);

namespace App\Controller;

use App\Bundle\CurrencyRateProviderBundle\Src\Base\Currency\CurrencyIso4217\CurrencyIso4217Enum;
use App\Bundle\CurrencyRateProviderBundle\Src\Base\Currency\CurrencyManagerContract;
use App\Domain\Contracts\CurrencyRate\CurrentRateStorageContract;
use App\Domain\Contracts\CurrencyRate\RateHistoryStorageContract;
use DateTimeImmutable;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CurrencyRateController extends AbstractController
{
    private const string BASE_CURRENCY = CurrencyIso4217Enum::RUB->value;

    public function __construct(
        private readonly PaginatorInterface $paginator,
        private readonly CurrentRateStorageContract $currentRateStorage,
        private readonly RateHistoryStorageContract $rateHistoryStorage,
        private readonly CurrencyManagerContract $currencyManager,
    ) {
    }

    #[Route('/current-rates', name: 'app_current_rates')]
    public function currentRates(Request $request): Response
    {
        $latestDate = $this->currentRateStorage->getLatestDate();

        if ($latestDate) {
            $queryBuilder = $this->currentRateStorage->findByDateAndBaseCurrencyQuery(
                $latestDate,
                $this->currencyManager::create(self::BASE_CURRENCY)
            );

            $pagination = $this->paginator->paginate(
                $queryBuilder,
                $request->query->getInt('page', 1),
                20
            );
        }

        return $this->render('currency-rate/current-rates.html.twig', [
            'pagination' => $pagination ?? null,
            'latestDate' => $latestDate?->format('Y-m-d'),
            'today' => new DateTimeImmutable('today')->format('Y-m-d'),
        ]);
    }

    #[Route('/rate-history/{targetCurrency}', name: 'app_rates_history')]
    public function rateHistory(string $targetCurrency, Request $request): Response
    {
        $queryBuilder = $this->rateHistoryStorage->findByCurrencyPair(
            $this->currencyManager::create(self::BASE_CURRENCY),
            $this->currencyManager::create($targetCurrency),
        );

        $pagination = $this->paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('currency-rate/rate-history.html.twig', [
            'pagination' => $pagination,
            'targetCurrency' => $targetCurrency,
        ]);
    }
}

