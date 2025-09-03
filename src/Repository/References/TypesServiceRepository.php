<?php

namespace App\Repository\References;

use App\Entity\References\TypesService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TypesService>
 *
 * @method TypesService|null find($id, $lockMode = null, $lockVersion = null)
 * @method TypesService|null findOneBy(array $criteria, array $orderBy = null)
 * @method TypesService[]    findAll()
 * @method TypesService[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TypesServiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TypesService::class);
    }
}
