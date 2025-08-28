<?php

namespace App\Controller\Autorisation;

use App\Entity\Admin\Option;
use App\Entity\Autorisation\AttributionPv;
use App\Entity\References\DocumentOperateur;
use App\Entity\References\Exploitant;
use App\Entity\References\TypeOperateur;
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
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class AttributionPvController extends AbstractController
{

    public function __construct(private TranslatorInterface $translator)
    {}

    #[Route('/snvlt/admin/att/pv', name: 'app_attributionpv')]
    public function index(AttributionPvRepository $attributionpvRepository,
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
            if ($this->isGranted('ROLE_MINEF') or  $this->isGranted('ROLE_ADMIN'))
            {
                $user = $userRepository->find($this->getUser());
                $code_groupe = $user->getCodeGroupe()->getId();
                $titre = $this->translator->trans("Add an attribution PV");


                return $this->render('autorisation/attributionpv/index.html.twig', [
                    'attributionpvs' => $attributionpvRepository->findAll(),
                    'liste_menus'=>$menus->findOnlyParent(),
                    "all_menus"=>$menus->findAll(),
                    'mes_notifs'=>$notification->findBy(['to_user'=>$user, 'lu'=>false],[],5,0),
                    'menus'=>$permissions->findBy(['code_groupe_id'=>$code_groupe]),
                    'groupe'=>$code_groupe,
                    'titre'=>$titre,
                    'liste_parent'=>$permissions
                ]);
            } else {
                return  $this->redirectToRoute('app_no_permission_user_active');
            }
        }
    }

    #[Route('snvlt/ref/edit/attribution/pv/{id_atrtributionpv?0}', name: 'attribution_pv.edit')]
    public function editAttributionPv(
        AttributionPv $attributionpv = null,
        ManagerRegistry $doctrine,
        Request $request,
        AttributionPvRepository $attributionpvs,
        MenuPermissionRepository $permissions,
        MenuRepository $menus,
        GroupeRepository $groupeRepository,
        UserRepository $userRepository,
        User $user = null,
        int $id_atrtributionpv,
        TypeAutorisationRepository $type_autorisations,
        NotificationRepository $notification): Response
    {
        if(!$request->getSession()->has('user_session')){
            return $this->redirectToRoute('app_login');
        } else {
            if ($this->isGranted('ROLE_MINEF') or  $this->isGranted('ROLE_ADMINISTRATIF') or $this->isGranted('ROLE_ADMIN'))
            {
                $user = $userRepository->find($this->getUser());
                $code_groupe = $user->getCodeGroupe()->getId();

                $date_creation = new \DateTimeImmutable();

                $titre = $this->translator->trans("Edit an attribution PV");
                $attributionpv = $attributionpvs->find($id_atrtributionpv);
                //dd($ddef);
                if(!$attributionpv){
                    $new = true;
                    $attributionpv = new AttributionPv();
                    $titre = $this->translator->trans("Add Attribution PV");

                    $attributionpv->setCreatedAt($date_creation);
                    $attributionpv->setCreatedBy($this->getUser());
                }



                $form = $this->createForm(AttributionPvType::class, $attributionpv);

                $form->handleRequest($request);

                if ( $form->isSubmitted() && $form->isValid() ){


                    $attributionpv->setCreatedAt($date_creation);
                    $attributionpv->setCreatedBy($this->getUser());
                    $attributionpv->setStatut(true);
                    $attributionpv->setReprise(false);

                    $option = $doctrine->getRepository(Option::class)->findBy(['name'=>'autorisations_validation'])[0];

                    $manager = $doctrine->getManager();
                    $manager->persist($attributionpv);

                    //$attributionpv->getAutorisationPvs()->get

                    //Mise à jour du champs attrubue dans la table Foret

                    $foret = $attributionpv->getCodeParcelle()->setAttribue(true);
                    $manager->persist($foret);

                    $manager->flush();

                    $this->addFlash('success',$this->translator->trans("The attributionpv has been updated successfully"));



                    return $this->redirectToRoute("app_attributionpv");
                } else {
                    return $this->render('autorisation/attributionpv/add-attributionpv.html.twig',[
                        'form' =>$form->createView(),
                        'titre'=>$titre,
                        'liste_ddefs' => $attributionpvs->findAll(),
                        'liste_menus'=>$menus->findOnlyParent(),
                        "all_menus"=>$menus->findAll(),
                        'menus'=>$permissions->findBy(['code_groupe_id'=>$code_groupe]),
                        'groupe'=>$code_groupe,
                        'mes_notifs'=>$notification->findBy(['to_user'=>$user],[],5,0),
                        'liste_parent'=>$permissions,
                        'type_autorisations'=>$type_autorisations->find(1)
                    ]);
                }

            } else {
                return $this->redirectToRoute('app_no_permission_user_active');
            }
        }

    }


}
