<?php

namespace App\Controller;

use App\Entity\Activity;
use App\Entity\Order;
use App\Entity\Service;
use App\Repository\ServiceRepository;
use App\Service\TelegramService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
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
    public function showOrder(ServiceRepository $serviceRepository, Security $security): Response
    {
        $services = $serviceRepository->findAll();
        $user = $security->getUser();

        return $this->render('home/order.html.twig', [
            'services' => $services,
            'user' => $user,
        ]);
    }

    #[Route('/order/create', name: 'app_order_create', methods: ['POST'])]
    public function createOrder(Request $request, EntityManagerInterface $entityManager, TelegramService $telegramService): Response
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

            $allowedMimeTypes = [
                'application/pdf',
                'text/plain',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'application/zip',
                'application/x-rar-compressed',
                'application/vnd.rar',
            ];

            if (!in_array($file->getMimeType(), $allowedMimeTypes, true)) {
                throw new \RuntimeException('Формат файлу не дозволено');
            }

            $filename = uniqid('', true) . '.' . $file->guessExtension();
            $file->move($this->getParameter('uploads_directory'), $filename);

            $order->setFile($filename);
        }

        $activity = new Activity("Нове замовлення створено");
        $entityManager->persist($activity);

        $entityManager->persist($order);
        $entityManager->flush();

        // Telegram Bot
        $deadlineString = $request->request->get('deadline');

        try {
            $deadlineDate = new \DateTime($deadlineString);
        } catch (\Exception $e) {
            $deadlineDate = null;
        }

        $fields = [
            'Ім\'я' => $fullName,
            'Email' => $email,
            'Телефон' => $phone,
            'Telegram' => $telegram,
            'Предмет' => $subject,
            'Тема' => $topic,
            'Побажання' => $wishes,
            'Дедлайн' => $deadlineDate ? $deadlineDate->format('d:m:Y') : 'не вказано',
        ];

        $textLines = ['<b>Нове замовлення!</b>', ''];

        foreach ($fields as $label => $value) {
            if (!empty(trim($value))) {
                $labelEscaped = str_replace(['&', '<', '>'], ['&amp;', '&lt;', '&gt;'], $label);
                $valueEscaped = str_replace(['&', '<', '>'], ['&amp;', '&lt;', '&gt;'], $value);
                $textLines[] = "<b>$labelEscaped:</b> $valueEscaped";
            }
        }

        $message = implode("\n", $textLines);

        if ($order->getFile()) {
            $filePath = $this->getParameter('uploads_directory') . '/' . $order->getFile();
            $telegramService->sendDocument($filePath, $message);
        } else {
            $telegramService->sendMessage($message);
        }

        flash()->success('Ваше замовлення успiшно створено', (array)'Success');

        return $this->redirectToRoute('app_home');
    }
}
