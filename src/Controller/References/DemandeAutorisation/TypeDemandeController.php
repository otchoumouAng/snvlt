<?php

namespace App\Controller\References\DemandeAutorisation;

use App\Controller\References\BaseReferenceController;
use App\Entity\DemandeAutorisation\TypeDemande;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\MenuRepository;
use App\Repository\Administration\NotificationRepository;
use App\Repository\MenuPermissionRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\DemandeAutorisation\TypeDemandeDetail;
use App\Entity\DemandeAutorisation\TypeDocument;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

#[Route('/admin/reference/types-demande')]
class TypeDemandeController extends BaseReferenceController
{
    public function __construct()
    {
        $this->entityClass = TypeDemande::class;
        $this->entityName = 'app_types_demande';
        $this->title = 'Types de Demande';
    }

    #[Route('/', name: 'app_types_demande_index', methods: ['GET'])]
    public function index(MenuRepository $menus, NotificationRepository $notification, MenuPermissionRepository $permissions, UserRepository $userRepository): Response
    {
        return parent::index($menus, $notification, $permissions, $userRepository);
    }

    #[Route('/data', name: 'app_types_demande_data', methods: ['GET'])]
    public function getData(EntityManagerInterface $em): JsonResponse
    {
        return parent::getData($em);
    }

    #[Route('/save', name: 'app_types_demande_save', methods: ['POST'])]
    public function save(Request $request, EntityManagerInterface $em): JsonResponse
    {
        return parent::save($request, $em);
    }

    #[Route('/delete/{id}', name: 'app_types_demande_delete', methods: ['DELETE'])]
    public function delete(int $id, EntityManagerInterface $em): JsonResponse
    {
        return parent::delete($id, $em);
    }

    #[Route('/documents', name: 'app_types_demande_documents', methods: ['GET'])]
    public function getDocuments(EntityManagerInterface $em): JsonResponse
    {
        $documents = $em->getRepository(TypeDocument::class)->findAll();
        $data = [];
        foreach ($documents as $document) {
            $data[] = [
                'id' => $document->getId(),
                'libelle' => $document->getDesignation()
            ];
        }
        return $this->json($data);
    }

    #[Route('/{id}/details', name: 'app_types_demande_details', methods: ['GET'])]
    public function getDetails(int $id, EntityManagerInterface $em): JsonResponse
    {
        $details = $em->getRepository(TypeDemandeDetail::class)->findBy(['typeDemande' => $id]);
        $data = [];
        foreach ($details as $detail) {
            $data[] = [
                'id' => $detail->getId(),
                'document' => $detail->getTypeDocument()->getDesignation()
            ];
        }
        return $this->json($data);
    }

    #[Route('/add-detail', name: 'app_types_demande_add_detail', methods: ['POST'])]
    public function addDetail(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $typeDemandeId = $data['type_demande_id'] ?? null;
        $typeDocumentId = $data['type_document_id'] ?? null;

        if (!$typeDemandeId || !$typeDocumentId) {
            return $this->json(['success' => false, 'message' => 'Données invalides'], 400);
        }

        $typeDemande = $em->getRepository(TypeDemande::class)->find($typeDemandeId);
        $typeDocument = $em->getRepository(TypeDocument::class)->find($typeDocumentId);

        if (!$typeDemande || !$typeDocument) {
            return $this->json(['success' => false, 'message' => 'Entités non trouvées'], 404);
        }

        // Check if the association already exists
        $existingDetail = $em->getRepository(TypeDemandeDetail::class)->findOneBy([
            'typeDemande' => $typeDemande,
            'typeDocument' => $typeDocument
        ]);

        if ($existingDetail) {
            return $this->json(['success' => false, 'message' => 'Ce document est déjà associé à ce type de demande.']);
        }

        $detail = new TypeDemandeDetail();
        $detail->setTypeDemande($typeDemande);
        $detail->setTypeDocument($typeDocument);

        $em->persist($detail);
        $em->flush();

        return $this->json(['success' => true, 'message' => 'Document ajouté avec succès']);
    }

    #[Route('/remove-detail/{id}', name: 'app_types_demande_remove_detail', methods: ['DELETE'])]
    public function removeDetail(int $id, EntityManagerInterface $em): JsonResponse
    {
        $detail = $em->getRepository(TypeDemandeDetail::class)->find($id);

        if (!$detail) {
            return $this->json(['success' => false, 'message' => 'Détail non trouvé'], 404);
        }

        $em->remove($detail);
        $em->flush();

        return $this->json(['success' => true, 'message' => 'Document supprimé avec succès']);
    }
}
