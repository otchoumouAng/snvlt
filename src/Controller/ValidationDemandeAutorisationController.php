<?php

namespace App\Controller;

use App\Entity\NouvelleDemande;
use App\Entity\TypeDemandeDetail;
use App\Entity\ValidationAction;
use App\Repository\NouvelleDemandeRepository;
use App\Repository\TypeDemandeDetailRepository;
use App\Repository\MenuPermissionRepository;
use App\Repository\MenuRepository;
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
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
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

                return $this->render('validation_demande_autorisation/index.html.twig', [
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
    public function getListeDemandes(NouvelleDemandeRepository $nouvelleDemandeRepository): JsonResponse
    {
        $demandes = $nouvelleDemandeRepository->findBy(['statut' => 'Soumis']);

        $data = [];
        foreach ($demandes as $demande) {
            $data[] = [
                'id' => $demande->getId(),
                'titre' => $demande->getRaisonSocial(),
                'description' => $demande->getDescription(),
                'statut' => $demande->getStatut(),
                'dateCreation' => $demande->getCreatedAt()->format('d/m/Y'),
                'typeDocument' => $demande->getTypeDemande() ? $demande->getTypeDemande()->getDesignation() : '',
                'societe' => $demande->getRaisonSocial()
            ];
        }

        return new JsonResponse($data);
    }

    /**
     * @Route("/details/{id}", name="app_validation_demande_autorisation_details")
     */
    public function getDetailsDemande(int $id, NouvelleDemandeRepository $nouvelleDemandeRepository, TypeDemandeDetailRepository $typeDemandeDetailRepository): JsonResponse
    {
        $demande = $nouvelleDemandeRepository->findWithDocuments($id);

        if (!$demande) {
            return new JsonResponse(['error' => 'Demande non trouvée'], 404);
        }

        $requiredDocuments = $typeDemandeDetailRepository->findBy(['type_demande_id' => $demande->getTypeDemande()->getId()]);
        $providedDocuments = $demande->getDocuments();

        $documents = [];
        foreach ($requiredDocuments as $requiredDoc) {
            $providedDoc = null;
            foreach ($providedDocuments as $pDoc) {
                if ($pDoc->getTypeDocumentId() === $requiredDoc->getTypeDocument()->getId()) {
                    $providedDoc = $pDoc;
                    break;
                }
            }

            $documents[] = [
                'id' => $requiredDoc->getTypeDocument()->getId(),
                'nom' => $requiredDoc->getTypeDocument()->getDesignation(),
                'statut' => $providedDoc ? 'Fourni' : 'Manquant',
                'url' => $providedDoc ? $this->generateUrl('app_document_download', ['id' => $providedDoc->getId()]) : null,
                'dateAjout' => $providedDoc ? $providedDoc->getDateAjout()->format('d/m/Y H:i') : null
            ];
        }

        $data = [
            'id' => $demande->getId(),
            'titre' => $demande->getRaisonSocial(),
            'description' => $demande->getDescription(),
            'statut' => $demande->getStatut(),
            'documents' => $documents,
            'typeDocument' => $demande->getTypeDemande() ? $demande->getTypeDemande()->getDesignation() : ''
        ];

        return new JsonResponse($data);
    }

    /**
     * @Route("/{id}/validate", name="app_validation_demande_autorisation_validate", methods={"POST"})
     */
    public function validateDemande(int $id, Request $request, NouvelleDemandeRepository $nouvelleDemandeRepository): JsonResponse
    {
        $demande = $nouvelleDemandeRepository->find($id);

        if (!$demande) {
            return new JsonResponse(['error' => 'Demande non trouvée'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $statut = $data['statut'];
        $note = $data['note'];
        $signatureData = $data['signature'];

        // 1. Update demand status
        $demande->setStatut($statut);

        // 2. Save signature
        $signaturePath = null;
        if ($signatureData) {
            $signaturePath = $this->saveSignature($signatureData, $demande->getId());
        }

        // 3. Create validation action
        $validationAction = new ValidationAction();
        $validationAction->setDemande($demande);
        $validationAction->setValidator($this->getUser());
        $validationAction->setStatut($statut);
        $validationAction->setNote($note);
        $validationAction->setSignaturePath($signaturePath);

        $this->entityManager->persist($validationAction);
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
