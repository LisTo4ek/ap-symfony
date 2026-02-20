<?php

namespace App\Controller;

use App\Entity\Author;
use App\Form\AuthorType;
use App\Repository\AuthorRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/author')]
class AuthorController extends AbstractController
{
    public function __construct(private AuthorRepository $authorRepository)
    {
    }

    #[Route('', name: 'author_index', methods: ['GET'])]
    public function index(): Response
    {
        $authors = $this->authorRepository->findAllOrderedByName();

        return $this->render('author/index.html.twig', [
            'authors' => $authors,
        ]);
    }

    #[Route('/new', name: 'author_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $author = new Author();
        $form = $this->createForm(AuthorType::class, $author);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->authorRepository->save($author, true);

            return $this->redirectToRoute('author_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('author/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'author_show', methods: ['GET'])]
    public function show(Author $author): Response
    {
        return $this->render('author/show.html.twig', [
            'author' => $author,
        ]);
    }

    #[Route('/{id}/edit', name: 'author_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Author $author): Response
    {
        $form = $this->createForm(AuthorType::class, $author);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->authorRepository->save($author, true);

            return $this->redirectToRoute('author_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('author/edit.html.twig', [
            'form' => $form,
            'author' => $author,
        ]);
    }

    #[Route('/{id}', name: 'author_delete', methods: ['POST'])]
    public function delete(Request $request, Author $author): Response
    {
        if ($this->isCsrfTokenValid('delete' . $author->getId(), $request->request->get('_token'))) {
            $this->authorRepository->remove($author, true);
        }

        return $this->redirectToRoute('author_index', [], Response::HTTP_SEE_OTHER);
    }
}
