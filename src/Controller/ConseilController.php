<?php

namespace App\Controller;

use App\Repository\ConseilRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
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

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/api/conseil/{month}', name: 'conseil_by_month', methods: ['GET'])]
    public function getByMonth(string $month, ConseilRepository $conseilRepository): JsonResponse
    {
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
}
