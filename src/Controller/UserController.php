<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Dom\Entity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;


final class UserController extends AbstractController
{
    #[Route('/api/user', name: 'user_create', methods: ['POST'])]
    public function createUser(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        ValidatorInterface $validator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $user = new User();
        $user->setEmail($data['email'] ?? null);
        $user->setCity($data['city'] ?? null);
        $user->setZipcode($data['zipcode'] ?? null);
        $user->setCreatedAt(new \DateTimeImmutable());
        $user->setRoles(['ROLE_USER']);

        if (isset($data['password'])) {
            $user->setPassword($passwordHasher->hashPassword($user, $data['password']));
        } else {
            $user->setPassword(null);
        }

        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json([
                'code' => 400,
                'errors' => $errorMessages
            ], 400);
        }

        $entityManager->persist($user);
        $entityManager->flush();

        return $this->json([
            'message' => 'Utilisateur créé avec succès',
            'id' => $user->getId(),
        ], 201);
    }


#[IsGranted('ROLE_ADMIN')]
#[Route('/api/user/{id}', name: 'update_user', methods: ['PUT'])]
public function updateUser(Request $request, User $user, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): JsonResponse
{
    $data = json_decode($request->getContent(), true);

    if (!is_array($data)) {
        return $this->json(['code' => 400, 'message' => 'Corps de la requête JSON invalide'], 400);
    }

    if (empty($data)) {
        return $this->json(['code' => 400, 'message' => 'Aucun champ à modifier fourni'], 400);
    }

    if (isset($data['email'])) {
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return $this->json(['code' => 400, 'message' => 'Format email invalide'], 400);
        }
        $user->setEmail($data['email']);
    }

    if (isset($data['city'])) {
        $user->setCity($data['city']);
    }

    if (isset($data['zipcode'])) {
        $user->setZipcode($data['zipcode']);
    }

    if (isset($data['password'])) {
        if (strlen($data['password']) < 8) {
            return $this->json(['code' => 400, 'message' => 'Le mot de passe doit contenir au moins 8 caractères'], 400);
        }
        $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);
    }

    if(isset($data['role'])) {
        $user->setRoles($data['role']);
    }

    $em->flush();

    return $this->json([
        'id'      => $user->getId(),
        'message' => 'Utilisateur mis à jour avec succès',
    ], 200);
}

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/api/user/{id}', name: 'delete_user', methods: ['DELETE'])]
    public function deleteUser(User $user, UserRepository $userRepository, EntityManagerInterface $em): JsonResponse
    {

        $em->remove($user);
        $em->flush();

        return $this->json(null, 204);
    }
}
