<?php

namespace App\Repository\DemandeAutorisation;

use App\Entity\DemandeAutorisation\EtapeValidation;
use App\Entity\References\Direction;
use App\Entity\References\ServiceMinef;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EtapeValidation>
 *
 * @method EtapeValidation|null find($id, $lockMode = null, $lockVersion = null)
 * @method EtapeValidation|null findOneBy(array $criteria, array $orderBy = null)
 * @method EtapeValidation[]    findAll()
 * @method EtapeValidation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EtapeValidationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EtapeValidation::class);
    }

    public function save(EtapeValidation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(EtapeValidation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return EtapeValidation[]
     */
    /*public function findPendingStepsForUser(?Direction $direction, ?ServiceMinef $service): array
    {
        

        $qb = $this->createQueryBuilder('e')
            ->where('e.statut = :statut')
            ->setParameter('statut', 'En cours');

        //if ($direction) {
        //    $qb->andWhere('e.nom = :nom')
        //        ->setParameter('nom', $direction->getDenomination());
        //} elseif ($service) {
        //    $qb->andWhere('e.nom = :nom')
        //        ->setParameter('nom', $service->getLibelleService());
        //} else {
        //    return [];
        //}


        return $qb->orderBy('e.id', 'DESC')
            ->getQuery()
            ->getResult();
    }*/


    public function findPendingStepsForUser(?Direction $direction, ?ServiceMinef $service): array
    {
        // Si ni direction ni service, on ne peut rien filtrer.
        if (!$direction && !$service) {
            return [];
        }

        // --- ÉTAPE 1: Sous-requête ---
        // Pour chaque demande, on trouve le plus petit 'ordre' de l'étape
        // dont la date de traitement est NULLE. C'est la nouvelle définition de l'étape "active".
        $subQb = $this->createQueryBuilder('e2');
        $subQb
            ->select('MIN(e2.ordre)')
            ->where('e2.demande = e.demande') // Corrélation avec la requête principale 'e'
            ->andWhere('e2.dateTraitement IS NULL'); // CHANGEMENT 1: On vérifie si la date est NULL

        // --- ÉTAPE 2: Requête Principale ---
        $qb = $this->createQueryBuilder('e');
        $qb
            // Condition 1: L'étape elle-même doit avoir une date de traitement NULLE.
            ->where('e.dateTraitement IS NULL') // CHANGEMENT 2: On vérifie si la date est NULL
            
            // Condition 2: L'ordre de l'étape doit correspondre à l'ordre minimal
            // trouvé par la sous-requête. On sélectionne ainsi UNIQUEMENT l'étape active.
            ->andWhere($qb->expr()->eq('e.ordre', '(' . $subQb->getDQL() . ')'));


        return $qb->orderBy('e.id', 'DESC')
            ->getQuery()
            ->getResult();
    }



    /*public function findPendingStepsForUser(?Direction $direction, ?ServiceMinef $service): array
    {
        // ... (tout le code du QueryBuilder comme dans ma réponse précédente)

        // AJOUTEZ CES LIGNES JUSTE AVANT LE RETURN
        // ===========================================
        echo "Requête SQL générée : <pre>" . $qb->getQuery()->getSQL() . "</pre>";
        echo "Paramètres : <pre>";
        print_r($qb->getQuery()->getParameters());
        echo "</pre>";
        die; // On arrête le script ici pour voir le résultat
        // ===========================================

        return $qb->getQuery()
            ->getResult();
    }*/
}
