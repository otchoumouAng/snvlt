<?php

namespace App\Controller\DemandeAutorisation;

use App\Entity\DemandeAutorisation\DemandeDocument;
use App\Entity\DemandeAutorisation\Document;
use App\Entity\DemandeAutorisation\EtapeValidation;
use App\Entity\DemandeAutorisation\NouvelleDemande;
use App\Entity\DemandeAutorisation\TypeDemande;
use App\Repository\Administration\NotificationRepository;
use App\Repository\DemandeAutorisation\DemandeDocumentRepository;
use App\Repository\DemandeAutorisation\EtapeValidationRepository;
use App\Repository\DemandeAutorisation\NouvelleDemandeRepository;
use App\Repository\DemandeAutorisation\TypeDemandeDetailRepository;
use App\Repository\DemandeAutorisation\TypeDocumentRepository;
use App\Repository\DemandeAutorisation\TypeDemandeRepository;
use App\Repository\MenuPermissionRepository;
use App\Repository\MenuRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
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
    private $etapeValidationRepository;

    public function __construct(EntityManagerInterface $entityManager, EtapeValidationRepository $etapeValidationRepository)
    {
        $this->entityManager = $entityManager;
        $this->etapeValidationRepository = $etapeValidationRepository;
    }

    /**
     * @Route("/", name="app_nouvelle_demande")
     */
    public function index(MenuRepository $menus, MenuPermissionRepository $permissions, Request $request, UserRepository $userRepository, NotificationRepository $notification, TypeDemandeRepository $typeDemandeRepository): Response
    {
        if (!$request->getSession()->has('user_session')) {
            return $this->redirectToRoute('app_login');
        }

        if ($this->isGranted('ROLE_MINEF') or $this->isGranted('ROLE_ADMIN') or $this->isGranted('ROLE_EXPLOITANT')) {
            $user = $userRepository->find($this->getUser());
            $code_groupe = $user->getCodeGroupe()->getId();

            return $this->render('DemandeAutorisation/nouvelle_demande/index.html.twig', [
                'liste_menus' => $menus->findOnlyParent(),
                "all_menus" => $menus->findAll(),
                'mes_notifs' => $notification->findBy(['to_user' => $user, 'lu' => false], [], 5, 0),
                'menus' => $permissions->findBy(['code_groupe_id' => $code_groupe]),
                'groupe' => $code_groupe,
                'liste_parent' => $permissions,
                'typesDemande' => $typeDemandeRepository->findAll()
            ]);
        } else {
            return $this->redirectToRoute('app_no_permission_user_active');
        }
    }

    /**
     * @Route("/liste", name="app_nouvelle_demande_liste")
     */
    public function getListeDemandes(NouvelleDemandeRepository $nouvelleDemandeRepository): JsonResponse
    {
        $demandes = $nouvelleDemandeRepository->findAll();

        $data = [];
        foreach ($demandes as $demande) {
            $data[] = [
                'id' => $demande->getId(),
                'titre' => $demande->getTitre(),
                'description' => $demande->getDescription(),
                'statut' => $demande->getStatut(),
                'dateCreation' => $demande->getCreatedAt()->format('d/m/Y'),
                'typeDemande' => $demande->getTypeDemande() ? $demande->getTypeDemande()->getDesignation() : 'N/A',
                'societe' => $demande->getRaisonSocial()
            ];
        }

        return new JsonResponse($data);
    }

    /**
     * @Route("/details/{id}", name="app_nouvelle_demande_details")
     */
    public function getDetailsDemande(int $id, NouvelleDemandeRepository $nouvelleDemandeRepository, TypeDemandeDetailRepository $typeDemandeDetailRepository): JsonResponse
    {
        $demande = $nouvelleDemandeRepository->find($id);

        if (!$demande) {
            return new JsonResponse(['error' => 'Demande non trouvée'], 404);
        }

        // Get uploaded documents for the demand
        $uploadedDocuments = [];
        foreach ($demande->getDemandeDocuments() as $demandeDocument) {
            $doc = $demandeDocument->getDocument();
            $uploadedDocuments[$doc->getTypeDocument()->getId()] = [
                'id' => $doc->getId(),
                'nom' => $doc->getNom(),
                'path' => $doc->getPath(),
                'statut' => 'fourni', // custom status
                'dateAjout' => $doc->getCreatedAt()->format('d/m/Y H:i')
            ];
        }

        // Get required document types for the demand's type
        $requiredDocuments = [];
        if ($demande->getTypeDemande()) {
            $requiredDocumentDetails = $typeDemandeDetailRepository->findBy(['typeDemande' => $demande->getTypeDemande()]);
            foreach ($requiredDocumentDetails as $detail) {
                $typeDocument = $detail->getTypeDocument();
                $typeDocId = $typeDocument->getId();

                if (isset($uploadedDocuments[$typeDocId])) {
                    // Document is provided
                    $requiredDocuments[] = [
                        'type_document_id' => $typeDocId,
                        'nom' => $typeDocument->getDesignation(),
                        'statut' => 'fourni',
                        'document_id' => $uploadedDocuments[$typeDocId]['id'],
                        'nom_fichier' => $uploadedDocuments[$typeDocId]['nom'],
                        'path' => $uploadedDocuments[$typeDocId]['path'],
                        'dateAjout' => $uploadedDocuments[$typeDocId]['dateAjout'],
                    ];
                } else {
                    // Document is missing
                    $requiredDocuments[] = [
                        'type_document_id' => $typeDocId,
                        'nom' => $typeDocument->getDesignation(),
                        'statut' => 'manquant',
                        'document_id' => null,
                        'nom_fichier' => null,
                        'path' => null,
                        'dateAjout' => null
                    ];
                }
            }
        }


        $data = [
            'id' => $demande->getId(),
            'titre' => $demande->getTitre(),
            'description' => $demande->getDescription(),
            'statut' => $demande->getStatut(),
            'documents' => $requiredDocuments,
            'typeDemande' => $demande->getTypeDemande() ? $demande->getTypeDemande()->getDesignation() : 'N/A',
        ];

        return new JsonResponse($data);
    }


    /**
     * @Route("/save", name="app_nouvelle_demande_save", methods={"POST"})
     */
    public function saveDemande(Request $request, NouvelleDemandeRepository $nouvelleDemandeRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $user = $this->getUser();

        try {
            if (!empty($data['id'])) {
                // Modification
                $demande = $nouvelleDemandeRepository->find($data['id']);
                if (!$demande) {
                    throw new \Exception('Demande non trouvée');
                }
            } else {
                // Création
                $demande = new NouvelleDemande();
                $demande->setCreatedAt(new \DateTime());
                $demande->setCreatedBy($user->getUserIdentifier());
                $demande->setOperateurId($user->getId()); // Assuming the user is the operator
                if ($user->getExploitant()) {
                    $demande->setRaisonSocial($user->getExploitant()->getRaisonSocialeExploitant());
                } else {
                    $demande->setRaisonSocial($user->getNom() . " " . $user->getPrenom());
                }
                $demande->setCodeSuivie(strtoupper(uniqid('SN-')));
            }

            $demande->setTitre($data['titre']);
            $demande->setDescription($data['description']);
            $demande->setStatut($data['statut'] ?? 'en_attente');

            if (isset($data['typeDemandeId'])) {
                $typeDemande = $this->entityManager->getReference(TypeDemande::class, $data['typeDemandeId']);
                $demande->setTypeDemande($typeDemande);
            }

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
    public function addDocument(int $id, Request $request, NouvelleDemandeRepository $nouvelleDemandeRepository, TypeDocumentRepository $typeDocumentRepository): JsonResponse
    {
        $demande = $nouvelleDemandeRepository->find($id);

        if (!$demande) {
            return new JsonResponse(['error' => 'Demande non trouvée'], 404);
        }

        $file = $request->files->get('document');
        $typeDocumentId = $request->request->get('type_document_id');

        if (!$file) {
            return new JsonResponse(['error' => 'Aucun fichier fourni'], 400);
        }

        if (!$typeDocumentId) {
            return new JsonResponse(['error' => 'Type de document non spécifié'], 400);
        }

        $typeDocument = $typeDocumentRepository->find($typeDocumentId);
        if (!$typeDocument) {
            return new JsonResponse(['error' => 'Type de document invalide'], 400);
        }

        // You should define 'documents_directory' in your services.yaml
        $uploadsDirectory = $this->getParameter('documents_directory');
        $newFilename = uniqid() . '.' . $file->guessExtension();

        try {
            $file->move($uploadsDirectory, $newFilename);
        } catch (FileException $e) {
            return new JsonResponse(['error' => 'Impossible de stocker le fichier'], 500);
        }

        $document = new Document();
        $document->setNom($file->getClientOriginalName());
        $document->setPath($newFilename);
        $document->setStatut('fourni');
        $document->setTypeDocument($typeDocument);
        $document->setCreatedAt(new \DateTime());
        $document->setCreatedBy($this->getUser()->getUserIdentifier());

        $demandeDocument = new DemandeDocument();
        $demandeDocument->setDemande($demande);
        $demandeDocument->setDocument($document);

        $this->entityManager->persist($document);
        $this->entityManager->persist($demandeDocument);
        $this->entityManager->flush();

        return new JsonResponse(['success' => true]);
    }

    /**
     * @Route("/{id}/remove_document", name="app_nouvelle_demande_remove_document", methods={"POST"})
     */
    public function removeDocument(int $id, Request $request, DemandeDocumentRepository $demandeDocumentRepository): JsonResponse
    {
        $documentId = $request->request->get('document_id');
        $demande = $this->entityManager->getRepository(NouvelleDemande::class)->find($id);

        if (!$demande) {
            return new JsonResponse(['error' => 'Demande non trouvée'], 404);
        }

        $document = $this->entityManager->getRepository(Document::class)->find($documentId);

        if (!$document) {
            return new JsonResponse(['error' => 'Document non trouvé'], 404);
        }

        $demandeDocument = $demandeDocumentRepository->findOneBy(['demande' => $demande, 'document' => $document]);

        if (!$demandeDocument) {
            return new JsonResponse(['error' => 'Liaison document-demande non trouvée'], 404);
        }

        // Optional: remove the file from storage
        // $filePath = $this->getParameter('documents_directory').'/'.$document->getPath();
        // if (file_exists($filePath)) {
        //     unlink($filePath);
        // }

        $this->entityManager->remove($demandeDocument);
        $this->entityManager->remove($document);
        $this->entityManager->flush();

        return new JsonResponse(['success' => true]);
    }


    #[Route('/suivi/{id}', name: 'admin_nouvelle_demande_suivi', methods: ['GET'])]
    public function suivi(NouvelleDemande $demande): Response
    {
        // 1. Logique pour récupérer les étapes de validation de la demande
        $etapes = $this->etapeValidationRepository->findBy(
            ['demande' => $demande],
            ['ordre' => 'ASC']
        );

        // 2. Préparer les données pour Twig (y compris le statut de chaque étape)
        $etapesPourTwig = [];
        $demandeStatut = $demande->getStatut(); // Statut global de la demande
        $etapeActiveTrouvee = false;

        foreach ($etapes as $etape) {
            list($status, $isActive) = $this->determineEtapeStatus($etape, $demandeStatut, $etapeActiveTrouvee);
            if ($isActive) {
                $etapeActiveTrouvee = true;
            }

            $etapesPourTwig[] = [
                'id' => $etape->getId(),
                'nom' => $etape->getNom(),
                'date' => $etape->getDateTraitement(),
                'status' => $status,
            ];
        }

        // 3. Rendre le template Twig et le retourner comme une réponse HTML
        return $this->render('DemandeAutorisation/nouvelle_demande/suivi.html.twig', [
            'demande' => $demande,
            'etapes' => $etapesPourTwig,
        ]);
    }

    #[Route('/suivi/{demandeId}/etape/{etapeId}', name: 'admin_nouvelle_demande_suivi_etape', methods: ['GET'])]
    public function detailEtape(int $demandeId, int $etapeId): Response
    {
        $etape = $this->etapeValidationRepository->find($etapeId);

        if (!$etape || $etape->getDemande()->getId() !== $demandeId) {
            return new Response("<div>Détails non trouvés.</div>", 404);
        }

        return $this->render('DemandeAutorisation/nouvelle_demande/_detail_etape.html.twig', [
            'etape' => $etape,
        ]);
    }

    /**
     * Détermine le statut d'une étape pour l'affichage du stepper.
     *
     * @param EtapeValidation $etape L'étape à évaluer.
     * @param string $demandeStatut Le statut global de la demande ('en_attente', 'approuvee', 'rejetee').
     * @param bool $etapeActiveTrouvee Indique si l'étape active a déjà été identifiée dans la boucle.
     * @return array [string status, bool isActive]
     */
    private function determineEtapeStatus(EtapeValidation $etape, string $demandeStatut, bool &$etapeActiveTrouvee): array
    {
        // Si l'étape a une date de traitement, elle est complétée.
        if ($etape->getDateTraitement() !== null) {
            return ['completed', false];
        }

        // Si une étape active a déjà été trouvée, les suivantes sont en attente.
        if ($etapeActiveTrouvee) {
            return ['', false]; // Statut neutre (grisé)
        }

        // La première étape sans date de traitement est l'étape "active".
        // Sauf si la demande est déjà terminée (approuvée/rejetée).
        if ($demandeStatut === 'approuvee' || $demandeStatut === 'rejetee') {
            return ['', false];
        }

        // C'est la première étape non complétée, elle est donc active.
        $etapeActiveTrouvee = true;
        return ['active', true];
    }
}