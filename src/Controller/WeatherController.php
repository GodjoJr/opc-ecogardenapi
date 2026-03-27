<?php

namespace App\Controller;

use App\Entity\MeteoCache;
use App\Repository\MeteoCacheRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class WeatherController extends AbstractController
{
    #[IsGranted('ROLE_USER')]
    #[Route('/api/weather', name: 'app_weather_user', methods: ['GET'])]
    public function getWeatherForCurrentUser(HttpClientInterface $client, MeteoCacheRepository $meteoCacheRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['code' => 401, 'message' => 'Utilisateur non connecté'], 401);
        }

        $city = strtolower($user->getCity());

        $cache = $meteoCacheRepository->findOneBy([
            'city' => $city,
            'countryCode' => 'fr'
        ]);

        $now = new \DateTimeImmutable();
        $cacheIsValid = $cache && $cache->getLastUpdate()->modify('+1 hour') > $now;

        if ($cacheIsValid) {
            return $this->json($cache->getData());
        }

        try {
            $url = 'https://api.openweathermap.org/data/2.5/weather?q=' . $city . ',fr&appid=' . $_ENV['OPEN_WEATHER_API_KEY'];
            $response = $client->request('GET', $url);
            $data = $response->toArray();

        } catch (\Exception $e) {

            $zipcode = $user->getZipcode();

            if (isset($zipcode) && !$zipcode) {
                return $this->json([
                    'code' => 404,
                    'message' => 'Ville introuvable et aucun code postal renseigné sur votre compte'
                ], 404);
            }

            try {
                $url = 'https://api.openweathermap.org/data/2.5/weather?zip=' . $zipcode . ',fr&appid=' . $_ENV['OPEN_WEATHER_API_KEY'];
                $response = $client->request('GET', $url);
                $data = $response->toArray();

            } catch (\Exception $e) {
                return $this->json([
                    'code' => 404,
                    'message' => 'Impossible de trouver la météo pour la ville "' . $city . '" et le code postal "' . $zipcode . '"'
                ], 404);
            }
        }

        if (!$cache) {
            $cache = new MeteoCache();
            $cache->setCity(strtolower($data['name']));
            $cache->setCountryCode('fr');
            $entityManager->persist($cache);
        }

        $cache->setCity(strtolower($data['name']));
        $cache->setData($data);
        $cache->setLastUpdate($now);
        $entityManager->flush();

        return $this->json($data);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/api/weather/{city}', name: 'app_weather_city', methods: ['GET'])]
    public function getWeatherFromCity(string $city, Request $request, HttpClientInterface $client, MeteoCacheRepository $meteoCacheRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $city = strtolower($city);
        $zipcode = $request->query->get('zipcode');

        $cache = $meteoCacheRepository->findOneBy([
            'city' => $city,
            'countryCode' => 'fr'
        ]);

        $now = new \DateTimeImmutable();
        $cacheIsValid = $cache && $cache->getLastUpdate()->modify('+1 hour') > $now;

        if ($cacheIsValid) {
            return $this->json($cache->getData());
        }

        try {
            $url = 'https://api.openweathermap.org/data/2.5/weather?q=' . $city . ',fr&appid=' . $_ENV['OPEN_WEATHER_API_KEY'];
            $response = $client->request('GET', $url);
            $data = $response->toArray();

        } catch (\Exception $e) {

            if (isset($zipcode) && !$zipcode) {
                return $this->json([
                    'code' => 404,
                    'message' => 'Ville introuvable. Vous pouvez réessayer avec ?zipcode=75001'
                ], 404);
            }

            try {
                $url = 'https://api.openweathermap.org/data/2.5/weather?zip=' . $zipcode . ',fr&appid=' . $_ENV['OPEN_WEATHER_API_KEY'];
                $response = $client->request('GET', $url);
                $data = $response->toArray();

            } catch (\Exception $e) {
                return $this->json([
                    'code' => 404,
                    'message' => 'Ville "' . $city . '" et code postal "' . $zipcode . '" introuvables'
                ], 404);
            }
        }

        if (!$cache) {
            $cache = new MeteoCache();
            $cache->setCity(strtolower($data['name']));
            $cache->setCountryCode('fr');
            $entityManager->persist($cache);
        }

        $cache->setCity(strtolower($data['name']));
        $cache->setData($data);
        $cache->setLastUpdate($now);
        $entityManager->flush();

        return $this->json($data);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/api/weather/', name: 'clear_weather_cache', methods: ['DELETE'])]
    public function clearWeatherCache(MeteoCacheRepository $meteoCacheRepository): JsonResponse
    {
        $meteoCacheRepository->deleteAll();

        return $this->json(null, 204);
    }
}
