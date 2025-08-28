<?php

namespace App\Controller\References;


use App\Entity\Transformation\Pdt2Transfo;
use App\Entity\User;
use App\Form\Transformation\Pdt2TransfoType;
use App\Repository\Administration\NotificationRepository;
use App\Repository\GroupeRepository;
use App\Repository\MenuPermissionRepository;
use App\Repository\MenuRepository;
use App\Repository\References\ServiceMinefRepository;
use App\Repository\Transformation\Pdt2TransfoRepository;
use App\Repository\UserRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class Pdt2TransfoController extends AbstractController
{

    public function __construct(private TranslatorInterface $translator)
    {

    }

    #[Route('snvlt/ref/pdt_usine_transfo/2', name: 'ref_pdt2transfo')]
    public function listing(Pdt2TransfoRepository $pdts,
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
                return $this->render('references/pdt2transfo/index.html.twig',
                    [

                        'liste_menus'=>$menus->findOnlyParent(),
                        "all_menus"=>$menus->findAll(),
                        'menus'=>$permissions->findBy(['code_groupe_id'=>$code_groupe]),
                        'mes_notifs'=>$notification->findBy(['to_user'=>$user],[],5,0),
                        'groupe'=>$code_groupe,
                        'pdts'=>$pdts->findAll(),
                        'liste_parent'=>$permissions
                    ]);
            } else {
                return $this->redirectToRoute('app_no_permission_user_active');
            }

        }
    }


    #[Route('/edit/pdt2transfo/{id_pdt?0}', name: 'pdt2transfo.edit')]
    public function editPdt2Transfo(
        Pdt2Transfo $pdt = null,
        ManagerRegistry $doctrine,
        Request $request,
        Pdt2TransfoRepository $produits,
        MenuPermissionRepository $permissions,
        MenuRepository $menus,
        ServiceMinefRepository $serviceMinefRepository,
        GroupeRepository $groupeRepository,
        int $id_pdt,
        UserRepository $userRepository,
        User $user = null,
        NotificationRepository $notification): Response
    {
        if(!$request->getSession()->has('user_session')){
            return $this->redirectToRoute('app_login');
        } else {
            if ($this->isGranted('ROLE_MINEF') or  $this->isGranted('ROLE_ADMIN') or  $this->isGranted('ROLE_ADMINISTRATIF'))
            {
                $user = $userRepository->find($this->getUser());
                $code_groupe = $user->getCodeGroupe()->getId();



        $titre = $this->translator->trans("Edit Transformation product");
        $pdt = $produits->find($id_pdt);

        $new = false;
        if(!$pdt){
            $new = true;
            $pdt = new Pdt2Transfo();
            $titre = $this->translator->trans("Add Transformation product");
        }


            $form = $this->createForm(Pdt2TransfoType::class, $pdt);

            $form->handleRequest($request);

            if ( $form->isSubmitted() && $form->isValid() ){


                $manager = $doctrine->getManager();
                $manager->persist($pdt);
                $manager->flush();

                $this->addFlash('success',$this->translator->trans("The product for 2nd or 3rd transformation has been edited and updated successfully"));
                return $this->redirectToRoute("ref_pdt2transfo");
            } else {
                return $this->render('references/pdt2transfo/add-pdt2transfo.html.twig',[
                    'form' =>$form->createView(),
                    'titre'=>$titre,
                    'liste_menus'=>$menus->findOnlyParent(),
                    "all_menus"=>$menus->findAll(),
                    'menus'=>$permissions->findBy(['code_groupe_id'=>$code_groupe]),
                    'groupe'=>$code_groupe,
                    'mes_notifs'=>$notification->findBy(['to_user'=>$user],[],5,0),
                    'produits'=>$produits->findAll(),
                    'liste_parent'=>$permissions
                ]);
            }
            } else {
                return $this->redirectToRoute('app_no_permission_user_active');
            }
            }

    }


}
