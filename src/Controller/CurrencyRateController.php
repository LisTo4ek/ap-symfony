<?php

declare(strict_types=1);

namespace App\Controller;

use App\Bundle\CurrencyRateProviderBundle\Src\Base\Currency\CurrencyIso4217\CurrencyIso4217Enum;
use App\Domain\Action\CurrencyRate\GetCurrentRatesAction;
use App\Domain\Action\CurrencyRate\GetRateHistoryAction;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CurrencyRateController extends AbstractController
{
    private const string BASE_CURRENCY = CurrencyIso4217Enum::RUB->value;

    public function __construct(
        private GetCurrentRatesAction $getCurrentRatesAction,
        private GetRateHistoryAction $getRateHistoryAction,
    ) {
    }

    #[Route('/current-rates', name: 'app_current_rates')]
    public function currentRates(Request $request): Response
    {
        [$latestDate, $paginator] = ($this->getCurrentRatesAction)(
            $request->query->getInt('page'),
            $request->query->getInt('itemsPerPage'),
            self::BASE_CURRENCY
        );

        return $this->render('currency-rate/current-rates.html.twig', [
            'pagination' => $paginator,
            'latestDate' => $latestDate?->format('Y-m-d'),
            'today' => new DateTimeImmutable('today')->format('Y-m-d'),
        ]);
    }

    #[Route('/rate-history/{targetCurrency}', name: 'app_rates_history')]
    public function rateHistory(string $targetCurrency, Request $request): Response
    {
        return $this->render('currency-rate/rate-history.html.twig', [
            'pagination' => ($this->getRateHistoryAction)(
                $request->query->getInt('page'),
                $request->query->getInt('itemsPerPage'),
                self::BASE_CURRENCY,
                $targetCurrency
            ),
            'targetCurrency' => $targetCurrency,
        ]);
    }
}

