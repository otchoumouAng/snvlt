<?php

namespace App\Repository\References;

use App\Entity\References\CatalogueServices;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CatalogueServices>
 *
 * @method CatalogueServices|null find($id, $lockMode = null, $lockVersion = null)
 * @method CatalogueServices|null findOneBy(array $criteria, array $orderBy = null)
 * @method CatalogueServices[]    findAll()
 * @method CatalogueServices[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CatalogueServicesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CatalogueServices::class);
    }
}
