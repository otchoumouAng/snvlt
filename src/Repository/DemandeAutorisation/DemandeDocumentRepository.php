<?php

namespace App\Repository\DemandeAutorisation;

use App\Entity\DemandeAutorisation\DemandeDocument;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DemandeDocument>
 *
 * @method DemandeDocument|null find($id, $lockMode = null, $lockVersion = null)
 * @method DemandeDocument|null findOneBy(array $criteria, array $orderBy = null)
 * @method DemandeDocument[]    findAll()
 * @method DemandeDocument[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DemandeDocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DemandeDocument::class);
    }
}
