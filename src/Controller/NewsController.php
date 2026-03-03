<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\Action\News\GetNewsSectionAction;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/news')]
class NewsController extends AbstractController
{
    public function __construct(
        private readonly GetNewsSectionAction $getNewsSectionAction
    ) {}



    #[Route('/', name: 'app_news_index')]
    public function index(): Response
    {
        return $this->render('news/index.html.twig');
    }

    #[Route('/{name}', name: 'app_news_section')]
    public function section(string $name): Response
    {
        $newsSection = ($this->getNewsSectionAction)($name);

        return $this->render("news/{$name}.html.twig", [
            'section' => $newsSection
        ]);
    }
}
