<?php

namespace App\Controller;

use App\Entity\Conseil;
use App\Repository\ConseilRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ConseilController extends AbstractController
{

    #[IsGranted('ROLE_USER')]
    #[Route('/api/conseil/', name: 'conseil_this_month', methods: ['GET'])]
    public function getThisMonth(ConseilRepository $conseilRepository): JsonResponse
    {
        $date = new \DateTime();
        $month = $date->format('m');
        $conseils = $conseilRepository->findAll();

        $filtered_conseils = array_filter($conseils, function ($conseil) use ($month) {
            return in_array($month, $conseil->getMonth());
        });

        $data = [];
        foreach ($filtered_conseils as $conseil) {
            $data[] = [
                'id' => $conseil->getId(),
                'text' => $conseil->getText(),
                'month' => $conseil->getMonth(),
                'author' => $conseil->getAuthor()?->getEmail(),
                'createdAt' => $conseil->getCreatedAt()?->format('Y-m-d H:i:s'),
                'updatedAt' => $conseil->getUpdatedAt()?->format('Y-m-d H:i:s'),
            ];
        }

        return $this->json($data);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/api/conseil/{month}', name: 'conseil_by_month', methods: ['GET'])]
    public function getByMonth(string $month, ConseilRepository $conseilRepository): JsonResponse
    {

        if (!is_numeric($month) || (int) $month < 1 || (int) $month > 12) {
            return $this->json(['code' => 400, 'message' => 'Mois invalide : ' . $month . '. Le mois doit avoir une valeur entre 01 et 12'], 400);
        }
        
        $conseils = $conseilRepository->findAll();

        $filtered_conseils = array_filter($conseils, function ($conseil) use ($month) {
            return in_array($month, $conseil->getMonth());
        });

        $data = [];
        foreach ($filtered_conseils as $conseil) {
            $data[] = [
                'id' => $conseil->getId(),
                'text' => $conseil->getText(),
                'month' => $conseil->getMonth(),
                'author' => $conseil->getAuthor()?->getEmail(),
                'createdAt' => $conseil->getCreatedAt()?->format('Y-m-d H:i:s'),
                'updatedAt' => $conseil->getUpdatedAt()?->format('Y-m-d H:i:s'),
            ];
        }

        return $this->json($data);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/api/conseil/', name: 'add_conseil', methods: ['POST'])]
    public function addConseil(Request $request, ConseilRepository $conseilRepository, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['code' => 400, 'message' => 'Corps de la requête JSON invalide'], 400);
        }

        $missingFields = [];
        if (empty($data['text']))
            $missingFields[] = 'text';
        if (empty($data['month']))
            $missingFields[] = 'month';


        if (!empty($missingFields)) {
            return $this->json([
                'code' => 400,
                'message' => 'Champs manquants : ' . implode(', ', $missingFields)
            ], 400);
        }
        $invalidMonths = [];
        foreach ($data['month'] as $month) {
            $monthInt = (int) $month;
            if ($monthInt < 1 || $monthInt > 12) {
                $invalidMonths[] = $month;
            }
        }

        if (!empty($invalidMonths)) {
            return $this->json([
                'code' => 400,
                'message' => 'Mois invalides : ' . implode(', ', $invalidMonths) . '. Chaque mois doit être entre 01 et 12'
            ], 400);
        }

        $conseil = new Conseil();
        $conseil->setText($data['text']);
        $conseil->setMonth($data['month']);
        $conseil->setCreatedAt(new \DateTimeImmutable());
        $conseil->setUpdatedAt(new \DateTimeImmutable());
        $conseil->setAuthor($this->getUser());

        $em->persist($conseil);
        $em->flush();

        return $this->json([
            'id' => $conseil->getId(),
            'text' => $conseil->getText(),
            'month' => $conseil->getMonth(),
        ], 201);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/api/conseil/{id}', name: 'update_conseil', methods: ['PUT'])]
    public function updateConseil(Request $request, Conseil $conseil, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['code' => 400, 'message' => 'Corps de la requête JSON invalide'], 400);
        }

        if (empty($data)) {
            return $this->json(['code' => 400, 'message' => 'Aucun champ à modifier fourni'], 400);
        }

        if (isset($data['text'])) {
            if (empty($data['text'])) {
                return $this->json(['code' => 400, 'message' => 'Le champ text ne peut pas être vide'], 400);
            }
            $conseil->setText($data['text']);
        }

        if (isset($data['month'])) {
            if (!is_array($data['month'])) {
                return $this->json(['code' => 400, 'message' => '"month" doit être un tableau JSON. Exemple : ["06", "07", "08"]'], 400);
            }

            $invalidMonths = [];
            foreach ($data['month'] as $month) {
                if ((int) $month < 1 || (int) $month > 12) {
                    $invalidMonths[] = $month;
                }
            }

            if (!empty($invalidMonths)) {
                return $this->json(['code' => 400, 'message' => 'Mois invalides : ' . implode(', ', $invalidMonths)], 400);
            }

            $conseil->setMonth($data['month']);
        }

        $conseil->setUpdatedAt(new \DateTimeImmutable());
        $em->flush();

        return $this->json([
            'id' => $conseil->getId(),
            'text' => $conseil->getText(),
            'month' => $conseil->getMonth(),
        ], 200);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/api/conseil/{id}', name: 'delete_conseil', methods: ['DELETE'])]
    public function deleteConseil(Conseil $conseil, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($conseil);
        $em->flush();

        return $this->json([
            'message' => 'Conseil n°' . $conseil->getId() . ' supprimé'
        ], 204);
    }
}
