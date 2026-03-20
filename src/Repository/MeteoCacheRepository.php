<?php

namespace App\Repository;

use App\Entity\MeteoCache;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MeteoCache>
 */
class MeteoCacheRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MeteoCache::class);
    }

    //    /**
    //     * @return MeteoCache[] Returns an array of MeteoCache objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('m.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?MeteoCache
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function findOneByCity(string $city, string $countryCode = 'fr'): ?MeteoCache
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.city = :city')
            ->andWhere('m.countryCode = :country')
            ->setParameter('city', strtolower($city))
            ->setParameter('country', $countryCode)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function deleteAll(): void
    {
        $this->createQueryBuilder('m')
            ->delete()
            ->getQuery()
            ->execute();
    }
}
