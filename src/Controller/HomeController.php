<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\CurrencyRateProvider\Action\GetCurrentRatesAction;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    public function __construct(
        private readonly GetCurrentRatesAction $getCurrentRatesAction
    ) {
    }

    #[Route('/', name: 'app_home_index')]
    public function index(): Response
    {
        $ratesDTO = ($this->getCurrentRatesAction)();

        return $this->render('home/index.html.twig', [
            'ratesDTO' => $ratesDTO,
        ]);
    }
}
