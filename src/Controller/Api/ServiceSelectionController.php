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
        $filters = [
            'type_service' => $request->query->get('type_service_id'),
            'categorie_activite' => $request->query->get('categorie_activite_id'),
            'type_demandeur' => $request->query->get('type_demandeur_id'),
            'type_demande' => $request->query->get('type_demande_id'),
            'regime_fiscal' => $request->query->get('regime_fiscal_id'),
        ];

        // Déterminer le prochain champ à peupler
        $nextField = null;
        foreach ($filters as $key => $value) {
            if (empty($value)) {
                $nextField = $key;
                break;
            }
        }

        // Si aucun champ n'est à peupler, on retourne les services finaux
        if ($nextField === null) {
            $services = $repo->findDistinctBy($filters, null);
             return $this->json(['type' => 'services', 'options' => $services]);
        }


        $options = $repo->findDistinctBy(array_filter($filters), $nextField);

        return $this->json([
            'type' => $nextField,
            'options' => $options
        ]);
    }
}
