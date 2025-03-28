<?php

namespace App\Controller;

use App\Entity\Review;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ReviewController extends AbstractController
{
    #[Route('/reviews', name: 'app_reviews')]
    public function index(ReviewRepository $reviewRepository): Response
    {
        $reviews = $reviewRepository->findAll();
        $isAuthenticated = $this->isGranted('IS_AUTHENTICATED_FULLY');

        return $this->render('home/reviews.html.twig', [
            'reviews' => $reviews,
            'isAuthenticated' => $isAuthenticated,
        ]);
    }

    #[Route('/reviews/create', name: 'app_review_create', methods: ['POST'])]
    public function createReview(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $user = $this->getUser();
        $reviewText = trim($data['reviewText']);
        $rating = (int) $data['rating'];

        $review = new Review();

        $review->setReviewText($reviewText);
        $review->setRating($rating);
        $review->setCreatedAt(new \DateTimeImmutable());
        $review->setAuthor($user);

        $entityManager->persist($review);
        $entityManager->flush();

        flash()->success('Ваш вiдгук успiшно доданий', (array)'Success');

        return new JsonResponse(['message' => 'Відгук додано!'], Response::HTTP_CREATED);
    }
}
