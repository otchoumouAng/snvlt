<?php

namespace App\Services;

use App\Entity\DemandeAutorisation\NouvelleDemande;
use App\Entity\References\CircuitCommunication;
use App\Entity\References\ModeleCommunication;
use Doctrine\ORM\EntityManagerInterface;

class ValidationService
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function initializeValidation(NouvelleDemande $nouvelleDemande)
    {
        $typeDemande = $nouvelleDemande->getTypeDemande();
        $modeleCommunication = $this->entityManager->getRepository(ModeleCommunication::class)->findOneBy(['typeDemande' => $typeDemande, 'statut' => 'ACTIF']);

        if ($modeleCommunication) {
            $detailsModele = $modeleCommunication->getDetailsModeles();

            foreach ($detailsModele as $detail) {
                $circuit = new CircuitCommunication();
                $circuit->setNouvelleDemande($nouvelleDemande);
                $circuit->setCodeModele($modeleCommunication);
                $circuit->setNumSeq($detail->getNumseq());
                $circuit->setTypeService($detail->getTypeService());
                $circuit->setCodeDirection($detail->getCodeDirection());
                $circuit->setCodeService($detail->getCodeService());
                $circuit->setStatut('En attente');
                $circuit->setCreatedAt(new \DateTimeImmutable());
                $circuit->setCreatedBy($nouvelleDemande->getCreatedBy());

                $this->entityManager->persist($circuit);
            }

            $this->entityManager->flush();

            // Notify first actor
            $this->notifyNextActor($nouvelleDemande);
        }
    }

    public function processValidation(CircuitCommunication $circuit, string $status, string $observation, string $user)
    {
        $circuit->setStatut($status);
        $circuit->setObservation($observation);
        $circuit->setValide($status === 'Validé');
        $circuit->setUpdatedAt(new \DateTime());
        $circuit->setUpdatedBy($user);

        $this->entityManager->flush();

        if ($status === 'Validé') {
            $this->notifyNextActor($circuit->getNouvelleDemande());
        } else {
            // Notify operator of refusal
        }
    }

    public function notifyNextActor(NouvelleDemande $nouvelleDemande)
    {
        $nextCircuit = $this->entityManager->getRepository(CircuitCommunication::class)->findOneBy(['nouvelleDemande' => $nouvelleDemande, 'statut' => 'En attente'], ['num_seq' => 'ASC']);

        if ($nextCircuit) {
            // Send notification to the actor(s) of the next step
        } else {
            // All steps are validated, update final status of the demand
            $nouvelleDemande->setStatut('Validée');
            $this->entityManager->flush();
        }
    }
}
