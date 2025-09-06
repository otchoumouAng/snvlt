<?php

namespace App\Repository\Paiement;

use App\Entity\Paiement\CatalogueServices;
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

    /**
     * @param array $filters
     * @param string|null $targetField
     * @return array
     */
    public function findDistinctBy(array $filters, ?string $targetField): array
    {
        $qb = $this->createQueryBuilder('cs');

        foreach ($filters as $key => $value) {
            if (!empty($value)) {
                $qb->andWhere("cs.{$key} = :{$key}")
                   ->setParameter($key, $value);
            }
        }

        if ($targetField) {
            $joinAlias = $targetField;
            $qb->join('cs.' . $targetField, $joinAlias);

            // Pour typePaiement, le champ est 'libelle'
            $labelField = 'libelle';

            $qb->select("DISTINCT $joinAlias.id, $joinAlias.$labelField as label")
               ->orderBy("label", 'ASC');
        } else {
             $qb->select('cs.id, cs.designation as label, cs.montant_fcfa');
        }


        return $qb->getQuery()->getResult();
    }
}
