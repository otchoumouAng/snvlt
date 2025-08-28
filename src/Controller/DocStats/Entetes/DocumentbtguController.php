<?php

namespace App\Controller\DocStats\Entetes;

use App\Entity\Autorisation\Attribution;
use App\Entity\Autorisation\Reprise;
use App\Entity\DocStats\Entetes\Documentbtgu;
use App\Entity\DocStats\Entetes\Documentcp;
use App\Entity\DocStats\Entetes\Documentlje;
use App\Entity\DocStats\Pages\Pagebrh;
use App\Entity\DocStats\Pages\Pagebtgu;
use App\Entity\DocStats\Pages\Pagecp;
use App\Entity\DocStats\Pages\Pagelje;
use App\Entity\DocStats\Saisie\Lignepagebtgu;
use App\Entity\DocStats\Saisie\Lignepagecp;
use App\Entity\DocStats\Saisie\Lignepagelje;
use App\Entity\References\Cantonnement;
use App\Entity\References\Exploitant;
use App\Entity\References\Foret;
use App\Entity\References\Usine;
use App\Entity\User;
use App\Repository\Administration\NotificationRepository;
use App\Repository\DocStats\Entetes\DocumentbtguRepository;
use App\Repository\DocStats\Pages\PagebtguRepository;
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

class DocumentbtguController extends AbstractController
{
    public function __construct(private ManagerRegistry $m)
    {
    }

    #[Route('/doc/stats/entetes/docbtgu', name: 'app_op_docbtgu')]
    public function index(
        Request $request,
        MenuRepository $menus,
        MenuPermissionRepository $permissions,
        GroupeRepository $groupeRepository,
        UserRepository $userRepository,
        User $user = null,
        NotificationRepository $notification,
        DocumentbtguRepository $docs_btgu,
        ManagerRegistry $registry
    ): Response
    {
        if(!$request->getSession()->has('user_session')){
            return $this->redirectToRoute('app_login');
        } else {
            if ($this->isGranted('ROLE_INDUSTRIEL') or  $this->isGranted('ROLE_ADMINISTRATIF') or $this->isGranted('ROLE_MINEF') or $this->isGranted('ROLE_ADMIN')  )
            {
                $user = $userRepository->find($this->getUser());
                $code_groupe = $user->getCodeGroupe()->getId();

                return $this->render('doc_stats/entetes/documentbtgu/index.html.twig', [
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

    #[Route('/snvlt/docbtgu/btgu/pages/{id_btgu}', name: 'affichage_btgu_json')]
    public function affiche_btgu(
        Request $request,
        int $id_btgu,
        MenuRepository $menus,
        MenuPermissionRepository $permissions,
        GroupeRepository $groupeRepository,
        UserRepository $userRepository,
        User $user = null,
        NotificationRepository $notification,
        DocumentbtguRepository $docs_btgu,
        ManagerRegistry $registry
    ): Response
    {
        if(!$request->getSession()->has('user_session')){
            return $this->redirectToRoute('app_login');
        } else {
            if ($this->isGranted('ROLE_INDUSTRIEL') or $this->isGranted('ROLE_ADMINISTRATIF') or $this->isGranted('ROLE_MINEF') or $this->isGranted('ROLE_MINEF'))
            {
                $user = $userRepository->find($this->getUser());
                $code_groupe = $user->getCodeGroupe()->getId();

                $numerodoc = "";

                $documentbtgu = $registry->getRepository(Documentbtgu::class)->find($id_btgu);
                if($documentbtgu){$numerodoc = $documentbtgu->getNumeroDocbtgu();}

                return $this->render('doc_stats/entetes/documentbtgu/affiche_btgu.html.twig', [
                    'document_name'=>$documentbtgu,
                    'liste_menus'=>$menus->findOnlyParent(),
                    "all_menus"=>$menus->findAll(),
                    'menus'=>$permissions->findBy(['code_groupe_id'=>$code_groupe]),
                    'mes_notifs'=>$notification->findBy(['to_user'=>$user, 'lu'=>false],[],5,0),
                    'groupe'=>$code_groupe,
                    'liste_parent'=>$permissions,
                    'villes'=>$registry->getRepository(Cantonnement::class)->findBy([],['nom_cantonnement'=>'ASC']),
                    'usines'=>$registry->getRepository(Usine::class)->findOnlyManager()
                ]);
            } else {
                return $this->redirectToRoute('app_no_permission_user_active');
            }

        }
    }

    #[Route('/snvlt/docbtgu/op', name: 'app_docs_btgu_json')]
    public function my_doc_btgu(
        Request $request,
        MenuRepository $menus,
        MenuPermissionRepository $permissions,
        GroupeRepository $groupeRepository,
        UserRepository $userRepository,
        User $user = null,
        NotificationRepository $notification,
        DocumentbtguRepository $docs_btgu,
        ManagerRegistry $registry
    ): Response
    {
        if(!$request->getSession()->has('user_session')){
            return $this->redirectToRoute('app_login');
        } else {
            if ($this->isGranted('ROLE_INDUSTRIEL') or  $this->isGranted('ROLE_ADMINISTRATIF') or $this->isGranted('ROLE_MINEF') or $this->isGranted('ROLE_ADMIN')  )
            {
                $user = $userRepository->find($this->getUser());
                $code_groupe = $user->getCodeGroupe()->getId();

                $mes_docs_btgu = array();
                //------------------------- Filtre les btgu par type Opérateur ------------------------------------- //

                //------------------------- Filtre les btgu ADMIN ------------------------------------- //
                if($user->getCodeGroupe()->getId() == 1){
                    $documents_btgu = $registry->getRepository(Documentbtgu::class)->findAll();
                    foreach ($documents_btgu as $document_btgu){
                        $exploitant = "-";
                        if ($document_btgu->getCodeUsine()->getCodeExploitant()) {
                            $exploitant = $document_btgu->getCodeUsine()->getCodeExploitant()->getNumeroExploitant() . '-'. $document_btgu->getCodeUsine()->getCodeExploitant()->getMarteauExploitant(). '-'. $document_btgu->getCodeUsine()->getCodeExploitant()->getRaisonSocialeExploitant();
                        }
                        if ($document_btgu->getCodeUsine()->getCodeCantonnement()){
                            $canton = $document_btgu->getCodeUsine()->getCodeCantonnement()->getNomCantonnement();
                            $d = $document_btgu->getCodeUsine()->getCodeCantonnement()->getCodeDr()->getDenomination();
                        }else {
                            $canton = "-";
                            $d = "-";
                        }
                        $mes_docs_btgu[] = array(
                            'id_doc_btgu'=>$document_btgu->getId(),
                            'numero_docbtgu'=>$document_btgu->getNumeroDocbtgu(),
                            'usine'=>$document_btgu->getCodeUsine()->getRaisonSocialeUsine(),
                            'code_usine'=>$document_btgu->getCodeUsine()->getNumeroUsine(),
                            'cantonnement'=>$canton,
                            'dr'=>$d,
                            'date_delivrance'=>$document_btgu->getDelivreDocbtgu()->format("d m Y"),
                            'etat'=>$document_btgu->isEtat(),
                            'exploitant'=>$exploitant,
                            'volume_btgu'=>$this->getVolumebtgu($document_btgu)
                        );
                    }
                    //------------------------- Filtre les btgu DR ------------------------------------- //
                } elseif ($user->getCodeDr()){
                    //dd($user->getCodeDr());
                    $cantonnements = $registry->getRepository(Cantonnement::class)->findBy(['code_dr'=>$user->getCodeDr()]);
                    foreach ($cantonnements as $cantonnement){

                        $usines = $registry->getRepository(Usine::class)->findBy(['code_cantonnement'=>$cantonnement]);
                        foreach ($usines as $usine){
                            $documents_btgu = $registry->getRepository(Documentbtgu::class)->findBy(['code_usine'=>$usine]);
                            foreach ($documents_btgu as $document_btgu){
                                $exploitant = "-";
                                if ($document_btgu->getCodeUsine()->getCodeExploitant()) {
                                    $exploitant = $document_btgu->getCodeUsine()->getCodeExploitant()->getNumeroExploitant() . '-'. $document_btgu->getCodeUsine()->getCodeExploitant()->getMarteauExploitant(). '-'. $document_btgu->getCodeUsine()->getCodeExploitant()->getRaisonSocialeExploitant();
                                }
                                if ($document_btgu->getCodeUsine()->getCodeCantonnement()){
                                    $canton = $document_btgu->getCodeUsine()->getCodeCantonnement()->getNomCantonnement();
                                    $d = $document_btgu->getCodeUsine()->getCodeCantonnement()->getCodeDr()->getDenomination();
                                }else {
                                    $canton = "-";
                                    $d = "-";
                                }
                                $mes_docs_btgu[] = array(
                                    'id_doc_btgu'=>$document_btgu->getId(),
                                    'numero_docbtgu'=>$document_btgu->getNumeroDocbtgu(),
                                    'usine'=>$document_btgu->getCodeUsine()->getRaisonSocialeUsine(),
                                    'code_usine'=>$document_btgu->getCodeUsine()->getNumeroUsine(),
                                    'cantonnement'=>$canton,
                                    'dr'=>$d,
                                    'date_delivrance'=>$document_btgu->getDelivreDocbtgu()->format("d m Y"),
                                    'etat'=>$document_btgu->isEtat(),
                                    'exploitant'=>$exploitant,
                                    'volume_btgu'=>$this->getVolumebtgu($document_btgu)
                                );
                            }

                        }
                    }
                    //------------------------- Filtre les btgu DD ------------------------------------- //
                } elseif ($user->getCodeDdef()){
                    //dd($user->getCodeDr());
                    $cantonnements = $registry->getRepository(Cantonnement::class)->findBy(['code_ddef'=>$user->getCodeDdef()]);
                    foreach ($cantonnements as $cantonnement){

                        $usines = $registry->getRepository(Usine::class)->findBy(['code_cantonnement'=>$cantonnement]);
                        foreach ($usines as $usine){
                            $documents_btgu = $registry->getRepository(Documentbtgu::class)->findBy(['code_usine'=>$usine]);
                            foreach ($documents_btgu as $document_btgu){
                                $exploitant = "-";
                                if ($document_btgu->getCodeUsine()->getCodeExploitant()) {
                                    $exploitant = $document_btgu->getCodeUsine()->getCodeExploitant()->getNumeroExploitant() . '-'. $document_btgu->getCodeUsine()->getCodeExploitant()->getMarteauExploitant(). '-'. $document_btgu->getCodeUsine()->getCodeExploitant()->getRaisonSocialeExploitant();
                                }
                                if ($document_btgu->getCodeUsine()->getCodeCantonnement()){
                                    $canton = $document_btgu->getCodeUsine()->getCodeCantonnement()->getNomCantonnement();
                                    $d = $document_btgu->getCodeUsine()->getCodeCantonnement()->getCodeDr()->getDenomination();
                                }else {
                                    $canton = "-";
                                    $d = "-";
                                }
                                $mes_docs_btgu[] = array(
                                    'id_doc_btgu'=>$document_btgu->getId(),
                                    'numero_docbtgu'=>$document_btgu->getNumeroDocbtgu(),
                                    'usine'=>$document_btgu->getCodeUsine()->getRaisonSocialeUsine(),
                                    'code_usine'=>$document_btgu->getCodeUsine()->getNumeroUsine(),
                                    'cantonnement'=>$canton,
                                    'dr'=>$d,
                                    'date_delivrance'=>$document_btgu->getDelivreDocbtgu()->format("d m Y"),
                                    'etat'=>$document_btgu->isEtat(),
                                    'exploitant'=>$exploitant,
                                    'volume_btgu'=>$this->getVolumebtgu($document_btgu)
                                );
                            }

                        }
                    }

                    //------------------------- Filtre les btgu CANTONNEMENT ------------------------------------- //
                } elseif ($user->getCodeCantonnement()){

                    $usines = $registry->getRepository(Usine::class)->findBy(['code_cantonnement'=>$user->getCodeCantonnement()]);
                    foreach ($usines as $usine){
                        $documents_btgu = $registry->getRepository(Documentbtgu::class)->findBy(['code_usine'=>$usine]);
                        foreach ($documents_btgu as $document_btgu){
                            $exploitant = "-";
                            if ($document_btgu->getCodeUsine()->getCodeExploitant()) {
                                $exploitant = $document_btgu->getCodeUsine()->getCodeExploitant()->getNumeroExploitant() . '-'. $document_btgu->getCodeUsine()->getCodeExploitant()->getMarteauExploitant(). '-'. $document_btgu->getCodeUsine()->getCodeExploitant()->getRaisonSocialeExploitant();
                            }
                            if ($document_btgu->getCodeUsine()->getCodeCantonnement()){
                                $canton = $document_btgu->getCodeUsine()->getCodeCantonnement()->getNomCantonnement();
                                $d = $document_btgu->getCodeUsine()->getCodeCantonnement()->getCodeDr()->getDenomination();
                            }else {
                                $canton = "-";
                                $d = "-";
                            }
                            $mes_docs_btgu[] = array(
                                'id_doc_btgu'=>$document_btgu->getId(),
                                'numero_docbtgu'=>$document_btgu->getNumeroDocbtgu(),
                                'usine'=>$document_btgu->getCodeUsine()->getRaisonSocialeUsine(),
                                'code_usine'=>$document_btgu->getCodeUsine()->getNumeroUsine(),
                                'cantonnement'=>$canton,
                                'dr'=>$d,
                                'date_delivrance'=>$document_btgu->getDelivreDocbtgu()->format("d m Y"),
                                'etat'=>$document_btgu->isEtat(),
                                'exploitant'=>$exploitant,
                                'volume_btgu'=>$this->getVolumebtgu($document_btgu)
                            );
                        }

                    }


                    //------------------------- Filtre les btgu INDUSTRIELS------------------------------------- //
                }  elseif ($user->getCodeindustriel()){
                    $documents_btgu = $registry->getRepository(Documentbtgu::class)->findBy(['code_usine'=>$user->getCodeindustriel(), 'signature_cef'=>true, 'signature_dr'=>true],['created_at'=>'DESC']);
                    foreach ($documents_btgu as $document_btgu){
                        $exploitant = "-";
                        if ($document_btgu->getCodeUsine()->getCodeExploitant()) {
                            $exploitant = $document_btgu->getCodeUsine()->getCodeExploitant()->getNumeroExploitant() . '-'. $document_btgu->getCodeUsine()->getCodeExploitant()->getMarteauExploitant(). '-'. $document_btgu->getCodeUsine()->getCodeExploitant()->getRaisonSocialeExploitant();
                        }
                        if ($document_btgu->getCodeUsine()->getCodeCantonnement()){
                            $canton = $document_btgu->getCodeUsine()->getCodeCantonnement()->getNomCantonnement();
                            $d = $document_btgu->getCodeUsine()->getCodeCantonnement()->getCodeDr()->getDenomination();
                        }else {
                            $canton = "-";
                            $d = "-";
                        }
                        $mes_docs_btgu[] = array(
                            'id_doc_btgu'=>$document_btgu->getId(),
                            'numero_docbtgu'=>$document_btgu->getNumeroDocbtgu(),
                            'usine'=>$document_btgu->getCodeUsine()->getRaisonSocialeUsine(),
                            'code_usine'=>$document_btgu->getCodeUsine()->getNumeroUsine(),
                            'cantonnement'=>$canton,
                            'dr'=>$d,
                            'date_delivrance'=>$document_btgu->getDelivreDocbtgu()->format("d m Y"),
                            'etat'=>$document_btgu->isEtat(),
                            'exploitant'=>$exploitant,
                            'volume_btgu'=>$this->getVolumebtgu($document_btgu)
                        );
                    }
                }

                return new JsonResponse(json_encode($mes_docs_btgu));
            }else {
                return $this->redirectToRoute('app_no_permission_user_active');
            }

        }

    }

    #[Route('/snvlt/docbtgu/op/pages_btgu/{id_btgu}', name: 'affichage_pages_btgu_json')]
    public function affiche_pages_btgu(
        Request $request,
        int $id_btgu,
        MenuRepository $menus,
        MenuPermissionRepository $permissions,
        GroupeRepository $groupeRepository,
        UserRepository $userRepository,
        User $user = null,
        NotificationRepository $notification,
        DocumentbtguRepository $docs_btgu,
        ManagerRegistry $registry
    ): Response
    {
        if(!$request->getSession()->has('user_session')){
            return $this->redirectToRoute('app_login');
        } else {
            if ($this->isGranted('ROLE_INDUSTRIEL') or $this->isGranted('ROLE_ADMINISTRATIF') or $this->isGranted('ROLE_MINEF') or $this->isGranted('ROLE_MINEF'))
            {
                $doc_btgu = $docs_btgu->find($id_btgu);
                if($doc_btgu){
                    $pages_btgu = $registry->getRepository(Pagebtgu::class)->findBy(['code_docbtgu'=>$doc_btgu], ['id'=>'ASC']);
                    $my_btgu_pages = array();

                    foreach ($pages_btgu as $page){
                        $my_btgu_pages[] = array(
                            'id_page'=>$page->getId(),
                            'numero_page'=>$page->getNumeroPagebtgu()
                        );
                    }
                    return  new JsonResponse(json_encode($my_btgu_pages));
                }else{
                    return  new JsonResponse("NO DATA FOUND");
                }
            } else {
                return $this->redirectToRoute('app_no_permission_user_active');
            }

        }
    }

    #[Route('/snvlt/docbtgu/op/pages_btgu/data/{id_page}', name: 'affichage_page_data_btgu_json')]
    public function affiche_page_courante(
        Request $request,
        int $id_page,
        MenuRepository $menus,
        MenuPermissionRepository $permissions,
        GroupeRepository $groupeRepository,
        UserRepository $userRepository,
        User $user = null,
        NotificationRepository $notification,
        PagebtguRepository $pages_btgu,
        ManagerRegistry $registry
    ): Response
    {
        if(!$request->getSession()->has('user_session')){
            return $this->redirectToRoute('app_login');
        } else {
            if ($this->isGranted('ROLE_INDUSTRIEL') or  $this->isGranted('ROLE_ADMINISTRATIF') or $this->isGranted('ROLE_MINEF') or $this->isGranted('ROLE_ADMIN')  )
            {
                $page_btgu = $pages_btgu->find($id_page);
                if($page_btgu){
                    $my_btgu_page = array();

                    $destination = "";
                    $usine = "";
                    $date_chr = "";
                    $date_dep = "";
                    $date_arr = "";

                    if ($page_btgu->getUsineDestinataire()){
                        $usine = $page_btgu->getUsineDestinataire()->getSigle();
                    }

                    if ($page_btgu->getDatechargement()){
                        $date_chr = $page_btgu->getDatechargement()->format('d/m/Y');
                    }
                    if ($page_btgu->getDateDepart()){
                        $date_dep = $page_btgu->getDateDepart()->format('d/m/Y');
                    }
                    if ($page_btgu->getDateArrivee()){
                        $date_arr = $page_btgu->getDateArrivee()->format('d/m/Y');
                    }

                    if ($page_btgu->getDestination()){
                        if ($registry->getRepository(Cantonnement::class)->find($page_btgu->getDestination())){
                            $destination = $registry->getRepository(Cantonnement::class)->find($page_btgu->getDestination())->getNomCantonnement();
                        } else {
                            $destination = $page_btgu->getDestination();
                        }
                    }

                    $my_btgu_page[] = array(
                        'id_page'=>$page_btgu->getId(),
                        'numero_page'=>$page_btgu->getNumeroPagebtgu(),
                        'annee'=>$page_btgu->getAnnee(),
                        'mois'=>$page_btgu->getMois(),
                        'volume_page'=>$this->getVolumebtguByPage($page_btgu),
                        'destination'=>$destination,
                        'parc_usine'=>$page_btgu->getUsineDestinataire(),
                        'usine_dest'=>$usine,
                        'transporteur'=>$page_btgu->getTransporteur(),
                        'chauffeur'=>$page_btgu->getChauffeur(),
                        'immatriculation'=>$page_btgu->getImmatriculation(),
                        'date_chargement'=>$date_chr,
                        'date_depart'=>$date_dep,
                        'date_arrivee'=>$date_arr
                    );

                    return  new JsonResponse(json_encode($my_btgu_page));
                } else {
                    return  new JsonResponse("NO DATA FOUND");
                }
            } else {
                return $this->redirectToRoute('app_no_permission_user_active');
            }

        }
    }

    #[Route('/snvlt/docbtgu/op/pages_btgu/data/edit/{id_page}/{data}', name: 'edit_page_btgu_json')]
    public function edit_page_btgu(
        Request $request,
        MenuRepository $menus,
        MenuPermissionRepository $permissions,
        GroupeRepository $groupeRepository,
        UserRepository $userRepository,
        User $user = null,
        string $data,
        int $id_page,
        NotificationRepository $notification,
        PagebtguRepository $pages_btgu,
        ManagerRegistry $registry
    ): Response
    {
        if(!$request->getSession()->has('user_session')){
            return $this->redirectToRoute('app_login');
        } else {
            if ($this->isGranted('ROLE_INDUSTRIEL') or $this->isGranted('ROLE_ADMIN'))
            {
                $user = $userRepository->find($this->getUser());
                $code_groupe = $user->getCodeGroupe()->getId();

                //$page_btgu = $pages_btgu->find($id_page);
                if($data){
                    $pagebtgu = $registry->getRepository(Pagebtgu::class)->find($id_page);

                    if ($pagebtgu){
                        //Decoder le JSON BRH
                        $arraydata = json_decode($data);

                        //dd($arraydata->numero_lignepagebtgu);
                        $date_jour = new \DateTime();

                        /*
                         *  obj.date_chargementbrh =  $("#date_chargement").val();
                            obj.date_depart =  $("#date_depart").val();
                            obj.date_arrivee =  $("#date_arrivee").val();
                            obj.destination =  $("#destination").val();
                            obj.parc_usine =  $("#parc_usine").val();
                            obj.transporteur =  $("#transporteur").val();
                            obj.chauffeur =  $("#chauffeur").val();
                            obj.mois =  $("#mois").val();
                            obj.annee =  $("#annee").val();
                            obj.immatriculation =  $("#immatriculation").val();
                            obj.code_docbtgu=  {{ document_name.id }};

                         */

                        $pagebtgu->setDestination(strtoupper($arraydata->destination));
                        $pagebtgu->setUsineDestinataire($registry->getRepository(Usine::class)->find((int)  $arraydata->parc_usine));


                        //$date_chargement = new \DateTime();
                        $date_chargement =  $arraydata->date_chargement;
                        $date_depart =  $arraydata->date_depart;
                        $date_arrivee =  $arraydata->date_arrivee;



                        $pagebtgu->setImmatriculation(strtoupper($arraydata->immatriculation));
                        $pagebtgu->setMois((int) $arraydata->mois);
                        $pagebtgu->setAnnee(strtoupper($arraydata->annee));
                        $pagebtgu->setChauffeur(strtoupper($arraydata->chauffeur));
                        $pagebtgu->setTransporteur(strtoupper($arraydata->transporteur));

                        $pagebtgu->setDatechargement(\DateTime::createFromFormat('Y-m-d', $date_chargement));
                        $pagebtgu->setDateDepart(\DateTime::createFromFormat('Y-m-d', $date_depart));
                        $pagebtgu->setDateArrivee(\DateTime::createFromFormat('Y-m-d', $date_arrivee));

                        $pagebtgu->setUpdatedAt(new \DateTime());
                        $pagebtgu->setUpdatedBy($user);

                        $pagebtgu->setCodeDocbtgu($registry->getRepository(Documentbtgu::class)->find((int) $arraydata->code_docbtgu));

                        $registry->getManager()->persist($pagebtgu);
                        $registry->getManager()->flush();

                        return  new JsonResponse([
                            'code'=>'PAGE_BRH_EDITED_SUCCESSFULLY',
                            'html'=>''
                        ]);
                    }

                }
            } else {
                return $this->redirectToRoute('app_no_permission_user_active');
            }

        }
    }
    
    

    #[Route('/snvlt/docbtgu/op/lignes_btgu/data/{id_page}', name: 'affichage_ligne_btgu_data_btgu_json')]
    public function affiche_lignes_btgu_courante(
        Request $request,
        int $id_page,
        MenuRepository $menus,
        MenuPermissionRepository $permissions,
        GroupeRepository $groupeRepository,
        UserRepository $userRepository,
        User $user = null,
        NotificationRepository $notification,
        PagebtguRepository $pages_btgu,
        ManagerRegistry $registry
    ): Response
    {
        if(!$request->getSession()->has('user_session')){
            return $this->redirectToRoute('app_login');
        } else {
            if ($this->isGranted('ROLE_INDUSTRIEL') or  $this->isGranted('ROLE_ADMINISTRATIF') or $this->isGranted('ROLE_MINEF') or $this->isGranted('ROLE_ADMIN')  )
            {
                $page_btgu = $pages_btgu->find($id_page);
                if($page_btgu){
                    $lignes_btgu = $registry->getRepository(Lignepagebtgu::class)->findBy(['code_pagebtgu'=>$page_btgu]);
                    $my_btgu_page = array();
                    foreach ($lignes_btgu as $lignebtgu){
                        $my_btgu_page[] = array(
                            'id_ligne'=>$lignebtgu->getId(),
                            'numero_ligne'=>$lignebtgu->getNumeroArbre(),
                            'lettre'=>$lignebtgu->getLettreBille(),
                            'essence'=>$lignebtgu->getEssence()->getNomVernaculaire(),
                            'x_btgu'=>$lignebtgu->getX(),
                            'y_btgu'=>$lignebtgu->getY(),
                            'zh_btgu'=>$lignebtgu->getZh()->getZone(),
                            'lng_btgu'=>$lignebtgu->getLng(),
                            'dm_btgu'=>$lignebtgu->getDm(),
                            'cubage_btgu'=>round($lignebtgu->getVolume(),3),
                            'created_at'=>$lignebtgu->getCreatedAt()->format('d/m/Y h:i:s'),
                            'created_by'=>$lignebtgu->getCreatedBy(),
                            'exploitant'=>$registry->getRepository(Exploitant::class)->findOneBy(["numero_exploitant"=>$lignebtgu->getCodeExploitant()])
                        );
                    }


                    return  new JsonResponse(json_encode($my_btgu_page));
                } else {
                    return  new JsonResponse("NO DATA FOUND");
                }
            } else {
                return $this->redirectToRoute('app_no_permission_user_active');
            }

        }
    }


    function getVolumebtgu(Documentbtgu $documentbtgu):float
    {
        $volumebtgu = 0;
        if($documentbtgu){
            $pagebtgu =$this->m->getRepository(Pagebtgu::class)->findBy(['code_docbtgu'=>$documentbtgu]);
            foreach ($pagebtgu as $page){
                $lignepages = $this->m->getRepository(Lignepagebtgu::class)->findBy(['code_pagebtgu'=>$page]);
                foreach ($lignepages as $ligne){
                    $volumebtgu = $volumebtgu +  $ligne->getVolume();
                }
            }
            return $volumebtgu;
        } else {
            return $volumebtgu;
        }
    }

    function getVolumebtguByPage(Pagebtgu $pagebtgu):float
    {
        $volumebtgu = 0;
        $lignepages = $this->m->getRepository(Lignepagebtgu::class)->findBy(['code_pagebtgu'=>$pagebtgu]);

        foreach ($lignepages as $ligne){
            $volumebtgu = $volumebtgu +  $ligne->getVolume();
        }

        return $volumebtgu;

    }

    #[Route('/snvlt/numlje/dispo', name:'num_lje_dispo')]
    public function num_lje_dispo(
        ManagerRegistry $registry,
        Request $request,
        MenuRepository $menus,
        MenuPermissionRepository $permissions,
        GroupeRepository $groupeRepository,
        UserRepository $userRepository,
        User $user = null,
        NotificationRepository $notifications
    ){
        if(!$request->getSession()->has('user_session')){
            return $this->redirectToRoute('app_login');
        } else {
            $liste_numeros = array();

            if ($this->isGranted('ROLE_INDUSTRIEL')){
                $docljes = $registry->getRepository(Documentlje::class)->findBy(['code_usine'=>$user->getCodeindustriel()]);

                        foreach ($docljes as $doclje){
                            $pageljes = $registry->getRepository(Pagelje::class)->findBy(['code_doclje'=>$doclje]);

                            foreach ($pageljes as $pagelje){
                                $lignes = $registry->getRepository(Lignepagelje::class)->findBy(['code_pagelje'=>$pagelje, 'tronconnee'=>false]);

                                foreach ($lignes as $ligne){
                                    $numero = "";
                                    if ($ligne->getCodeTypeDoc()->getId() == 2){
                                        $numero = $ligne->getNumeroArbre().$ligne->getLettre()." [".$registry->getRepository(Pagebrh::class)->find($ligne->getCodeFeuillet())->getCodeDocbrh()->getCodeReprise()->getCodeAttribution()->getCodeForet()->getDenomination()."]";
                                    }
                                    $liste_numeros[] = array(
                                        'id_ligne'=>$ligne->getId(),
                                        'num'=>$numero
                                    );
                                }
                            }
                        }
                    rsort($liste_numeros);
                    return new JsonResponse(json_encode($liste_numeros));

            }elseif ($this->isGranted('ROLE_ADMIN')){
                $lignes = $registry->getRepository(Lignepagelje::class)->findBy(['tronconnee'=>false]);

                foreach ($lignes as $ligne){
                    $numero = "";
                    if ($ligne->getCodeTypeDoc()->getId() == 2){
                        $numero = $ligne->getNumeroArbre().$ligne->getLettre()." [".$registry->getRepository(Pagebrh::class)->find($ligne->getCodeFeuillet())->getCodeDocbrh()->getCodeReprise()->getCodeAttribution()->getCodeForet()->getDenomination()."]";
                    }
                    $liste_numeros[] = array(
                        'id_ligne'=>$ligne->getId(),
                        'num'=>$numero
                    );
                }

                rsort($liste_numeros);
                return new JsonResponse(json_encode($liste_numeros));


            }  else {
                return $this->redirectToRoute('app_no_permission_user_active');
            }
        }
    }

    #[Route('/snvlt/numlje/dispo/info/{id_bille}', name:'lettres_bille_lje')]
    public function lettres_bille_lje(
        ManagerRegistry $registry,
        Request $request,
        int $id_bille,
        MenuRepository $menus,
        MenuPermissionRepository $permissions,
        GroupeRepository $groupeRepository,
        UserRepository $userRepository,
        User $user = null,
        NotificationRepository $notifications
    ){
        if(!$request->getSession()->has('user_session')){
            return $this->redirectToRoute('app_login');
        } else {
            if ($this->isGranted('ROLE_INDUSTRIEL')  or $this->isGranted('ROLE_ADMIN') ){

                $infos_bille = array();

                $bille = $registry->getRepository(Lignepagelje::class)->find($id_bille);

                if ($bille){
                    if ($bille->getLongeuraBillecp() && !$bille->isAUtlise()){
                        $infos_bille[] = array(
                            'lettre'=>'A'
                        );
                    }
                    if ($bille->getLongeurbBillecp() && !$bille->isBUtilise()){
                        $infos_bille[] = array(
                            'lettre'=>'B'
                        );
                    }
                    if ($bille->getLongeurcBillecp() && !$bille->isCUtilise()){
                        $infos_bille[] = array(
                            'lettre'=>'C'
                        );
                    }

                    return new JsonResponse(json_encode($infos_bille));

                }
                else {
                    return new JsonResponse(json_encode(false));
                }


            } else {
                return $this->redirectToRoute('app_no_permission_user_active');
            }
        }
    }

}
