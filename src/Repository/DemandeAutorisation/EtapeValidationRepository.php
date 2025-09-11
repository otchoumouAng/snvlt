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
    public function findPendingStepsForUser(?Direction $direction, ?ServiceMinef $service): array
    {
        $qb = $this->createQueryBuilder('e')
            ->where('e.statut = :statut')
            ->setParameter('statut', 'en_attente');

        if ($direction) {
            $qb->andWhere('e.nom = :nom')
                ->setParameter('nom', $direction->getDenomination());
        } elseif ($service) {
            $qb->andWhere('e.nom = :nom')
                ->setParameter('nom', $service->getLibelleService());
        } else {
            // If user has no direction or service, they see nothing
            return [];
        }

        // This is the complex part: only show steps where the previous step is validated.
        // For now, we keep it simple as discussed. A more advanced query would be needed here.
        // e.g., check if order is 1, OR if the step with (e.ordre - 1) for the same demande has status 'validé'.

        return $qb->orderBy('e.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
