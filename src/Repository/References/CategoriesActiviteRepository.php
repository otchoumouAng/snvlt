<?php

namespace App\Repository\References;

use App\Entity\References\CategoriesActivite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CategoriesActivite>
 *
 * @method CategoriesActivite|null find($id, $lockMode = null, $lockVersion = null)
 * @method CategoriesActivite|null findOneBy(array $criteria, array $orderBy = null)
 * @method CategoriesActivite[]    findAll()
 * @method CategoriesActivite[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CategoriesActiviteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CategoriesActivite::class);
    }
}
