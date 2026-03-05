<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\Action\CurrencyRate\GetCurrentRatesAction;
use App\Domain\Action\CurrencyRate\GetRateHistoryAction;
use App\Domain\Config\Pagination\PaginatorConfigDefault;
use App\Domain\Contracts\Pagination\PaginatorConfigContract;
use App\Domain\Dto\CurrencyRate\CurrentRateDto;
use App\Domain\Dto\CurrencyRate\RateHistoryDto;
use App\Domain\Validation\ArgumentResolver\CurrentRateAbstractDtoValueResolver;
use App\Domain\Validation\ArgumentResolver\RateHistoryAbstractDtoValueResolver;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;

class CurrencyRateController extends AbstractController
{
    public function __construct(
        private GetCurrentRatesAction $getCurrentRatesAction,
        private GetRateHistoryAction $getRateHistoryAction,
        #[Autowire(service: PaginatorConfigDefault::class)]
        private readonly PaginatorConfigContract $paginatorConfig,
    ) {
    }

    #[Route('/current-rates/{baseCurrencyCode}', name: 'app_current_rates')]
    public function currentRates(#[ValueResolver(CurrentRateAbstractDtoValueResolver::class)] CurrentRateDto $dto): Response
    {
        [$latestDate, $pagination] = ($this->getCurrentRatesAction)(
            $this->paginatorConfig,
            $dto,
        );

        return $this->render('currency-rate/current-rates.html.twig', [
            'baseCurrencyCode' => $dto->baseCurrencyCode,
            'pagination' => $pagination,
            'latestDate' => $latestDate?->format('Y-m-d'),
            'today' => new DateTimeImmutable('today')->format('Y-m-d'),
        ]);
    }

    #[Route('/rate-history/{baseCurrencyCode}/{targetCurrencyCode}/', name: 'app_rates_history')]
    public function rateHistory(#[ValueResolver(RateHistoryAbstractDtoValueResolver::class)] RateHistoryDto $dto): Response
    {
        return $this->render('currency-rate/rate-history.html.twig', [
            'pagination' => ($this->getRateHistoryAction)(
                $this->paginatorConfig,
                $dto,
            ),
            'baseCurrencyCode' => $dto->baseCurrencyCode,
            'targetCurrencyCode' => $dto->targetCurrencyCode,
        ]);
    }
}
