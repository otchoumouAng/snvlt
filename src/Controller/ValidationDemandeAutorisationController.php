<?php

namespace App\Controller;

use App\Repository\NouvelleDemandeRepository;
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
        // For now, we'll fetch all demandes. We can filter them later.
        $demandes = $nouvelleDemandeRepository->findAll();

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
     * @Route("/details/{id}", name="app_validation_demande_autorisation_details")
     */
    public function getDetailsDemande(int $id, NouvelleDemandeRepository $nouvelleDemandeRepository): JsonResponse
    {
        $demande = $nouvelleDemandeRepository->findWithDocuments($id);

        if (!$demande) {
            return new JsonResponse(['error' => 'Demande non trouvée'], 404);
        }

        $documents = [];
        foreach ($demande->getDocuments() as $document) {
            $documents[] = [
                'id' => $document->getId(),
                'nom' => $document->getNom(),
                'statut' => $document->getStatut(),
                'url' => $this->generateUrl('app_document_download', ['id' => $document->getId()]), // Assuming you have a route for downloading documents
                'dateAjout' => $document->getDateAjout()->format('d/m/Y H:i')
            ];
        }

        $data = [
            'id' => $demande->getId(),
            'titre' => $demande->getRaisonSocial(),
            'description' => $demande->getDescription(),
            'statut' => $demande->getStatut(),
            'documents' => $documents,
            'typeDocument' => $demande->getTypeDocument()->getNom()
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

        $demande->setStatut($data['statut']);
        // Here you would handle the note and signature
        // For example, you could create a new ValidationNote entity
        // and save the signature as a file.

        $this->entityManager->flush();

        return new JsonResponse(['success' => true]);
    }
}
