<?php

namespace App\Repository\DemandeAutorisation;

use App\Entity\DemandeAutorisation\NouvelleDemande;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<NouvelleDemande>
 *
 * @method NouvelleDemande|null find($id, $lockMode = null, $lockVersion = null)
 * @method NouvelleDemande|null findOneBy(array $criteria, array $orderBy = null)
 * @method NouvelleDemande[]    findAll()
 * @method NouvelleDemande[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NouvelleDemandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NouvelleDemande::class);
    }

    public function findWithDocuments(int $id): ?NouvelleDemande
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.documents', 'docs')
            ->addSelect('docs')
            ->andWhere('d.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

//    /**
//     * @return NouvelleDemande[] Returns an array of NouvelleDemande objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('n')
//            ->andWhere('n.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('n.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?NouvelleDemande
//    {
//        return $this->createQueryBuilder('n')
//            ->andWhere('n.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
