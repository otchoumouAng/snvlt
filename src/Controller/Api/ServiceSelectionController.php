<?php

namespace App\Controller\Api;

use App\Repository\Paiement\CatalogueServicesRepository;
use App\Repository\References\TypeDemandeRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @Route("/api")
 */
class ServiceSelectionController extends AbstractController
{
    #[Route('/service-details', name: 'api_service_details', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function getServiceDetails(
        Request $request,
        CatalogueServicesRepository $catalogueServicesRepository,
        UserRepository $userRepository
    ): JsonResponse
    {
        $typeDemandeId = $request->query->get('type_demande_id');
        $typePaiementId = $request->query->get('type_paiement_id');

        if (!$typeDemandeId || !$typePaiementId) {
            return $this->json(['error' => 'Les paramètres type_demande_id et type_paiement_id sont requis.'], 400);
        }

        // Déterminer le type de demandeur (personne morale ou physique)

        $user = $userRepository->find($this->getUser());
        $idOperateur = $user->getCodeOperateur()->getId();

        $typeDemandeurId = ($user->getCodeOperateur()->getId() == 2) ? 2 : 3;

        $service = $catalogueServicesRepository->findOneBy([
            'categorie_activite' => $typeDemandeId,
            'typePaiement' => $typePaiementId,
            'type_demandeur' => $typeDemandeurId
        ]);

        if (!$service) {
            // Let's try again without the type_demandeur criteria as a fallback
            $service = $catalogueServicesRepository->findOneBy([
                'categorie_activite' => $typeDemandeId,
                'typePaiement' => $typePaiementId,
                'type_demandeur' => null
            ]);
        }

        if (!$service) {
            return $this->json(['error' => 'Aucun service correspondant trouvé pour les critères fournis.'], 404);
        }

        $data = [
            'id' => $service->getId(),
            'designation' => $service->getDesignation(),
            'montant_fcfa' => $service->getMontantFcfa(),
            'note' => $service->getNote()
        ];

        return $this->json($data);
    }

    /**
     * @Route("/services_by_category", name="api_services_by_category", methods={"GET"})
     */
    public function getServicesByCategory(Request $request, CatalogueServicesRepository $repo): JsonResponse
    {
        $categorieId = $request->query->get('categorie_id');
        $typeDemandeId = $request->query->get('type_demande_id');

        if (!$categorieId) {
            return $this->json(['error' => 'categorie_id is required'], 400);
        }

        $criteria = ['categorie_activite' => $categorieId];
        if ($typeDemandeId) {
            $criteria['type_demande'] = $typeDemandeId;
        }

        $services = $repo->findBy($criteria);

        $data = [];
        foreach ($services as $service) {
            $data[] = [
                'id' => $service->getId(),
                'label' => $service->getDesignation() . ' - ' . number_format($service->getMontantFcfa(), 0, ',', ' ') . ' FCFA',
                'montant' => $service->getMontantFcfa()
            ];
        }

        return $this->json($data);
    }

    /**
     * @Route("/types_demande_options", name="api_types_demande_options", methods={"GET"})
     */
    public function getTypeDemandeOptions(TypeDemandeRepository $repo): JsonResponse
    {
        $types = $repo->findAll();

        $data = [];
        foreach ($types as $type) {
            $data[] = [
                'id' => $type->getId(),
                'label' => $type->getLibelle()
            ];
        }

        return $this->json($data);
    }
}
