<?php

namespace App\Repository;

use App\Entity\EtapeValidation;
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
}
