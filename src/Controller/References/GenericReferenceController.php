<?php

namespace App\Controller\References;

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
 * @Route("/admin/reference")
 */
class GenericReferenceController extends AbstractController
{
    private const ENTITY_MAP = [
        'types_service' => \App\Entity\References\TypesService::class,
        'categories_activite' => \App\Entity\Paiement\CategoriesActivite::class,
        'types_demandeur' => \App\Entity\References\TypesDemandeur::class,
        'types_paiement' => \App\Entity\Paiement\TypePaiement::class,
    ];

    private function getEntityClass(string $entityName): ?string
    {
        return self::ENTITY_MAP[$entityName] ?? null;
    }

    private function getTitle(string $entityName): string
    {
        $titles = [
            'types_service' => 'Types de Service',
            'categories_activite' => 'Catégories d\'Activité',
            'types_demandeur' => 'Types de Demandeur',
            'types_paiement' => 'Type de paiement',
        ];
        return $titles[$entityName] ?? 'Gestion de Référence';
    }


    /**
     * @Route("/{entityName}/index", name="app_generic_reference_index")
     */
    public function index(string $entityName, MenuRepository $menus, NotificationRepository $notification, MenuPermissionRepository $permissions, UserRepository $userRepository): Response
    {
        $entityClass = $this->getEntityClass($entityName);
        if (!$entityClass) {
            throw $this->createNotFoundException('Type de référence non valide');
        }

        $user = $userRepository->find($this->getUser());
        $code_groupe = $user->getCodeGroupe()->getId();

        $form = $this->renderView('references/generic/form.html.twig', [
            'mode' => 'new',
            'entity' => null,
            'entityName' => $entityName,
            'title' => $this->getTitle($entityName)
        ]);

        return $this->render('references/generic/index.html.twig', [
            'liste_menus' => $menus->findOnlyParent(),
            "all_menus" => $menus->findAll(),
            'mes_notifs' => $notification->findBy(['to_user' => $this->getUser(), 'lu' => false], [], 5, 0),
            'menus' => $permissions->findBy(['code_groupe_id' => $code_groupe]),
            'groupe' => $code_groupe,
            'titre' => $this->getTitle($entityName),
            'liste_parent' => $permissions,
            'preloaded_form' => $form,
            'entityName' => $entityName
        ]);
    }

    /**
     * @Route("/{entityName}/data", name="app_generic_reference_data", methods={"GET"})
     */
    public function getData(string $entityName, EntityManagerInterface $em): JsonResponse
    {
        $entityClass = $this->getEntityClass($entityName);
        if (!$entityClass) {
            return $this->json(['data' => []]);
        }

        try {
            $items = $em->getRepository($entityClass)->findAll();

            $data = [];
            foreach ($items as $item) {
                $data[] = [
                    'id' => $item->getId(),
                    'libelle' => $item->getLibelle(),
                    'DT_RowId' => 'row_' . $item->getId()
                ];
            }

            return $this->json(['data' => $data]);
        } catch (\Exception $e) {
            return $this->json(['data' => []]);
        }
    }

    /**
     * @Route("/{entityName}/save", name="app_generic_reference_save", methods={"POST"})
     */
    public function save(string $entityName, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $entityClass = $this->getEntityClass($entityName);
        if (!$entityClass) {
            return $this->json(['success' => false, 'message' => 'Type de référence non valide'], 400);
        }

        try {
            $data = json_decode($request->getContent(), true);
            if ($data === null) return $this->json(['success' => false, 'message' => 'JSON invalide'], 400);

            $id = $data['id'] ?? null;
            $libelle = $data['libelle'] ?? null;

            if (empty($libelle)) {
                return $this->json(['success' => false, 'message' => 'Le libellé est requis'], 400);
            }

            $item = $id ? $em->getRepository($entityClass)->find($id) : new $entityClass();

            if (!$item) {
                return $this->json(['success' => false, 'message' => 'Élément non trouvé'], 404);
            }

            $item->setLibelle($libelle);

            $em->persist($item);
            $em->flush();

            return $this->json([
                'success' => true,
                'message' => 'Enregistré avec succès',
            ]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()], 500);
        }
    }
}
