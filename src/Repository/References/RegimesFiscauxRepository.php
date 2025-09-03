<?php

namespace App\Repository\References;

use App\Entity\References\RegimesFiscaux;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RegimesFiscaux>
 *
 * @method RegimesFiscaux|null find($id, $lockMode = null, $lockVersion = null)
 * @method RegimesFiscaux|null findOneBy(array $criteria, array $orderBy = null)
 * @method RegimesFiscaux[]    findAll()
 * @method RegimesFiscaux[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RegimesFiscauxRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RegimesFiscaux::class);
    }
}
