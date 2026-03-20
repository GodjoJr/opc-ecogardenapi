<?php

namespace App\Controller;

use App\Entity\MeteoCache;
use App\Repository\MeteoCacheRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class WeatherController extends AbstractController
{
    #[Route('/api/meteo', name: 'app_weather')]
    public function getWeatherForCurrentUser(HttpClientInterface $client, MeteoCacheRepository $meteoCacheRepository, EntityManagerInterface $entityManager): JsonResponse
    {

        $user = $this->getUser();

        if (!$user) {
            return $this->json([
                'code' => 401,
                'message' => 'Utilisateur non connecté'
            ], 401);
        }

        $cache = $meteoCacheRepository->findOneBy([
            'city' => $user->getCity(),
            'countryCode' => 'fr'
        ]);

        $now = new \DateTimeImmutable();
        $cacheIsValid = $cache && $cache->getLastUpdate()->modify('+1 hour') > $now;

        if ($cacheIsValid) {
            return $this->json($cache->getData());
        }

        $url = 'https://api.openweathermap.org/data/2.5/weather?q=' . strtolower($user->getCity()) . ',fr&appid=' . $_ENV['OPEN_WEATHER_API_KEY'];

        $response = $client->request('GET', $url);
        $data = $response->toArray();

        if (!$cache) {
            $cache = new MeteoCache();
            $cache->setCity(strtolower($user->getCity()));
            $cache->setCountryCode('fr');
            $entityManager->persist($cache);
        }

        $cache->setData($data);
        $cache->setLastUpdate($now);
        $entityManager->flush();

        return $this->json($data);

    }
}
