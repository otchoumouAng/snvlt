<?php

namespace App\Controller\Paiement;

use App\Repository\Paiement\CategoriesActiviteRepository;
use App\Repository\Paiement\CatalogueServicesRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class ApiController extends AbstractController
{
    #[Route('/categories_activite', name: 'api_get_categories_activite', methods: ['GET'])]
    public function getCategoriesActivite(CategoriesActiviteRepository $repo): JsonResponse
    {
        $categories = $repo->findAll();
        $data = [];
        foreach ($categories as $categorie) {
            $data[] = [
                'id' => $categorie->getId(),
                'label' => $categorie->getLibelle(),
            ];
        }
        return $this->json($data);
    }

    #[Route('/services_by_type_and_category', name: 'api_get_services_by_type_and_category', methods: ['GET'])]
    public function getServicesByTypeAndCategory(Request $request, CatalogueServicesRepository $repo): JsonResponse
    {
        $typePaiementId = $request->query->get('type_paiement_id');
        $categoryId = $request->query->get('categorie_id');

        $services = $repo->findBy([
            'typePaiement' => $typePaiementId,
            'categorie_activite' => $categoryId,
        ]);

        $data = [];
        foreach ($services as $service) {
            $data[] = [
                'id' => $service->getId(),
                'label' => $service->getDesignation(),
                'montant' => $service->getMontantFcfa(),
            ];
        }
        return $this->json($data);
    }
}
