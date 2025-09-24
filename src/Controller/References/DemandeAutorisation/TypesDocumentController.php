<?php

namespace App\Controller\References\DemandeAutorisation;

use App\Controller\References\BaseReferenceController;
use App\Entity\DemandeAutorisation\TypeDocument;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\MenuRepository;
use App\Repository\Administration\NotificationRepository;
use App\Repository\MenuPermissionRepository;
use App\Repository\UserRepository;

#[Route('/admin/reference/types-document')]
class TypesDocumentController extends BaseReferenceController
{
    public function __construct()
    {
        $this->entityClass = TypeDocument::class;
        $this->entityName = 'app_types_document';
        $this->title = 'Types de Document';
    }

    /**
     * {@inheritdoc}
     * Nous surchargeons cette méthode pour mapper correctement 'designation' vers la clé 'libelle'
     * attendue par le frontend.
     */
    protected function toArray($item): array
    {
        // On s'assure que l'item est du bon type pour l'analyse statique (garanti à l'exécution).
        if (!$item instanceof TypeDocument) {
            return [];
        }
        return [
            'id' => $item->getId(),
            'libelle' => $item->getDesignation(), // On utilise getDesignation()
            'fichierSpecial' => $item->isFichierSpecial(),
            'DT_RowId' => 'row_' . $item->getId()
        ];
    }

    /**
     * {@inheritdoc}
     * Nous surchargeons cette méthode pour remplir correctement le champ 'designation'
     * à partir des données de la requête.
     */
    protected function fromArray($item, array $data)
    {
        // On s'assure que l'item est du bon type.
        if (!$item instanceof TypeDocument) {
            return $item;
        }

        $label = $data['libelle'] ?? null;
        if (empty($label)) {
            throw new \InvalidArgumentException('La désignation est requise');
        }
        $item->setDesignation($label); // On utilise setDesignation()

        $fichierSpecial = $data['fichierSpecial'] ?? false;
        $item->setFichierSpecial((bool)$fichierSpecial);

        return $item;
    }


    #[Route('/', name: 'app_types_document_index', methods: ['GET'])]
    public function index(MenuRepository $menus, NotificationRepository $notification, MenuPermissionRepository $permissions, UserRepository $userRepository): Response
    {
        return parent::index($menus, $notification, $permissions, $userRepository);
    }

    #[Route('/data', name: 'app_types_document_data', methods: ['GET'])]
    public function getData(EntityManagerInterface $em): JsonResponse
    {
        return parent::getData($em);
    }

    #[Route('/save', name: 'app_types_document_save', methods: ['POST'])]
    public function save(Request $request, EntityManagerInterface $em): JsonResponse
    {
        return parent::save($request, $em);
    }

    #[Route('/delete/{id}', name: 'app_types_document_delete', methods: ['DELETE'])]
    public function delete(int $id, EntityManagerInterface $em): JsonResponse
    {
        return parent::delete($id, $em);
    }
}

