<?php

namespace App\Controller\References;

use App\Repository\Administration\NotificationRepository;
use App\Repository\MenuPermissionRepository;
use App\Repository\MenuRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class BaseReferenceController extends AbstractController
{
    protected string $entityClass;
    protected string $entityName;
    protected string $title;

    protected function getTitle(): string
    {
        return $this->title;
    }

    public function index(MenuRepository $menus, NotificationRepository $notification, MenuPermissionRepository $permissions, UserRepository $userRepository): Response
    {
        $user = $userRepository->find($this->getUser());
        $code_groupe = $user->getCodeGroupe()->getId();

        $form = $this->renderView('references/generic/form.html.twig', [
            'mode' => 'new',
            'entity' => null,
            'entityName' => $this->entityName,
            'title' => $this->getTitle()
        ]);

        return $this->render('references/generic/index.html.twig', [
            'liste_menus' => $menus->findOnlyParent(),
            "all_menus" => $menus->findAll(),
            'mes_notifs' => $notification->findBy(['to_user' => $this->getUser(), 'lu' => false], [], 5, 0),
            'menus' => $permissions->findBy(['code_groupe_id' => $code_groupe]),
            'groupe' => $code_groupe,
            'titre' => $this->getTitle(),
            'liste_parent' => $permissions,
            'preloaded_form' => $form,
            'entityName' => $this->entityName,
            'data_url' => $this->generateUrl($this->entityName . '_data'),
            'save_url' => $this->generateUrl($this->entityName . '_save'),
            'delete_url' => $this->generateUrl($this->entityName . '_delete', ['id' => 0]),
        ]);
    }

    public function getData(EntityManagerInterface $em): JsonResponse
    {
        try {
            $items = $em->getRepository($this->entityClass)->findAll();

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

    public function save(Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            if ($data === null) return $this->json(['success' => false, 'message' => 'JSON invalide'], 400);

            $id = $data['id'] ?? null;
            $libelle = $data['libelle'] ?? null;

            if (empty($libelle)) {
                return $this->json(['success' => false, 'message' => 'Le libellé est requis'], 400);
            }

            if ($id) {
                $item = $em->getRepository($this->entityClass)->find($id);
                if (!$item) {
                    return $this->json(['success' => false, 'message' => 'Élément non trouvé'], 404);
                }
                if (method_exists($item, 'setUpdatedAt')) {
                    $item->setUpdatedAt(new \DateTimeImmutable());
                }
                 if (method_exists($item, 'setUpdatedBy')) {
                    $item->setUpdatedBy($this->getUser()->getUserIdentifier());
                }
            } else {
                $item = new $this->entityClass();
                if (method_exists($item, 'setCreatedAt')) {
                    $item->setCreatedAt(new \DateTimeImmutable());
                }
                if (method_exists($item, 'setCreatedBy')) {
                    $item->setCreatedBy($this->getUser()->getUserIdentifier());
                }
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

    public function delete(int $id, EntityManagerInterface $em): JsonResponse
    {
        try {
            $item = $em->getRepository($this->entityClass)->find($id);
            if (!$item) {
                return $this->json(['success' => false, 'message' => 'Élément non trouvé'], 404);
            }

            $em->remove($item);
            $em->flush();

            return $this->json(['success' => true, 'message' => 'Supprimé avec succès']);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()], 500);
        }
    }
}
