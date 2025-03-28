<?php

namespace App\Controller;

use App\Entity\Message;
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
    public function contact(Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $name = $request->request->get('name');
            $email = $request->request->get('email');
            $messageText = $request->request->get('message');

            $message = new Message();
            $message->setUsername($name);
            $message->setEmail($email);
            $message->setMessage($messageText);

            $entityManager->persist($message);
            $entityManager->flush();

            flash()->success('Ваше повідомлення успішно відправлено', (array)'Success');

            return $this->redirectToRoute('app_contacts');
        }

        return $this->render('home/contacts.html.twig');
    }
}
