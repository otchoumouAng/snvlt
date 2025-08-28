<?php

namespace App\Controller\References;

use App\Repository\Administration\NotificationRepository;
use App\Repository\GroupeRepository;
use App\Repository\MenuPermissionRepository;
use App\Repository\MenuRepository;
use App\Repository\References\ExploitantRepository;
use App\Repository\References\NationaliteRepository;
use App\Repository\UserRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class NationnaliteController extends AbstractController
{
    #[Route('/nationnalite', name: 'app_nationnalite')]
    public function index(): Response
    {
        return $this->render('nationnalite/index.html.twig', [
            'controller_name' => 'NationnaliteController',
        ]);
    }

    #[Route('snvlt/ref/nationalites/data_json', name: 'json_nationalites')]
    public function exploitants_json(NationaliteRepository $nationaliteRepository,
                                     MenuRepository $menus,
                                     MenuPermissionRepository $permissions,
                                     GroupeRepository $groupeRepository,
                                     UserRepository $userRepository,
                                     ManagerRegistry $doctrine,
                                     Request $request,
                                     NotificationRepository $notificationRepository,
    ): Response
    {
        if(!$request->getSession()->has('user_session')){

            return $this->redirectToRoute('app_login');
        } else {
            if ($this->isGranted('ROLE_MINEF') or  $this->isGranted('ROLE_ADMIN'))
            {
                $user = $userRepository->find($this->getUser());
                $code_groupe = $user->getCodeGroupe()->getId();

                $response = array();
                $liste_nationalite = $nationaliteRepository->findBy([], ['nationalite'=>'ASC']);
                foreach ( $liste_nationalite as $nationalite){
                    $response[] =  array(
                        'id'=>$nationalite->getId(),
                        'nationalite'=>$nationalite->getNationalite()
                    );


                }
                return  new  JsonResponse(json_encode($response));

            } else {
                return $this->redirectToRoute('app_no_permission_user_active');
            }
        }
    }
}
