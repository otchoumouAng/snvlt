Pour automatiser la création du circuit de validation après la création d'une `NouvelleDemande`, l'approche la plus robuste et la plus propre dans une application Symfony est d'utiliser un **Event Listener** (un écouteur d'événements).

Le principe est le suivant :
1.  Votre application va "écouter" le moment précis où une nouvelle `NouvelleDemande` est sauvegardée en base de données.
2.  À ce moment-là, un service dédié se déclenchera automatiquement.
3.  Ce service se chargera de :
    a.  Récupérer le `TypeDemande` de la `NouvelleDemande` qui vient d'être créée.
    b.  Trouver le circuit de validation (`ModeleCommunication`) actif qui correspond à ce `TypeDemande`.
    c.  Parcourir toutes les étapes (`DetailsModele`) de ce circuit.
    d.  Pour chaque étape, créer une nouvelle entrée dans la table `metier.aut_etape_validation` et l'associer à la `NouvelleDemande`.

Voici les étapes techniques pour mettre cela en place :

**Étape 1 : Créer un Event Listener**

Vous devez créer une nouvelle classe PHP, par exemple `NouvelleDemandeListener.php`, dans le dossier `src/EventListeners/`.

```php
<?php

namespace App\EventListeners;

use App\Entity\DemandeAutorisation\EtapeValidation;
use App\Entity\DemandeAutorisation\NouvelleDemande;
use App\Repository\References\ModeleCommunicationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class NouvelleDemandeListener
{
    private $modeleCommunicationRepository;
    private $entityManager;

    public function __construct(ModeleCommunicationRepository $modeleCommunicationRepository, EntityManagerInterface $entityManager)
    {
        $this->modeleCommunicationRepository = $modeleCommunicationRepository;
        $this->entityManager = $entityManager;
    }

    public function postPersist(NouvelleDemande $nouvelleDemande, LifecycleEventArgs $event): void
    {
        // 1. Récupérer le type de la demande
        $typeDemande = $nouvelleDemande->getTypeDemande();

        if (!$typeDemande) {
            return; // Pas de type de demande, on ne fait rien
        }

        // 2. Trouver le modèle de communication actif pour ce type de demande
        $modele = $this->modeleCommunicationRepository->findOneBy([
            'typeDemande' => $typeDemande,
            'statut' => 'ACTIF'
        ]);

        if (!$modele) {
            return; // Pas de circuit de validation actif, on ne fait rien
        }

        // 3. Récupérer les détails (étapes) du modèle
        $detailsModele = $modele->getDetailsModeles();

        // 4. Créer une EtapeValidation pour chaque détail du modèle
        foreach ($detailsModele as $detail) {
            $etapeValidation = new EtapeValidation();
            $etapeValidation->setDemande($nouvelleDemande);
            $etapeValidation->setOrdre($detail->getNumseq());

            // Définir le nom de l'étape en fonction du type de service
            if ($detail->getTypeService() === 'DIRECTION' && $detail->getCodeDirection()) {
                $etapeValidation->setNom($detail->getCodeDirection()->getDenomination());
            } elseif ($detail->getTypeService() === 'SERVICE' && $detail->getCodeService()) {
                $etapeValidation->setNom($detail->getCodeService()->getLibelleService());
            } else {
                $etapeValidation->setNom('Étape inconnue');
            }

            // Statut initial de l'étape
            $etapeValidation->setStatut('en_attente');

            $this->entityManager->persist($etapeValidation);
        }

        // 5. Sauvegarder toutes les nouvelles étapes en base de données
        $this->entityManager->flush();
    }
}
```

**Étape 2 : Déclarer le Listener comme un service**

Vous devez ensuite déclarer cette classe comme un service et lui dire d'écouter l'événement `postPersist` de l'entité `NouvelleDemande`. Pour cela, ajoutez ce qui suit à votre fichier `config/services.yaml` :

```yaml
services:
    # ... autres services

    App\EventListeners\NouvelleDemandeListener:
        tags:
            - { name: 'doctrine.orm.entity_listener', entity: 'App\Entity\DemandeAutorisation\NouvelleDemande', event: 'postPersist' }

```

**Comment ça marche, en résumé :**

*   **`postPersist`** : C'est un événement Doctrine qui se déclenche juste après qu'une nouvelle entité a été insérée en base de données pour la première fois.
*   **Injection de dépendances** : Symfony injectera automatiquement le `ModeleCommunicationRepository` et l'`EntityManager` dans votre listener, vous donnant accès à tout ce dont vous avez besoin.
*   **Logique métier** : La logique est contenue et isolée dans le listener, ce qui rend votre `NouvelleDemandeController` plus simple et respecte le principe de responsabilité unique.

Avec cette implémentation, chaque fois qu'une `NouvelleDemande` sera créée, son circuit de validation sera automatiquement généré, rendant le processus fiable et transparent.
