<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class AuthorizationController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;
    private EventDispatcherInterface $eventDispatcher;
    private TokenStorageInterface $tokenStorage;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher, EventDispatcherInterface $eventDispatcher, TokenStorageInterface $tokenStorage)
    {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
        $this->eventDispatcher = $eventDispatcher;
        $this->tokenStorage = $tokenStorage;
    }

    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(Request $request): Response
    {
        $data = $request->request->all();

        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $data['email']]);

        if ($existingUser) {
            return $this->render('home/index.html.twig', [
                'error' => 'Користувач з таким email вже зареєстрований'
            ]);
        }

        $user = new User();

        $user->setUsername($data['name']);
        $user->setEmail($data['email']);
        $user->setPassword($this->passwordHasher->hashPassword($user, $data['password']));
        $user->setRoles(['ROLE_USER']);
        $user->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());

        $this->tokenStorage->setToken($token);
        $request->getSession()->set('_security_main', serialize($token));

        $event = new InteractiveLoginEvent($request, $token);
        $this->eventDispatcher->dispatch($event, 'security.interactive_login');

        return $this->redirectToRoute('app_home');
    }

    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('home/index.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error ? 'Неправильна пошта або пароль' : null
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
    }
}
