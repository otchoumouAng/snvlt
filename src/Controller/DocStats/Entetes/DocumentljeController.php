<?php

namespace App\Controller\DocStats\Entetes;

use App\Controller\Services\AdministrationService;
use App\Controller\Services\Utils;
use App\Entity\DocStats\Entetes\Documentbrh;
use App\Entity\DocStats\Entetes\Documentlje;
use App\Entity\DocStats\Pages\Pagebrh;
use App\Entity\DocStats\Pages\Pagelje;
use App\Entity\DocStats\Saisie\Lignepagelje;
use App\Entity\References\Cantonnement;
use App\Entity\References\Essence;
use App\Entity\References\Usine;
use App\Entity\References\ZoneHemispherique;
use App\Entity\Transformation\Billon;
use App\Entity\User;
use App\Repository\Administration\NotificationRepository;
use App\Repository\DocStats\Entetes\DocumentcpRepository;
use App\Repository\DocStats\Entetes\DocumentljeRepository;
use App\Repository\DocStats\Pages\PagebrhRepository;
use App\Repository\DocStats\Pages\PageljeRepository;
use App\Repository\GroupeRepository;
use App\Repository\MenuPermissionRepository;
use App\Repository\MenuRepository;
use App\Repository\UserRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DocumentljeController extends AbstractController
{
    public function __construct(private ManagerRegistry $m, private Utils $utils, private AdministrationService $administrationService)
    {
    }

    #[Route('/doc/stats/entetes/doclje', name: 'app_op_doclje')]
    public function index(
        Request $request,
        MenuRepository $menus,
        MenuPermissionRepository $permissions,
        GroupeRepository $groupeRepository,
        UserRepository $userRepository,
        User $user = null,
        NotificationRepository $notification,
        DocumentljeRepository $docs_lje,
        ManagerRegistry $registry
    ): Response
    {
        if(!$request->getSession()->has('user_session')){
            return $this->redirectToRoute('app_login');
        } else {
            if ($this->isGranted('ROLE_INDUSTRIEL') or $this->isGranted('ROLE_ADMINISTRATIF') or $this->isGranted('ROLE_ADMIN') or $this->isGranted('ROLE_MINEF'))
            {
                $user = $userRepository->find($this->getUser());
                $code_groupe = $user->getCodeGroupe()->getId();

                return $this->render('doc_stats/entetes/documentlje/index.html.twig', [
                    'liste_menus'=>$menus->findOnlyParent(),
                    "all_menus"=>$menus->findAll(),
                    'menus'=>$permissions->findBy(['code_groupe_id'=>$code_groupe]),
                    'mes_notifs'=>$notification->findBy(['to_user'=>$user, 'lu'=>false],[],5,0),
                    'groupe'=>$code_groupe,
                    'liste_parent'=>$permissions
                ]);
            } else {
                return $this->redirectToRoute('app_no_permission_user_active');
            }

        }

    }

    #[Route('/snvlt/doclje/lje/pages/{id_lje}', name: 'affichage_lje_json')]
    public function affiche_lje(
        Request $request,
        int $id_lje,
        MenuRepository $menus,
        MenuPermissionRepository $permissions,
        GroupeRepository $groupeRepository,
        UserRepository $userRepository,
        User $user = null,
        NotificationRepository $notification,
        DocumentljeRepository $docs_lje,
        ManagerRegistry $registry
    ): Response
    {
        if(!$request->getSession()->has('user_session')){
            return $this->redirectToRoute('app_login');
        } else {
            if ($this->isGranted('ROLE_INDUSTRIEL') or $this->isGranted('ROLE_ADMINISTRATIF') or $this->isGranted('ROLE_ADMIN') or $this->isGranted('ROLE_MINEF'))
            {
                $user = $userRepository->find($this->getUser());
                $code_groupe = $user->getCodeGroupe()->getId();

                $numerodoc = "";

                $documentlje = $registry->getRepository(Documentlje::class)->find($id_lje);
                if($documentlje){$numerodoc = $documentlje->getNumeroDoclje();}

                return $this->render('doc_stats/entetes/documentlje/affiche_lje.html.twig', [
                    'document_name'=>$documentlje,
                    'liste_menus'=>$menus->findOnlyParent(),
                    "all_menus"=>$menus->findAll(),
                    'menus'=>$permissions->findBy(['code_groupe_id'=>$code_groupe]),
                    'mes_notifs'=>$notification->findBy(['to_user'=>$user, 'lu'=>false],[],5,0),
                    'groupe'=>$code_groupe,
                    'liste_parent'=>$permissions,
                    'essences'=>$registry->getRepository(Essence::class)->findBy([], ['nom_vernaculaire'=>'ASC']),
                    'zones'=>$registry->getRepository(ZoneHemispherique::class)->findBy([], ['zone'=>'ASC']),
                    'usines'=>$registry->getRepository(Usine::class)->findBy([], ['raison_sociale_usine'=>'ASC'])

                ]);
            } else {
                return $this->redirectToRoute('app_no_permission_user_active');
            }

        }
    }

    #[Route('/snvlt/doclje/op', name: 'app_docs_lje_json')]
    public function my_doc_lje(
        Request $request,
        MenuRepository $menus,
        MenuPermissionRepository $permissions,
        GroupeRepository $groupeRepository,
        UserRepository $userRepository,
        User $user = null,
        NotificationRepository $notification,
        DocumentljeRepository $docs_lje,
        ManagerRegistry $registry
    ): Response
    {
        if(!$request->getSession()->has('user_session')){
            return $this->redirectToRoute('app_login');
        } else {
            if ($this->isGranted('ROLE_INDUSTRIEL') or $this->isGranted('ROLE_ADMINISTRATIF') or $this->isGranted('ROLE_ADMIN') or $this->isGranted('ROLE_MINEF'))
            {
                $user = $userRepository->find($this->getUser());
                $code_groupe = $user->getCodeGroupe()->getId();

                $mes_docs_lje = array();
                //------------------------- Filtre les lje par type Opérateur ------------------------------------- //

                //------------------------- Filtre les lje ADMIN ------------------------------------- //
                if($user->getCodeGroupe()->getId() == 1 or $user->getCodeOperateur()->getId() == 1){
                    $documents_lje = $registry->getRepository(Documentlje::class)->findBy(['transmission'=>true],['created_at'=>'DESC']);
                    foreach ($documents_lje as $document_lje){
                        $exploitant = "-";
                        if ($document_lje->getCodeUsine()->getCodeExploitant()) {
                            $exploitant = $document_lje->getCodeUsine()->getCodeExploitant()->getNumeroExploitant() . '-'. $document_lje->getCodeUsine()->getCodeExploitant()->getMarteauExploitant(). '-'. $document_lje->getCodeUsine()->getCodeExploitant()->getRaisonSocialeExploitant();
                        }
                        if ($document_lje->getCodeUsine()->getCodeCantonnement()){
                            $canton = $document_lje->getCodeUsine()->getCodeCantonnement()->getNomCantonnement();
                            $d = $document_lje->getCodeUsine()->getCodeCantonnement()->getCodeDr()->getDenomination();
                        }else {
                            $canton = "-";
                            $d = "-";
                        }
                        $mes_docs_lje[] = array(
                            'id_doc_lje'=>$document_lje->getId(),
                            'numero_doclje'=>$document_lje->getNumeroDoclje(),
                            'usine'=>$document_lje->getCodeUsine()->getRaisonSocialeUsine(),
                            'code_usine'=>$document_lje->getCodeUsine()->getNumeroUsine(),
                            'cantonnement'=>$canton,
                            'dr'=>$d,
                            'date_delivrance'=>$document_lje->getDelivreDoclje()->format("d m Y"),
                            'etat'=>$document_lje->isEtat(),
                            'exploitant'=>$exploitant,
                            'volume_lje'=>round($this->getVolumelje($document_lje),3)
                        );
                    }
                    //------------------------- Filtre les lje DR ------------------------------------- //
                } elseif ($user->getCodeDr()){
                    //dd($user->getCodeDr());
                    $cantonnements = $registry->getRepository(Cantonnement::class)->findBy(['code_dr'=>$user->getCodeDr()]);
                    foreach ($cantonnements as $cantonnement){

                        $usines = $registry->getRepository(Usine::class)->findBy(['code_cantonnement'=>$cantonnement]);
                        foreach ($usines as $usine){
                            $documents_lje = $registry->getRepository(Documentlje::class)->findBy(['code_usine'=>$usine, 'transmission'=>true],['created_at'=>'DESC']);
                            foreach ($documents_lje as $document_lje){
                                $exploitant = "-";
                                if ($document_lje->getCodeUsine()->getCodeExploitant()) {
                                    $exploitant = $document_lje->getCodeUsine()->getCodeExploitant()->getNumeroExploitant() . '-'. $document_lje->getCodeUsine()->getCodeExploitant()->getMarteauExploitant(). '-'. $document_lje->getCodeUsine()->getCodeExploitant()->getRaisonSocialeExploitant();
                                }
                                if ($document_lje->getCodeUsine()->getCodeCantonnement()){
                                    $canton = $document_lje->getCodeUsine()->getCodeCantonnement()->getNomCantonnement();
                                    $d = $document_lje->getCodeUsine()->getCodeCantonnement()->getCodeDr()->getDenomination();
                                }else {
                                    $canton = "-";
                                    $d = "-";
                                }
                                $mes_docs_lje[] = array(
                                    'id_doc_lje'=>$document_lje->getId(),
                                    'numero_doclje'=>$document_lje->getNumeroDoclje(),
                                    'usine'=>$document_lje->getCodeUsine()->getRaisonSocialeUsine(),
                                    'code_usine'=>$document_lje->getCodeUsine()->getNumeroUsine(),
                                    'cantonnement'=>$canton,
                                    'dr'=>$d,
                                    'date_delivrance'=>$document_lje->getDelivreDoclje()->format("d m Y"),
                                    'etat'=>$document_lje->isEtat(),
                                    'exploitant'=>$exploitant,
                                    'volume_lje'=>round($this->getVolumelje($document_lje),3)
                                );
                            }

                        }
                    }
                    //------------------------- Filtre les lje DD ------------------------------------- //
                } elseif ($user->getCodeDdef()){
                    //dd($user->getCodeDr());
                    $cantonnements = $registry->getRepository(Cantonnement::class)->findBy(['code_ddef'=>$user->getCodeDdef()]);
                    foreach ($cantonnements as $cantonnement){

                        $usines = $registry->getRepository(Usine::class)->findBy(['code_cantonnement'=>$cantonnement]);
                        foreach ($usines as $usine){
                            $documents_lje = $registry->getRepository(Documentlje::class)->findBy(['code_usine'=>$usine, 'transmission'=>true],['created_at'=>'DESC']);
                            foreach ($documents_lje as $document_lje){
                                $exploitant = "-";
                                if ($document_lje->getCodeUsine()->getCodeExploitant()) {
                                    $exploitant = $document_lje->getCodeUsine()->getCodeExploitant()->getNumeroExploitant() . '-'. $document_lje->getCodeUsine()->getCodeExploitant()->getMarteauExploitant(). '-'. $document_lje->getCodeUsine()->getCodeExploitant()->getRaisonSocialeExploitant();
                                }
                                if ($document_lje->getCodeUsine()->getCodeCantonnement()){
                                    $canton = $document_lje->getCodeUsine()->getCodeCantonnement()->getNomCantonnement();
                                    $d = $document_lje->getCodeUsine()->getCodeCantonnement()->getCodeDr()->getDenomination();
                                }else {
                                    $canton = "-";
                                    $d = "-";
                                }
                                $mes_docs_lje[] = array(
                                    'id_doc_lje'=>$document_lje->getId(),
                                    'numero_doclje'=>$document_lje->getNumeroDoclje(),
                                    'usine'=>$document_lje->getCodeUsine()->getRaisonSocialeUsine(),
                                    'code_usine'=>$document_lje->getCodeUsine()->getNumeroUsine(),
                                    'cantonnement'=>$canton,
                                    'dr'=>$d,
                                    'date_delivrance'=>$document_lje->getDelivreDoclje()->format("d m Y"),
                                    'etat'=>$document_lje->isEtat(),
                                    'exploitant'=>$exploitant,
                                    'volume_lje'=>round($this->getVolumelje($document_lje),3)
                                );
                            }

                        }
                    }

                    //------------------------- Filtre les lje CANTONNEMENT ------------------------------------- //
                } elseif ($user->getCodeCantonnement()){

                    $usines = $registry->getRepository(Usine::class)->findBy(['code_cantonnement'=>$user->getCodeCantonnement()]);
                    foreach ($usines as $usine){
                        $documents_lje = $registry->getRepository(Documentlje::class)->findBy(['code_usine'=>$usine, 'transmission'=>true],['created_at'=>'DESC']);
                        foreach ($documents_lje as $document_lje){
                            $exploitant = "-";
                            if ($document_lje->getCodeUsine()->getCodeExploitant()) {
                                $exploitant = $document_lje->getCodeUsine()->getCodeExploitant()->getNumeroExploitant() . '-'. $document_lje->getCodeUsine()->getCodeExploitant()->getMarteauExploitant(). '-'. $document_lje->getCodeUsine()->getCodeExploitant()->getRaisonSocialeExploitant();
                            }
                            if ($document_lje->getCodeUsine()->getCodeCantonnement()){
                                $canton = $document_lje->getCodeUsine()->getCodeCantonnement()->getNomCantonnement();
                                $d = $document_lje->getCodeUsine()->getCodeCantonnement()->getCodeDr()->getDenomination();
                            }else {
                                $canton = "-";
                                $d = "-";
                            }
                            $mes_docs_lje[] = array(
                                'id_doc_lje'=>$document_lje->getId(),
                                'numero_doclje'=>$document_lje->getNumeroDoclje(),
                                'usine'=>$document_lje->getCodeUsine()->getRaisonSocialeUsine(),
                                'code_usine'=>$document_lje->getCodeUsine()->getNumeroUsine(),
                                'cantonnement'=>$canton,
                                'dr'=>$d,
                                'date_delivrance'=>$document_lje->getDelivreDoclje()->format("d m Y"),
                                'etat'=>$document_lje->isEtat(),
                                'exploitant'=>$exploitant,
                                'volume_lje'=>round($this->getVolumelje($document_lje),3)
                            );
                        }

                    }


                    //------------------------- Filtre les lje INDUSTRIELS------------------------------------- //
                }  elseif ($user->getCodeindustriel()){
                    $documents_lje = $registry->getRepository(Documentlje::class)->findBy(['code_usine'=>$user->getCodeindustriel(), 'signature_cef'=>true, 'signature_dr'=>true],['created_at'=>'DESC']);
                    foreach ($documents_lje as $document_lje){
                        $exploitant = "-";
                        if ($document_lje->getCodeUsine()->getCodeExploitant()) {
                            $exploitant = $document_lje->getCodeUsine()->getCodeExploitant()->getNumeroExploitant() . '-'. $document_lje->getCodeUsine()->getCodeExploitant()->getMarteauExploitant(). '-'. $document_lje->getCodeUsine()->getCodeExploitant()->getRaisonSocialeExploitant();
                        }
                        if ($document_lje->getCodeUsine()->getCodeCantonnement()){
                            $canton = $document_lje->getCodeUsine()->getCodeCantonnement()->getNomCantonnement();
                            $d = $document_lje->getCodeUsine()->getCodeCantonnement()->getCodeDr()->getDenomination();
                        }else {
                            $canton = "-";
                            $d = "-";
                        }
                        $mes_docs_lje[] = array(
                            'id_doc_lje'=>$document_lje->getId(),
                            'numero_doclje'=>$document_lje->getNumeroDoclje(),
                            'usine'=>$document_lje->getCodeUsine()->getRaisonSocialeUsine(),
                            'code_usine'=>$document_lje->getCodeUsine()->getNumeroUsine(),
                            'cantonnement'=>$canton,
                            'dr'=>$d,
                            'date_delivrance'=>$document_lje->getDelivreDoclje()->format("d m Y"),
                            'etat'=>$document_lje->isEtat(),
                            'exploitant'=>$exploitant,
                            'volume_lje'=>round($this->getVolumelje($document_lje),3)
                        );
                    }
                }

                return new JsonResponse(json_encode($mes_docs_lje));
            }else {
                return $this->redirectToRoute('app_no_permission_user_active');
            }

        }

    }

    #[Route('/snvlt/doclje/op/pages_lje/{id_lje}', name: 'affichage_pages_lje_json')]
    public function affiche_pages_lje(
        Request $request,
        int $id_lje,
        MenuRepository $menus,
        MenuPermissionRepository $permissions,
        GroupeRepository $groupeRepository,
        UserRepository $userRepository,
        User $user = null,
        NotificationRepository $notification,
        DocumentljeRepository $docs_lje,
        ManagerRegistry $registry
    ): Response
    {
        if(!$request->getSession()->has('user_session')){
            return $this->redirectToRoute('app_login');
        } else {
            if ($this->isGranted('ROLE_INDUSTRIEL') or $this->isGranted('ROLE_ADMINISTRATIF') or $this->isGranted('ROLE_ADMIN') or $this->isGranted('ROLE_MINEF'))
            {
                $doc_lje = $docs_lje->find($id_lje);
                if($doc_lje){
                    $pages_lje = $registry->getRepository(Pagelje::class)->findBy(['code_doclje'=>$doc_lje], ['id'=>'ASC']);
                    $my_lje_pages = array();

                    foreach ($pages_lje as $page){
                        $my_lje_pages[] = array(
                            'id_page'=>$page->getId(),
                            'numero_page'=>$page->getNumeroPagelje()
                        );
                    }
                    return  new JsonResponse(json_encode($my_lje_pages));
                }else{
                    $my_lje_page[] = array(
                        'code'=>'NO_DATA_FOUND'
                    )
                    ;
                    return  new JsonResponse($my_lje_page);
                }
            } else {
                return $this->redirectToRoute('app_no_permission_user_active');
            }

        }
    }

    #[Route('/snvlt/doclje/op/pages_lje/data/{id_page}', name: 'affichage_page_data_lje_json')]
    public function affiche_page_courante(
        Request $request,
        int $id_page,
        MenuRepository $menus,
        MenuPermissionRepository $permissions,
        GroupeRepository $groupeRepository,
        UserRepository $userRepository,
        User $user = null,
        NotificationRepository $notification,
        PageljeRepository $pages_lje,
        ManagerRegistry $registry
    ): Response
    {
        if(!$request->getSession()->has('user_session')){
            return $this->redirectToRoute('app_login');
        } else {
            if ($this->isGranted('ROLE_INDUSTRIEL') or $this->isGranted('ROLE_ADMINISTRATIF') or $this->isGranted('ROLE_ADMIN') or $this->isGranted('ROLE_MINEF'))
            {
                $page_lje = $pages_lje->find($id_page);
                if($page_lje){
                    $my_lje_page = array();
                    $my_lje_page[] = array(
                        'id_page'=>$page_lje->getId(),
                        'numero_page'=>$page_lje->getNumeroPagelje(),
                        'annee'=>$page_lje->getAnnee(),
                        'mois'=>$page_lje->getMois(),
                        'volume_page'=>$this->getVolumeljeByPage($page_lje)
                    );

                    return  new JsonResponse(json_encode($my_lje_page));
                } else {
                    $my_lje_page[] = array(
                        'code'=>'NO_DATA_FOUND'
                    )
                    ;
                    return  new JsonResponse($my_lje_page);
                }
            } else {
                return $this->redirectToRoute('app_no_permission_user_active');
            }

        }
    }

    #[Route('/snvlt/doclje/op/lignes_lje/data/{id_page}', name: 'affichage_ligne_lje_data_lje_json')]
    public function affiche_lignes_lje_courante(
        Request $request,
        int $id_page,
        MenuRepository $menus,
        MenuPermissionRepository $permissions,
        GroupeRepository $groupeRepository,
        UserRepository $userRepository,
        User $user = null,
        NotificationRepository $notification,
        PageljeRepository $pages_lje,
        ManagerRegistry $registry
    ): Response
    {
        if(!$request->getSession()->has('user_session')){
            return $this->redirectToRoute('app_login');
        } else {
            if ($this->isGranted('ROLE_INDUSTRIEL') or $this->isGranted('ROLE_ADMINISTRATIF') or $this->isGranted('ROLE_ADMIN') or $this->isGranted('ROLE_MINEF'))
            {
                $page_lje = $pages_lje->find($id_page);
                if($page_lje){
                    $lignes_lje = $registry->getRepository(Lignepagelje::class)->findBy(['code_pagelje'=>$page_lje]);
                    $my_lje_page = array();
                    foreach ($lignes_lje as $lignelje){
                        $feuillet = $registry->getRepository(Pagebrh::class)->find($lignelje->getCodeFeuillet());


                        $my_lje_page[] = array(
                            'id_ligne'=>$lignelje->getId(),
                            'numero_ligne'=>$lignelje->getNumeroArbre(),
                            'lettre'=>$lignelje->getLettre(),
                            'essence'=>$lignelje->getEssence()->getNomVernaculaire(),
                            'essence_code'=>$lignelje->getEssence()->getNumeroEssence(),
                            'x_lje'=>$lignelje->getX(),
                            'y_lje'=>$lignelje->getY(),
                            'zh_lje'=>$lignelje->getZh()->getZone(),
                            'lng_lje'=>$lignelje->getLng(),
                            'dm_lje'=>$lignelje->getDm(),
                            'cubage_lje'=>$lignelje->getVolume(),
                            'date_dechargement'=>$lignelje->getDateDechargement()->format('d m Y'),
                            'feuillet'=>$lignelje->getCodeFeuillet(),
                            'created_at'=>$lignelje->getCreatedAt()->format('d/m/Y h:i:s'),
                            'created_by'=>$lignelje->getCreatedBy(),
                            'exploitant'=>$feuillet->getCodeDocbrh()->getCodeReprise()->getCodeAttribution()->getCodeExploitant()->getSigle(),
                            'marteau'=>$feuillet->getCodeDocbrh()->getCodeReprise()->getCodeAttribution()->getCodeExploitant()->getMarteauExploitant(),
                            'code'=>$feuillet->getCodeDocbrh()->getCodeReprise()->getCodeAttribution()->getCodeExploitant()->getNumeroExploitant(),
                            'foret'=>$feuillet->getCodeDocbrh()->getCodeReprise()->getCodeAttribution()->getCodeForet()->getDenomination(),
                            'page_brh'=>$feuillet->getNumeroPagebrh(),
                        );
                    }

                    rsort($my_lje_page);
                    return  new JsonResponse(json_encode($my_lje_page));
                } else {
                    $my_lje_page[] = array(
                        'code'=>'NO_DATA_FOUND'
                    )
                    ;
                    return  new JsonResponse($my_lje_page);
                }
            } else {
                return $this->redirectToRoute('app_no_permission_user_active');
            }

        }
    }


    function getVolumelje(Documentlje $documentlje):float
    {
        $volumelje = 0;
        if($documentlje){
            $pagelje =$this->m->getRepository(Pagelje::class)->findBy(['code_doclje'=>$documentlje]);
            foreach ($pagelje as $page){
                $lignepages = $this->m->getRepository(Lignepagelje::class)->findBy(['code_pagelje'=>$page]);
                foreach ($lignepages as $ligne){
                    $volumelje = $volumelje +  $ligne->getVolume();
                }
            }
            return $volumelje;
        } else {
            return $volumelje;
        }
    }

    function getVolumeljeByPage(Pagelje $pagelje):float
    {
        $volumelje = 0;
        $lignepages = $this->m->getRepository(Lignepagelje::class)->findBy(['code_pagelje'=>$pagelje]);

        foreach ($lignepages as $ligne){
            $volumelje = $volumelje +  $ligne->getVolume();
        }

        return $volumelje;

    }

    #[Route('/snvlt/doclje/op/pages_lje/data/edit/{id_page}/{data}', name: 'edit_page_lje_json')]
    public function edit_page_lje(
        Request $request,
        MenuRepository $menus,
        MenuPermissionRepository $permissions,
        GroupeRepository $groupeRepository,
        UserRepository $userRepository,
        User $user = null,
        string $data,
        int $id_page,
        NotificationRepository $notification,
        PageljeRepository $pages_lje,
        ManagerRegistry $registry
    ): Response
    {
        if(!$request->getSession()->has('user_session')){
            return $this->redirectToRoute('app_login');
        } else {
            if ($this->isGranted('ROLE_INDUSTRIEL') or $this->isGranted('ROLE_DPIF') or $this->isGranted('ROLE_DPIF_SAISIE') or $this->isGranted('ROLE_ADMIN'))
            {
                $user = $userRepository->find($this->getUser());
                $code_groupe = $user->getCodeGroupe()->getId();

                //$page_lje = $pages_lje->find($id_page);
                if($data){
                    $pagelje = $registry->getRepository(Pagelje::class)->find($id_page);

                    if ($pagelje){
                        //Decoder le JSON BRH
                        $arraydata = json_decode($data);

                        //dd($arraydata->numero_lignepagelje);
                        $date_jour = new \DateTime();
                        $pagelje->setAnnee((int) $arraydata->annee);
                        $pagelje->setMois((int) $arraydata->mois);


                        $pagelje->setUpdatedAt(new \DateTime());
                        $pagelje->setUpdatedBy($user);

                        $registry->getManager()->persist($pagelje);
                        $registry->getManager()->flush();

                        return  new JsonResponse([
                            'code'=>Pagelje::PAGE_BRH_EDITED_SUCCESSFULLY,
                            'html'=>''
                        ]);
                    }

                }
            } else {
                return $this->redirectToRoute('app_no_permission_user_active');
            }

        }
    }


    #[Route('/snvlt/search_data/lje/{id_page}', name: 'search_source_data')]
    public function search_source_data(
        Request $request,
        int $id_page,
        MenuRepository $menus,
        MenuPermissionRepository $permissions,
        GroupeRepository $groupeRepository,
        UserRepository $userRepository,
        User $user = null,
        NotificationRepository $notification,
        PagebrhRepository $pages_brh,
        ManagerRegistry $registry
    ): Response
    {
        if(!$request->getSession()->has('user_session')){
            return $this->redirectToRoute('app_login');
        } else {
            if ($this->isGranted('ROLE_INDUSTRIEL') or $this->isGranted('ROLE_ADMINISTRATIF') or $this->isGranted('ROLE_MINEF') or $this->isGranted('ROLE_DPIF') or $this->isGranted('ROLE_DPIF_SAISIE') or $this->isGranted('ROLE_ADMIN'))
            {
                $user = $userRepository->find($this->getUser());
                $code_groupe = $user->getCodeGroupe()->getId();

                $sources= $pages_brh->findBy(['id'=> $id_page, 'confirmation_usine'=>true, 'parc_usine_brh'=>$user->getCodeindustriel(), 'entre_lje'=>false]);

                $sources_forets = array();

                foreach ($sources as $foret){
                    $sources_forets[] = array(
                        'id_source'=>$foret->getCodeDocbrh()->getCodeReprise()->getCodeAttribution()->getCodeForet()->getId(),
                        'foret'=>$foret->getCodeDocbrh()->getCodeReprise()->getCodeAttribution()->getCodeForet()->getDenomination(),
                        'id_page'=>$foret->getId()
                    );
                }

                return  new JsonResponse(json_encode($sources_forets));

            } else {
                return $this->redirectToRoute('app_no_permission_user_active');
            }

        }
    }

    #[Route('/snvlt/lje/save/{id_page_lje}/{id_page_brh}/{date_dechargement}', name: 'lje_save')]
    public function lje_save(
        Request $request,
        int $id_page_lje,
        int $id_page_brh,
        string $date_dechargement,
        MenuRepository $menus,
        MenuPermissionRepository $permissions,
        GroupeRepository $groupeRepository,
        UserRepository $userRepository,
        User $user = null,
        NotificationRepository $notification,
        PagebrhRepository $pages_brh,
        ManagerRegistry $registry
    ): Response
    {
        if(!$request->getSession()->has('user_session')){
            return $this->redirectToRoute('app_login');
        } else {
            if ($this->isGranted('ROLE_INDUSTRIEL') or $this->isGranted('ROLE_DPIF') or $this->isGranted('ROLE_DPIF_SAISIE') or $this->isGranted('ROLE_ADMIN'))
            {
                $user = $userRepository->find($this->getUser());
                $code_groupe = $user->getCodeGroupe()->getId();

                $pagebrh = $registry->getRepository(Pagebrh::class)->find($id_page_brh);
                $pagelhje= $registry->getRepository(Pagelje::class)->find($id_page_lje);

                //dd($pagelhje);

                if ($pagebrh && $pagelhje && $pagebrh->isConfirmationUsine() && !$pagebrh->isEntreLje()){



                    foreach ($pagebrh->getLignepagebrhs() as $lignebrh){

                        $ligne_lje = new Lignepagelje();

                        //Affectation des valeurs à LignLje
                        $ligne_lje->setNumeroArbre($lignebrh->getNumeroLignepagebrh());
                        $ligne_lje->setEssence($lignebrh->getNomEssencebrh());
                        $ligne_lje->setZh($lignebrh->getZhLignepagebrh());
                        $ligne_lje->setX($lignebrh->getXLignepagebrh());
                        $ligne_lje->setY($lignebrh->getYLignepagebrh());
                        $ligne_lje->setLettre($lignebrh->getLettreLignepagebrh());
                        $ligne_lje->setLng($lignebrh->getLongeurLignepagebrh());
                        $ligne_lje->setDm($lignebrh->getDiametreLignepagebrh());
                        $ligne_lje->setVolume($lignebrh->getCubageLignepagebrh());
                        $ligne_lje->setDateDechargement( \DateTime::createFromFormat('Y-m-d', $date_dechargement) );
                        $ligne_lje->setCreatedAt(new \DateTime());
                        $ligne_lje->setCreatedBy($user);
                        $ligne_lje->setCodeTypeDoc($lignebrh->getCodePagebrh()->getCodeDocbrh()->getTypeDocument());
                        $ligne_lje->setCodeFeuillet($pagebrh->getId());
                        $ligne_lje->setCodePagelje($pagelhje);
                        $ligne_lje->setTransforme(false);
                        $ligne_lje->setTronconnee(false);

                        $registry->getManager()->persist($ligne_lje);

                    }

                    $registry->getManager()->flush();

                    $pagebrh->setEntreLje(true);
                    $registry->getManager()->persist($pagebrh);
                    $registry->getManager()->flush();

                }


                return  new JsonResponse(json_encode(Lignepagelje::ADD_LJE_SUCCESS));

            } else {
                return $this->redirectToRoute('app_no_permission_user_active');
            }

        }
    }

    #[Route('/doc/stats/lje/accept', name: 'chargements_non_accepts')]
    public function chargements_non_accepts(
        Request $request,
        MenuRepository $menus,
        MenuPermissionRepository $permissions,
        GroupeRepository $groupeRepository,
        UserRepository $userRepository,
        User $user = null,
        NotificationRepository $notification,
        DocumentljeRepository $docs_lje,
        ManagerRegistry $registry
    ): Response
    {
        if(!$request->getSession()->has('user_session')){
            return $this->redirectToRoute('app_login');
        } else {
            if ($this->isGranted('ROLE_INDUSTRIEL') or $this->isGranted('ROLE_DPIF') or $this->isGranted('ROLE_DPIF_SAISIE') or $this->isGranted('ROLE_ADMIN'))
            {
                $user = $userRepository->find($this->getUser());
                $code_groupe = $user->getCodeGroupe()->getId();

                $chargement_non_acceptes = $registry->getRepository(Pagebrh::class)->findBy(['confirmation_usine'=>false, 'parc_usine_brh'=>$user->getCodeindustriel()]);
                return $this->render('doc_stats/entetes/documentlje/chargements_non_acceptes.html.twig', [
                    'liste_menus'=>$menus->findOnlyParent(),
                    'chargements'=>$chargement_non_acceptes,
                    "all_menus"=>$menus->findAll(),
                    'menus'=>$permissions->findBy(['code_groupe_id'=>$code_groupe]),
                    'mes_notifs'=>$notification->findBy(['to_user'=>$user, 'lu'=>false],[],5,0),
                    'groupe'=>$code_groupe,
                    'liste_parent'=>$permissions
                ]);
            } else {
                return $this->redirectToRoute('app_no_permission_user_active');
            }

        }

    }
    #[Route('/doc/stats/lje/acceptall', name: 'accept_loadings')]
    public function accept_loadings(
        Request $request,
        MenuRepository $menus,
        MenuPermissionRepository $permissions,
        GroupeRepository $groupeRepository,
        UserRepository $userRepository,
        User $user = null,
        NotificationRepository $notification,
        DocumentljeRepository $docs_lje,
        ManagerRegistry $registry
    ): Response
    {
        if(!$request->getSession()->has('user_session')){
            return $this->redirectToRoute('app_login');
        } else {
            if ($this->isGranted('ROLE_INDUSTRIEL') or $this->isGranted('ROLE_DPIF') or $this->isGranted('ROLE_DPIF_SAISIE') or $this->isGranted('ROLE_ADMIN'))
            {
                $user = $userRepository->find($this->getUser());
                $code_groupe = $user->getCodeGroupe()->getId();

                $chargement_non_acceptes = $registry->getRepository(Pagebrh::class)->findBy(['confirmation_usine'=>false, 'parc_usine_brh'=>$user->getCodeindustriel(), 'fini'=>true, 'entre_lje'=>false]);
                $description = "";
                foreach($chargement_non_acceptes as $chargement){
                    $chargement->setConfirmationUsine(true);
                    $chargement->setUpdatedBy($user);
                    $chargement->setUpdatedAt(new \DateTime());
                    $description = $description .$chargement->getNumeroPagebrh() .  " ";
                    $registry->getManager()->persist($chargement);
                    $registry->getManager()->flush();
                }

                //Log
                $this->administrationService->save_action(
                    $user,
                    "PAGE_BRH",
                    "ACCEPTATION_CHARGEMENT_BLOC",
                    new \DateTimeImmutable(),
                    "L'utilisateur " . $user . " vient d'accepter en bloc les chargements suivants : " . $description
                );

                return $this->render('doc_stats/entetes/documentlje/chargements_non_acceptes.html.twig', [
                    'liste_menus'=>$menus->findOnlyParent(),
                    'chargements'=>$chargement_non_acceptes,
                    "all_menus"=>$menus->findAll(),
                    'menus'=>$permissions->findBy(['code_groupe_id'=>$code_groupe]),
                    'mes_notifs'=>$notification->findBy(['to_user'=>$user, 'lu'=>false],[],5,0),
                    'groupe'=>$code_groupe,
                    'liste_parent'=>$permissions
                ]);
            } else {
                return $this->redirectToRoute('app_no_permission_user_active');
            }

        }

    }

    #[Route('snvlt/doc/stats/lje/liste_chrg_acc/{id_pagelje}', name: 'liste_chargements_a_accepter')]
    public function liste_chargements_a_accepter(
        Request $request,
        MenuRepository $menus,
        MenuPermissionRepository $permissions,
        GroupeRepository $groupeRepository,
        UserRepository $userRepository,
        User $user = null,
        int $id_pagelje = null,
        NotificationRepository $notification,
        DocumentljeRepository $docs_lje,
        ManagerRegistry $registry
    ): Response
    {
        if(!$request->getSession()->has('user_session')){
            return $this->redirectToRoute('app_login');
        } else {
            if ($this->isGranted('ROLE_INDUSTRIEL') or $this->isGranted('ROLE_DPIF') or $this->isGranted('ROLE_DPIF_SAISIE') or $this->isGranted('ROLE_ADMIN'))
            {
                $user = $userRepository->find($this->getUser());
                $code_groupe = $user->getCodeGroupe()->getId();

                //Recherche de l'usine
                $usine = $registry->getRepository(Pagelje::class)->find($id_pagelje)->getCodeDoclje()->getCodeUsine();
                //dd($usine->getId());
                $chargement_non_acceptes = $registry->getRepository(Pagebrh::class)->findBy(['confirmation_usine'=>true, 'parc_usine_brh'=>$usine, 'fini'=>true, 'entre_lje'=>false], ['date_chargementbrh'=>'ASC']);

                $liste_chargements = array();

                foreach($chargement_non_acceptes as $chargement){
                    $liste_chargements[] = array(
                        'numero'=>(int) $chargement->getNumeroPagebrh(),
                        'reference'=>"[" . $chargement->getCodeDocbrh()->getNumeroDocbrh() . "] - [". $chargement->getCodeDocbrh()->getCodeReprise()->getCodeAttribution()->getCodeForet()->getNumeroForet() . "]",
                        'id_page'=>$chargement->getId()

                    );
                }
                sort($liste_chargements, SORT_NUMERIC );
                return new JsonResponse(json_encode($liste_chargements));
            } else {
                return $this->redirectToRoute('app_no_permission_user_active');
            }

        }

    }


    #[Route('snvlt/doc/stats/lje/cmb_chrg_acc/{id_pagelje}/{numero_feuillet}', name: 'cmb_chargements_a_accepter')]
    public function cmb_chargements_a_accepter(
        Request $request,
        MenuRepository $menus,
        MenuPermissionRepository $permissions,
        GroupeRepository $groupeRepository,
        UserRepository $userRepository,
        User $user = null,
        int $id_pagelje = null,
        string $numero_feuillet = null,
        NotificationRepository $notification,
        DocumentljeRepository $docs_lje,
        ManagerRegistry $registry
    ): Response
    {
        if(!$request->getSession()->has('user_session')){
            return $this->redirectToRoute('app_login');
        } else {
            if ($this->isGranted('ROLE_INDUSTRIEL') or $this->isGranted('ROLE_DPIF') or $this->isGranted('ROLE_DPIF_SAISIE') or $this->isGranted('ROLE_ADMIN'))
            {
                $user = $userRepository->find($this->getUser());
                $code_groupe = $user->getCodeGroupe()->getId();

                //Recherche de l'usine
                $usine = $registry->getRepository(Pagelje::class)->find($id_pagelje)->getCodeDoclje()->getCodeUsine();
                //dd($usine->getId());

                $liste_chargements = array();

                $feuillets = $registry->getRepository(Pagebrh::class)->findBy([
                    'confirmation_usine'=>true,
                    'parc_usine_brh'=>$usine,
                    'fini'=>true,
                    'entre_lje'=>false,
                    'numero_pagebrh'=>$numero_feuillet
                ],
                    [
                        'date_chargementbrh'=>'ASC'
                    ]);
                //dd($feuillets);
                foreach($feuillets as $feuillet ){
                    $liste_chargements[] = array(
                        'reference'=>"[" . $feuillet->getCodeDocbrh()->getNumeroDocbrh() . "] - [". $feuillet->getCodeDocbrh()->getCodeReprise()->getCodeAttribution()->getCodeForet()->getNumeroForet() . "]",
                        'id_page'=>$feuillet->getId(),
                        'id_brh'=>$feuillet->getCodeDocbrh()->getId(),
                    );
                }




                sort($liste_chargements, SORT_NUMERIC );
                return new JsonResponse(json_encode($liste_chargements));
            } else {
                return $this->redirectToRoute('app_no_permission_user_active');
            }

        }

    }


    #[Route('snvlt/doc/stats/lje/liste_doc_source_acc/{id_pagelje}', name: 'liste_doc_source_a_accepter')]
    public function liste_doc_source_a_accepter(
        Request $request,
        MenuRepository $menus,
        MenuPermissionRepository $permissions,
        GroupeRepository $groupeRepository,
        UserRepository $userRepository,
        User $user = null,
        int $id_pagelje = null,
        NotificationRepository $notification,
        DocumentljeRepository $docs_lje,
        ManagerRegistry $registry
    ): Response
    {
        if(!$request->getSession()->has('user_session')){
            return $this->redirectToRoute('app_login');
        } else {
            if ($this->isGranted('ROLE_INDUSTRIEL') or $this->isGranted('ROLE_DPIF') or $this->isGranted('ROLE_DPIF_SAISIE') or $this->isGranted('ROLE_ADMIN'))
            {
                $user = $userRepository->find($this->getUser());
                $code_groupe = $user->getCodeGroupe()->getId();

                //Recherche de l'usine
                $usine = $registry->getRepository(Pagelje::class)->find($id_pagelje)->getCodeDoclje()->getCodeUsine();
                //dd($usine->getId());
                $chargement_non_acceptes = $registry->getRepository(Pagebrh::class)->findBy(['confirmation_usine'=>true, 'parc_usine_brh'=>$usine, 'fini'=>true, 'entre_lje'=>false], ['date_chargementbrh'=>'ASC']);

                $liste_chargements = array();

                foreach($chargement_non_acceptes as $chargement){
                    $liste_chargements[] = array(
                        'reference'=>"[" . $chargement->getCodeDocbrh()->getNumeroDocbrh() . "] - [". $chargement->getCodeDocbrh()->getCodeReprise()->getCodeAttribution()->getCodeForet()->getNumeroForet() . "]",
                        'id_doc'=>$chargement->getCodeDocbrh()->getId()
                    );
                }

                sort($liste_chargements);
                return new JsonResponse(json_encode($liste_chargements));
            } else {
                return $this->redirectToRoute('app_no_permission_user_active');
            }

        }

    }

    #[Route('/snvlt/admin/parc_usine', name: 'app_parc_usine')]
    public function app_parc_usine(
        Request $request,
        MenuRepository $menus,
        MenuPermissionRepository $permissions,
        GroupeRepository $groupeRepository,
        UserRepository $userRepository,
        User $user = null,
        NotificationRepository $notification,
        DocumentcpRepository $docs_cp,
        ManagerRegistry $registry
    ): Response
    {


        if(!$request->getSession()->has('user_session')){
            return $this->redirectToRoute('app_login');
        } else {
            if ($this->isGranted('ROLE_INDUSTRIEL') or $this->isGranted('ROLE_ADMINISTRATIF') or $this->isGranted('ROLE_MINEF') or $this->isGranted('ROLE_DPIF') or $this->isGranted('ROLE_DPIF_SAISIE') or $this->isGranted('ROLE_ADMIN'))
            {
                $user = $userRepository->find($this->getUser());
                $code_groupe = $user->getCodeGroupe()->getId();


                return $this->render('doc_stats/entetes/documentlje/parc_usine.html.twig', [
                    'liste_menus'=>$menus->findOnlyParent(),
                    "all_menus"=>$menus->findAll(),
                    'menus'=>$permissions->findBy(['code_groupe_id'=>$code_groupe]),
                    'mes_notifs'=>$notification->findBy(['to_user'=>$user, 'lu'=>false],[],5,0),
                    'groupe'=>$code_groupe,
                    'liste_parent'=>$permissions
                ]);
            } else {
                return $this->redirectToRoute('app_no_permission_user_active');
            }

        }
    }


    #[Route('/snvlt/doclje/logs', name: 'app_logs_lje_json')]
    public function app_logs_lje_json(
        Request $request,
        MenuRepository $menus,
        MenuPermissionRepository $permissions,
        GroupeRepository $groupeRepository,
        UserRepository $userRepository,
        User $user = null,
        NotificationRepository $notification,
        DocumentcpRepository $docs_cp,
        ManagerRegistry $registry
    ): Response
    {
        if(!$request->getSession()->has('user_session')){
            return $this->redirectToRoute('app_login');
        } else {
            if ($this->isGranted('ROLE_INDUSTRIEL') or $this->isGranted('ROLE_ADMINISTRATIF') or $this->isGranted('ROLE_MINEF') or $this->isGranted('ROLE_DPIF') or $this->isGranted('ROLE_DPIF_SAISIE') or $this->isGranted('ROLE_ADMIN'))
            {
                $user = $userRepository->find($this->getUser());
                $code_groupe = $user->getCodeGroupe()->getId();

                $mes_docs_cp = array();
                //------------------------- Filtre les CP par type Opérateur ------------------------------------- //

                //------------------------- Filtre les CP ADMIN ------------------------------------- //
                if($user->getCodeGroupe()->getId() == 1){
                    $ligneljes = $registry->getRepository(Lignepagelje::class)->findAll();
                    foreach ($ligneljes as $lignelje){
                        $billons = $registry->getRepository(Billon::class)->findBy(['code_lignepagelje'=>$lignelje]);
                        $lng_billons = 0;
                        foreach ($billons as $billon){
                            $lng_billons = $lng_billons + $billon->getLng();
                        }
                        $usine = $lignelje->getCodePagelje()->getCodeDoclje()->getCodeUsine();
                        $mes_docs_cp[] = array(
                            'id_bille'=>$lignelje->getId(),
                            'bille'=>$lignelje->getNumeroArbre() . $lignelje->getLettre(),
                            'essence'=>$lignelje->getEssence()->getNomVernaculaire(),
                            'foret'=>$registry->getRepository(Pagebrh::class)->find($lignelje->getCodeFeuillet())->getCodeDocbrh()->getCodeReprise()->getCodeAttribution()->getCodeForet()->getNumeroForet(),
                            'x_bille'=> $lignelje->getX(),
                            'y_bille'=> $lignelje->getY(),
                            'zh_bille'=> $lignelje->getZh()->getZone(),
                            'lng'=> $lignelje->getLng(),
                            'dm'=>$lignelje->getLng(),
                            'volume_arbre'=>$lignelje->getVolume(),
                            'billons_lng'=>$lng_billons,
                            'rm'=>round(($lng_billons / $lignelje->getLng()),2) * 100,
                            'nom_fichier'=>$usine->getRaisonSocialeUsine(). " - [" . $usine->getCodeCantonnement()->getNomCantonnement(). " - ". $usine->getCodeCantonnement()->getCodeDr().  " ]",
                            'tronconnee'=>$lignelje->isTronconnee()
                        );
                    }
                    //------------------------- Filtre les CP DR ------------------------------------- //
                } elseif ($user->getCodeDr()){
                    //dd($user->getCodeDr());
                    $cantonnements = $registry->getRepository(Cantonnement::class)->findBy(['code_dr'=>$user->getCodeDr()]);
                    foreach ($cantonnements as $cantonnement){
                        $usines = $registry->getRepository(Usine::class)->findBy(['code_cantonnement'=>$cantonnement]);

                        foreach ($usines as $usine){
                            $documents_lje = $registry->getRepository(Documentlje::class)->findBy(['code_usine'=>$usine]);

                            foreach ($documents_lje as $doc){
                                $pageslje = $registry->getRepository(Pagelje::class)->findBy(['code_doclje'=>$doc]);
                                foreach ($pageslje as $page){
                                    $ligneljes = $registry->getRepository(Lignepagelje::class)->findBy(['code_pagelje'=>$page]);
                                    foreach ($ligneljes as $lignelje){
                                        $billons = $registry->getRepository(Billon::class)->findBy(['code_lignepagelje'=>$lignelje]);
                                        $lng_billons = 0;
                                        foreach ($billons as $billon){
                                            $lng_billons = $lng_billons + $billon->getLng();
                                        }

                                        $mes_docs_cp[] = array(
                                            'id_bille'=>$lignelje->getId(),
                                            'bille'=>$lignelje->getNumeroArbre() . $lignelje->getLettre(),
                                            'essence'=>$lignelje->getEssence()->getNomVernaculaire(),
                                            'foret'=>$registry->getRepository(Pagebrh::class)->find($lignelje->getCodeFeuillet())->getCodeDocbrh()->getCodeReprise()->getCodeAttribution()->getCodeForet()->getNumeroForet(),
                                            'x_bille'=> $lignelje->getX(),
                                            'y_bille'=> $lignelje->getY(),
                                            'zh_bille'=> $lignelje->getZh()->getZone(),
                                            'lng'=> $lignelje->getLng(),
                                            'dm'=>$lignelje->getLng(),
                                            'volume_arbre'=>$lignelje->getVolume(),
                                            'billons_lng'=>$lng_billons,
                                            'rm'=>round(($lng_billons / $lignelje->getLng()),2) * 100,
                                            'nom_fichier'=>$usine->getRaisonSocialeUsine(). " - [" . $usine->getCodeCantonnement()->getNomCantonnement(). " - ". $usine->getCodeCantonnement()->getCodeDr().  " ]",
                                            'tronconnee'=>$lignelje->isTronconnee()
                                        );
                                    }
                                }
                            }
                        }
                    }
                    //------------------------- Filtre les CP DD ------------------------------------- //
                } elseif ($user->getCodeDdef()){
                    $cantonnements = $registry->getRepository(Cantonnement::class)->findBy(['code_ddef'=>$user->getCodeDdef()]);
                    foreach ($cantonnements as $cantonnement){
                        $usines = $registry->getRepository(Usine::class)->findBy(['code_cantonnement'=>$cantonnement]);

                        foreach ($usines as $usine){
                            $documents_lje = $registry->getRepository(Documentlje::class)->findBy(['code_usine'=>$usine]);

                            foreach ($documents_lje as $doc){
                                $pageslje = $registry->getRepository(Pagelje::class)->findBy(['code_doclje'=>$doc]);
                                foreach ($pageslje as $page){
                                    $ligneljes = $registry->getRepository(Lignepagelje::class)->findBy(['code_pagelje'=>$page]);
                                    foreach ($ligneljes as $lignelje){
                                        $billons = $registry->getRepository(Billon::class)->findBy(['code_lignepagelje'=>$lignelje]);
                                        $lng_billons = 0;
                                        foreach ($billons as $billon){
                                            $lng_billons = $lng_billons + $billon->getLng();
                                        }

                                        $mes_docs_cp[] = array(
                                            'id_bille'=>$lignelje->getId(),
                                            'bille'=>$lignelje->getNumeroArbre() . $lignelje->getLettre(),
                                            'essence'=>$lignelje->getEssence()->getNomVernaculaire(),
                                            'foret'=>$registry->getRepository(Pagebrh::class)->find($lignelje->getCodeFeuillet())->getCodeDocbrh()->getCodeReprise()->getCodeAttribution()->getCodeForet()->getNumeroForet(),
                                            'x_bille'=> $lignelje->getX(),
                                            'y_bille'=> $lignelje->getY(),
                                            'zh_bille'=> $lignelje->getZh()->getZone(),
                                            'lng'=> $lignelje->getLng(),
                                            'dm'=>$lignelje->getLng(),
                                            'volume_arbre'=>$lignelje->getVolume(),
                                            'billons_lng'=>$lng_billons,
                                            'rm'=>round(($lng_billons / $lignelje->getLng()),2) * 100,
                                            'nom_fichier'=>$usine->getRaisonSocialeUsine(). " - [" . $usine()->getCodeCantonnement()->getNomCantonnement(). " - ". $usine->getCodeCantonnement()->getCodeDr().  " ]",
                                            'tronconnee'=>$lignelje->isTronconnee()
                                        );
                                    }
                                }
                            }
                        }
                    }

                    //------------------------- Filtre les CP CANTONNEMENT ------------------------------------- //
                } elseif ($user->getCodeCantonnement()){

                    $usines = $registry->getRepository(Usine::class)->findBy(['code_cantonnement'=>$user->getCodeCantonnement()]);

                    foreach ($usines as $usine){
                        $documents_lje = $registry->getRepository(Documentlje::class)->findBy(['code_usine'=>$usine]);

                        foreach ($documents_lje as $doc){
                            $pageslje = $registry->getRepository(Pagelje::class)->findBy(['code_doclje'=>$doc]);
                            foreach ($pageslje as $page){
                                $ligneljes = $registry->getRepository(Lignepagelje::class)->findBy(['code_pagelje'=>$page]);
                                foreach ($ligneljes as $lignelje){
                                    $billons = $registry->getRepository(Billon::class)->findBy(['code_lignepagelje'=>$lignelje]);
                                    $lng_billons = 0;
                                    foreach ($billons as $billon){
                                        $lng_billons = $lng_billons + $billon->getLng();
                                    }

                                    $mes_docs_cp[] = array(
                                        'id_bille'=>$lignelje->getId(),
                                        'bille'=>$lignelje->getNumeroArbre() . $lignelje->getLettre(),
                                        'essence'=>$lignelje->getEssence()->getNomVernaculaire(),
                                        'foret'=>$registry->getRepository(Pagebrh::class)->find($lignelje->getCodeFeuillet())->getCodeDocbrh()->getCodeReprise()->getCodeAttribution()->getCodeForet()->getNumeroForet(),
                                        'x_bille'=> $lignelje->getX(),
                                        'y_bille'=> $lignelje->getY(),
                                        'zh_bille'=> $lignelje->getZh()->getZone(),
                                        'lng'=> $lignelje->getLng(),
                                        'dm'=>$lignelje->getLng(),
                                        'volume_arbre'=>$lignelje->getVolume(),
                                        'billons_lng'=>$lng_billons,
                                        'rm'=>round(($lng_billons / $lignelje->getLng()),2) * 100,
                                        'nom_fichier'=>$usine->getRaisonSocialeUsine(). " - [" . $usine()->getCodeCantonnement()->getNomCantonnement(). " - ". $usine->getCodeCantonnement()->getCodeDr().  " ]",
                                        'tronconnee'=>$lignelje->isTronconnee()
                                    );
                                }
                            }
                        }
                    }

                    //------------------------- Filtre les LJE INDUSTRIELS------------------------------------- //

                } elseif ($user->getCodeindustriel()){

                    $documents_lje = $registry->getRepository(Documentlje::class)->findBy(['code_usine'=>$user->getCodeindustriel()]);

                    foreach ($documents_lje as $doc){
                        $pageslje = $registry->getRepository(Pagelje::class)->findBy(['code_doclje'=>$doc]);
                        foreach ($pageslje as $page){
                            $ligneljes = $registry->getRepository(Lignepagelje::class)->findBy(['code_pagelje'=>$page]);
                            foreach ($ligneljes as $lignelje){
                                $billons = $registry->getRepository(Billon::class)->findBy(['code_lignepagelje'=>$lignelje]);
                                $lng_billons = 0;
                                foreach ($billons as $billon){
                                    $lng_billons = $lng_billons + $billon->getLng();
                                }

                                $mes_docs_cp[] = array(
                                    'id_bille'=>$lignelje->getId(),
                                    'bille'=>$lignelje->getNumeroArbre() . $lignelje->getLettre(),
                                    'essence'=>$lignelje->getEssence()->getNomVernaculaire(),
                                    'foret'=>$registry->getRepository(Pagebrh::class)->find($lignelje->getCodeFeuillet())->getCodeDocbrh()->getCodeReprise()->getCodeAttribution()->getCodeForet()->getNumeroForet(),
                                    'x_bille'=> $lignelje->getX(),
                                    'y_bille'=> $lignelje->getY(),
                                    'zh_bille'=> $lignelje->getZh()->getZone(),
                                    'lng'=> $lignelje->getLng(),
                                    'dm'=>$lignelje->getLng(),
                                    'volume_arbre'=>$lignelje->getVolume(),
                                    'billons_lng'=>$lng_billons,
                                    'rm'=>round(($lng_billons / $lignelje->getLng()),2) * 100,
                                    'nom_fichier'=>$user->getCodeindustriel()->getRaisonSocialeUsine(). " - [" . $user->getCodeindustriel()->getCodeCantonnement()->getNomCantonnement(). " - ". $user->getCodeindustriel()->getCodeCantonnement()->getCodeDr().  " ]",
                                    'tronconnee'=>$lignelje->isTronconnee()
                                );
                            }
                        }
                    }

                }
                return new JsonResponse(json_encode($mes_docs_cp));
            } else {
                return $this->redirectToRoute('app_no_permission_user_active');
            }

        }



    }

    #[Route('/snvlt/op/usines/logs', name: 'app_liste_usines')]
    public function app_liste_usines(
        Request $request,
        MenuRepository $menus,
        MenuPermissionRepository $permissions,
        GroupeRepository $groupeRepository,
        UserRepository $userRepository,
        User $user = null,
        NotificationRepository $notification,
        DocumentcpRepository $docs_cp,
        ManagerRegistry $registry
    ): Response
    {
        if(!$request->getSession()->has('user_session')){
            return $this->redirectToRoute('app_login');
        } else {
            if ($this->isGranted('ROLE_ADMINISTRATIF') or $this->isGranted('ROLE_MINEF')   or $this->isGranted('ROLE_DPIF') or $this->isGranted('ROLE_DPIF_SAISIE') or $this->isGranted('ROLE_ADMIN'))
            {
                $user = $userRepository->find($this->getUser());

                $mes_usines = array();
                //------------------------- Filtre les USINES par type Opérateur ------------------------------------- //

                //------------------------- Filtre les USINES ADMIN ------------------------------------- //
                if($user->getCodeGroupe()->getId() == 1){
                    $usines = $registry->getRepository(Usine::class)->findAll();
                    foreach ($usines as $usine){


                        $mes_usines[] = array(
                            'denomination'=>$usine->getRaisonSocialeUsine(),
                            'idusine'=>$usine->getId()
                        );
                    }
                    //------------------------- Filtre les USINES DR ------------------------------------- //
                } elseif ($user->getCodeDr()){
                    $cantonnements = $registry->getRepository(Cantonnement::class)->findBy(['code_dr'=>$user->getCodeDr()]);
                    foreach ($cantonnements as $cantonnement){
                        $usines = $registry->getRepository(Usine::class)->findBy(['code_cantonnement'=>$cantonnement]);
                        foreach ($usines as $usine){
                            $mes_usines[] = array(
                                'denomination'=>$usine->getRaisonSocialeUsine(),
                                'idusine'=>$usine->getId()
                            );
                        }


                    }
                    //------------------------- Filtre les USINES DD ------------------------------------- //
                } elseif ($user->getCodeDdef()){
                    $cantonnements = $registry->getRepository(Cantonnement::class)->findBy(['code_ddef'=>$user->getCodeDdef()]);
                    foreach ($cantonnements as $cantonnement){
                        $usines = $registry->getRepository(Usine::class)->findBy(['code_cantonnement'=>$cantonnement]);
                        foreach ($usines as $usine){
                            $mes_usines[] = array(
                                'denomination'=>$usine->getRaisonSocialeUsine(),
                                'idusine'=>$usine->getId()
                            );
                        }


                    }

                    //------------------------- Filtre les USINES CANTONNEMENT ------------------------------------- //
                } elseif ($user->getCodeCantonnement()){
                    $usines = $registry->getRepository(Usine::class)->findBy(['code_cantonnement'=>$user->getCodeCantonnement()]);
                    foreach ($usines as $usine){
                        $mes_usines[] = array(
                            'denomination'=>$usine->getRaisonSocialeUsine(),
                            'idusine'=>$usine->getId(),
                        );
                    }


                }
                sort($mes_usines);
                return new JsonResponse(json_encode($mes_usines));
            } else {
                return $this->redirectToRoute('app_no_permission_user_active');
            }

        }
    }

    #[Route('/snvlt/doclje/logs/flt/{id_usine}', name: 'app_logs_by_usine_lje_json')]
    public function app_logs_by_usine_lje_json(
        Request $request,
        MenuRepository $menus,
        MenuPermissionRepository $permissions,
        GroupeRepository $groupeRepository,
        UserRepository $userRepository,
        int $id_usine,
        User $user = null,
        NotificationRepository $notification,
        DocumentcpRepository $docs_cp,
        ManagerRegistry $registry
    ): Response
    {
        if(!$request->getSession()->has('user_session')){
            return $this->redirectToRoute('app_login');
        } else {
            if ($this->isGranted('ROLE_ADMINISTRATIF') or $this->isGranted('ROLE_MINEF') or $this->isGranted('ROLE_DPIF') or $this->isGranted('ROLE_DPIF_SAISIE') or $this->isGranted('ROLE_ADMIN'))
            {
                $user = $userRepository->find($this->getUser());
                $code_groupe = $user->getCodeGroupe()->getId();

                $mes_docs_cp = array();
                //------------------------- Filtre les CP par type Opérateur ------------------------------------- //

                //------------------------- Filtre les CP ADMIN ------------------------------------- //


                $usine = $registry->getRepository(Usine::class)->find($id_usine);
                if ($usine){
                    $documents_lje = $registry->getRepository(Documentlje::class)->findBy(['code_usine'=>$usine]);

                    foreach ($documents_lje as $doc){
                        $pageslje = $registry->getRepository(Pagelje::class)->findBy(['code_doclje'=>$doc]);
                        foreach ($pageslje as $page){
                            $ligneljes = $registry->getRepository(Lignepagelje::class)->findBy(['code_pagelje'=>$page]);
                            foreach ($ligneljes as $lignelje){
                                $billons = $registry->getRepository(Billon::class)->findBy(['code_lignepagelje'=>$lignelje]);
                                $lng_billons = 0;
                                foreach ($billons as $billon){
                                    $lng_billons = $lng_billons + $billon->getLng();
                                }

                                $mes_docs_cp[] = array(
                                    'id_bille'=>$lignelje->getId(),
                                    'bille'=>$lignelje->getNumeroArbre() . $lignelje->getLettre(),
                                    'essence'=>$lignelje->getEssence()->getNomVernaculaire(),
                                    'x_bille'=> $lignelje->getX(),
                                    'y_bille'=> $lignelje->getY(),
                                    'zh_bille'=> $lignelje->getZh()->getZone(),
                                    'lng'=> $lignelje->getLng(),
                                    'dm'=>$lignelje->getLng(),
                                    'volume_arbre'=>$lignelje->getVolume(),
                                    'billons_lng'=>$lng_billons,
                                    'rm'=>round(($lng_billons / $lignelje->getLng()),2) * 100,
                                    'nom_fichier'=>$usine->getRaisonSocialeUsine(). " - [" . $usine->getCodeCantonnement()->getNomCantonnement(). " - ". $usine->getCodeCantonnement()->getCodeDr().  " ]"
                                );
                            }
                        }
                    }
                }

                return new JsonResponse(json_encode($mes_docs_cp));
            }else{
                return $this->redirectToRoute('app_no_permission_user_active');
            }

        }
    }
    #[Route('/snvlt/connaissement/brh', name: 'lje_connaissement_brh')]
    public function lje_connaissement_brh(
        Request $request,
        MenuRepository $menus,
        MenuPermissionRepository $permissions,
        GroupeRepository $groupeRepository,
        UserRepository $userRepository,
        User $user = null,
        NotificationRepository $notification,
        DocumentcpRepository $docs_cp,
        ManagerRegistry $registry
    ): Response
    {
        if(!$request->getSession()->has('user_session')){
            return $this->redirectToRoute('app_login');
        } else {
            if ($this->isGranted('ROLE_INDUSTRIEL') or $this->isGranted('ROLE_MINEF') or $this->isGranted('ROLE_DPIF') or $this->isGranted('ROLE_DPIF_SAISIE') or $this->isGranted('ROLE_ADMIN'))
            {
                $user = $userRepository->find($this->getUser());
                $code_groupe = $user->getCodeGroupe()->getId();

                $mes_connaissements_brh = array();

                $connaisseements = $registry->getRepository(Pagebrh::class)->findBy([
                    'fini'=>true,
                    'confirmation_usine'=>true,
                    'parc_usine_brh'=>$user->getCodeindustriel(),
                    'entre_lje'=>false
                ]);

                    foreach ($connaisseements as $connaisseement){
                        $mes_connaissements_brh[] = array(
                            'numero'=>$connaisseement->getNumeroPagebrh(),
                            'id_page'=>$connaisseement->getId(),
                            'brh'=>$connaisseement->getCodeDocbrh()->getNumeroDocbrh()
                        );
                            }
                        rsort($mes_connaissements_brh);

                return new JsonResponse(json_encode($mes_connaissements_brh));
            }else{
                return $this->redirectToRoute('app_no_permission_user_active');
            }

        }
    }
}
