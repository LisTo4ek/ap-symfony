<?php

namespace App\Controller;

use App\RateProvider\Domain\ValueObject\Currency;
use App\Repository\RateHistoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Knp\Component\Pager\PaginatorInterface;

class CurrencyController extends AbstractController
{
    public function __construct(
        private readonly RateHistoryRepository $rateHistoryRepository,
        private readonly PaginatorInterface $paginator
    ) {
    }

    #[Route('/currency/{code}/history', name: 'app_currency_history')]
    public function history(Request $request, string $currency): Response
    {
        // Validate currency code
        if (!preg_match('/^[A-Z]{3}$/', $currency)) {
            throw $this->createNotFoundException('Invalid currency code');
        }

        // Get filter date from query
        $filterDate = $request->query->get('date');
        $dateObject = null;

        if ($filterDate) {
            try {
                $dateObject = new \DateTimeImmutable($filterDate);
            } catch (\Exception $e) {
                $this->addFlash('warning', 'Неверный формат даты');
            }
        }

        // Build query
        $queryBuilder = $this->rateHistoryRepository->createQueryBuilder('rh')
            ->where('rh.charCode = :code')
            ->setParameter('code', $currency)
            ->orderBy('rh.date', 'DESC')
            ->addOrderBy('rh.id', 'DESC');

        if ($dateObject) {
            $queryBuilder
                ->andWhere('rh.date = :date')
                ->setParameter('date', $dateObject);
        }

        // Get only latest record per day
        $queryBuilder
            ->groupBy('rh.date')
            ->addGroupBy('rh.charCode')
            ->addGroupBy('rh.value')
            ->addGroupBy('rh.nominal')
            ->addGroupBy('rh.id');

        $pagination = $this->paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('currency/history.html.twig', [
            'currency' => Currency::from($currency),
            'pagination' => $pagination,
            'filterDate' => $filterDate,
        ]);
    }
}

