<?php

namespace App\Controller\DemandeAutorisation;

use App\Entity\DemandeAutorisation\NouvelleDemande;
use App\Entity\References\CircuitCommunication;
use App\Services\ValidationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/validation")
 */
class ValidationController extends AbstractController
{
    private $validationService;
    private $entityManager;

    public function __construct(ValidationService $validationService, EntityManagerInterface $entityManager)
    {
        $this->validationService = $validationService;
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/{id}", name="app_validation_show")
     */
    public function show(NouvelleDemande $nouvelleDemande): Response
    {
        // Find the current validation step for the current user
        $user = $this->getUser();
        // This logic depends on how users are associated with Direction/ServiceMinef
        // For now, let's assume we get the circuit to validate from the request or another way
        $circuit = $this->entityManager->getRepository(CircuitCommunication::class)->findOneBy(['nouvelleDemande' => $nouvelleDemande, 'statut' => 'En attente'], ['num_seq' => 'ASC']);

        return $this->render('demande_autorisation/validation/show.html.twig', [
            'demande' => $nouvelleDemande,
            'circuit' => $circuit,
        ]);
    }

    /**
     * @Route("/process/{id}", name="app_validation_process", methods={"POST"})
     */
    public function process(Request $request, CircuitCommunication $circuit): Response
    {
        $status = $request->request->get('status');
        $observation = $request->request->get('observation');
        $user = $this->getUser()->getUserIdentifier();

        $this->validationService->processValidation($circuit, $status, $observation, $user);

        $this->addFlash('success', 'Validation traitée avec succès.');

        return $this->redirectToRoute('app_home'); // Or wherever you want to redirect
    }
}
