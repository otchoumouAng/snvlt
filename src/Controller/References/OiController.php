<?php

namespace App\Controller\References;


use App\Entity\References\Oi;
use App\Entity\User;
use App\Form\References\OiType;
use App\Repository\Administration\NotificationRepository;
use App\Repository\DemandeOperateurRepository;
use App\Repository\GroupeRepository;
use App\Repository\MenuPermissionRepository;
use App\Repository\MenuRepository;
use App\Repository\References\OiRepository;
use App\Repository\UserRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class OiController extends AbstractController
{

    public function __construct(private TranslatorInterface $translator)
    {

    }

    #[Route('snvlt/ref/oi/list', name: 'ref_ois')]
    public function listing(OiRepository $ois,
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
            if ($this->isGranted('ROLE_MINEF') or  $this->isGranted('ROLE_ADMIN'))
            {
                $user = $userRepository->find($this->getUser());
                $code_groupe = $user->getCodeGroupe()->getId();
                return $this->render('references/oi/index.html.twig',
                    [

                        'liste_menus'=>$menus->findOnlyParent(),
                        "all_menus"=>$menus->findAll(),
                        'menus'=>$permissions->findBy(['code_groupe_id'=>$code_groupe]),
                        'mes_notifs'=>$notification->findBy(['to_user'=>$user],[],5,0),
                        'groupe'=>$code_groupe,
                        'ois'=>$ois->findAll(),
                        'liste_parent'=>$permissions
                    ]);
            } else {
                return $this->redirectToRoute('app_no_permission_user_active');
            }

        }
    }


    #[Route('/edit/oi/{id_oi?0}', name: 'oi.edit')]
    public function editOi(
        Oi $oi = null,
        ManagerRegistry $doctrine,
        Request $request,
        OiRepository $ois,
        MenuPermissionRepository $permissions,
        MenuRepository $menus,
        GroupeRepository $groupeRepository,
        int $id_oi,
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



        $titre = $this->translator->trans("Modifier un Observateur Indépendant");
        $oi = $ois->find($id_oi);

        if(!$oi){
            $new = true;
            $oi = new Oi();
            $titre = $this->translator->trans("Ajouter un Observateur Indépendant");
            $oi->setCreatedAt(new \DateTimeImmutable());
            $oi->setCreatedBy($user);
        }

            $new = false;
            if(!$oi){
                $new = true;
                $oi = new Oi();

            }
            $form = $this->createForm(OiType::class, $oi);

            $form->handleRequest($request);

            if ( $form->isSubmitted() && $form->isValid() ){

                $oi->setUpdatedAt(new \DateTimeImmutable());
                $oi->setUpdatedBy($user);

                $manager = $doctrine->getManager();
                $manager->persist($oi);
                $manager->flush();

                $this->addFlash('success',$this->translator->trans("L'OI a été mis à jour avec succès"));
                return $this->redirectToRoute("ref_ois");
            } else {
                return $this->render('references/oi/add-oi.html.twig',[
                    'form' =>$form->createView(),
                    'titre'=>$titre,
                    'ref_ois' => $ois->findAll(),
                    'liste_menus'=>$menus->findOnlyParent(),
                    "all_menus"=>$menus->findAll(),
                    'menus'=>$permissions->findBy(['code_groupe_id'=>$code_groupe]),
                    'groupe'=>$code_groupe,
                    'mes_notifs'=>$notification->findBy(['to_user'=>$user],[],5,0),
                    'ois'=>$ois->findAll(),
                    'liste_parent'=>$permissions
                ]);
            }
            } else {
                return $this->redirectToRoute('app_no_permission_user_active');
            }
            }

    }
    #[Route('snvlt/ois/list', name: 'liste_ois')]
    public function oi_json(Request $request, OiRepository $oiRepository):Response{
        if(!$request->getSession()->has('user_session')){
            return $this->redirectToRoute('app_login');
        } else {
            if ($this->isGranted('ROLE_MINEF') or  $this->isGranted('ROLE_ADMIN'))
                {

                    $liste_ois = $oiRepository->findWithResponsable();

                    $response = array();
                    foreach ($liste_ois as $oi) {
                        $response[] = array(
                            'id' => $oi->getId(),
                            'denomination' => $oi->getSigle()
                        );
                    }

                    return new JsonResponse(json_encode($response));

                } else {
                return $this->redirectToRoute('app_no_permission_user_active');
            }
        }
    }
    #[Route('snvlt/ref/oi/data_json', name: 'json_ois')]
    public function json_ois(OiRepository $repository,
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

                $liste_ois= $repository->findBy([], ['sigle'=>'ASC']);
                foreach ( $liste_ois as $oi){

                    $response[] =  array(
                        'id'=>$oi->getId(),
                        'sigle'=>$oi->getSigle(),
                        'rs'=>$oi->getSigle()
                    );

                }


                return  new  JsonResponse(json_encode($response));

            } else {
                return $this->redirectToRoute('app_no_permission_user_active');
            }
        }
    }
}
