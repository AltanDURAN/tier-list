<?php

namespace App\Repository;

use App\Entity\Tier;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Tier>
 */
class TierRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tier::class);
    }

    public function findByTierListOrderedByPosition($tierListId): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.tierList = :tierListId')
            ->setParameter('tierListId', $tierListId)
            ->orderBy('t.position', 'ASC') // tri par position croissante
            ->getQuery()
            ->getResult();
    }


    //    public function findOneBySomeField($value): ?Tier
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
