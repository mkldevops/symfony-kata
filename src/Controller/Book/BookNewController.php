<?php

namespace App\Controller\Book;

use App\Entity\Book;
use App\Form\BookType;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Client\GoogleBookClient;
use Symfony\Component\String\Slugger\SluggerInterface;

#[IsGranted('ROLE_USER')]
#[Route('/book')]
class BookNewController extends AbstractController
{

    #[Route('/new', name: 'app_book_new', methods: ['GET', 'POST'])]
    public function __invoke(Request $request, EntityManagerInterface $entityManager, GoogleBookClient $client, SluggerInterface $slugger): Response
    {
        $book = new Book();
        $form = $this->createForm(BookType::class, $book);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $bookDto = $client->searchBook(title: $book->getTitle(), author: $book->getAuthor());

            $book->setTitle($bookDto->title);
            $book->setAuthor($bookDto->author);
            $book->setPublishedAt($bookDto->publishedDate);
            $book->setIsbn($bookDto->isbn );
            $book->setPublisher($this->getUser());
            $book->setCreatedAt(new \DateTimeImmutable());
            $book->setSlug($slugger->slug($bookDto->title));
            
            $entityManager->persist($book);
            $entityManager->flush();

            return $this->redirectToRoute('app_book_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('book/new.html.twig', [
            'book' => $book,
            'form' => $form,
        ]);
    }
}
