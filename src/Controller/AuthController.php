<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

class AuthController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/api/register', name: 'register', methods: ['POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        JWTTokenManagerInterface $jwtManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $email = trim($data['email'] ?? '');
        $pseudo = trim($data['pseudo'] ?? '');
        $password = $data['password'] ?? '';

        // Validation basique
        $errors = [];
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email invalide';
        }
        if ($pseudo === '' || strlen($pseudo) < 2) {
            $errors[] = 'Le pseudo doit contenir au moins 2 caractères';
        }
        if (strlen($password) < 8) {
            $errors[] = 'Le mot de passe doit contenir au moins 8 caractères';
        }

        if (!empty($errors)) {
            return new JsonResponse(['message' => 'Données invalides', 'errors' => $errors], 400);
        }

        // Vérifier si l'email existe déjà
        $existingUser = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => $email]);

        if ($existingUser !== null) {
            return new JsonResponse(['message' => 'Un compte existe déjà avec cet email'], 409);
        }

        $user = new User();
        $user->setEmail($email);
        $user->setPseudo($pseudo);
        $user->setRoles(['ROLE_USER']);

        $hashedPassword = $passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        try {
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException $e) {
            return new JsonResponse(['message' => 'Un compte existe déjà avec cet email'], 409);
        }

        // On connecte directement l'utilisateur après inscription (optionnel mais pratique)
        $token = $jwtManager->create($user);

        return new JsonResponse([
            'token' => $token,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'pseudo' => $user->getPseudo(),
            ],
        ], 201);
    }

    #[Route('/api/login', name: 'login', methods: ['POST'])]
    public function login(
        Request $request,
        UserProviderInterface $userProvider,
        UserPasswordHasherInterface $passwordHasher,
        JWTTokenManagerInterface $jwtManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (!$email || !$password) {
            return new JsonResponse(['error' => 'Email et mot de passe requis'], 400);
        }

        try {
            $user = $userProvider->loadUserByIdentifier($email);
        } catch (UserNotFoundException $e) {
            return new JsonResponse(['error' => 'Identifiants invalides'], 401);
        }

        if (!$passwordHasher->isPasswordValid($user, $password)) {
            return new JsonResponse(['error' => 'Identifiants invalides'], 401);
        }

        $token = $jwtManager->create($user);

        return new JsonResponse([
            'token' => $token,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'pseudo' => method_exists($user, 'getPseudo') ? $user->getPseudo() : null,
            ],
        ]);
    }
}