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
use Symfony\Component\Security\Core\User\UserInterface;

abstract class BaseReferenceController extends AbstractController
{
    protected string $entityClass;
    protected string $entityName;
    protected string $title;

    protected function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Mappe une entité vers un tableau pour le DataTable.
     * Les contrôleurs enfants peuvent surcharger cette méthode pour les entités qui n'utilisent pas 'libelle'.
     * @param object $item L'entité à convertir.
     * @return array
     */
    protected function toArray($item): array
    {
        return [
            'id' => $item->getId(),
            'libelle' => $item->getLibelle(), // Par défaut, on suppose que la méthode getLibelle existe
            'DT_RowId' => 'row_' . $item->getId()
        ];
    }

    /**
     * Remplit une entité à partir d'un tableau de données.
     * Les contrôleurs enfants peuvent surcharger cette méthode pour les entités qui n'utilisent pas 'libelle'.
     * @param object $item L'entité à remplir.
     * @param array $data Les données provenant de la requête.
     * @return object
     */
    protected function fromArray($item, array $data)
    {
        $label = $data['libelle'] ?? null;
        if (empty($label)) {
            throw new \InvalidArgumentException('Le libellé est requis');
        }
        $item->setLibelle($label); // Par défaut, on suppose que la méthode setLibelle existe
        return $item;
    }

    public function index(MenuRepository $menus, NotificationRepository $notification, MenuPermissionRepository $permissions, UserRepository $userRepository): Response
    {
        /** @var UserInterface|null $user */
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à cette page.');
        }

        $dbUser = $userRepository->find($user);
        if (!$dbUser || !method_exists($dbUser, 'getCodeGroupe')) {
             throw $this->createNotFoundException('Détails de l\'utilisateur non trouvés.');
        }
        
        $code_groupe = $dbUser->getCodeGroupe()->getId();

        $form = $this->renderView('references/generic/form.html.twig', [
            'mode' => 'new',
            'entity' => null,
            'entityName' => $this->entityName,
            'title' => $this->getTitle()
        ]);

        return $this->render('references/generic/index.html.twig', [
            'liste_menus' => $menus->findOnlyParent(),
            "all_menus" => $menus->findAll(),
            'mes_notifs' => $notification->findBy(['to_user' => $user, 'lu' => false], [], 5, 0),
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
            // Utilise la nouvelle méthode toArray pour le mappage
            $data = array_map([$this, 'toArray'], $items);
            return $this->json(['data' => $data]);
        } catch (\Exception $e) {
            return $this->json(['data' => [], 'error' => 'Impossible de récupérer les données : ' . $e->getMessage()], 500);
        }
    }

    public function save(Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            if ($data === null) {
                return $this->json(['success' => false, 'message' => 'JSON invalide'], 400);
            }

            $id = $data['id'] ?? null;
            $item = $id ? $em->getRepository($this->entityClass)->find($id) : new $this->entityClass();

            if (!$item) {
                return $this->json(['success' => false, 'message' => 'Élément non trouvé'], 404);
            }

            // Gestion des champs d'audit
            if ($id) { // Élément existant
                if (method_exists($item, 'setUpdatedAt')) {
                    $item->setUpdatedAt(new \DateTimeImmutable());
                }
                if (method_exists($item, 'setUpdatedBy') && $this->getUser()) {
                    $item->setUpdatedBy($this->getUser()->getUserIdentifier());
                }
            } else { // Nouvel élément
                if (method_exists($item, 'setCreatedAt')) {
                    $item->setCreatedAt(new \DateTimeImmutable());
                }
                if (method_exists($item, 'setCreatedBy') && $this->getUser()) {
                    $item->setCreatedBy($this->getUser()->getUserIdentifier());
                }
            }

            // Utilise la nouvelle méthode fromArray pour remplir l'entité
            $this->fromArray($item, $data);

            $em->persist($item);
            $em->flush();

            return $this->json([
                'success' => true,
                'message' => 'Enregistré avec succès',
            ]);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 400);
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
            if (str_contains($e->getMessage(), 'ConstraintViolationException')) {
                return $this->json(['success' => false, 'message' => 'Erreur: Impossible de supprimer cet élément car il est utilisé ailleurs.'], 409);
            }
            return $this->json(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()], 500);
        }
    }
}

