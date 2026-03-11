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

/**
 * Handles HTTP requests for viewing current exchange rates and rate history.
 *
 * Provides two routes:
 * - /current-rates/{baseCurrencyCode}: displays the latest rates for a base currency
 * - /rate-history/{baseCurrencyCode}/{targetCurrencyCode}: displays paginated rate history for a currency pair
 */
class CurrencyRateController extends AbstractController
{
    /**
     * @param CurrentRatesGetterService $currentRatesGetter Service that fetches (and optionally imports) current
     *        rates
     * @param RateHistoryPaginatorService $rateHistoryPaginator Service that paginates rate history records
     * @param PaginationConfigInterface $paginatorConfig Default pagination configuration (per-page options, etc.)
     * @param int $displayRatePrecision Number of decimal places to display for rates
     */
    public function __construct(
        private CurrentRatesGetterService $currentRatesGetter,
        private RateHistoryPaginatorService $rateHistoryPaginator,
        #[Autowire(service: PaginationConfigDefault::class)]
        private readonly PaginationConfigInterface $paginatorConfig,
        #[Autowire(param: 'currency_rate_provider.display_rate_precision')]
        private readonly int $displayRatePrecision,
    ) {
    }

    /**
     * Displays the latest exchange rates for the given base currency.
     *
     * If today's rates are not yet stored, attempts to import them from CBR first.
     * Renders a paginated list of current rates.
     *
     * @param CurrentRateContainer $container Validated request DTO with pagination and base currency
     *
     * @return Response The rendered current-rates template
     */
    #[Route('/current-rates/{baseCurrencyCode}', name: 'app_current_rates')]
    public function currentRates(
        #[ValueResolver(CurrentRateContainerResolver::class)]
        CurrentRateContainer $container,
    ): Response {
        $result = $this->currentRatesGetter->get($this->paginatorConfig, $container);

        return $this->render('currency-rate/current-rates.html.twig', [
            'displayRatePrecision' => $this->displayRatePrecision,
            'baseCurrencyCode' => $container->baseCurrencyCode,
            'pagination' => $result->pagination,
            'latestDate' => $result->latestDate?->format('Y-m-d'),
            'importExceptionMessage' => $result->importRatesException?->getMessage(),
            'today' => new DateTimeImmutable('today')->format('Y-m-d'),
        ]);
    }

    /**
     * Displays paginated exchange rate history for a specific currency pair.
     *
     * @param RateHistoryContainer $container Validated request DTO with pagination, base and target currency codes
     *
     * @return Response The rendered rate-history template
     */
    #[Route('/rate-history/{baseCurrencyCode}/{targetCurrencyCode}', name: 'app_rates_history')]
    public function rateHistory(
        #[ValueResolver(RateHistoryContainerResolver::class)]
        RateHistoryContainer $container,
    ): Response {
        return $this->render('currency-rate/rate-history.html.twig', [
            'displayRatePrecision' => $this->displayRatePrecision,
            'pagination' => $this->rateHistoryPaginator->get($this->paginatorConfig, $container),
            'baseCurrencyCode' => $container->baseCurrencyCode,
            'targetCurrencyCode' => $container->targetCurrencyCode,
        ]);
    }
}
