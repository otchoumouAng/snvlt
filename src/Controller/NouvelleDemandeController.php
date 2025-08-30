<?php

namespace App\Controller;

use App\Entity\NouvelleDemande;
use App\Entity\TypeDocument;
use App\Repository\NouvelleDemandeRepository;
use App\Repository\TypeDocumentRepository;
use App\Repository\Administration\NotificationRepository;
use App\Repository\Autorisation\ContratBcbgfhRepository;
use App\Repository\MenuPermissionRepository;
use App\Repository\MenuRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/nouvelle_demande")
 */
class NouvelleDemandeController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/", name="app_nouvelle_demande")
     */
    public function index(ContratBcbgfhRepository $contratbcbgfhRepository, MenuRepository $menus, MenuPermissionRepository $permissions, Request $request, UserRepository $userRepository, NotificationRepository $notification, TypeDocumentRepository $typeDocumentRepos): Response
    {
        if(!$request->getSession()->has('user_session')){
            return $this->redirectToRoute('app_login');
        } else {
            if ($this->isGranted('ROLE_MINEF') or  $this->isGranted('ROLE_ADMIN'))
            {
                $user = $userRepository->find($this->getUser());
                $code_groupe = $user->getCodeGroupe()->getId();

                return $this->render('nouvelle_demande/index.html.twig', [
                    'contrats' => $contratbcbgfhRepository->findAll(),
                    'liste_menus'=>$menus->findOnlyParent(),
                    "all_menus"=>$menus->findAll(),
                    'mes_notifs'=>$notification->findBy(['to_user'=>$user, 'lu'=>false],[],5,0),
                    'menus'=>$permissions->findBy(['code_groupe_id'=>$code_groupe]),
                    'groupe'=>$code_groupe,
                    'liste_parent'=>$permissions,
                    'typesDocument' => $typeDocumentRepos->findAll()
                ]);
            } else {
                return  $this->redirectToRoute('app_no_permission_user_active');
            }
        }
    }

    /**
     * @Route("/liste", name="app_nouvelle_demande_liste")
     */
    public function getListeDemandes(NouvelleDemandeRepository $NouvelleDemandeRepository): JsonResponse
    {
        $demandes = $NouvelleDemandeRepository->findAll();

        $data = [];
        foreach ($demandes as $demande) {
            $data[] = [
                'id' => $demande->getId(),
                'titre' => $demande->getRaisonSocial(),
                'description' => $demande->getDescription(),
                'statut' => $demande->getStatut(),
                'dateCreation' => $demande->getCreatedAt()->format('d/m/Y'),
                'typeDocument' => 'test',//$demande->getTypeDocument()->getNom(),
                'societe' => $demande->getRaisonSocial()
            ];
        }

        return new JsonResponse($data);
    }

    /**
     * @Route("/details/{id}", name="app_nouvelle_demande_details")
     */
    public function getDetailsDemande(int $id, NouvelleDemandeRepository $NouvelleDemandeRepository): JsonResponse
    {
        $demande = $NouvelleDemandeRepository->findWithDocuments($id);
        
        if (!$demande) {
            return new JsonResponse(['error' => 'Demande non trouvée'], 404);
        }

        $documents = [];
        foreach ($demande->getDocuments() as $document) {
            $documents[] = [
                'id' => $document->getId(),
                'nom' => $document->getNom(),
                'statut' => $document->getStatut(),
                'dateAjout' => $document->getDateAjout()->format('d/m/Y H:i')
            ];
        }

        $data = [
            'id' => $demande->getId(),
            'titre' => $demande->getTitre(),
            'description' => $demande->getDescription(),
            'statut' => $demande->getStatut(),
            'documents' => $documents,
            'typeDocument' => $demande->getTypeDocument()->getNom()
        ];

        return new JsonResponse($data);
    }

    /**
     * @Route("/form", name="app_nouvelle_demande_form")
     */
    public function getForm(Request $request, NouvelleDemandeRepository $NouvelleDemandeRepository): Response
    {
        $mode = $request->query->get('mode', 'new');
        $id = $request->query->get('id');
        
        $typesDocument = $NouvelleDemandeRepository->findAll();
        
        return $this->render('nouvelle_demande/form.html.twig', [
            'mode' => $mode,
            'id' => $id,
            'typesDocument' => $typesDocument
        ]);
    }

   

    /**
     * @Route("/save", name="app_nouvelle_demande_save", methods={"POST"})
     */
    public function saveDemande(Request $request, NouvelleDemandeRepository $NouvelleDemandeRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        try {
            if (isset($data['id'])) {
                // Modification
                $demande = $NouvelleDemandeRepository->find($data['id']);
                if (!$demande) {
                    throw new \Exception('Demande non trouvée');
                }
            } else {
                // Création
                $demande = new NouvelleDemande();
                $demande->setDateCreation(new \DateTime());
                $demande->setUser($this->getUser());
            }
            
            $demande->setTitre($data['titre']);
            $demande->setDescription($data['description']);
            $demande->setStatut($data['statut'] ?? 'en_attente');
            
            $typeDocument = $this->entityManager->getReference('App\Entity\TypeDocument', $data['typeDocumentId']);
            $demande->setTypeDocument($typeDocument);
            
            $this->entityManager->persist($demande);
            $this->entityManager->flush();
            
            return new JsonResponse(['success' => true, 'id' => $demande->getId()]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * @Route("/{id}/add_document", name="app_nouvelle_demande_add_document", methods={"POST"})
     */
    public function addDocument(int $id, Request $request, NouvelleDemandeRepository $NouvelleDemandeRepository): JsonResponse
    {
        $demande = $NouvelleDemandeRepository->find($id);
        
        if (!$demande) {
            return new JsonResponse(['error' => 'Demande non trouvée'], 404);
        }
        
        $file = $request->files->get('document');
        
        if ($file && $file->getClientOriginalExtension() === 'pdf') {
            $document = new TypeDocument();
            $document->setNom($file->getClientOriginalName());
            $document->setStatut('en_attente');
            $document->setDateAjout(new \DateTime());
            $document->setDemande($demande);
            
            // Ici vous devriez gérer le stockage du fichier
            // $file->move($this->getParameter('documents_directory'), $newFilename);
            
            $this->entityManager->persist($document);
            $this->entityManager->flush();
            
            return new JsonResponse(['success' => true]);
        }
        
        return new JsonResponse(['error' => 'Fichier PDF requis'], 400);
    }

    /**
     * @Route("/{id}/remove_document", name="app_nouvelle_demande_remove_document", methods={"POST"})
     */
    public function removeDocument(int $id, Request $request): JsonResponse
    {
        $documentId = $request->request->get('document_id');
        $document = $this->entityManager->getRepository(TypeDocument::class)->find($documentId);

        if (!$document || $document->getDemande()->getId() !== $id) {
            return new JsonResponse(['error' => 'Document non trouvé'], 404);
        }
        
        $this->entityManager->remove($document);
        $this->entityManager->flush();
        
        return new JsonResponse(['success' => true]);
    }

     // Vous aurez aussi besoin d'une route pour les détails d'une étape
    #[Route('/admin/nouvelle_demande/suivi/{demandeId}/etape/{etapeId}', name: 'admin_nouvelle_demande_suivi_etape', methods: ['GET'])]
    public function detailEtape(int $demandeId, int $etapeId): Response
    {
        // Logique pour récupérer les détails d'une étape spécifique
        $details = "Détails pour l'étape $etapeId de la demande $demandeId...";
        
        // Vous rendrez un autre petit template Twig ici
        return new Response("<div>$details</div>");
    }

    #[Route('/admin/nouvelle_demande/suivi/{id}', name: 'admin_nouvelle_demande_suivi', methods: ['GET'])]
    public function suivi(NouvelleDemande $demande): Response
    {
        // 1. Logique pour récupérer les étapes de validation de la demande
        // C'est un exemple, vous devrez l'adapter à votre modèle de données.
        $etapes = $this->getDoctrine()->getRepository(EtapeValidation::class)->findBy(
            ['demande' => $demande],
            ['ordre' => 'ASC']
        );

        // 2. Préparer les données pour Twig (y compris le statut de chaque étape)
        $etapesPourTwig = [];
        foreach ($etapes as $etape) {
            $etapesPourTwig[] = [
                'id' => $etape->getId(),
                'nom' => $etape->getNom(),
                'date' => $etape->getDateTraitement(),
                'status' => $this->determineEtapeStatus($etape), // une fonction privée pour calculer 'completed', 'active', etc.
            ];
        }

        // 3. Rendre le template Twig et le retourner comme une réponse HTML
        return $this->render('nouvelle_demande/suivi.html.twig', [
            'demande' => $demande,
            'etapes' => $etapesPourTwig,
        ]);
    }




}