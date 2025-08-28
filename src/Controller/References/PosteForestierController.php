<?php

namespace App\Controller\References;


use App\Entity\References\PosteForestier;
use App\Form\References\PosteForestierType;
use App\Repository\Administration\NotificationRepository;
use App\Repository\GroupeRepository;
use App\Repository\MenuPermissionRepository;
use App\Repository\MenuRepository;
use App\Repository\References\CantonnementRepository;
use App\Repository\References\PosteForestierRepository;
use App\Repository\UserRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class PosteForestierController extends AbstractController
{
    public function __construct(private TranslatorInterface $translator)
    {
    }
    #[Route('snvlt/ref/poste_f', name: 'ref_poste_forestiers')]
    public function listing(PosteForestierRepository $poste_forestiers,
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

                return $this->render('references/poste_forestier/index.html.twig', [
                    'liste_poste_forestiers' => $poste_forestiers->findAll(),
                    'liste_menus'=>$menus->findOnlyParent(),
                    "all_menus"=>$menus->findAll(),
                    'mes_notifs'=>$notificationRepository->findBy(['to_user'=>$user, 'lu'=>false],[],5,0),
                    'menus'=>$permissions->findBy(['code_groupe_id'=>$code_groupe]),
                    'groupe'=>$code_groupe,
                    'liste_parent'=>$permissions
                ]);
            } else {
                return $this->redirectToRoute('app_no_permission_user_active');
            }
        }
    }


    #[Route('/edit/ref/poste_f/{id_poste_forestier?0}', name: 'poste_forestier.edit')]
    public function editPosteForestier(
        PosteForestier $poste_forestier = null,
        ManagerRegistry $doctrine,
        Request $request,
        PosteForestierRepository $poste_forestiers,
        MenuPermissionRepository $permissions,
        UserRepository $userRepository,
        MenuRepository $menus,
        GroupeRepository $groupeRepository,
        int $id_poste_forestier,
        NotificationRepository $notificationRepository): Response
    {
        if(!$request->getSession()->has('user_session')){

            return $this->redirectToRoute('app_login');
        } else {
            if ($this->isGranted('ROLE_MINEF') or  $this->isGranted('ROLE_ADMIN'))
            {
                $user = $userRepository->find($this->getUser());
                $code_groupe = $user->getCodeGroupe()->getId();

                $titre = $this->translator->trans("Edit forester checkpoint");
                $poste_forestier = $poste_forestiers->find($id_poste_forestier);

                $new = false;

                if(!$poste_forestier){
                    $new = true;
                    $poste_forestier = new PosteForestier();
                    $titre = $this->translator->trans("Add forester checkpoint");
                }




            $form = $this->createForm(PosteForestierType::class, $poste_forestier);

            $form->handleRequest($request);

            if ( $form->isSubmitted() && $form->isValid() ){

                $manager = $doctrine->getManager();
                $manager->persist($poste_forestier);
                $manager->flush();

                $this->addFlash('success',$this->translator->trans("forester checkpoint has been edited succesfully"));
                return $this->redirectToRoute("ref_poste_forestiers");
            } else {
                return $this->render('references/poste_forestier/add-poste_forestier.html.twig',[
                    'form' =>$form->createView(),
                    'titre'=>$titre,
                    'liste_poste_forestiers' => $poste_forestiers->findAll(),
                    'mes_notifs'=>$notificationRepository->findBy(['to_user'=>$user, 'lu'=>false],[],5,0),
                    'liste_menus'=>$menus->findOnlyParent(),
                    "all_menus"=>$menus->findAll(),
                    'menus'=>$permissions->findBy(['code_groupe_id'=>$code_groupe]),
                    'groupe'=>$code_groupe,
                    'liste_parent'=>$permissions
                ]);
            }

                } else {
                return $this->redirectToRoute('app_no_permission_user_active');
            }
        }

    }

    #[Route('snvlt/ref/pf/data_json', name: 'json_pf')]
    public function json_pf(PosteForestierRepository $pfs,
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

                $liste_pfs= $pfs->findBy([], ['denomination'=>'ASC']);
                foreach ( $liste_pfs as $pf){

                    $response[] =  array(
                        'id'=>$pf->getId(),
                        'sigle'=>$pf->getDenomination(),
                        'rs'=>$pf->getDenomination(),
                    );

                }


                return  new  JsonResponse(json_encode($response));

            } else {
                return $this->redirectToRoute('app_no_permission_user_active');
            }
        }
    }
}
