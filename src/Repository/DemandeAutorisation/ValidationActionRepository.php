<?php

namespace App\Repository;

use App\Entity\ValidationAction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ValidationAction>
 *
 * @method ValidationAction|null find($id, $lockMode = null, $lockVersion = null)
 * @method ValidationAction|null findOneBy(array $criteria, array $orderBy = null)
 * @method ValidationAction[]    findAll()
 * @method ValidationAction[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ValidationActionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ValidationAction::class);
    }
}
