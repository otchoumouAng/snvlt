<?php

namespace App\Controller\Paiement;

use App\Repository\Paiement\TypePaiementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\Administration\NotificationRepository;
use App\Repository\MenuPermissionRepository;
use App\Repository\MenuRepository;
use App\Repository\UserRepository;

class PaiementWorkflowController extends AbstractController
{
    /**
     * @Route("/paiement/new", name="app_paiement_workflow")
     */
    public function index(
        TypePaiementRepository $typePaiementRepository,
        MenuRepository $menus,
        NotificationRepository $notification,
        MenuPermissionRepository $permissions,
        UserRepository $userRepository
    ): Response
    {
        $user = $userRepository->find($this->getUser());
        $code_groupe = $user->getCodeGroupe()->getId();

        $userInfo = [
            'nom' => $user->getNomUtilisateur(),
            'prenom' => $user->getPrenomsUtilisateur(),
            'telephone' => $user->getMobile()
        ];

        return $this->render('paiement/index.html.twig', [
            'liste_menus' => $menus->findOnlyParent(),
            "all_menus" => $menus->findAll(),
            'mes_notifs' => $notification->findBy(['to_user' => $this->getUser(), 'lu' => false], [], 5, 0),
            'menus' => $permissions->findBy(['code_groupe_id' => $code_groupe]),
            'groupe' => $code_groupe,
            'titre' => 'Initier une Transaction',
            'liste_parent' => $permissions,
            'type_paiements' => $typePaiementRepository->findAll(),
            'user_info' => $userInfo,
        ]);
    }
}
