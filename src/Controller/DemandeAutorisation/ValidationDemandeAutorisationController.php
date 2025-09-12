<?php

namespace App\Controller\DemandeAutorisation;

use App\Entity\DemandeAutorisation\EtapeValidation;
use App\Entity\DemandeAutorisation\NouvelleDemande;
use App\Entity\DemandeAutorisation\TypeDemandeDetail;
use App\Entity\DemandeAutorisation\ValidationAction;
use App\Entity\References\DetailsModele;
use App\Repository\DemandeAutorisation\EtapeValidationRepository;
use App\Repository\DemandeAutorisation\NouvelleDemandeRepository;
use App\Repository\DemandeAutorisation\TypeDemandeDetailRepository;
use App\Repository\References\ModeleCommunicationRepository;
use App\Repository\MenuPermissionRepository;
use App\Repository\MenuRepository;
use App\Service\NotificationService;
use App\Repository\UserRepository;
use App\Repository\Administration\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/validation_demande_autorisation")
 */
class ValidationDemandeAutorisationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EtapeValidationRepository $etapeValidationRepository,
        private NouvelleDemandeRepository $nouvelleDemandeRepository,
        private TypeDemandeDetailRepository $typeDemandeDetailRepository,
        private NotificationService $notificationService,
        private ModeleCommunicationRepository $modeleCommunicationRepository
    ) {
    }

    /**
     * @Route("/", name="app_validation_demande_autorisation")
     */
    public function index(MenuRepository $menus, MenuPermissionRepository $permissions, Request $request, UserRepository $userRepository, NotificationRepository $notification): Response
    {
        if(!$request->getSession()->has('user_session')){
            return $this->redirectToRoute('app_login');
        } else {
            if ($this->isGranted('ROLE_AGENT_INSPECTEUR') or $this->isGranted('ROLE_ADMIN'))
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

        // Find steps that are 'en_attente' and for which the previous step is 'validé'
        // This is a simplified logic. A more robust implementation would be needed.
        // For now, we'll just fetch all steps assigned to the user's direction/service that are pending.

        $userDirection = $currentUser->getCodeDirection();
        $userService = $currentUser->getCodeService();

        $pendingSteps = $this->etapeValidationRepository->findPendingStepsForUser($userDirection, $userService);

        $data = [];
        foreach ($pendingSteps as $etape) {
            $demande = $etape->getDemande();
            $operateur = $demande->getOperateur();
            $societe = $operateur ? ($operateur->getCodeexploitant() ? $operateur->getCodeexploitant()->getRaisonSocialeExploitant() : $operateur->getNomUtilisateur() . ' ' . $operateur->getPrenomsUtilisateur()) : 'N/A';

            $data[] = [
                'id' => $demande->getId(),
                'etape_id' => $etape->getId(),
                'titre' => $demande->getTitre(),
                'description' => $etape->getNom(), // Show the step name as description
                'statut' => $demande->getStatut(),
                'dateCreation' => $demande->getCreatedAt()->format('d/m/Y'),
                'typeDemande' => $demande->getTypeDemande() ? $demande->getTypeDemande()->getDesignation() : 'N/A',
                'societe' => $societe
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
                        'statut' => 'Non soumis',
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

        $data = [
            'id' => $demande->getId(),
            'titre' => $demande->getTitre(),
            'description' => $demande->getDescription(),
            'statut' => $demande->getStatut(),
            'typeDemande' => $demande->getTypeDemande() ? $demande->getTypeDemande()->getDesignation() : 'N/A',
            'societe' => $societe,
            'documents' => $requiredDocuments,
            'etapes_validation' => $etapesData,
        ];

        return new JsonResponse($data);
    }

    /**
     * @Route("/etape/{id}/validate", name="app_validation_demande_autorisation_etape_validate", methods={"POST"})
     */
    public function validateStep(EtapeValidation $etape, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $decision = $data['decision'] ?? null; // 'approve' or 'reject'

        if (!in_array($decision, ['approve', 'reject'])) {
            return new JsonResponse(['error' => 'Invalid decision'], 400);
        }

        $demande = $etape->getDemande();

        if ($decision === 'approve') {
            $etape->setStatut('Validé');
            $etape->setDateTraitement(new \DateTimeImmutable());

            // Find next step
            $nextEtape = $this->etapeValidationRepository->findOneBy([
                'demande' => $demande,
                'ordre' => $etape->getOrdre() + 1
            ]);

            if ($nextEtape) {
                // Not the last step, find the corresponding circuit detail to notify next validators
                $typeDemande = $demande->getTypeDemande();
                $nextDetail = null;
                if ($typeDemande) {
                    $modele = $this->modeleCommunicationRepository->findOneBy([
                        'typeDemande' => $typeDemande,
                        'statut' => 'ACTIF'
                    ]);

                    if ($modele) {
                        foreach ($modele->getDetailsModeles() as $detail) {
                            if ($detail->getNumseq() === $nextEtape->getOrdre()) {
                                $nextDetail = $detail;
                                break;
                            }
                        }
                    }
                }

                if ($nextDetail) {
                    $this->notificationService->sendNotificationForStep($demande, $nextDetail, $this->getUser());
                }
                // If $nextDetail is not found, we might want to log an error or handle it somehow
                // For now, it will just not send a notification.

            } else {
                // This was the last step, approve the whole request
                $demande->setStatut('accepté');
            }
        } else { // 'reject'
            $etape->setStatut('Rejeté');
            $etape->setDateTraitement(new \DateTimeImmutable());
            $demande->setStatut('rejeté');
        }

        $this->entityManager->flush();

        return new JsonResponse(['success' => true]);
    }

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
