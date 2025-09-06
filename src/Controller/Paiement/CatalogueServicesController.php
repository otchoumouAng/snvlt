<?php

namespace App\Controller\Paiement;

use App\Entity\Paiement\TypePaiement;
use App\Entity\Paiement\CatalogueServices;
use App\Entity\References\TypesService;
use App\Entity\Paiement\CategoriesActivite;
use App\Entity\References\TypesDemandeur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\Administration\NotificationRepository;
use App\Repository\MenuPermissionRepository;
use App\Repository\MenuRepository;
use App\Repository\UserRepository;

/**
 * @Route("admin/paiement/catalogue_services")
 */
class CatalogueServicesController extends AbstractController
{
    /**
     * @Route("/index", name="app_catalogue_services_index")
     */
    public function index(EntityManagerInterface $em, MenuRepository $menus, NotificationRepository $notification, MenuPermissionRepository $permissions, UserRepository $userRepository): Response
    {
        $user = $userRepository->find($this->getUser());
        $code_groupe = $user->getCodeGroupe()->getId();
        $titre = 'Gestion du Catalogue des Services';

        // Data for form dropdowns
        $formData = [
            'types_service' => $em->getRepository(TypesService::class)->findAll(),
            'categories_activite' => $em->getRepository(CategoriesActivite::class)->findAll(),
            'types_demandeur' => $em->getRepository(TypesDemandeur::class)->findAll(),
            'type_paiements' => $em->getRepository(TypePaiement::class)->findAll(),
            'catalogue_service' => null
        ];

        $newForm = $this->renderView('paiement/catalogue_services/form.html.twig', ['mode' => 'new'] + $formData);
        $editForm = $this->renderView('paiement/catalogue_services/form.html.twig', ['mode' => 'edit'] + $formData);

        return $this->render('paiement/catalogue_services/index.html.twig', [
            'liste_menus' => $menus->findOnlyParent(),
            "all_menus" => $menus->findAll(),
            'mes_notifs' => $notification->findBy(['to_user' => $this->getUser(), 'lu' => false], [], 5, 0),
            'menus' => $permissions->findBy(['code_groupe_id' => $code_groupe]),
            'groupe' => $code_groupe,
            'titre' => $titre,
            'liste_parent' => $permissions,
            'preloaded_forms' => [
                'new' => $newForm,
                'edit' => $editForm
            ]
        ]);
    }

    /**
     * @Route("/data", name="app_catalogue_services_data", methods={"GET"})
     */
    public function getData(EntityManagerInterface $em): JsonResponse
    {
        try {
            $catalogue = $em->getRepository(CatalogueServices::class)->findAll();

            $data = [];
            foreach ($catalogue as $service) {
                $data[] = [
                    'id' => $service->getId(),
                    'code_service' => $service->getCodeService(),
                    'designation' => $service->getDesignation(),
                    'montant_fcfa' => number_format($service->getMontantFcfa(), 0, ',', ' '),
                    'type_service' => $service->getTypeService()?->getLibelle(),
                    'DT_RowId' => 'row_' . $service->getId()
                ];
            }

            return $this->json(['data' => $data]);
        } catch (\Exception $e) {
            return $this->json(['data' => []]);
        }
    }

    /**
     * @Route("/save", name="app_catalogue_services_save", methods={"POST"})
     */
    public function save(Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            if ($data === null) return $this->json(['success' => false, 'message' => 'JSON invalide'], 400);

            $id = $data['id'] ?? null;
            $catalogueService = $id ? $em->getRepository(CatalogueServices::class)->find($id) : new CatalogueServices();

            if (!$catalogueService) {
                return $this->json(['success' => false, 'message' => 'Service non trouvé'], 404);
            }

            $catalogueService->setCodeService($data['code_service'] ?? '');
            $catalogueService->setDesignation($data['designation'] ?? '');
            $catalogueService->setMontantFcfa($data['montant_fcfa'] ?? '0');
            $catalogueService->setNote($data['note'] ?? null);

            $typeService = $em->getRepository(TypesService::class)->find($data['type_service_id']);
            if (!$typeService) return $this->json(['success' => false, 'message' => 'Type de service invalide'], 400);
            $catalogueService->setTypeService($typeService);

            $categorieActivite = $em->getRepository(CategoriesActivite::class)->find($data['categorie_activite_id']);
            if (!$categorieActivite) return $this->json(['success' => false, 'message' => 'Catégorie d\'activité invalide'], 400);
            $catalogueService->setCategorieActivite($categorieActivite);

            if (!empty($data['type_demandeur_id'])) {
                $catalogueService->setTypeDemandeur($em->getRepository(TypesDemandeur::class)->find($data['type_demandeur_id']));
            } else {
                 $catalogueService->setTypeDemandeur(null);
            }

            if (!empty($data['type_paiement_id'])) {
                $catalogueService->setTypePaiement($em->getRepository(TypePaiement::class)->find($data['type_paiement_id']));
            } else {
                $catalogueService->setTypePaiement(null);
            }


            $em->persist($catalogueService);
            $em->flush();

            return $this->json(['success' => true, 'message' => 'Service enregistré avec succès']);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()], 500);
        }
    }

    /**
     * @Route("/{id}/details", name="app_catalogue_services_details", methods={"GET"})
     */
    public function getDetails(CatalogueServices $catalogueService): JsonResponse
    {
        try {
            $data = [
                'id' => $catalogueService->getId(),
                'code_service' => $catalogueService->getCodeService(),
                'designation' => $catalogueService->getDesignation(),
                'montant_fcfa' => $catalogueService->getMontantFcfa(),
                'note' => $catalogueService->getNote(),
                'type_service_id' => $catalogueService->getTypeService()?->getId(),
                'categorie_activite_id' => $catalogueService->getCategorieActivite()?->getId(),
                'type_demandeur_id' => $catalogueService->getTypeDemandeur()?->getId(),
                'type_paiement_id' => $catalogueService->getTypePaiement()?->getId(),
            ];

            return $this->json($data);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors de la récupération des détails'], 500);
        }
    }
}
