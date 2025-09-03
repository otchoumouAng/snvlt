<?php

namespace App\Controller;

use App\Repository\References\TypesServiceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\Administration\NotificationRepository;
use App\Repository\MenuPermissionRepository;
use App\Repository\MenuRepository;
use App\Repository\UserRepository;

class TransactionWorkflowController extends AbstractController
{
    /**
     * @Route("/transaction/new", name="app_transaction_workflow")
     */
    public function index(
        TypesServiceRepository $typesServiceRepo,
        MenuRepository $menus,
        NotificationRepository $notification,
        MenuPermissionRepository $permissions,
        UserRepository $userRepository
    ): Response
    {
        $user = $userRepository->find($this->getUser());
        $code_groupe = $user->getCodeGroupe()->getId();

        return $this->render('transaction_workflow/index.html.twig', [
            'liste_menus' => $menus->findOnlyParent(),
            "all_menus" => $menus->findAll(),
            'mes_notifs' => $notification->findBy(['to_user' => $this->getUser(), 'lu' => false], [], 5, 0),
            'menus' => $permissions->findBy(['code_groupe_id' => $code_groupe]),
            'groupe' => $code_groupe,
            'titre' => 'Initier une Transaction',
            'liste_parent' => $permissions,
            'initial_options' => $typesServiceRepo->findAll(),
        ]);
    }
}
