<?php

namespace App\Repository\References;

use App\Entity\References\TypeDemande;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TypeDemande>
 *
 * @method TypeDemande|null find($id, $lockMode = null, $lockVersion = null)
 * @method TypeDemande|null findOneBy(array $criteria, array $orderBy = null)
 * @method TypeDemande[]    findAll()
 * @method TypeDemande[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TypeDemandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TypeDemande::class);
    }
}
