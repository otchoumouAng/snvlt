<?php

namespace App\Controller;

use App\Entity\DetailsCircuitActes;
use App\Entity\Paiement\CatalogueServices;
use App\Entity\References\Direction;
use App\Entity\References\ServiceMinef;
use App\Entity\User;
use App\Repository\Administration\NotificationRepository;
use App\Repository\DetailsCircuitActesRepository;
use App\Repository\GroupeRepository;
use App\Repository\MenuPermissionRepository;
use App\Repository\MenuRepository;
use App\Repository\References\CantonnementRepository;
use App\Repository\References\DirectionRepository;
use App\Repository\References\ServiceMinefRepository;
use App\Repository\UserRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CircuitValidationActesController extends AbstractController
{

    #[Route('/circuit/validation/actes', name: 'app_cv_actes')]
    public function index(
        CantonnementRepository $cantonnements,
        MenuRepository $menus,
        MenuPermissionRepository $permissions,
        GroupeRepository $groupeRepository,
        Request $request,
        UserRepository $userRepository,
        User $user = null,
        NotificationRepository $notification
    ): Response
    {
        if(!$request->getSession()->has('user_session') or !$this->getUser()){

            return $this->redirectToRoute('app_login');
        } else {
            if ($this->isGranted('ROLE_ADMIN') or $this->isGranted('ROLE_MINEF'))
            {
                $user = $userRepository->find($this->getUser());
                $code_groupe = $user->getCodeGroupe()->getId();

                return $this->render('circuit_validation_actes/index.html.twig', [
                    'liste_cantonnements' => $cantonnements->findAll(),
                    'liste_menus'=>$menus->findOnlyParent(),
                    "all_menus"=>$menus->findAll(),
                    'menus'=>$permissions->findBy(['code_groupe_id'=>$code_groupe]),
                    'groupe'=>$code_groupe,
                    'mes_notifs'=>$notification->findBy(['to_user'=>$user, 'lu'=>false],[],5,0),
                    'liste_parent'=>$permissions
                ]);
            } else {
                return $this->redirectToRoute('app_no_permission_user_active');
            }
        }
    }

    #[Route('snvlt/addDetail/CircuitActes/{code_acte}/{code_service}', name: 'app_cv_actes_add_details')]
    public function addDetailsCircuit(
        Request $request,
        UserRepository $userRepository,
        int $code_acte,
        int $code_service,
        ManagerRegistry $registry
    ): Response
    {
        if(!$request->getSession()->has('user_session') or !$this->getUser()){

            return $this->redirectToRoute('app_login');
        } else {
            if ($this->isGranted('ROLE_ADMIN') or $this->isGranted('ROLE_MINEF'))
            {
                $user = $userRepository->find($this->getUser());
                $code_groupe = $user->getCodeGroupe()->getId();

                $reponse = array();

                $serv = $registry->getRepository(ServiceMinef::class)
                    ->find($code_service);
                $acte = $registry->getRepository(CatalogueServices::class)
                    ->find($code_acte);
                //dd($serv);
                if ($serv && $acte){
                    // Recherche le detail si Existant
                    $rech = $registry->getRepository(DetailsCircuitActes::class)
                        ->findBy(['code_service'=>$serv, 'code_acte'=>$acte]);
                    if (!$rech){
                        $detail = new DetailsCircuitActes();
                        $detail->setCodeActe($acte);
                        $detail->setCodeService($serv);
                        $detail->setOrdre($registry->getRepository(DetailsCircuitActes::class)->count(['code_acte'=>$acte]) + 1);

                        $registry->getManager()->persist($detail);
                        $registry->getManager()->flush();

                        $reponse = array(
                            'code'=>'SUCCESS',
                        );
                    } else {
                        $reponse = array(
                            'code'=>'TROUVE',
                        );
                    }

                } else {
                    $reponse = array(
                        'code'=>'ERROR',
                    );
                }
                return new JsonResponse(json_encode($reponse));
            } else {
                return $this->redirectToRoute('app_no_permission_user_active');
            }
        }
    }
    #[Route('snvlt/getDetail/CircuitActes/{code_acte}', name: 'app_cv_actes_get_details')]
    public function getDetailsCircuit(
        Request $request,
        UserRepository $userRepository,
        int $code_acte,
        ManagerRegistry $registry
    ): Response
    {
        if(!$request->getSession()->has('user_session') or !$this->getUser()){

            return $this->redirectToRoute('app_login');
        } else {
            if ($this->isGranted('ROLE_ADMIN') or $this->isGranted('ROLE_MINEF'))
            {
                $user = $userRepository->find($this->getUser());
                $code_groupe = $user->getCodeGroupe()->getId();

                $reponse = array();


                $acte = $registry->getRepository(CatalogueServices::class)
                    ->find($code_acte);

                if ($acte){
                    $details = $registry->getRepository(DetailsCircuitActes::class)
                        ->findBy(['code_acte'=>$acte]);
                    foreach ($details as $detail){
                        $reponse[] = array(
                            'code'=>'SUCCESS',
                            'ordre'=>$detail->getOrdre(),
                            'service'=>$detail->getCodeService()->getSigle() ? $detail->getCodeService()->getSigle() : $detail->getCodeService()->getLibelleService(),
                            'id'=>$detail->getId()
                        );
                    }
                }
                return new JsonResponse(json_encode($reponse));
            } else {
                return $this->redirectToRoute('app_no_permission_user_active');
            }
        }
    }

    #[Route('snvlt/getDetail/CircuitActes/Del/{id_detail}', name: 'app_cv_actes_del_details')]
    public function delDetailsCircuit(
        Request $request,
        UserRepository $userRepository,
        int $id_detail,
        ManagerRegistry $registry
    ): Response
    {
        if(!$request->getSession()->has('user_session') or !$this->getUser()){

            return $this->redirectToRoute('app_login');
        } else {
            if ($this->isGranted('ROLE_ADMIN') or $this->isGranted('ROLE_MINEF'))
            {
                $user = $userRepository->find($this->getUser());
                $code_groupe = $user->getCodeGroupe()->getId();

                $reponse = array();


                $detail = $registry->getRepository(DetailsCircuitActes::class)
                    ->find($id_detail);

                if ($detail){
                    $registry->getManager()->remove($detail);
                    $registry->getManager()->flush();
                    $reponse[] = array(
                        'code'=>'SUCCESS'
                    );
                } else {
                    $reponse[] = array(
                        'code'=>'ERROR'
                    );
                }
                return new JsonResponse(json_encode($reponse));
            } else {
                return $this->redirectToRoute('app_no_permission_user_active');
            }
        }
    }
    #[Route('/snvlt/servicemenef/list/{code_direction}/{acte}', name: 'liste_services_minef')]
    public function direction_json(
            int $code_direction,
            int $acte,
            Request $request,
            Direction $direction = null,
            DirectionRepository $directionRepository,
            ServiceMinefRepository $serviceMinefRepository,
            DetailsCircuitActesRepository $circuitActes):Response{
        if(!$request->getSession()->has('user_session') or !$this->getUser()){
            return $this->redirectToRoute('app_login');
        } else {
            if ($this->isGranted('ROLE_MINEF') or  $this->isGranted('ROLE_ADMIN'))
            {
                if($code_direction){
                    $direction = $directionRepository->find($code_direction);

                    $liste_services= $serviceMinefRepository->findBy(['code_direction'=>$direction]);

                    $response = array();
                    foreach ($liste_services as $serviceMinef) {
                        // Recherche le service non entré dans un circuit de validation d'actes
                        $rech_serv =  $circuitActes->findBy(['code_service'=>$serviceMinef, 'code_acte'=>$acte]);
                        if (!$rech_serv){
                            $response[] = array(
                                'id' => $serviceMinef->getId(),
                                'libelle_service' => $serviceMinef->getSigle() ? $serviceMinef->getSigle() : $serviceMinef->getLibelleService()
                            );
                        }

                    }

                    return new JsonResponse(json_encode($response));
                }


            } else {
                return $this->redirectToRoute('app_no_permission_user_active');
            }
        }

    }
}
