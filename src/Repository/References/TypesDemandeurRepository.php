<?php

namespace App\Repository\References;

use App\Entity\References\TypesDemandeur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TypesDemandeur>
 *
 * @method TypesDemandeur|null find($id, $lockMode = null, $lockVersion = null)
 * @method TypesDemandeur|null findOneBy(array $criteria, array $orderBy = null)
 * @method TypesDemandeur[]    findAll()
 * @method TypesDemandeur[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TypesDemandeurRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TypesDemandeur::class);
    }
}
