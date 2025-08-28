<?php

namespace App\Controller\References;


use App\Entity\References\Direction;
use App\Entity\References\ServiceMinef;
use App\Entity\User;
use App\Form\References\ServiceMinefType;
use App\Repository\Administration\NotificationRepository;
use App\Repository\GroupeRepository;
use App\Repository\MenuPermissionRepository;
use App\Repository\MenuRepository;
use App\Repository\References\DirectionRepository;
use App\Repository\References\ServiceMinefRepository;
use App\Repository\UserRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class ServiceMinefController extends AbstractController
{
    public function __construct(private TranslatorInterface $translator)
    {
    }
    #[Route('/snvlt/ref/servminef', name: 'ref_serviceminef')]
    public function listing(ServiceMinefRepository $serviceminefs,
                            MenuRepository $menus,
                            MenuPermissionRepository $permissions,
                            GroupeRepository $groupeRepository,
                            ManagerRegistry $doctrine,
                            Request $request,
                            UserRepository $userRepository,
                            User $user = null,
                            NotificationRepository $notification): Response
    {
        //dd($request);
        if(!$request->getSession()->has('user_session')){
            return $this->redirectToRoute('app_login');
        } else {
            if ($this->isGranted('ROLE_ADMIN'))
            {
                $user = $userRepository->find($this->getUser());
                $code_groupe = $user->getCodeGroupe()->getId();
                return $this->render('references/serviceminef/index.html.twig',
                    [

                        'liste_menus'=>$menus->findOnlyParent(),
                        "all_menus"=>$menus->findAll(),
                        'menus'=>$permissions->findBy(['code_groupe_id'=>$code_groupe]),
                        'mes_notifs'=>$notification->findBy(['to_user'=>$user],[],5,0),
                        'groupe'=>$code_groupe,
                        'serviceminef'=>$serviceminefs->findAll(),
                        'liste_parent'=>$permissions
                    ]);
            } else {
                return $this->redirectToRoute('app_no_permission_user_active');
            }

        }
    }


    #[Route('/snvlt/ref/edit/serviceminef/{id_serviceminef?0}', name: 'serviceminef.edit')]
    public function editServiceMinef(
        ServiceMinef $serviceminef = null,
        ManagerRegistry $doctrine,
        Request $request,
        ServiceMinefRepository $serviceminefs,
        MenuPermissionRepository $permissions,
        MenuRepository $menus,
        GroupeRepository $groupeRepository,
        int $id_serviceminef,
        UserRepository $userRepository,
        User $user = null,
        NotificationRepository $notification): Response
    {
        if(!$request->getSession()->has('user_session')){
            return $this->redirectToRoute('app_login');
        } else {
            if ($this->isGranted('ROLE_MINEF') or  $this->isGranted('ROLE_ADMIN'))
            {
                $user = $userRepository->find($this->getUser());
                $code_groupe = $user->getCodeGroupe()->getId();



                $titre = $this->translator->trans("Edit Ministry service");
                $serviceminef = $serviceminefs->find($id_serviceminef);

                if(!$serviceminef){
                    $new = true;
                    $serviceminef = new ServiceMinef();
                    $titre = $this->translator->trans("Add Ministry service");
                }

                $new = false;
                if(!$serviceminef){
                    $new = true;
                    $serviceminef = new ServiceMinef();
                }
                $form = $this->createForm(ServiceMinefType::class, $serviceminef);

                $form->handleRequest($request);

                if ( $form->isSubmitted() && $form->isValid() ){

                    $manager = $doctrine->getManager();
                    $manager->persist($serviceminef);
                    $manager->flush();

                    $this->addFlash('success',$this->translator->trans("Ministry service has been edited successfully"));
                    return $this->redirectToRoute("ref_serviceminef");
                } else {
                    return $this->render('references/serviceminef/add-serviceminef.html.twig',[
                        'form' =>$form->createView(),
                        'titre'=>$titre,
                        'ref_serviceminefs' => $serviceminefs->findAll(),
                        'liste_menus'=>$menus->findOnlyParent(),
                        "all_menus"=>$menus->findAll(),
                        'menus'=>$permissions->findBy(['code_groupe_id'=>$code_groupe]),
                        'groupe'=>$code_groupe,
                        'mes_notifs'=>$notification->findBy(['to_user'=>$user],[],5,0),
                        'liste_parent'=>$permissions
                    ]);
                }
            } else {
                return $this->redirectToRoute('app_no_permission_user_active');
            }
        }

    }


        #[Route('/snvlt/servicemenif/list/{code_direction}', name: 'liste_services')]
        public function direction_json(int $code_direction,   Request $request, Direction $direction = null, DirectionRepository $directionRepository, ServiceMinefRepository $serviceMinefRepository):Response{
            if(!$request->getSession()->has('user_session')){
                return $this->redirectToRoute('app_login');
            } else {
                if ($this->isGranted('ROLE_MINEF') or  $this->isGranted('ROLE_ADMIN'))
                {
                    if($code_direction){
                        $direction = $directionRepository->find($code_direction);

                        $liste_services= $serviceMinefRepository->findBy(['code_direction'=>$direction]);

                        $response = array();
                        foreach ($liste_services as $serviceMinef) {
                            $response[] = array(
                                'id' => $serviceMinef->getId(),
                                'libelle_service' => $serviceMinef->getLibelleService()
                            );
                        }

                        return new JsonResponse(json_encode($response));
                    }


                } else {
                    return $this->redirectToRoute('app_no_permission_user_active');
                }
            }

    }

    #[Route('/snvlt/serviceminef/directions/{id_direction}', name: 'services_json')]
    public function services_json(
        int $id_direction,
        Request $request,
        ManagerRegistry $registry,
        User $user = null,
    ):Response{


        $response = array();
                    $direction = $registry->getRepository(Direction::class)->find($id_direction);
                    if($direction){
                    $liste_services= $registry->getRepository(ServiceMinef::class)->findBy(['code_direction'=>$direction]);


                    foreach ($liste_services as $serviceMinef) {
                        $response[] = array(
                            'id' => $serviceMinef->getId(),
                            'libelle_service' => $serviceMinef->getLibelleService()
                        );
                    }


                }
        return new JsonResponse(json_encode($response));

    }
    #[Route('snvlt/ref/services-minef/data_json', name: 'json_services_minef')]
    public function json_services_minef(ServiceMinefRepository $services_minef,
                            UserRepository $userRepository,
                            Request $request
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

                //------------------------- Filtre les CP par type Opérateur ------------------------------------- //

                $liste_servs= $services_minef->findBy([], ['code_direction'=>'ASC']);
                foreach ( $liste_servs as $serviceMinef){

                    $response[] =  array(
                        'id'=>$serviceMinef->getId(),
                        'sigle'=>$serviceMinef->getCodeDirection()->getSigle()." - ".  $serviceMinef->getSigle(),
                        'rs'=>$serviceMinef->getCodeDirection()->getSigle()." - ".  $serviceMinef->getSigle()
                    );

                }

                return  new  JsonResponse(json_encode($response));

            } else {
                return $this->redirectToRoute('app_no_permission_user_active');
            }
        }
    }
}