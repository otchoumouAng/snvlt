<?php

namespace App\Controller\Api;

use App\Repository\References\CatalogueServicesRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/services")
 */
class ServiceSelectionController extends AbstractController
{
    /**
     * @Route("/options", name="api_services_options", methods={"GET"})
     */
    public function getOptions(Request $request, CatalogueServicesRepository $repo): JsonResponse
    {
        $categorie_activite_id = $request->query->get('categorie_activite_id');
        $type_service_id = $request->query->get('type_service_id');

        $filters = [];
        $nextField = 'type_service'; // Par défaut, on cherche les types de service

        if ($categorie_activite_id) {
            $filters['categorie_activite'] = $categorie_activite_id;
        }

        if ($type_service_id) {
            $filters['type_service'] = $type_service_id;
            $nextField = 'services'; // Si les deux sont fournis, on cherche les services finaux
        }

        if ($nextField === 'services') {
             $options = $repo->findDistinctBy($filters, null);
        } else {
             $options = $repo->findDistinctBy($filters, $nextField);
        }

        return $this->json([
            'type' => $nextField,
            'options' => $options
        ]);
    }
}
