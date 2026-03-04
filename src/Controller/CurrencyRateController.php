<?php

declare(strict_types=1);

namespace App\Controller;

use App\Bundle\CurrencyRateProviderBundle\Src\Base\Currency\CurrencyIso4217\CurrencyIso4217Enum;
use App\Bundle\CurrencyRateProviderBundle\Src\Base\Currency\CurrencyManagerContract;
use App\Domain\Contracts\CurrencyRate\CurrentRateStorageContract;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CurrencyRateController extends AbstractController
{
    public function __construct(
        private readonly PaginatorInterface $paginator,
        private readonly CurrentRateStorageContract $storage,
        private readonly CurrencyManagerContract $currencyManager,
    ) {
    }

    #[Route('/current-rates', name: 'app_current_rates')]
    public function history(Request $request): Response
    {
        $latestDate = $this->storage->getLatestDate();

        if (null === $latestDate) {

        }

        $queryBuilder = $this->storage->findByDateAndBaseCurrencyQuery(
            $latestDate,
            $this->currencyManager::create(CurrencyIso4217Enum::RUB->value)
        );

        $pagination = $this->paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('currency-rate/current.html.twig', [
            'pagination' => $pagination,
        ]);
    }
}

