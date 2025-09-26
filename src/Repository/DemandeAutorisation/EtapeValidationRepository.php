<?php

namespace App\Repository\DemandeAutorisation;

use App\Entity\DemandeAutorisation\EtapeValidation;
use App\Entity\References\Direction;
use App\Entity\References\ServiceMinef;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EtapeValidation>
 *
 * @method EtapeValidation|null find($id, $lockMode = null, $lockVersion = null)
 * @method EtapeValidation|null findOneBy(array $criteria, array $orderBy = null)
 * @method EtapeValidation[]    findAll()
 * @method EtapeValidation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EtapeValidationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EtapeValidation::class);
    }

    public function save(EtapeValidation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(EtapeValidation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return EtapeValidation[]
     */
    /*public function findPendingStepsForUser(?Direction $direction, ?ServiceMinef $service): array
    {
        

        $qb = $this->createQueryBuilder('e')
            ->where('e.statut = :statut')
            ->setParameter('statut', 'En cours');

        //if ($direction) {
        //    $qb->andWhere('e.nom = :nom')
        //        ->setParameter('nom', $direction->getDenomination());
        //} elseif ($service) {
        //    $qb->andWhere('e.nom = :nom')
        //        ->setParameter('nom', $service->getLibelleService());
        //} else {
        //    return [];
        //}


        return $qb->orderBy('e.id', 'DESC')
            ->getQuery()
            ->getResult();
    }*/

    public function findPendingStepsForUser(?Direction $direction, ?ServiceMinef $service): array
    {
        $subQueryBuilder = $this->createQueryBuilder('e2');
        $subQuery = $subQueryBuilder
            ->select('MIN(e2.id)')
            ->join('e2.demande', 'd2')
            ->where('d2.statut != :statut_signe')
            ->andWhere('e2.statut = :statut')
            ->groupBy('e2.demande')
            ->getDQL();

        $queryBuilder = $this->createQueryBuilder('e');
        $query = $queryBuilder
            ->where($queryBuilder->expr()->in('e.id', $subQuery))
            ->setParameter('statut_signe', 'Signé')
            ->setParameter('statut', 'En cours')
            ->orderBy('e.id', 'DESC');

        return $query->getQuery()->getResult();
    }


    


}
