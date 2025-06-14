<?php

namespace App\Controller;

use App\Entity\Message;
use App\Service\TelegramService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig');
    }

    #[Route('/contacts', name: 'app_contacts', methods: ['GET', 'POST'])]
    public function contact(Request $request, EntityManagerInterface $entityManager, TelegramService $telegramService): Response
    {
        if ($request->isMethod('POST')) {
            $name = $request->request->get('name');
            $email = $request->request->get('email');
            $messageText = $request->request->get('message');

            $message = new Message();
            $message->setUsername($name);
            $message->setEmail($email);
            $message->setMessage($messageText);
            $message->setCreatedAt(new \DateTimeImmutable());

            $entityManager->persist($message);
            $entityManager->flush();

            // Telegram Bot
            $fields = [
                'Ім\'я' => $name,
                'Email' => $email,
                'Повiдомлення' => $messageText
            ];

            $textLines = ['<b>Новий лист!</b>', ''];

            foreach ($fields as $label => $value) {
                if (!empty(trim($value))) {
                    $labelEscaped = str_replace(['&', '<', '>'], ['&amp;', '&lt;', '&gt;'], $label);
                    $valueEscaped = str_replace(['&', '<', '>'], ['&amp;', '&lt;', '&gt;'], $value);
                    $textLines[] = "<b>$labelEscaped:</b> $valueEscaped";
                }
            }

            $message = implode("\n", $textLines);

            $telegramService->sendMessage($message);

            flash()->success('Ваше повідомлення успішно відправлено', (array)'Success');

            return $this->redirectToRoute('app_contacts');
        }

        return $this->render('home/contacts.html.twig');
    }
}
