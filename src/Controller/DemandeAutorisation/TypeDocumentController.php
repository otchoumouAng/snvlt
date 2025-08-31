<?php

namespace App\Controller\DemandeAutorisation;

use App\Entity\DemandeAutorisation\TypeDocument;
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
 * @Route("/admin/type_documents")
 */
class TypeDocumentController extends AbstractController
{
    /**
     * @Route("/", name="app_type_document_index")
     */
    public function index(MenuRepository $menus, NotificationRepository $notification, MenuPermissionRepository $permissions, UserRepository $userRepository): Response
    {
        $user = $userRepository->find($this->getUser());
        $code_groupe = $user->getCodeGroupe()->getId();
        $titre = 'Gestion des type_documents';
        
        // Précharger les formulaires dans le template
        $newForm = $this->renderView('DemandeAutorisation/type_document/form.html.twig', [
            'mode' => 'new',
            'type_document' => null,
        ]);
        
        $editForm = $this->renderView('DemandeAutorisation/type_document/form.html.twig', [
            'mode' => 'edit',
            'type_document' => null,
        ]);
        
        $readForm = $this->renderView('DemandeAutorisation/type_document/form.html.twig', [
            'mode' => 'read',
            'type_document' => null,
        ]);
        
        return $this->render('DemandeAutorisation/type_document/index.html.twig', [
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
     * @Route("/data", name="app_type_document_data", methods={"GET"})
     */
    public function getData(EntityManagerInterface $em): JsonResponse
    {
        try {
            $typeDocuments = $em->getRepository(TypeDocument::class)->findAll();
            
            $data = [];
            foreach ($typeDocuments as $typeDocument) {
                $data[] = [
                    'id' => $typeDocument->getId(),
                    'designation' => $typeDocument->getDesignation(),
                    'desactivate' => $typeDocument->isDesactivate(),
                    'DT_RowId' => 'row_' . $typeDocument->getId() // Ajout d'un ID unique pour chaque ligne
                ];
            }
            
            return $this->json(['data' => $data]);
        } catch (\Exception $e) {
            return $this->json(['data' => []]);
        }
    }

    /**
     * @Route("/form", name="app_type_document_form")
     */
    public function form(Request $request, EntityManagerInterface $em): Response
    {
        $id = $request->query->get('id');
        $mode = $request->query->get('mode', 'new');
        
        $type_document = null;
        if ($id) {
            $type_document = $em->getRepository(TypeDocument::class)->find($id);
        }
        
        return $this->render('DemandeAutorisation/type_document/form.html.twig', [
            'mode' => $mode,
            'type_document' => $type_document,
        ]);
    }

    /**
     * @Route("/save", name="app_type_document_save", methods={"POST"})
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
                $typeDocument = $em->getRepository(TypeDocument::class)->find($id);
                if (!$typeDocument) {
                    return $this->json(['success' => false, 'message' => 'TypeDocument non trouvé'], 404);
                }
            } else {
                $typeDocument = new TypeDocument();
            }

            if (empty($designation) && $designation !== '0') {
                return $this->json(['success' => false, 'message' => 'La désignation est requise'], 400);
            }

            $typeDocument->setDesignation($designation);
            $typeDocument->setDesactivate((bool)$desactivate);

            $em->persist($typeDocument);
            $em->flush();

            // Retourner les données mises à jour pour mise à jour côté client
            return $this->json([
                'success' => true, 
                'message' => 'TypeDocument enregistré avec succès',
                'typeDocument' => [
                    'id' => $typeDocument->getId(),
                    'designation' => $typeDocument->getDesignation(),
                    'desactivate' => $typeDocument->isDesactivate(),
                    'DT_RowId' => 'row_' . $typeDocument->getId()
                ]
            ]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()], 500);
        }
    }

    
}