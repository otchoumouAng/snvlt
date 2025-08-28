<?php

namespace App\Controller\References;


use App\Entity\References\Cantonnement;
use App\Entity\User;
use App\Form\References\CantonnementType;
use App\Repository\Administration\NotificationRepository;
use App\Repository\GroupeRepository;
use App\Repository\MenuPermissionRepository;
use App\Repository\MenuRepository;
use App\Repository\References\CantonnementRepository;
use App\Repository\References\UsineRepository;
use App\Repository\UserRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class CantonnementController extends AbstractController
{

    public function __construct(private TranslatorInterface $translator)
    {

    }

    #[Route('/snvlt/ref/cef', name: 'ref_cantonnements')]
    public function listing(
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
        if(!$request->getSession()->has('user_session')){

            return $this->redirectToRoute('app_login');
            } else {
                if ($this->isGranted('ROLE_ADMIN') or $this->isGranted('ROLE_MINEF'))
                {
                    $user = $userRepository->find($this->getUser());
                    $code_groupe = $user->getCodeGroupe()->getId();
                    $titre = $this->translator->trans("Edit Cantonment");

                return $this->render('references/cantonnement/index.html.twig', [
                    'liste_cantonnements' => $cantonnements->findAll(),
                    'liste_menus'=>$menus->findOnlyParent(),
                    "all_menus"=>$menus->findAll(),
                    'menus'=>$permissions->findBy(['code_groupe_id'=>$code_groupe]),
                    'groupe'=>$code_groupe,
                    'titre'=>$titre,
                    'mes_notifs'=>$notification->findBy(['to_user'=>$user, 'lu'=>false],[],5,0),
                    'liste_parent'=>$permissions
                ]);
            } else {
            return $this->redirectToRoute('app_no_permission_user_active');
        }
        }
    }





    #[Route('/snvlt/edit/cef/{id_cantonnement?0}', name: 'cantonnement.edit')]
    public function editCantonnement(
        Cantonnement $cantonnement = null,
        ManagerRegistry $doctrine,
        Request $request,
        CantonnementRepository $cantonnements,
        MenuPermissionRepository $permissions,
        MenuRepository $menus,
        GroupeRepository $groupeRepository,
        int $id_cantonnement,
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

        $date_creation = new \DateTimeImmutable();
        $code_groupe = $groupeRepository->find(1);
        $titre =  $this->translator->trans("Add Cantonment");
        $cantonnement = $cantonnements->find($id_cantonnement);
        //dd($cantonnement);
        if(!$cantonnement){
            $new = true;
            $cantonnement = new Cantonnement();
            $titre = $this->translator->trans("Add Cantonment");

            $cantonnement->setCreatedAt($date_creation);
            $cantonnement->setCreatedBy($this->getUser());
        }

        $session = $request->getSession();
        if (!$session->has("user_session")){
            $this->addFlash('error', $this->translator->trans('You must log in first to access SNVLT'));
            return $this->redirectToRoute('app_login');
        } else {

            $new = false;
            if(!$cantonnement){
                $new = true;
                $cantonnement = new Cantonnement();
            }
            $form = $this->createForm(CantonnementType::class, $cantonnement);

            $form->handleRequest($request);

            if ( $form->isSubmitted() && $form->isValid() ){


                $cantonnement->setUpdatedAt($date_creation);
                $cantonnement->setUpdatedBy($this->getUser());

                $manager = $doctrine->getManager();
                $manager->persist($cantonnement);
                $manager->flush();

                $this->addFlash('success',$this->translator->trans("The forest cantonment has just been successfully updated"));
                return $this->redirectToRoute("ref_cantonnements");
            } else {
                return $this->render('references/cantonnement/add-cantonnement.html.twig',[
                    'form' =>$form->createView(),
                    'titre'=>$titre,
                    'liste_cantonnements' => $cantonnements->findAll(),
                    'liste_menus'=>$menus->findOnlyParent(),
                    "all_menus"=>$menus->findAll(),
                    'menus'=>$permissions->findBy(['code_groupe_id'=>$code_groupe]),
                    'groupe'=>$code_groupe,
                    'mes_notifs'=>$notification->findBy(['to_user'=>$user, 'lu'=>false],[],5,0),
                    'liste_parent'=>$permissions
                ]);
            }
        }
    } else {
                return $this->redirectToRoute('app_no_permission_user_active');
            }

        }
    }

    #[Route('snvlt/ref/cantonnement/data_json', name: 'json_cantonnement')]
    public function json_cantonnement(CantonnementRepository $cantonnements,
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

                //------------------------- Filtre les CP par type Opérateur ------------------------------------- //

                $liste_cantonnements= $cantonnements->findBy([], ['nom_cantonnement'=>'ASC']);
                foreach ( $liste_cantonnements as $cantonnement){

                    $response[] =  array(
                        'id'=>$cantonnement->getId(),
                        'sigle'=>$cantonnement->getNomCantonnement(),
                        'rs'=>$cantonnement->getNomCantonnement(),
                    );

                }


                return  new  JsonResponse(json_encode($response));

            } else {
                return $this->redirectToRoute('app_no_permission_user_active');
            }
        }
    }
}
