<?php

namespace App\Controller\Validation;

use App\Entity\NouvelleDemande;
use App\Entity\Validation\ValidationNouvelleDemande;
use App\Repository\References\ModeleCommunicationRepository;
use App\Repository\Validation\ValidationNouvelleDemandeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/validation/nouvelle-demande')]
class ValidationNouvelleDemandeController extends AbstractController
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    #[Route('/', name: 'app_validation_nouvelle_demande_index')]
    public function index(): Response
    {
        // This will be the main page for listing demandes that need validation
        return $this->render('validation_nouvelle_demande/index.html.twig');
    }

    #[Route('/{id}', name: 'app_validation_nouvelle_demande_show', methods: ['GET'])]
    public function show(NouvelleDemande $nouvelleDemande, ModeleCommunicationRepository $modeleRepo, ValidationNouvelleDemandeRepository $validationRepo): JsonResponse
    {
        // Assume a mapping between TypeDemande and ModeleCommunication
        // For now, we'll hardcode it. Let's say TypeDemande ID 1 maps to ModeleCommunication ID 1.
        $modeleId = 1; // This should be dynamic in a real app
        $modele = $modeleRepo->find($modeleId);

        if (!$modele) {
            return $this->json(['error' => 'Modèle de validation non trouvé'], 404);
        }

        $circuitSteps = $modele->getCircuitCommunications()->getValues();
        $completedValidations = $validationRepo->findBy(['nouvelleDemande' => $nouvelleDemande]);

        $etapes = [];
        $isEnCoursSet = false;

        foreach ($circuitSteps as $step) {
            $statut = 'en_attente';
            foreach ($completedValidations as $validation) {
                if ($validation->getEtape() === $step->getServiceValidation()) {
                    $statut = 'validé';
                    break;
                }
            }
            $etapes[] = ['nom' => $step->getServiceValidation(), 'statut' => $statut];
        }

        // Determine the 'en_cours' step
        foreach ($etapes as &$etape) {
            if ($etape['statut'] === 'en_attente') {
                $etape['statut'] = 'en_cours';
                break;
            }
        }


        return $this->json([
            'id' => $nouvelleDemande->getId(),
            'titre' => $nouvelleDemande->getRaisonSocial(),
            'etapes' => $etapes
        ]);
    }

    #[Route('/{id}/valider-etape', name: 'app_validation_nouvelle_demande_valider_etape', methods: ['POST'])]
    public function validerEtape(NouvelleDemande $nouvelleDemande, ValidationNouvelleDemandeRepository $validationRepo, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Utilisateur non authentifié'], 401);
        }

        // In a real app, we would determine the current step from the logic in the show() method.
        // For now, let's just find the first step that is not yet validated.

        // This is a simplified logic. A real implementation needs to get the definition of steps
        // as in the show() method.
        $completedValidations = $validationRepo->findBy(['nouvelleDemande' => $nouvelleDemande]);
        $completedStepsNames = array_map(fn($v) => $v->getEtape(), $completedValidations);

        // Let's assume the steps are fixed for this example
        $allSteps = ['Instruction dossier', 'Vérification pièces', 'Visite de terrain', 'Rapport final', 'Signature'];
        $currentStepName = null;
        foreach($allSteps as $stepName){
            if(!in_array($stepName, $completedStepsNames)){
                $currentStepName = $stepName;
                break;
            }
        }

        if ($currentStepName === null) {
            return $this->json(['success' => false, 'message' => 'Toutes les étapes sont déjà validées.']);
        }


        $validation = new ValidationNouvelleDemande();
        $validation->setNouvelleDemande($nouvelleDemande);
        $validation->setValidateur($user);
        $validation->setEtape($currentStepName);
        $validation->setStatut('validé');

        $this->entityManager->persist($validation);
        $this->entityManager->flush();

        return $this->json(['success' => true, 'message' => 'Étape validée avec succès.']);
    }
}
