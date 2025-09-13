<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

use App\Entity\DemandeAutorisation\TypeDemande;
use App\Entity\DemandeAutorisation\TypeDemandeDetail;
use App\Repository\DemandeAutorisation\TypeDocumentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[Route('/api')]
class TypeDemandeApiController extends AbstractController
{
    #[Route('/type-documents', name: 'api_type_documents_list', methods: ['GET'])]
    public function listTypeDocuments(TypeDocumentRepository $typeDocumentRepository): JsonResponse
    {
        $documents = $typeDocumentRepository->findAll();
        // Using ->findAll() is simple. For larger datasets, consider pagination.
        // The 'groups' attribute can be used for serialization to control output.
        return $this->json($documents, 200, [], ['groups' => 'document:list']);
    }

    #[Route('/type-demande/{id}/documents', name: 'api_type_demande_documents_list', methods: ['GET'])]
    public function listAssociatedDocuments(TypeDemande $typeDemande): JsonResponse
    {
        // The 'demande:details' group should be configured on TypeDemande,
        // TypeDemandeDetail, and TypeDocument entities to shape the output.
        return $this->json($typeDemande, 200, [], ['groups' => 'demande:details']);
    }

    #[Route('/type-demande/{id}/documents', name: 'api_type_demande_documents_add', methods: ['POST'])]
    public function addAssociatedDocument(Request $request, TypeDemande $typeDemande, EntityManagerInterface $entityManager, TypeDocumentRepository $typeDocumentRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $typeDocumentId = $data['type_document_id'] ?? null;

        if (!$typeDocumentId) {
            return $this->json(['error' => 'Missing type_document_id'], Response::HTTP_BAD_REQUEST);
        }

        $typeDocument = $typeDocumentRepository->find($typeDocumentId);
        if (!$typeDocument) {
            return $this->json(['error' => 'TypeDocument not found'], Response::HTTP_NOT_FOUND);
        }

        // Check if the association already exists to avoid duplicates
        $existing = $entityManager->getRepository(TypeDemandeDetail::class)->findOneBy([
            'typeDemande' => $typeDemande,
            'typeDocument' => $typeDocument
        ]);

        if ($existing) {
            return $this->json(['error' => 'Association already exists'], Response::HTTP_CONFLICT);
        }

        $typeDemandeDetail = new TypeDemandeDetail();
        $typeDemandeDetail->setTypeDemande($typeDemande);
        $typeDemandeDetail->setTypeDocument($typeDocument);

        $entityManager->persist($typeDemandeDetail);
        $entityManager->flush();

        return $this->json($typeDemandeDetail, Response::HTTP_CREATED, [], ['groups' => 'demande:details']);
    }

    #[Route('/type-demande-detail/{id}', name: 'api_type_demande_detail_delete', methods: ['DELETE'])]
    public function deleteAssociatedDocument(TypeDemandeDetail $typeDemandeDetail, EntityManagerInterface $entityManager): JsonResponse
    {
        $entityManager->remove($typeDemandeDetail);
        $entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
