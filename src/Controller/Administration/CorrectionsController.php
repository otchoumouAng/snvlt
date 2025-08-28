<?php

namespace App\Controller\Administration;

use App\Entity\Admin\LogSnvlt;
use App\Entity\DocStats\Entetes\Documentbrh;
use App\Entity\DocStats\Pages\Pagebrh;
use App\Entity\Groupe;
use App\Entity\References\Essence;
use App\Entity\References\SousPrefecture;
use App\Entity\References\Titre;
use App\Entity\References\TypeDocumentStatistique;
use App\Entity\References\TypeOperateur;
use App\Entity\References\Usine;
use App\Entity\References\ZoneHemispherique;
use App\Entity\ResetPasswordRequest;
use App\Entity\User;
use App\Repository\Administration\NotificationRepository;
use App\Repository\GroupeRepository;
use App\Repository\MenuPermissionRepository;
use App\Repository\MenuRepository;
use App\Repository\References\DirectionRepository;
use App\Repository\UserRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CorrectionsController extends AbstractController
{
    #[Route('snvlt/corrections', name: 'app_corrections')]
    public function index(
                          MenuRepository $menus,
                          MenuPermissionRepository $permissions,
                          Request $request,
                          UserRepository $userRepository,
                          ManagerRegistry $registry,
                          NotificationRepository $notification): Response
    {
        if(!$request->getSession()->has('user_session')){
            return $this->redirectToRoute('app_login');
        } else {
            if ($this->isGranted('ROLE_MINEF') or  $this->isGranted('ROLE_ADMIN'))
            {
                $user = $userRepository->find($this->getUser());
                $code_groupe = $user->getCodeGroupe()->getId();
                        return $this->render('administration/corrections/index.html.twig', [
                            'liste_menus'=>$menus->findOnlyParent(),
                            "all_menus"=>$menus->findAll(),
                            'menus'=>$permissions->findBy(['code_groupe_id'=>$code_groupe]),
                            'mes_notifs'=>$notification->findBy(['to_user'=>$user],[],5,0),
                            'groupe'=>$code_groupe,
                            'liste_parent'=>$permissions,
                            'liste_docs'=>$registry->getRepository(TypeDocumentStatistique::class)->findBy(['statut'=>'ACTIF'], ['abv'=>'ASC']),
                            'essences'=>$registry->getRepository(Essence::class)->findBy([], ['nom_vernaculaire'=>'ASC']),
                            'zones'=>$registry->getRepository(ZoneHemispherique::class)->findBy([], ['zone'=>'ASC']),
                            'usines'=>$registry->getRepository(Usine::class)->findBy([], ['raison_sociale_usine'=>'ASC']),
                            'villes'=>$registry->getRepository(SousPrefecture::class)->findBy([], ['nom_sousprefecture'=>'ASC']),
                            'users'=>$registry->getRepository(User::class)->findBy([],['email'=>'ASC','nom_utilisateur'=>'ASC', 'prenoms_utilisateur'=>'ASC']),
                            'titres'=>$registry->getRepository(Titre::class)->findAll(),
                            'type_op'=>$registry->getRepository(TypeOperateur::class)->findAll(),
                            'groupes'=>$registry->getRepository(Groupe::class)->findBy(['parent_groupe'=>0],['nom_groupe'=>'ASC']),
                        ]);
            } else {
                return $this->redirectToRoute('app_no_permission_user_active');
            }

        }
    }

    #[Route('snvlt/corrections/search/brh/{numero_brh}', name: 'app_corrections_search_brh')]
    public function search_brh(
        MenuRepository $menus,
        MenuPermissionRepository $permissions,
        Request $request,
        string $numero_brh,
        UserRepository $userRepository,
        ManagerRegistry $registry,
        NotificationRepository $notification): Response
    {
        if(!$request->getSession()->has('user_session')){
            return $this->redirectToRoute('app_login');
        } else {
            if ($this->isGranted('ROLE_MINEF') or  $this->isGranted('ROLE_ADMIN'))
            {
                $info_brh = array();
                //dd(str_replace("-", "/", $numero_brh));
                $brh = $registry->getRepository(Documentbrh::class)->findOneBy(['numero_docbrh'=>str_replace("-", "/", $numero_brh) ]);
                if ($brh){
                    $volume = 0;
                    foreach ($brh->getPagebrhs() as $pagebrh){
                        foreach ($pagebrh->getLignepagebrhs() as $lignepagebrh){
                            $volume = $volume + $lignepagebrh->getCubageLignepagebrh();
                        }
                    }
                    $info_brh[] = array(
                        'id'=>$brh->getId(),
                        'date_delivrance'=>$brh->getDelivreDocbrh()->format('d/m/Y'),
                        'foret'=>$brh->getCodeReprise()->getCodeAttribution()->getCodeForet()->getDenomination(),
                        'exploitant'=>$brh->getCodeReprise()->getCodeAttribution()->getCodeExploitant()->getSigle(),
                        'volume' => round($volume,3)
                    );
                }
                return  new  JsonResponse(json_encode($info_brh));
            } else {
                return $this->redirectToRoute('app_no_permission_user_active');
            }

        }
    }

    #[Route('snvlt/corrections/delete/brh/{id_brh}', name: 'app_corrections_delete_brh')]
    public function delete_brh(
        MenuRepository $menus,
        MenuPermissionRepository $permissions,
        Request $request,
        int $id_brh,
        UserRepository $userRepository,
        ManagerRegistry $registry,
        NotificationRepository $notification): Response
    {
        if(!$request->getSession()->has('user_session')){
            return $this->redirectToRoute('app_login');
        } else {
            if ($this->isGranted('ROLE_MINEF') or  $this->isGranted('ROLE_ADMIN'))
            {
                $info_brh = array();
                //dd(str_replace("-", "/", $numero_brh));
                $brh = $registry->getRepository(Documentbrh::class)->find($id_brh);
                if ($brh){
                    $pages = $brh->getPagebrhs();
                    foreach ($pages as $page){
                        $lignes = $page->getLignepagebrhs();
                        foreach ($lignes as $ligne){
                           $registry->getManager()->remove($ligne);
                        }
                        $registry->getManager()->remove($page);
                    }
                    $registry->getManager()->remove($brh);
                    $registry->getManager()->flush();
                    $info_brh[] = array(
                        'statut'=>'OK'
                    );
                } else {
                    $info_brh[] = array(
                        'statut'=>'DENY'
                    );
                }
                return  new  JsonResponse(json_encode($info_brh));
            } else {
                return $this->redirectToRoute('app_no_permission_user_active');
            }

        }
    }

    #[Route('snvlt/corrections/user/del/{id_user}', name: 'app_corrections_delete_user')]
    public function delete_user(
        MenuRepository $menus,
        MenuPermissionRepository $permissions,
        Request $request,
        int $id_user,
        UserRepository $userRepository,
        ManagerRegistry $registry,
        NotificationRepository $notification): Response
    {
        if(!$request->getSession()->has('user_session')){
            return $this->redirectToRoute('app_login');
        } else {
            if ($this->isGranted('ROLE_DPIF') or  $this->isGranted('ROLE_ADMIN'))
            {
                $info_user = array();
                //dd(str_replace("-", "/", $numero_brh));
                $utilisateur = $registry->getRepository(User::class)->find($id_user);
                if ($utilisateur){
                    //Recherche les logs du USER
                    $log_snvlt = $registry->getRepository(LogSnvlt::class)->findBy(['created_by'=>$utilisateur]);
                    foreach ($log_snvlt as $lg){
                        $registry->getManager()->remove($lg);
                    }

                    // Recherche les ResetPassword
                    $resetP = $registry->getRepository(ResetPasswordRequest::class)->findBy(['user'=>$utilisateur]);
                    foreach ($resetP as $rp){
                        $registry->getManager()->remove($rp);
                    }

                    // supression d'l'utilisateur
                    $registry->getManager()->remove($utilisateur);

                    $registry->getManager()->flush();

                    $info_user[] = array(
                        'statut'=>'OK'
                    );
                } else {
                    $info_user[] = array(
                        'statut'=>'DENY'
                    );
                }
                return  new  JsonResponse(json_encode($info_user));
            } else {
                return $this->redirectToRoute('app_no_permission_user_active');
            }

        }
    }

    #[Route('snvlt/corrections/search/users', name: 'app_corrections_search_users')]
    public function app_corrections_search_users(
        MenuRepository $menus,
        MenuPermissionRepository $permissions,
        Request $request,
        UserRepository $userRepository,
        ManagerRegistry $registry,
        NotificationRepository $notification): Response
    {
        if(!$request->getSession()->has('user_session')){
            return $this->redirectToRoute('app_login');
        } else {
            if ($this->isGranted('ROLE_DPIF') or  $this->isGranted('ROLE_ADMIN'))
            {
                $info_users = array();
                //dd(str_replace("-", "/", $numero_brh));
                $utilisateurs = $registry->getRepository(User::class)->findBy([], ['email'=>'ASC']);
                foreach ($utilisateurs as $utilisateur){
                    $info_users[] = array(
                        'id_user'=>$utilisateur->getId(),
                        'email'=>$utilisateur->getEmail()
                    );
                }

                return  new  JsonResponse(json_encode($info_users));
            } else {
                return $this->redirectToRoute('app_no_permission_user_active');
            }

        }
    }
}
