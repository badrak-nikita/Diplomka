<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\Service;
use App\Repository\ActivityRepository;
use App\Repository\MessageRepository;
use App\Repository\OrderRepository;
use App\Repository\ReviewRepository;
use App\Repository\ServiceRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class AdminController extends AbstractController
{
    #[Route('/admin', name: 'admin_home')]
    #[IsGranted('ROLE_ADMIN')]
    public function index(OrderRepository $orderRepository, UserRepository $userRepository, ActivityRepository $activityRepository): Response
    {
        $pendingOrdersCount = $orderRepository->count(['status' => Order::STATUS_PENDING]);
        $completedRevenue = $orderRepository->getTotalRevenue();
        $totalUsersCount = $userRepository->count([]);
        $recentActivities = $activityRepository->getRecentActivities();

        return $this->render('admin/dashboard/index.html.twig', [
            'pendingOrdersCount' => $pendingOrdersCount,
            'completedRevenue' => $completedRevenue,
            'totalUsersCount' => $totalUsersCount,
            'recentActivities' => $recentActivities,
        ]);
    }

    #[Route('/admin/services', name: 'admin_services')]
    #[IsGranted('ROLE_ADMIN')]
    public function showServices(ServiceRepository $serviceRepository): Response
    {
        $services = $serviceRepository->findAll();

        return $this->render('admin/services/index.html.twig', [
            'services' => $services,
        ]);
    }

    #[Route('/admin/services/create', name: 'admin_services_create')]
    #[IsGranted('ROLE_ADMIN')]
    public function createServices(Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $serviceName = $request->request->get('serviceName');
            $duration = $request->request->get('duration');
            $price = $request->request->get('price');

            $service = new Service();
            $service->setServiceName($serviceName);
            $service->setDuration($duration ?: null);
            $service->setPrice($price !== null ? (float)$price : null);

            $entityManager->persist($service);
            $entityManager->flush();

            return $this->redirectToRoute('admin_services');
        }

        return $this->render('admin/services/create.html.twig');
    }

    #[Route('/admin/services/edit/{id}', name: 'admin_services_edit')]
    #[IsGranted('ROLE_ADMIN')]
    public function editService(int $id, Request $request, EntityManagerInterface $entityManager, ServiceRepository $serviceRepository): Response
    {
        $service = $serviceRepository->find($id);

        if (!$service) {
            throw $this->createNotFoundException('Послуга не знайдена');
        }

        if ($request->isMethod('POST')) {
            $serviceName = $request->request->get('serviceName');
            $duration = $request->request->get('duration');
            $price = $request->request->get('price');

            if (!$serviceName) {
                return $this->redirectToRoute('admin_services_edit', ['id' => $id]);
            }

            $service->setServiceName($serviceName);
            $service->setDuration($duration ?: null);
            $service->setPrice($price !== null ? (float)$price : null);

            $entityManager->flush();

            return $this->redirectToRoute('admin_services');
        }

        return $this->render('admin/services/edit.html.twig', [
            'service' => $service,
        ]);
    }

    #[Route('/admin/services/delete/{id}', name: 'admin_services_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteService(int $id, EntityManagerInterface $entityManager, ServiceRepository $serviceRepository): Response
    {
        $service = $serviceRepository->find($id);

        if (!$service) {
            throw $this->createNotFoundException('Послуга не знайдена');
        }

        $entityManager->remove($service);
        $entityManager->flush();

        return $this->redirectToRoute('admin_services');
    }

    #[Route('/admin/orders', name: 'admin_orders')]
    #[IsGranted('ROLE_ADMIN')]
    public function showOrders(OrderRepository $orderRepository): Response
    {
        $orders = $orderRepository->findBy([], ['createdAt' => 'DESC']);

        return $this->render('admin/orders/index.html.twig', [
            'orders' => $orders,
        ]);
    }

    #[Route('/admin/orders/update_status/{id}', name: 'admin_orders_update_status', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function updateOrderStatus(int $id, EntityManagerInterface $entityManager, OrderRepository $orderRepository): Response
    {
        $order = $orderRepository->find($id);

        if (!$order) {
            throw $this->createNotFoundException('Замовлення не знайдено');
        }

        $newStatus = $order->getStatus() === Order::STATUS_PENDING ? Order::STATUS_COMPLETED : Order::STATUS_PENDING;
        $order->setStatus($newStatus);

        $entityManager->flush();

        return $this->redirectToRoute('admin_orders');
    }

    #[Route('/admin/orders/cancel/{id}', name: 'admin_orders_cancel', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function cancelOrder(int $id, EntityManagerInterface $entityManager, OrderRepository $orderRepository): Response
    {
        $order = $orderRepository->find($id);

        if (!$order) {
            throw $this->createNotFoundException('Замовлення не знайдено');
        }

        $order->setStatus(Order::STATUS_CANCELED);
        $entityManager->flush();

        return $this->redirectToRoute('admin_orders');
    }

    #[Route('/admin/users', name: 'admin_users')]
    #[IsGranted('ROLE_ADMIN')]
    public function showUsers(UserRepository $userRepository): Response
    {
        $users = $userRepository->findBy([], ['createdAt' => 'DESC']);

        return $this->render('admin/users/index.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/admin/reviews', name: 'admin_reviews')]
    #[IsGranted('ROLE_ADMIN')]
    public function showReviews(ReviewRepository $reviewRepository): Response
    {
        $reviews = $reviewRepository->findBy([], ['createdAt' => 'DESC']);

        return $this->render('admin/reviews/index.html.twig', [
            'reviews' => $reviews,
        ]);
    }

    #[Route('/admin/reviews/edit/{id}', name: 'admin_review_edit')]
    #[IsGranted('ROLE_ADMIN')]
    public function editReview(int $id, Request $request, ReviewRepository $reviewRepository, EntityManagerInterface $entityManager): Response
    {
        $review = $reviewRepository->find($id);

        if (!$review) {
            throw $this->createNotFoundException('Відгук не знайдено');
        }

        if ($request->isMethod('POST')) {
            $review->setReviewText($request->request->get('reviewText'));
            $review->setRating((int)$request->request->get('rating'));

            $entityManager->flush();

            return $this->redirectToRoute('admin_reviews');
        }

        return $this->render('admin/reviews/edit.html.twig', [
            'review' => $review,
        ]);
    }

    #[Route('/admin/reviews/delete/{id}', name: 'admin_review_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteReview(int $id, ReviewRepository $reviewRepository, EntityManagerInterface $entityManager): Response
    {
        $review = $reviewRepository->find($id);

        if (!$review) {
            throw $this->createNotFoundException('Відгук не знайдено');
        }

        $entityManager->remove($review);
        $entityManager->flush();

        return $this->redirectToRoute('admin_reviews');
    }

    #[Route('/error/{code}', name: 'app_error')]
    public function error(int $code): Response
    {
        return $this->render('error_page.html.twig', [
            'code' => $code,
        ]);
    }

    #[Route('/admin/messages', name: 'admin_messages')]
    #[IsGranted('ROLE_ADMIN')]
    public function showMessages(MessageRepository $messageRepository): Response
    {
        $messages = $messageRepository->findBy([], ['createdAt' => 'DESC']);

        return $this->render('admin/messages/index.html.twig', [
            'messages' => $messages,
        ]);
    }
}
