<?php

namespace App\Repository\DemandeAutorisation;

use App\Entity\DemandeAutorisation\TypeDemandeDetail;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TypeDemandeDetail>
 *
 * @method TypeDemandeDetail|null find($id, $lockMode = null, $lockVersion = null)
 * @method TypeDemandeDetail|null findOneBy(array $criteria, array $orderBy = null)
 * @method TypeDemandeDetail[]    findAll()
 * @method TypeDemandeDetail[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TypeDemandeDetailRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TypeDemandeDetail::class);
    }

//    /**
//     * @return TypeDemandeDetail[] Returns an array of TypeDemandeDetail objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('t.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?TypeDemandeDetail
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
