<?php

namespace App\Repository\Validation;

use App\Entity\Validation\ValidationNouvelleDemande;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ValidationNouvelleDemande>
 *
 * @method ValidationNouvelleDemande|null find($id, $lockMode = null, $lockVersion = null)
 * @method ValidationNouvelleDemande|null findOneBy(array $criteria, array $orderBy = null)
 * @method ValidationNouvelleDemande[]    findAll()
 * @method ValidationNouvelleDemande[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ValidationNouvelleDemandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ValidationNouvelleDemande::class);
    }

//    /**
//     * @return ValidationNouvelleDemande[] Returns an array of ValidationNouvelleDemande objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('v')
//            ->andWhere('v.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('v.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?ValidationNouvelleDemande
//    {
//        return $this->createQueryBuilder('v')
//            ->andWhere('v.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
