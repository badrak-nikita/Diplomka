<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\Service;
use App\Repository\ServiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PriceController extends AbstractController
{
    #[Route('/prices', name: 'app_prices')]
    public function index(ServiceRepository $serviceRepository): Response
    {
        $services = $serviceRepository->findAll();

        return $this->render('home/price.html.twig', [
            'services' => $services,
        ]);
    }

    #[Route('/order', name: 'app_order')]
    public function showOrder(ServiceRepository $serviceRepository): Response
    {
        $services = $serviceRepository->findAll();

        return $this->render('home/order.html.twig', [
            'services' => $services,
        ]);
    }

    #[Route('/order/create', name: 'app_order_create', methods: ['POST'])]
    public function createOrder(Request $request, EntityManagerInterface $entityManager): Response
    {
        $order = new Order();

        $fullName = $request->request->get('fullName');
        $email = $request->request->get('email');
        $phone = $request->request->get('phone');
        $serviceId = $request->request->get('service');
        $subject = $request->request->get('subject');
        $topic = $request->request->get('topic');
        $wishes = $request->request->get('wishes');
        $deadline = $request->request->get('deadline');
        $telegram = $request->request->get('telegram');

        $order->setClientName($fullName);
        $order->setEmail($email);
        $order->setPhone($phone);
        $order->setService($entityManager->getRepository(Service::class)->find($serviceId));
        $order->setSubject($subject);
        $order->setTopic($topic);
        $order->setWishes($wishes);
        $order->setDeadline(new \DateTime($deadline));
        $order->setTelegram($telegram);
        $order->setCreatedAt(new \DateTimeImmutable());
        $order->setStatus(1);

        if ($request->files->get('fileInput')) {
            $file = $request->files->get('fileInput');
            $filename = uniqid('', true) . '.' . $file->guessExtension();
            $file->move($this->getParameter('uploads_directory'), $filename);

            $order->setFile($filename);
        }

        $entityManager->persist($order);
        $entityManager->flush();

        flash()->success('Ваше замовлення успiшно створено', (array)'Success');

        return $this->redirectToRoute('app_home');
    }
}
