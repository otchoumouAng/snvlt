<?php

namespace App\Controller\Api;

use App\Repository\References\CatalogueServicesRepository;
use App\Repository\References\TypeDemandeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api")
 */
class ServiceSelectionController extends AbstractController
{
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
