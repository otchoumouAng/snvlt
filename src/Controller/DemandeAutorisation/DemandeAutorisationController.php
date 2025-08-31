<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


use App\Entity\User;
use App\Form\Autorisation\AttributionPvType;
use App\Repository\Administration\NotificationRepository;
use App\Repository\Autorisations\AttributionPvRepository;
use App\Repository\DocumentOperateurRepository;
use App\Repository\GroupeRepository;
use App\Repository\MenuPermissionRepository;
use App\Repository\MenuRepository;
use App\Repository\TypeAutorisationRepository;
use App\Repository\UserRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Contracts\Translation\TranslatorInterface;

class DemandeAutorisationController extends AbstractController
{
    #[Route('/admin/demande/autorisation', name: 'app_demande_autorisation')]
    public function index(
        MenuRepository $menus,
        MenuPermissionRepository $permissions,
        Request $request,
        UserRepository $userRepository,
        NotificationRepository $notification
    ): Response
    {
        $user = $userRepository->find($this->getUser());
        $code_groupe = $user->getCodeGroupe()->getId();
        $titre ="Add an attribution PV";


        //dd($menus->findOnlyParent());
        //dd($permissions->findBy(['code_groupe_id'=>$code_groupe]));
        //dd($menus->findAll());
        //dd($code_groupe);
      

        return $this->render('demande_autorisation/index.html.twig', [
                    'liste_menus'=>$menus->findOnlyParent(),
                    "all_menus"=>$menus->findAll(),
                    'mes_notifs'=>$notification->findBy(['to_user'=>$user, 'lu'=>false],[],5,0),
                    'menus'=>$permissions->findBy(['code_groupe_id'=>$code_groupe]),
                    'groupe'=>$code_groupe,
                    'titre'=>$titre,
                    'liste_parent'=>$permissions
                ]);
    }
}
