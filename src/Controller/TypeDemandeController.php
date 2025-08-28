<?php

namespace App\Controller;

use App\Entity\TypeDemande;
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

use App\Repository\TypeDocumentRepository;
use App\Entity\TypeDemandeDetail;
use App\Entity\TypeDocument;

/**
 * @Route("/admin/type_demandes")
 */
class TypeDemandeController extends AbstractController
{
    /**
     * @Route("/index", name="app_type_demandes_index")
     */
    public function index(MenuRepository $menus, NotificationRepository $notification, MenuPermissionRepository $permissions, UserRepository $userRepository): Response
    {
        $user = $userRepository->find($this->getUser());
        $code_groupe = $user->getCodeGroupe()->getId();
        $titre = 'Gestion des type_Demandes';
        
        // Précharger les formulaires dans le template
        $newForm = $this->renderView('type_demande/form.html.twig', [
            'mode' => 'new',
            'type_demande' => null,
        ]);

        $editForm = $this->renderView('type_demande/form.html.twig', [
            'mode' => 'edit',
            'type_demande' => null,
        ]);

        $readForm = $this->renderView('type_demande/form.html.twig', [
            'mode' => 'read',
            'type_demande' => null,
        ]);

        return $this->render('type_demande/index.html.twig', [
            'liste_menus' => $menus->findOnlyParent(),
            "all_menus" => $menus->findAll(),
            'mes_notifs' => $notification->findBy(['to_user' => $this->getUser(), 'lu' => false], [], 5, 0),
            'menus' => $permissions->findBy(['code_groupe_id' => $code_groupe]),
            'groupe' => $code_groupe,
            'titre' => $titre,
            'liste_parent' => $permissions,
            'preloaded_forms' => [
                'new' => $newForm,
                'edit' => $editForm,
                'read' => $readForm
            ]
        ]);
    }

    /**
     * @Route("/data", name="app_type_Demande_data", methods={"GET"})
     */
    public function getData(EntityManagerInterface $em): JsonResponse
    {
        try {
            $typeDemandes = $em->getRepository(TypeDemande::class)->findAll();
            
            $data = [];
            foreach ($typeDemandes as $typeDemande) {
                $data[] = [
                    'id' => $typeDemande->getId(),
                    'designation' => $typeDemande->getDesignation(),
                    'desactivate' => $typeDemande->isDesactivate(),
                    'DT_RowId' => 'row_' . $typeDemande->getId() // Ajout d'un ID unique pour chaque ligne
                ];
            }
            
            return $this->json(['data' => $data]);
        } catch (\Exception $e) {
            return $this->json(['data' => []]);
        }
    }

    /**
     * @Route("/form", name="app_type_Demande_form")
     */
    public function form(Request $request, EntityManagerInterface $em): Response
    {
        $id = $request->query->get('id');
        $mode = $request->query->get('mode', 'new');
        
        $type_Demande = null;
        if ($id) {
            $type_Demande = $em->getRepository(TypeDemande::class)->find($id);
        }
        
        return $this->render('type_demande/form.html.twig', [
            'mode' => $mode,
            'type_demande' => $type_Demande,
        ]);
    }

    /**
     * @Route("/save", name="app_type_demande_save", methods={"POST"})
     */
    public function save(Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $content = $request->getContent();
            $data = null;

            if (!empty($content)) {
                $decoded = json_decode($content, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $data = $decoded;
                }
            }

            if (!is_array($data)) {
                $data = $request->request->all();
            }

            $id = $data['id'] ?? null;
            $designation = $data['designation'] ?? null;
            $desactivateRaw = $data['desactivate'] ?? ($data['desactivated'] ?? null);

            $desactivate = filter_var($desactivateRaw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($desactivate === null) {
                $desactivate = false;
            }

            if ($id) {
                $typeDemande = $em->getRepository(TypeDemande::class)->find($id);
                if (!$typeDemande) {
                    return $this->json(['success' => false, 'message' => 'TypeDemande non trouvé'], 404);
                }
            } else {
                $typeDemande = new TypeDemande();
            }

            if (empty($designation) && $designation !== '0') {
                return $this->json(['success' => false, 'message' => 'La désignation est requise'], 400);
            }

            $typeDemande->setDesignation($designation);
            $typeDemande->setDesactivate((bool)$desactivate);

            $em->persist($typeDemande);
            $em->flush();

            // Retourner les données mises à jour pour mise à jour côté client
            return $this->json([
                'success' => true, 
                'message' => 'TypeDemande enregistré avec succès',
                'typeDemande' => [
                    'id' => $typeDemande->getId(),
                    'designation' => $typeDemande->getDesignation(),
                    'desactivate' => $typeDemande->isDesactivate(),
                    'DT_RowId' => 'row_' . $typeDemande->getId()
                ]
            ]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()], 500);
        }
    }


    /**
     * @Route("/{id}/details", name="app_type_demande_details", methods={"GET"})
     */
    public function getDetails(TypeDemande $typeDemande, EntityManagerInterface $em): JsonResponse
    {
        try {
            // Récupérer les documents associés
            
            $sql = "
                SELECT td.id, td.designation
                FROM metier.type_demande_detail tdd
                INNER JOIN metier.type_document td ON tdd.type_document_id = td.id
                WHERE tdd.type_demande_id = :typeDemande
            ";

            $stmt = $em->getConnection()->prepare($sql);
            $stmt->bindValue('typeDemande', $typeDemande->getId());
            $documents = $stmt->executeQuery()->fetchAllAssociative();

            $data = [
                'id' => $typeDemande->getId(),
                'designation' => $typeDemande->getDesignation(),
                'desactivate' => $typeDemande->isDesactivate(),
                'created_at' => $typeDemande->getCreatedAt()->format('Y-m-d H:i:s'),
                'created_by' => $typeDemande->getCreatedBy(),
                'modified_at' => $typeDemande->getModifiedAt() ? $typeDemande->getModifiedAt()->format('Y-m-d H:i:s') : null,
                'modified_by' => $typeDemande->getModifiedBy(),
                'documents' => $documents
            ];

            return $this->json($data);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur lors de la récupération des détails',
                'exception' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @Route("/{id}/documents", name="app_type_demande_save_documents", methods={"POST"})
     */
    public function saveDocuments(Request $request, TypeDemande $typeDemande, EntityManagerInterface $em): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $documentIds = $data['documents'] ?? [];

            // Supprimer les anciennes associations
            $existingDetails = $em->getRepository(TypeDemandeDetail::class)
                ->findBy(['typeDemande' => $typeDemande]);
            
            foreach ($existingDetails as $detail) {
                $em->remove($detail);
            }
            
            // Ajouter les nouvelles associations
            foreach ($documentIds as $docId) {
                $typeDocument = $em->getRepository(TypeDocument::class)->find($docId);
                if ($typeDocument) {
                    $detail = new TypeDemandeDetail();
                    $detail->setTypeDemande($typeDemande);
                    $detail->setTypeDocument($typeDocument);
                    $em->persist($detail);
                }
            }
            
            $em->flush();

            return $this->json(['success' => true, 'message' => 'Documents associés avec succès']);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()], 500);
        }
    }
    
}