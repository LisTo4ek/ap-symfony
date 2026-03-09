<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Src\Controller;

use App\Bundle\CurrencyRateBundle\Src\ArgumentResolver\CurrentRateContainerResolver;
use App\Bundle\CurrencyRateBundle\Src\ArgumentResolver\RateHistoryContainerResolver;
use App\Bundle\CurrencyRateBundle\Src\Config\PaginationConfigDefault;
use App\Bundle\CurrencyRateBundle\Src\Config\PaginationConfigInterface;
use App\Bundle\CurrencyRateBundle\Src\Container\CurrentRateContainer;
use App\Bundle\CurrencyRateBundle\Src\Container\RateHistoryContainer;
use App\Bundle\CurrencyRateBundle\Src\Service\CurrentRatesGetterService;
use App\Bundle\CurrencyRateBundle\Src\Service\RateHistoryPaginatorService;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;

class CurrencyRateController extends AbstractController
{
    public function __construct(
        private CurrentRatesGetterService $currentRatesGetterService,
        private RateHistoryPaginatorService $rateHistoryPaginatorService,
        #[Autowire(service: PaginationConfigDefault::class)]
        private readonly PaginationConfigInterface $paginatorConfig,
        #[Autowire(param: 'currency_rate_provider.display_rate_precision')]
        private readonly int $displayRatePrecision,
    ) {
    }

    #[Route('/current-rates/{baseCurrencyCode}', name: 'app_current_rates')]
    public function currentRates(
        #[ValueResolver(CurrentRateContainerResolver::class)]
        CurrentRateContainer $container,
    ): Response {
        [$latestDate, $pagination] = $this->currentRatesGetterService->get($this->paginatorConfig, $container);

        return $this->render('currency-rate/current-rates.html.twig', [
            'displayRatePrecision' => $this->displayRatePrecision,
            'baseCurrencyCode' => $container->baseCurrencyCode,
            'pagination' => $pagination,
            'latestDate' => $latestDate?->format('Y-m-d'),
            'today' => new DateTimeImmutable('today')->format('Y-m-d'),
        ]);
    }

    #[Route('/rate-history/{baseCurrencyCode}/{targetCurrencyCode}', name: 'app_rates_history')]
    public function rateHistory(
        #[ValueResolver(RateHistoryContainerResolver::class)]
        RateHistoryContainer $container,
    ): Response {
        return $this->render('currency-rate/rate-history.html.twig', [
            'displayRatePrecision' => $this->displayRatePrecision,
            'pagination' => $this->rateHistoryPaginatorService->get($this->paginatorConfig, $container),
            'baseCurrencyCode' => $container->baseCurrencyCode,
            'targetCurrencyCode' => $container->targetCurrencyCode,
        ]);
    }
}
