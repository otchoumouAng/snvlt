<?php

namespace App\Repository;

use App\Entity\DetailsCircuitActes;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DetailsCircuitActes>
 *
 * @method DetailsCircuitActes|null find($id, $lockMode = null, $lockVersion = null)
 * @method DetailsCircuitActes|null findOneBy(array $criteria, array $orderBy = null)
 * @method DetailsCircuitActes[]    findAll()
 * @method DetailsCircuitActes[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DetailsCircuitActesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DetailsCircuitActes::class);
    }

//    /**
//     * @return DetailsCircuitActes[] Returns an array of DetailsCircuitActes objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('d')
//            ->andWhere('d.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('d.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?DetailsCircuitActes
//    {
//        return $this->createQueryBuilder('d')
//            ->andWhere('d.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
