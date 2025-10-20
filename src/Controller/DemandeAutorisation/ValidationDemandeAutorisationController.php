<?php

namespace App\Controller\DemandeAutorisation;

use App\Entity\DemandeAutorisation\EtapeValidation;
use App\Entity\DemandeAutorisation\NouvelleDemande;
use App\Entity\DemandeAutorisation\TypeDemandeDetail;
use App\Entity\DemandeAutorisation\ValidationAction;
use App\Entity\References\DetailsModele;
use App\Repository\DemandeAutorisation\EtapeValidationRepository;
use App\Repository\DemandeAutorisation\NouvelleDemandeRepository;
use App\Entity\Admin\Exercice;
use App\Entity\Autorisation\Reprise;
use App\Repository\Autorisations\AttributionRepository;
use App\Repository\Autorisations\RepriseRepository;
use App\Repository\DemandeAutorisation\TypeDemandeDetailRepository;
use App\Repository\References\ForetRepository;
use App\Repository\References\ModeleCommunicationRepository;
use App\Repository\MenuPermissionRepository;
use App\Repository\MenuRepository;
use App\Service\NotificationService;
use App\Repository\UserRepository;
use Psr\Log\LoggerInterface;
use App\Repository\Administration\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\References\ServiceMinefRepository; 


/**
 * @Route("/admin/validation_demande_autorisation")
 */
class ValidationDemandeAutorisationController extends AbstractController
{
    private $serviceMinefRepository;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private EtapeValidationRepository $etapeValidationRepository,
        private NouvelleDemandeRepository $nouvelleDemandeRepository,
        private TypeDemandeDetailRepository $typeDemandeDetailRepository,
        private NotificationService $notificationService,
        private ModeleCommunicationRepository $modeleCommunicationRepository,
        private AttributionRepository $attributionRepository,
        private ForetRepository $foretRepository,
        private RepriseRepository $repriseRepository,
        private LoggerInterface $logger,
        ServiceMinefRepository $serviceMinefRepository
    ) {
        $this->serviceMinefRepository = $serviceMinefRepository;
    }

    /**
     * @Route("/", name="app_validation_demande_autorisation")
     */
    public function index(MenuRepository $menus, MenuPermissionRepository $permissions, Request $request, UserRepository $userRepository, NotificationRepository $notification): Response
    {
        if(!$request->getSession()->has('user_session')){
            return $this->redirectToRoute('app_login');
        } else {
            if ($this->isGranted('ROLE_MINEF') or $this->isGranted('ROLE_ADMIN') or $this->isGranted('ROLE_DPIF'))
            {
                $user = $userRepository->find($this->getUser());
                $code_groupe = $user->getCodeGroupe()->getId();

            return $this->render('DemandeAutorisation/validation_demande_autorisation/index.html.twig', [
                    'liste_menus'=>$menus->findOnlyParent(),
                    "all_menus"=>$menus->findAll(),
                    'mes_notifs'=>$notification->findBy(['to_user'=>$user, 'lu'=>false],[],5,0),
                    'menus'=>$permissions->findBy(['code_groupe_id'=>$code_groupe]),
                    'groupe'=>$code_groupe,
                    'liste_parent'=>$permissions,
                ]);
            } else {
                return  $this->redirectToRoute('app_no_permission_user_active');
            }
        }
    }

    /**
     * @Route("/liste", name="app_validation_demande_autorisation_liste")
     */
    public function getListeDemandes(UserRepository $userRepository): JsonResponse
    {
        $currentUser = $this->getUser();
        if (!$currentUser) {
            return new JsonResponse(['error' => 'User not authenticated'], 401);
        }

        $userDirection = $currentUser->getCodeDirection();
        $userService = $currentUser->getCodeService();

        $pendingSteps = $this->etapeValidationRepository->findPendingStepsForUser($userDirection, $userService);

        $data = [];
        foreach ($pendingSteps as $etape) {
            $demande = $etape->getDemande();
            $operateur = $demande->getOperateur();
            $societe = $operateur ? ($operateur->getCodeexploitant() ? $operateur->getCodeexploitant()->getRaisonSocialeExploitant() : $operateur->getNomUtilisateur() . ' ' . $operateur->getPrenomsUtilisateur()) : 'N/A';
            /*if ($demande->getStatut() != "Signé") {
            }*/
                $data[] = [
                    'id' => $demande->getId(),
                    'etape_id' => $etape->getId(),
                    'titre' => $demande->getTypePaiement() ? $demande->getTypePaiement()->getLibelle() : 'N/A',
                    'description' => $etape->getNom(),
                    'statut' => $demande->getStatut(),
                    'dateCreation' => $demande->getCreatedAt()->format('d/m/Y'),
                    'typeDemande' => $demande->getTypeDemande() ? $demande->getTypeDemande()->getDesignation() : 'N/A',
                    'societe' => $societe
                ];
        }

        return new JsonResponse($data);
    }

    /**
     * @Route("/liste-traitees", name="app_validation_demande_autorisation_liste_traitees")
     */
    public function getListeDemandesTraitees(): JsonResponse
    {
        $demandesTraitees = $this->nouvelleDemandeRepository->findBy(['statut' => 'Signé']);

        $data = [];
        foreach ($demandesTraitees as $demande) {
            $operateur = $demande->getOperateur();
            $societe = $operateur ? ($operateur->getCodeexploitant() ? $operateur->getCodeexploitant()->getRaisonSocialeExploitant() : $operateur->getNomUtilisateur() . ' ' . $operateur->getPrenomsUtilisateur()) : 'N/A';
            $derniereEtape = $this->etapeValidationRepository->findOneBy(['demande' => $demande], ['dateTraitement' => 'DESC']);

            $data[] = [
                'id' => $demande->getId(),
                'titre' => $demande->getTypePaiement() ? $demande->getTypePaiement()->getLibelle() : 'N/A',
                'societe' => $societe,
                'statut' => $demande->getStatut(),
                'typeDemande' => $demande->getTypeDemande() ? $demande->getTypeDemande()->getDesignation() : 'N/A',
                'pef' => $demande->getNumeroPef() ? $demande->getNumeroPef() : 'N/A',
                'produit' => $demande->getProduit() ? $demande->getProduit(): 'N/A',
                'dateTraitement' => $derniereEtape && $derniereEtape->getDateTraitement() ? $derniereEtape->getDateTraitement()->format('d/m/Y') : 'N/A',
                'documentSignePath' => $demande->getDocumentSignePath(),
            ];

        }

        return new JsonResponse($data);
    }

    /**
     * @Route("/details/{id}", name="app_validation_demande_autorisation_details")
     */
    public function getDetailsDemande(int $id): JsonResponse
    {
        $demande = $this->nouvelleDemandeRepository->find($id);

        if (!$demande) {
            return new JsonResponse(['error' => 'Demande non trouvée'], 404);
        }

        // This logic is duplicated from NouvelleDemandeController, consider a shared service later
        $uploadedDocuments = [];
        foreach ($demande->getDemandeDocuments() as $demandeDocument) {
            $doc = $demandeDocument->getDocument();
            $uploadedDocuments[$doc->getTypeDocument()->getId()] = [
                'id' => $doc->getId(),
                'nom' => $doc->getNom(),
                'path' => '/uploads/documents/' . $doc->getPath(),
                'statut' => $doc->getStatut(),
                'dateAjout' => $doc->getCreatedAt()->format('d/m/Y H:i')
            ];
        }

        $requiredDocuments = [];
        if ($demande->getTypeDemande()) {
            $requiredDocumentDetails = $this->typeDemandeDetailRepository->findBy(['typeDemande' => $demande->getTypeDemande()]);
            foreach ($requiredDocumentDetails as $detail) {
                $typeDocument = $detail->getTypeDocument();
                $typeDocId = $typeDocument->getId();

                if (isset($uploadedDocuments[$typeDocId])) {
                    $requiredDocuments[] = [
                        'type_document_id' => $typeDocId,
                        'nom' => $typeDocument->getDesignation(),
                        'statut' => $uploadedDocuments[$typeDocId]['statut'],
                        'document_id' => $uploadedDocuments[$typeDocId]['id'],
                        'path' => $uploadedDocuments[$typeDocId]['path'],
                        'dateAjout' => $uploadedDocuments[$typeDocId]['dateAjout'],
                    ];
                } else {
                    $requiredDocuments[] = [
                        'type_document_id' => $typeDocId,
                        'nom' => $typeDocument->getDesignation(),
                        'statut' => 'Non chargé',
                        'document_id' => null,
                        'path' => null,
                        'dateAjout' => null
                    ];
                }
            }
        }

        $etapes = $this->etapeValidationRepository->findBy(['demande' => $demande], ['ordre' => 'ASC']);
        $etapesData = array_map(function ($etape) {
            return [
                'id' => $etape->getId(),
                'nom' => $etape->getNom(),
                'statut' => $etape->getStatut(),
                'ordre' => $etape->getOrdre(),
                'dateTraitement' => $etape->getDateTraitement() ? $etape->getDateTraitement()->format('d/m/Y H:i') : null,
            ];
        }, $etapes);

        $operateur = $demande->getOperateur();
        $societe = $operateur ? ($operateur->getCodeexploitant() ? $operateur->getCodeexploitant()->getRaisonSocialeExploitant() : $operateur->getNomUtilisateur() . ' ' . $operateur->getPrenomsUtilisateur()) : 'N/A';

        $publicDir = $this->getParameter('kernel.project_dir') . '/public';
        $documentsDir = $this->getParameter('documents_directory');
        $webPath = str_replace($publicDir, '', $documentsDir);

        /*if ($demande->getDocumentExcelPath()) {
            array_unshift($requiredDocuments, [
                'type_document_id' => 'excel_special',
                'nom' => 'Fichier Spécial Excel',
                'statut' => 'Chargé',
                'document_id' => null,
                'path' => $webPath . '/' . $demande->getDocumentExcelPath(),
                'dateAjout' => null,
            ]);
        }*/

        $data = [
            'id' => $demande->getId(),
            'titre' => $demande->getTypePaiement() ? $demande->getTypePaiement()->getLibelle() : 'N/A',
            'description' => $demande->getDescription(),
            'statut' => $demande->getStatut(),
            'typeDemande' => $demande->getTypeDemande() ? $demande->getTypeDemande()->getDesignation() : 'N/A',
            'societe' => $societe,
            'documents' => $requiredDocuments,
            'etapes_validation' => $etapesData,
            'documentSignePath' => $demande->getDocumentSignePath() ? '/uploads/documents/' . $demande->getDocumentSignePath() : null,

        ];

        return new JsonResponse($data);
    }

    /**
     * @Route("/{id}/rejected-documents", name="app_validation_demande_rejected_documents", methods={"GET"})
     */
    public function getRejectedDocuments(NouvelleDemande $demande): JsonResponse
    {
        $rejectedDocuments = [];
        $publicDir = $this->getParameter('kernel.project_dir') . '/public';
        $documentsDir = $this->getParameter('documents_directory');
        $webPath = str_replace($publicDir, '', $documentsDir);

        foreach ($demande->getDemandeDocuments() as $demandeDocument) {
            $document = $demandeDocument->getDocument();
            if ($document->getStatut() === 'Rejeté') {
                $rejectedDocuments[] = [
                    'nom_original' => $document->getNom(),
                    'type_document' => $document->getTypeDocument()->getDesignation(),
                    'path' => $webPath . '/' . $document->getPath(),
                ];
            }
        }

        return new JsonResponse($rejectedDocuments);
    }


    //**************


    /**
     * @Route("/{id}/validate_demande", name="app_validation_demande_autorisation_validate_demande", methods={"POST"})
     */
    public function validateDemande(NouvelleDemande $demande, Request $request): JsonResponse
    {
        $newStatus = $request->request->get('newStatus');
        $refusedDocuments = json_decode($request->request->get('refusedDocuments', '[]'), true);
        $justification = $request->request->get('justification');
        $etapeId = $request->request->get('etapeId');
        $uploadedFile = $request->files->get('signedDocument');
        $numeroAutorisation = $request->request->get('numeroAutorisation');

        if (empty($newStatus)) {
            return new JsonResponse(['error' => 'Veuillez selectionner un statut valide'], 400);
        }

        if (empty($etapeId)) {
            return new JsonResponse(['error' => 'L\'identifiant de l\'étape de validation est manquant.'], 400);
        }
        if ($newStatus === 'Suspendu' && empty($justification)) {
            return new JsonResponse(['error' => 'La justification est obligatoire pour suspendre une demande.'], 400);
        }
        if ($newStatus === 'Signé' && !$uploadedFile) {
            return new JsonResponse(['error' => 'Le document signé est obligatoire pour ce statut.'], 400);
        }

        $currentEtape = $this->etapeValidationRepository->find($etapeId);
        if (!$currentEtape) {
            return new JsonResponse(['error' => 'Étape de validation non trouvée.'], 404);
        }

        // --- Main Logic ---

        // 1. Set the overall demand status based on agent's choice
        $demande->setStatut($newStatus);

        // 2. Handle the validation step's status and details
        if ($newStatus != 'Suspendu') {
            // For all other actions (e.g., 'Signé', 'En cours'), validate the current step.
            $currentEtape->setDateTraitement(new \DateTime());
            $currentEtape->setStatut('Validé');
        }

        // 3. Handle individual document rejections (this is now independent)
        if (!empty($refusedDocuments)) {
            foreach ($refusedDocuments as $docId => $status) {
                $document = $this->entityManager->getRepository(\App\Entity\DemandeAutorisation\Document::class)->find($docId);
                if ($document) {
                    $document->setStatut('Rejeté');
                }
            }
        }

        // 4. Handle 'Signé' specific actions
        if ($newStatus === 'Signé' && $uploadedFile) {
            $newFilename = uniqid().'.'.$uploadedFile->guessExtension();
            $documentsDirectory = $this->getParameter('documents_directory');

            if (!is_dir($documentsDirectory)) {
                mkdir($documentsDirectory, 0777, true);
            }

            $uploadedFile->move($documentsDirectory, $newFilename);
            $demande->setDocumentSignePath($newFilename);

            if ($demande->getNumeroPef()) {
                $exercice = $this->entityManager->getRepository(Exercice::class)->findCurrentExercice();

                if (!$exercice) {
                    $this->logger->warning('No current exercice found.');
                } else {
                    $foret = $this->foretRepository->findOneBy(['numero_foret' => $demande->getNumeroPef()]);

                    if (!$foret) {
                        $this->logger->warning(sprintf('Foret with PEF number "%s" not found.', $demande->getNumeroPef()));
                    } else {
                        $attribution = $this->attributionRepository->findOneBy(['code_foret' => $foret, 'exercice' => $exercice]);

                        if (!$attribution) {
                            $this->logger->warning(sprintf('Attribution for PEF number "%s" and exercice "%s" not found.', $demande->getNumeroPef(), $exercice->getAnnee()));
                        } else {
                            $reprise = $this->repriseRepository->findOneBy(['code_attribution' => $attribution, 'exercice' => $exercice]);

                            if (!$reprise) {
                                $reprise = new Reprise();
                                $reprise->setCreatedBy($this->getUser()->getUserIdentifier());
                                $reprise->setCreatedAt(new \DateTimeImmutable());
                            }

                            $reprise->setNumeroAutorisation($numeroAutorisation);
                            $reprise->setDateAutorisation(new \DateTime());
                            $reprise->setCodeAttribution($attribution);
                            $reprise->setExercice($exercice);
                            $reprise->setUpdatedBy($this->getUser()->getUserIdentifier());
                            $reprise->setUpdatedAt(new \DateTime());
                            $this->entityManager->persist($reprise);
                        }
                    }
                }
            }

            // Also mark the "Signé" step itself as validated
            $etapeSigne = $this->etapeValidationRepository->findOneBy(['nom' => 'Signé', 'demande' => $demande]);
            if($etapeSigne) {
                $etapeSigne->setStatut('Validé');
                $etapeSigne->setDateTraitement(new \DateTime());
                $this->entityManager->persist($etapeSigne);
            }
        }

        $validationAction = new ValidationAction();
        $validationAction->setDemande($demande);
        $validationAction->setValidator($this->getUser());
        $validationAction->setStatut($newStatus); // The action status is the one selected by the agent
        $validationAction->setNote($justification);
        $validationAction->setCreatedBy($this->getUser()->getUserIdentifier());
        if ($newStatus === 'Signé') {
            $validationAction->setNumeroAutorisation($numeroAutorisation);
        }

        $this->entityManager->persist($validationAction);
        $this->entityManager->flush();

        // Send notification based on the demand's new status
        if ($newStatus === 'Signé') {
            $this->notificationService->createNotification(
                $demande->getOperateur(),
                "Votre demande a été validée",
                "Votre demande N°" . $demande->getCodeSuivie() . " a été validée et le document signé est maintenant disponible.",
                'emails/demande_validee.html.twig',
                ['demande' => $demande]
            );
        } elseif ($newStatus === 'Suspendu') {
            $this->notificationService->createNotification(
                $demande->getOperateur(),
                "Votre demande a été suspendue",
                "Votre demande N°" . $demande->getCodeSuivie() . " a été suspendue. Motif : " . $justification,
                'emails/rejetee.html.twig', 
                ['demande' => $demande, 'justification' => $justification]
            );
        }

        return new JsonResponse(['success' => true]);
    }


    //*************

    private function saveSignature(string $dataUrl, int $demandeId): ?string
    {
        if (preg_match('/^data:image\/(\w+);base64,/', $dataUrl, $type)) {
            $data = substr($dataUrl, strpos($dataUrl, ',') + 1);
            $type = strtolower($type[1]); // png, jpg, gif

            if (!in_array($type, ['jpg', 'jpeg', 'gif', 'png'])) {
                throw new \Exception('invalid image type');
            }
            $data = base64_decode($data);
            if ($data === false) {
                throw new \Exception('base64_decode failed');
            }
        } else {
            throw new \Exception('did not match data URI with image data');
        }

        $directory = $this->getParameter('signatures_directory');
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $filename = sprintf('signature-%d-%s.%s', $demandeId, uniqid(), $type);
        $path = $directory . '/' . $filename;
        file_put_contents($path, $data);

        return $filename;
    }
}