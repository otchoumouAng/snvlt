<?php

namespace App\Repository\Paiement;

use App\Entity\Paiement\TypePaiement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TypePaiement>
 *
 * @method TypePaiement|null find($id, $lockMode = null, $lockVersion = null)
 * @method TypePaiement|null findOneBy(array $criteria, array $orderBy = null)
 * @method TypePaiement[]    findAll()
 * @method TypePaiement[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TypePaiementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TypePaiement::class);
    }
}
