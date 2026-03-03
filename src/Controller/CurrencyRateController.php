<?php

declare(strict_types=1);

namespace App\Controller;

use App\Bundle\CurrencyRateProviderBundle\Src\Base\Currency\CurrencyContract;
use App\Bundle\CurrencyRateProviderBundle\Src\Base\Currency\CurrencyIso4217\CurrencyIso4217Enum;
use App\Bundle\CurrencyRateProviderBundle\Src\Base\Currency\CurrencyManagerContract;
use App\Bundle\CurrencyRateProviderBundle\Src\Base\CurrencyEnum;
use App\Domain\Contracts\CurrencyRate\CurrentRateStorageContract;
use App\Domain\CurrencyRate\Service\CurrentRateDateCache;
use App\Repository\RateHistoryRepository;
use DateTimeImmutable;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CurrencyRateController extends AbstractController
{
    public function __construct(
//        private readonly RateHistoryRepository $rateHistoryRepository,
        private readonly PaginatorInterface $paginator,
//        private readonly CurrentRateDateCache $currentRateDateCache,
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

//
//        // Get filter date from query
//        $filterDate = $request->query->get('date');
//        $dateObject = null;
//
//        if ($filterDate) {
//            try {
//                $dateObject = new DateTimeImmutable($filterDate);
//            } catch (\Exception $e) {
//                $this->addFlash('warning', 'Неверный формат даты');
//            }
//        }
//
//        // Build query
//        $queryBuilder = $this->rateHistoryRepository->createQueryBuilder('rh')
//            ->where('rh.charCode = :code')
//            ->setParameter('code', $currency)
//            ->orderBy('rh.date', 'DESC')
//            ->addOrderBy('rh.id', 'DESC');
//
//        if ($dateObject) {
//            $queryBuilder
//                ->andWhere('rh.date = :date')
//                ->setParameter('date', $dateObject);
//        }
//
//        // Get only latest record per day
//        $queryBuilder
//            ->groupBy('rh.date')
//            ->addGroupBy('rh.charCode')
//            ->addGroupBy('rh.value')
//            ->addGroupBy('rh.nominal')
//            ->addGroupBy('rh.id');

        $pagination = $this->paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('currency-rate/current.html.twig', [
//            'currency' => CurrencyEnum::from($currency),
            'pagination' => $pagination,
//            'filterDate' => $filterDate,
        ]);
    }
}

