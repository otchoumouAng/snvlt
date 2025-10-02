<?php

namespace App\Controller\DemandeAutorisation;

use App\Entity\DemandeAutorisation\DemandeDocument;
use App\Entity\DemandeAutorisation\Document;
use App\Entity\DemandeAutorisation\EtapeValidation;
use App\Entity\Administration\Notification;
use App\Entity\DemandeAutorisation\NouvelleDemande;
use App\Entity\DemandeAutorisation\TypeDemande;
use App\Entity\Paiement\TypePaiement;
use App\Repository\Administration\NotificationRepository;
use App\Repository\DemandeAutorisation\DemandeDocumentRepository;
use App\Repository\Paiement\TypePaiementRepository;
use App\Repository\DemandeAutorisation\EtapeValidationRepository;
use App\Repository\DemandeAutorisation\NouvelleDemandeRepository;
use App\Repository\DemandeAutorisation\TypeDemandeDetailRepository;
use App\Repository\DemandeAutorisation\TypeDocumentRepository;
use App\Repository\DemandeAutorisation\TypeDemandeRepository;
use App\Repository\References\ModeleCommunicationRepository;
use App\Repository\MenuPermissionRepository;
use App\Repository\MenuRepository;
use App\Service\NotificationService;
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
    /**
     * @Route("/api/user/pefs", name="app_user_pefs_json", methods={"GET"})
     */
    public function getUserPefsAction(EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();

        $rolesAutorises = ['ROLE_EXPLOITANT', 'ROLE_EXPORTATEUR','ROLE_INDUSTRIEL'];

        if (!$user || empty(array_intersect($rolesAutorises, $user->getRoles()))) {
            return new JsonResponse(['error' => 'Accès non autorisé ou utilisateur non valide'], 403);
        }


        $exploitant = $user->getCodeexploitant();
        if (!$exploitant) {
            return new JsonResponse([]);
        }

        $conn = $em->getConnection();
        $sql = '
            SELECT DISTINCT f.numero_foret as libelle, f.id as id
            FROM metier.foret f
            JOIN metier.attribution a ON f.id = a.code_foret_id
            WHERE a.code_exploitant_id = :code_exploitant_id
            AND (a.retire IS NULL OR a.retire = false)
            AND (a.abandonne IS NULL OR a.abandonne = false)
        ';

        $stmt = $conn->prepare($sql);
        $resultSet = $stmt->executeQuery(['code_exploitant_id' => $exploitant->getId()]);
        $pefs = $resultSet->fetchAllAssociative();

        return new JsonResponse($pefs);
    }
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EtapeValidationRepository $etapeValidationRepository,
        private ModeleCommunicationRepository $modeleCommunicationRepository,
        private UserRepository $userRepository,
        private NotificationService $notificationService
    ) {
    }

    /**
     * @Route("/", name="app_nouvelle_demande")
     */
    public function index(MenuRepository $menus, MenuPermissionRepository $permissions, Request $request, UserRepository $userRepository, NotificationRepository $notification, TypeDemandeRepository $typeDemandeRepository, TypePaiementRepository $typePaiementRepository): Response
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
                'typesDemande' => $typeDemandeRepository->findAll(),
                'typesPaiement' => $typePaiementRepository->findAll()
            ]);
        } else {
            return $this->redirectToRoute('app_no_permission_user_active');
        }
    }

    /**
     * @Route("/liste", name="app_nouvelle_demande_liste")
     */
    public function getListeDemandes(NouvelleDemandeRepository $nouvelleDemandeRepository, UserRepository $userRepository): JsonResponse
    {
        $user = $userRepository->find($this->getUser());

        //dd($user->getId());
        
     
        $demandes = $nouvelleDemandeRepository->findBy(['operateur' => $user->getId()]);

        
        $data = [];
        foreach ($demandes as $demande) {
            $operateur = $demande->getOperateur();

            $societe = $operateur ? ($operateur->getCodeexploitant() ? $operateur->getCodeexploitant()->getRaisonSocialeExploitant() : $operateur->getNomUtilisateur() . ' ' . $operateur->getPrenomsUtilisateur()) : 'N/A';

            $data[] = [
                'id' => $demande->getId(),
                'titre' => $demande->getTypePaiement() ? $demande->getTypePaiement()->getLibelle() : 'N/A',
                'description' => $demande->getDescription(),
                'statut' => $demande->getStatut(),
                'dateCreation' => $demande->getCreatedAt()->format('d/m/Y'),
                'typeDemande' => $demande->getTypeDemande() ? $demande->getTypeDemande()->getDesignation() : 'N/A',
                'societe' => $societe,
                'numero_pef' => $demande->getNumeroPef() ?? 'N/A',
                'produit' => $demande->getProduit() ?? 'N/A'
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

        $publicDir = $this->getParameter('kernel.project_dir') . '/public';
        $documentsDir = $this->getParameter('documents_directory');
        $webPath = str_replace($publicDir, '', $documentsDir);

        // Get uploaded documents for the demand
        $uploadedDocuments = [];
        foreach ($demande->getDemandeDocuments() as $demandeDocument) {
            $doc = $demandeDocument->getDocument();
            $status = $doc->getStatut();
            if ($status === 'soumis') {
                $status = 'Chargé';
            }
            $uploadedDocuments[$doc->getTypeDocument()->getId()] = [
                'id' => $doc->getId(),
                'nom' => $doc->getNom(),
                'path' => $webPath . '/' . $doc->getPath(), // Assurez-vous que c'est le bon chemin public
                'statut' => $status, // Utiliser le statut réel du document
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
                        'statut' => $uploadedDocuments[$typeDocId]['statut'],
                        'document_id' => $uploadedDocuments[$typeDocId]['id'],
                        'nom_fichier' => $uploadedDocuments[$typeDocId]['nom'],
                        'path' => $uploadedDocuments[$typeDocId]['path'],
                        'dateAjout' => $uploadedDocuments[$typeDocId]['dateAjout'],
                        'fichierSpecial' => $typeDocument->isFichierSpecial(),
                    ];
                } else {
                    // Document is missing
                    $requiredDocuments[] = [
                        'type_document_id' => $typeDocId,
                        'nom' => $typeDocument->getDesignation(),
                        'statut' => 'Non chargé',
                        'document_id' => null,
                        'nom_fichier' => null,
                        'path' => null,
                        'dateAjout' => null,
                        'fichierSpecial' => $typeDocument->isFichierSpecial(),
                    ];
                }
            }
        }


        $data = [
            'id' => $demande->getId(),
            'titre' => $demande->getTypePaiement() ? $demande->getTypePaiement()->getLibelle() : 'N/A',
            'description' => $demande->getDescription(),
            'statut' => $demande->getStatut(),
            'documents' => $requiredDocuments,
            'typeDemande' => $demande->getTypeDemande() ? $demande->getTypeDemande()->getDesignation() : 'N/A',
            'signed_document_path' => $demande->getDocumentSignePath() ? $webPath . '/' . $demande->getDocumentSignePath() : null,
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
                $demande->setUpdatedAt(new \DateTimeImmutable());
                $demande->setUpdatedBy($user->getUserIdentifier());
            } else {
                // Création
                $demande = new NouvelleDemande();
                $demande->setCreatedBy($user->getUserIdentifier());
                $demande->setOperateur($user);
                $demande->setCodeSuivie(strtoupper(uniqid('SNVLT-')));
            }

            $demande->setDescription($data['description']);
            $demande->setStatut($data['statut'] ?? 'Créé');
            $demande->setNumeroPef($data['numero_pef'] ?? null);
            $demande->setProduit($data['produit'] ?? null);

            if (isset($data['typeDemandeId'])) {
                $typeDemande = $this->entityManager->getReference(TypeDemande::class, $data['typeDemandeId']);
                $demande->setTypeDemande($typeDemande);
            }

            if (isset($data['typePaiementId'])) {
                $typePaiement = $this->entityManager->getReference(TypePaiement::class, $data['typePaiementId']);
                $demande->setTypePaiement($typePaiement);
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

        // 1. Validation du type de fichier (PDF et Excel)
        $allowedMimeTypes = ['application/pdf', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
        if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
            return new JsonResponse(['error' => 'Le fichier doit être un PDF ou un fichier Excel.'], 400);
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
        $document->setStatut('Chargé');
        $document->setTypeDocument($typeDocument);
        $document->setCreatedBy($this->getUser()->getUserIdentifier());
        $document->setDesactivate(false);

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


    /**
     * @Route("/suivi/{id}", name="admin_nouvelle_demande_suivi", methods={"GET"})
     */
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
                'statut' => $etape->getStatut(),
                'status' => $status,
                'details' => $etape->getDetails(),
            ];
        }

        // 3. Rendre le template Twig et le retourner comme une réponse HTML
        return $this->render('DemandeAutorisation/nouvelle_demande/suivi.html.twig', [
            'demande' => $demande,
            'etapes' => $etapesPourTwig,
        ]);
    }

    /**
     * @Route("/suivi/{demandeId}/etape/{etapeId}", name="admin_nouvelle_demande_suivi_etape", methods={"GET"})
     */
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
     * @Route("/{id}/submit", name="app_nouvelle_demande_submit", methods={"POST"})
     */
    public function submitForValidation(NouvelleDemande $demande): JsonResponse
    {
        try {
            //$demande->setStatut('En cours');
            $demande->setStatut('Soumis');

            $etapes = $demande->getEtapesValidation();

            if ($etapes->isEmpty()) {
                // First submission
                $this->createValidationCircuit($demande);
                /*echo "Première condition";
                dd($etapes);*/
            } else {
                
                // Re-submission
                foreach ($etapes as $etape) {
                    $etape->setStatut('En cours');
                    $etape->setDateTraitement();
                    $etape->setDetails(null);
                    $this->entityManager->persist($etape);

                }

                // Notify first validator again
                $typeDemande = $demande->getTypeDemande();
                if ($typeDemande) {
                    $modele = $this->modeleCommunicationRepository->findOneBy(['typeDemande' => $typeDemande, 'statut' => 'ACTIF']);
                    if ($modele) {
                        $firstDetail = $this->findDetailBySequence($modele, 1);
                        if ($firstDetail) {
                            $this->notificationService->sendNotificationForStep($demande, $firstDetail, $this->getUser());
                        }
                    }
                }
            }

            $this->entityManager->persist($demande);
            $this->entityManager->flush();

            return new JsonResponse(['success' => true, 'message' => 'Demande soumise pour validation.']);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    private function findDetailBySequence(\App\Entity\References\ModeleCommunication $modele, int $sequence): ?\App\Entity\References\DetailsModele
    {
        foreach ($modele->getDetailsModeles() as $detail) {
            if ($detail->getNumseq() === $sequence) {
                return $detail;
            }
        }
        return null;
    }


    /**
     * Détermine le statut d'une étape pour l'affichage du stepper.
     *
     * @param EtapeValidation $etape L'étape à évaluer.
     * @param string $demandeStatut Le statut global de la demande ('En cours', 'approuvee', 'rejetee').
     * @param bool $etapeActiveTrouvee Indique si l'étape active a déjà été identifiée dans la boucle.
     * @return array [string status, bool isActive]
     */
    private function determineEtapeStatus(EtapeValidation $etape, string $demandeStatut, bool &$etapeActiveTrouvee): array
    {
        if ($etape->getStatut() === 'Rejeté') {
            return ['rejected', false];
        }
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
        if ($demandeStatut === 'Accepté' || $demandeStatut === 'rejetee' || $demandeStatut === 'Rejeté') {
            return ['', false];
        }

        // C'est la première étape non complétée, elle est donc active.
        $etapeActiveTrouvee = true;
        return ['active', true];
    }

    private function createValidationCircuit(NouvelleDemande $nouvelleDemande): void
    {
        $typeDemande = $nouvelleDemande->getTypeDemande();
        if (!$typeDemande) {
            return;
        }

        $modele = $this->modeleCommunicationRepository->findOneBy([
            'typeDemande' => $typeDemande,
            'statut' => 'ACTIF'
        ]);

        if (!$modele) {
            return;
        }

        $detailsModele = $modele->getDetailsModeles();

        #$EtapeSpecial = ['Soumis','En cours de traitement','Demande signée et disponible'];
        $EtapeSpecial = ['Soumis','En cours','Signé'];
        //$Sequence = ['Soumis','En cours de traitement','Demande signée et disponible'];

        $i = 0;

        foreach ($detailsModele as $detail) {
            $etapeValidation = new EtapeValidation();
            $etapeValidation->setDemande($nouvelleDemande);
            //$etapeValidation->setOrdre($detail->getNumseq());
            $etapeValidation->setOrdre($i);

            $nomEtape = 'Étape inconnue';

           /* if ($detail->getTypeService() === 'DIRECTION' && $detail->getCodeDirection()) {
                $nomEtape = $detail->getCodeDirection()->getDenomination();
            } elseif ($detail->getTypeService() === 'SERVICE' && $detail->getCodeService()) {
                $nomEtape = $detail->getCodeService()->getLibelleService();
            } elseif ($detail->getTypeService() === 'SPECIAL' && $detail->getCodeService()) {
                $nomEtape = $EtapeSpecial[$i]; #$detail->getCodeService()->getLibelleService();
            }*/

            if ($detail->getTypeService() === 'SPECIAL'){
                $nomEtape = $EtapeSpecial[$i]; 
            }

            $etapeValidation->setNom($nomEtape);

            /*echo "Sequence: ".$detail->getNumseq();
            echo "Etape: ".$nomEtape;
            continue;*/

            $etapeValidation->setStatut('En cours');
            //Fonction spécial, activation du premier niveau du circuit
            if ($i== 0) {
                $etapeValidation->setStatut('Validé');
                $etapeValidation->setDateTraitement(new \DateTime());
            }
            $this->entityManager->persist($etapeValidation);

            // Send notification for the first step
            if ($detail->getNumseq() === 0) {
                $this->notificationService->sendNotificationForStep($nouvelleDemande, $detail, $this->getUser());
            }

            $i ++;
        }

        $this->entityManager->flush();
    }

    /**
     * @Route("/{id}/etat_depot", name="app_nouvelle_demande_etat_depot_pdf")
     */
    public function generateEtatDepotPdf(NouvelleDemande $demande, \App\Service\Paiement\PdfService $pdfService, TypeDemandeDetailRepository $typeDemandeDetailRepository): Response
    {
        $uploadedDocuments = [];
        foreach ($demande->getDemandeDocuments() as $demandeDocument) {
            $doc = $demandeDocument->getDocument();
            $uploadedDocuments[$doc->getTypeDocument()->getId()] = true;
        }

        $allDocuments = [];
        if ($demande->getTypeDemande()) {
            $requiredDocumentDetails = $typeDemandeDetailRepository->findBy(['typeDemande' => $demande->getTypeDemande()]);
            foreach ($requiredDocumentDetails as $detail) {
                $typeDocument = $detail->getTypeDocument();
                $typeDocId = $typeDocument->getId();
                $allDocuments[] = [
                    'nom' => $typeDocument->getDesignation(),
                    'fourni' => isset($uploadedDocuments[$typeDocId]) ? 'Fourni' : 'Non fourni',
                ];
            }
        }

        $html = $this->renderView('DemandeAutorisation/nouvelle_demande/etat_depot.html.twig', [
            'demande' => $demande,
            'documents' => $allDocuments
        ]);

        return new Response(
            $pdfService->generateBinaryPDF($html),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="etat_depot_' . $demande->getCodeSuivie() . '.pdf"',
            ]
        );
    }
}