<?php

namespace App\Controller\DocStats\Entetes;

use App\Entity\Autorisation\Attribution;
use App\Entity\Autorisation\AttributionPv;
use App\Entity\Autorisation\AutorisationPv;
use App\Entity\Autorisation\Reprise;
use App\Entity\DocStats\Entetes\Documentbcbp;
use App\Entity\DocStats\Pages\Pagebcbp;
use App\Entity\DocStats\Saisie\Lignepagebcbp;
use App\Entity\References\Cantonnement;
use App\Entity\References\Foret;
use App\Entity\User;
use App\Repository\Administration\NotificationRepository;
use App\Repository\DocStats\Entetes\DocumentbcbpRepository;
use App\Repository\DocStats\Pages\PagebcbpRepository;
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

class DocumentbcbpController extends AbstractController
{

    public function __construct(private ManagerRegistry $m)
    {
    }

    #[Route('/doc/stats/entetes/docbcbp', name: 'app_op_docbcbp')]
    public function index(
        Request $request,
        MenuRepository $menus,
        MenuPermissionRepository $permissions,
        GroupeRepository $groupeRepository,
        UserRepository $userRepository,
        User $user = null,
        NotificationRepository $notification,
        DocumentbcbpRepository $docs_bcbp,
        ManagerRegistry $registry
    ): Response
    {
        if(!$request->getSession()->has('user_session')){
            return $this->redirectToRoute('app_login');
        } else {
            if ($this->isGranted('ROLE_EXPLOITANT') or  $this->isGranted('ROLE_ADMINISTRATIF') or $this->isGranted('ROLE_MINEF') or $this->isGranted('ROLE_ADMIN')  )
            {
                $user = $userRepository->find($this->getUser());
                $code_groupe = $user->getCodeGroupe()->getId();

                return $this->render('doc_stats/entetes/documentbcbp/index.html.twig', [
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

    #[Route('/snvlt/docbcbp/bcbp/pages/{id_bcbp}', name: 'affichage_bcbp_json')]
    public function affiche_bcbp(
        Request $request,
        int $id_bcbp,
        MenuRepository $menus,
        MenuPermissionRepository $permissions,
        GroupeRepository $groupeRepository,
        UserRepository $userRepository,
        User $user = null,
        NotificationRepository $notification,
        DocumentbcbpRepository $docs_bcbp,
        ManagerRegistry $registry
    ): Response
    {
        if(!$request->getSession()->has('user_session')){
            return $this->redirectToRoute('app_login');
        } else {
            if ($this->isGranted('ROLE_EXPLOITANT') or  $this->isGranted('ROLE_INDUSTRIEL') or $this->isGranted('ROLE_EXPORTATEUR'))
            {
                $user = $userRepository->find($this->getUser());
                $code_groupe = $user->getCodeGroupe()->getId();

                $numerodoc = "";

                $documentbcbp = $registry->getRepository(Documentbcbp::class)->find($id_bcbp);
                if($documentbcbp){$numerodoc = $documentbcbp->getNumeroDocbcbp();}

                return $this->render('doc_stats/entetes/documentbcbp/affiche_bcbp.html.twig', [
                    'document_name'=>$documentbcbp,
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

    #[Route('/snvlt/docbcbp/op', name: 'app_docs_bcbp_json')]
    public function my_doc_bcbp(
        Request $request,
        MenuRepository $menus,
        MenuPermissionRepository $permissions,
        GroupeRepository $groupeRepository,
        UserRepository $userRepository,
        User $user = null,
        NotificationRepository $notification,
        DocumentbcbpRepository $docs_bcbp,
        ManagerRegistry $registry
    ): Response
    {
        if(!$request->getSession()->has('user_session')){
            return $this->redirectToRoute('app_login');
        } else {
            if ($this->isGranted('ROLE_EXPLOITANT') or  $this->isGranted('ROLE_ADMINISTRATIF') or $this->isGranted('ROLE_MINEF') or $this->isGranted('ROLE_ADMIN')  )
            {
                $user = $userRepository->find($this->getUser());
                $code_groupe = $user->getCodeGroupe()->getId();

                $mes_docs_bcbp = array();
                //------------------------- Filtre les bcbp par type Opérateur ------------------------------------- //

                //------------------------- Filtre les BCBP ADMIN ------------------------------------- //
                if($user->getCodeGroupe()->getId() == 1){
                    $documents_bcbp = $registry->getRepository(Documentbcbp::class)->findAll();





                    foreach ($documents_bcbp as $document_bcbp){

                        if ($document_bcbp->getCodeAutorisationPv()->getCodeAttributionPv()->getCodeParcelle()->getCodeCantonnement()){
                            $canton = $document_bcbp->getCodeAutorisationPv()->getCodeAttributionPv()->getCodeParcelle()->getCodeCantonnement()->getNomCantonnement();
                            $d = $document_bcbp->getCodeAutorisationPv()->getCodeAttributionPv()->getCodeParcelle()->getCodeCantonnement()->getCodeDr()->getDenomination();
                        }else {
                            $canton = "-";
                            $d = "-";
                        }

                        $mes_docs_bcbp[] = array(
                            'id_document_bcbp'=>$document_bcbp->getId(),
                            'numero_docbcbp'=>$document_bcbp->getNumeroDocbcbp(),
                            'parcelle'=>$document_bcbp->getCodeAutorisationPv()->getCodeAttributionPv()->getCodeParcelle()->getDenomination(),
                            'cantonnement'=>$canton,
                            'dr'=>$d,
                            'date_delivrance'=>$document_bcbp->getDelivreDocbcbp()->format("d m Y"),
                            'etat'=>$document_bcbp->isEtat(),
                            'exploitant'=>$document_bcbp->getCodeAutorisationPv()->getCodeExploitant()->getRaisonSocialeExploitant(),
                            'code_exploitant'=>$document_bcbp->getCodeAutorisationPv()->getCodeExploitant()->getNumeroExploitant(),
                            'attributaire'=>$document_bcbp->getCodeAutorisationPv()->getCodeAttributionPv()->getRaisonSociale(),
                            'volume_bcbp'=>$this->getVolumebcbp($document_bcbp)
                        );
                    }
                    //------------------------- Filtre les BCBP DR ------------------------------------- //
                } else {
                    if ($user->getCodeDr()){
                        //dd($user->getCodeDr());
                        $cantonnements = $registry->getRepository(Cantonnement::class)->findBy(['code_dr'=>$user->getCodeDr()]);
                        foreach ($cantonnements as $cantonnement){
                            $parcelles = $registry->getRepository(Foret::class)->findBy(['code_cantonnement'=>$cantonnement, 'code_type_foret'=>3]);

                            foreach ($parcelles as $parcelle){
                                $attributions = $registry->getRepository(AttributionPv::class)->findBy(['code_parcelle'=>$parcelle]);
                                foreach ($attributions as $attribution){
                                    $reprises = $registry->getRepository(AutorisationPv::class)->findBy(['code_attribution_pv'=>$attribution]);
                                    foreach ($reprises as $reprise){
                                        $documents_bcbp = $registry->getRepository(Documentbcbp::class)->findBy(['code_autorisation_pv'=>$reprise]);
                                        foreach ($documents_bcbp as $document_bcbp){
                                            if ($document_bcbp->getCodeAutorisationPv()->getCodeAttributionPv()->getCodeParcelle()->getCodeCantonnement()){
                                                $canton = $document_bcbp->getCodeAutorisationPv()->getCodeAttributionPv()->getCodeParcelle()->getCodeCantonnement()->getNomCantonnement();
                                                $d = $document_bcbp->getCodeAutorisationPv()->getCodeAttributionPv()->getCodeParcelle()->getCodeCantonnement()->getCodeDr()->getDenomination();
                                            }else {
                                                $canton = "-";
                                                $d = "-";
                                            }
                                            $mes_docs_bcbp[] = array(
                                                'id_document_bcbp'=>$document_bcbp->getId(),
                                                'numero_docbcbp'=>$document_bcbp->getNumeroDocbcbp(),
                                                'parcelle'=>$document_bcbp->getCodeAutorisationPv()->getCodeAttributionPv()->getCodeParcelle()->getDenomination(),
                                                'cantonnement'=>$document_bcbp->getCodeAutorisationPv()->getCodeAttributionPv()->getCodeParcelle()->getCodeCantonnement()->getNomCantonnement(),
                                                'dr'=>$document_bcbp->getCodeAutorisationPv()->getCodeAttributionPv()->getCodeParcelle()->getCodeCantonnement()->getCodeDr()->getDenomination(),
                                                'date_delivrance'=>$document_bcbp->getDelivreDocbcbp()->format("d m Y"),
                                                'etat'=>$document_bcbp->isEtat(),
                                                'exploitant'=>$document_bcbp->getCodeAutorisationPv()->getCodeExploitant()->getRaisonSocialeExploitant(),
                                                'code_exploitant'=>$document_bcbp->getCodeAutorisationPv()->getCodeExploitant()->getNumeroExploitant(),
                                                'attributaire'=>$document_bcbp->getCodeAutorisationPv()->getCodeAttributionPv()->getRaisonSociale(),
                                                'volume_bcbp'=>$this->getVolumebcbp($document_bcbp)
                                            );
                                        }

                                    }
                                }
                            }
                        }

                        //------------------------- Filtre les BCBP DD ------------------------------------- //
                    } elseif ($user->getCodeDdef()){
                        //dd($user->getCodeDr());
                        $cantonnements = $registry->getRepository(Cantonnement::class)->findBy(['code_ddef'=>$user->getCodeDdef()]);
                        foreach ($cantonnements as $cantonnement){
                            $parcelles = $registry->getRepository(Foret::class)->findBy(['code_cantonnement'=>$cantonnement, 'code_type_foret'=>3]);

                            foreach ($parcelles as $parcelle){
                                $attributions = $registry->getRepository(AttributionPv::class)->findBy(['code_parcelle'=>$parcelle]);
                                foreach ($attributions as $attribution){
                                    $reprises = $registry->getRepository(AutorisationPv::class)->findBy(['code_attribution_pv'=>$attribution]);
                                    foreach ($reprises as $reprise){
                                        $documents_bcbp = $registry->getRepository(Documentbcbp::class)->findBy(['code_autorisation_pv'=>$reprise]);
                                        foreach ($documents_bcbp as $document_bcbp){
                                            if ($document_bcbp->getCodeAutorisationPv()->getCodeAttributionPv()->getCodeParcelle()->getCodeCantonnement()){
                                                $canton = $document_bcbp->getCodeAutorisationPv()->getCodeAttributionPv()->getCodeParcelle()->getCodeCantonnement()->getNomCantonnement();
                                                $d = $document_bcbp->getCodeAutorisationPv()->getCodeAttributionPv()->getCodeParcelle()->getCodeCantonnement()->getCodeDr()->getDenomination();
                                            }else {
                                                $canton = "-";
                                                $d = "-";
                                            }
                                            $mes_docs_bcbp[] = array(
                                                'id_document_bcbp'=>$document_bcbp->getId(),
                                                'numero_docbcbp'=>$document_bcbp->getNumeroDocbcbp(),
                                                'parcelle'=>$document_bcbp->getCodeAutorisationPv()->getCodeAttributionPv()->getCodeParcelle()->getDenomination(),
                                                'cantonnement'=>$document_bcbp->getCodeAutorisationPv()->getCodeAttributionPv()->getCodeParcelle()->getCodeCantonnement()->getNomCantonnement(),
                                                'dr'=>$document_bcbp->getCodeAutorisationPv()->getCodeAttributionPv()->getCodeParcelle()->getCodeCantonnement()->getCodeDr()->getDenomination(),
                                                'date_delivrance'=>$document_bcbp->getDelivreDocbcbp()->format("d m Y"),
                                                'etat'=>$document_bcbp->isEtat(),
                                                'exploitant'=>$document_bcbp->getCodeAutorisationPv()->getCodeExploitant()->getRaisonSocialeExploitant(),
                                                'code_exploitant'=>$document_bcbp->getCodeAutorisationPv()->getCodeExploitant()->getNumeroExploitant(),
                                                'attributaire'=>$document_bcbp->getCodeAutorisationPv()->getCodeAttributionPv()->getRaisonSociale(),
                                                'volume_bcbp'=>$this->getVolumebcbp($document_bcbp)
                                            );
                                        }

                                    }
                                }
                            }
                        }

                        //------------------------- Filtre les BCBP CANTONNEMENT ------------------------------------- //
                    } elseif ($user->getCodeCantonnement()){
                        $parcelles = $registry->getRepository(Foret::class)->findBy(['code_cantonnement'=>$user->getCodeCantonnement(), 'code_type_foret'=>3]);

                        foreach ($parcelles as $parcelle){
                            $attributions = $registry->getRepository(AttributionPv::class)->findBy(['code_parcelle'=>$parcelle]);
                            foreach ($attributions as $attribution){
                                $reprises = $registry->getRepository(AutorisationPv::class)->findBy(['code_attribution_pv'=>$attribution]);
                                foreach ($reprises as $reprise){
                                    $documents_bcbp = $registry->getRepository(Documentbcbp::class)->findBy(['code_autorisation_pv'=>$reprise]);
                                    foreach ($documents_bcbp as $document_bcbp){
                                        if ($document_bcbp->getCodeAutorisationPv()->getCodeAttributionPv()->getCodeParcelle()->getCodeCantonnement()){
                                            $canton = $document_bcbp->getCodeAutorisationPv()->getCodeAttributionPv()->getCodeParcelle()->getCodeCantonnement()->getNomCantonnement();
                                            $d = $document_bcbp->getCodeAutorisationPv()->getCodeAttributionPv()->getCodeParcelle()->getCodeCantonnement()->getCodeDr()->getDenomination();
                                        }else {
                                            $canton = "-";
                                            $d = "-";
                                        }
                                        $mes_docs_bcbp[] = array(
                                            'id_document_bcbp'=>$document_bcbp->getId(),
                                            'numero_docbcbp'=>$document_bcbp->getNumeroDocbcbp(),
                                            'parcelle'=>$document_bcbp->getCodeAutorisationPv()->getCodeAttributionPv()->getCodeParcelle()->getDenomination(),
                                            'cantonnement'=>$document_bcbp->getCodeAutorisationPv()->getCodeAttributionPv()->getCodeParcelle()->getCodeCantonnement()->getNomCantonnement(),
                                            'dr'=>$document_bcbp->getCodeAutorisationPv()->getCodeAttributionPv()->getCodeParcelle()->getCodeCantonnement()->getCodeDr()->getDenomination(),
                                            'date_delivrance'=>$document_bcbp->getDelivreDocbcbp()->format("d m Y"),
                                            'etat'=>$document_bcbp->isEtat(),
                                            'exploitant'=>$document_bcbp->getCodeAutorisationPv()->getCodeExploitant()->getRaisonSocialeExploitant(),
                                            'code_exploitant'=>$document_bcbp->getCodeAutorisationPv()->getCodeExploitant()->getNumeroExploitant(),
                                            'attributaire'=>$document_bcbp->getCodeAutorisationPv()->getCodeAttributionPv()->getRaisonSociale(),
                                            'volume_bcbp'=>$this->getVolumebcbp($document_bcbp)
                                        );
                                    }

                                }
                            }
                        }

                        //------------------------- Filtre les BCBP POSTE CONTROLE ------------------------------------- //
                    } elseif ($user->getCodePosteControle()){
                        $parcelles = $registry->getRepository(Foret::class)->findBy(['code_cantonnement'=>$user->getCodePosteControle()->getCodeCantonnement(), 'code_type_foret'=>3]);

                        foreach ($parcelles as $parcelle){
                            $attributions = $registry->getRepository(AttributionPv::class)->findBy(['code_parcelle'=>$parcelle]);
                            foreach ($attributions as $attribution){
                                $reprises = $registry->getRepository(AutorisationPv::class)->findBy(['code_attribution_pv'=>$attribution]);
                                foreach ($reprises as $reprise){
                                    $documents_bcbp = $registry->getRepository(Documentbcbp::class)->findBy(['code_autorisation_pv'=>$reprise]);
                                    foreach ($documents_bcbp as $document_bcbp){
                                        if ($document_bcbp->getCodeAutorisationPv()->getCodeAttributionPv()->getCodeParcelle()->getCodeCantonnement()){
                                            $canton = $document_bcbp->getCodeAutorisationPv()->getCodeAttributionPv()->getCodeParcelle()->getCodeCantonnement()->getNomCantonnement();
                                            $d = $document_bcbp->getCodeAutorisationPv()->getCodeAttributionPv()->getCodeParcelle()->getCodeCantonnement()->getCodeDr()->getDenomination();
                                        }else {
                                            $canton = "-";
                                            $d = "-";
                                        }
                                        $mes_docs_bcbp[] = array(
                                            'id_document_bcbp'=>$document_bcbp->getId(),
                                            'numero_docbcbp'=>$document_bcbp->getNumeroDocbcbp(),
                                            'parcelle'=>$document_bcbp->getCodeAutorisationPv()->getCodeAttributionPv()->getCodeParcelle()->getDenomination(),
                                            'cantonnement'=>$document_bcbp->getCodeAutorisationPv()->getCodeAttributionPv()->getCodeParcelle()->getCodeCantonnement()->getNomCantonnement(),
                                            'dr'=>$document_bcbp->getCodeAutorisationPv()->getCodeAttributionPv()->getCodeParcelle()->getCodeCantonnement()->getCodeDr()->getDenomination(),
                                            'date_delivrance'=>$document_bcbp->getDelivreDocbcbp()->format("d m Y"),
                                            'etat'=>$document_bcbp->isEtat(),
                                            'exploitant'=>$document_bcbp->getCodeAutorisationPv()->getCodeExploitant()->getRaisonSocialeExploitant(),
                                            'code_exploitant'=>$document_bcbp->getCodeAutorisationPv()->getCodeExploitant()->getNumeroExploitant(),
                                            'attributaire'=>$document_bcbp->getCodeAutorisationPv()->getCodeAttributionPv()->getRaisonSociale(),
                                            'volume_bcbp'=>$this->getVolumebcbp($document_bcbp)
                                        );
                                    }

                                }
                            }
                        }
                        //------------------------- Filtre les BCBP EXPLOITANT------------------------------------- //
                    } elseif ($user->getCodeexploitant()){

                            $reprises = $registry->getRepository(AutorisationPv::class)->findBy(['code_exploitant'=>$user->getCodeexploitant()]);
                            foreach ($reprises as $reprise){
                                $documents_bcbp = $registry->getRepository(Documentbcbp::class)->findBy(['code_autorisation_pv'=>$reprise, 'signature_dr'=>true, 'signature_cef'=>true],['created_at'=>'DESC']);
                                foreach ($documents_bcbp as $document_bcbp){
                                    if ($document_bcbp->getCodeAutorisationPv()->getCodeAttributionPv()->getCodeParcelle()->getCodeCantonnement()){
                                        $canton = $document_bcbp->getCodeAutorisationPv()->getCodeAttributionPv()->getCodeParcelle()->getCodeCantonnement()->getNomCantonnement();
                                        $d = $document_bcbp->getCodeAutorisationPv()->getCodeAttributionPv()->getCodeParcelle()->getCodeCantonnement()->getCodeDr()->getDenomination();
                                    }else {
                                        $canton = "-";
                                        $d = "-";
                                    }
                                    $mes_docs_bcbp[] = array(
                                        'id_document_bcbp'=>$document_bcbp->getId(),
                                        'numero_docbcbp'=>$document_bcbp->getNumeroDocbcbp(),
                                        'parcelle'=>$document_bcbp->getCodeAutorisationPv()->getCodeAttributionPv()->getCodeParcelle()->getDenomination(),
                                        'cantonnement'=>$document_bcbp->getCodeAutorisationPv()->getCodeAttributionPv()->getCodeParcelle()->getCodeCantonnement()->getNomCantonnement(),
                                        'dr'=>$document_bcbp->getCodeAutorisationPv()->getCodeAttributionPv()->getCodeParcelle()->getCodeCantonnement()->getCodeDr()->getDenomination(),
                                        'date_delivrance'=>$document_bcbp->getDelivreDocbcbp()->format("d m Y"),
                                        'etat'=>$document_bcbp->isEtat(),
                                        'exploitant'=>$document_bcbp->getCodeAutorisationPv()->getCodeExploitant()->getRaisonSocialeExploitant(),
                                        'code_exploitant'=>$document_bcbp->getCodeAutorisationPv()->getCodeExploitant()->getNumeroExploitant(),
                                        'attributaire'=>$document_bcbp->getCodeAutorisationPv()->getCodeAttributionPv()->getRaisonSociale(),
                                        'volume_bcbp'=>$this->getVolumebcbp($document_bcbp)
                                    );
                                }


                        }
                    }


                }
                return new JsonResponse(json_encode($mes_docs_bcbp));
            } else {
                return $this->redirectToRoute('app_no_permission_user_active');
            }

        }



    }

    #[Route('/snvlt/docbcbp/op/pages_bcbp/{id_bcbp}', name: 'affichage_pages_bcbp_json')]
    public function affiche_pages_bcbp(
        Request $request,
        int $id_bcbp,
        MenuRepository $menus,
        MenuPermissionRepository $permissions,
        GroupeRepository $groupeRepository,
        UserRepository $userRepository,
        User $user = null,
        NotificationRepository $notification,
        DocumentbcbpRepository $docs_bcbp,
        ManagerRegistry $registry
    ): Response
    {
        if(!$request->getSession()->has('user_session')){
            return $this->redirectToRoute('app_login');
        } else {
            if ($this->isGranted('ROLE_EXPLOITANT') or  $this->isGranted('ROLE_INDUSTRIEL') or $this->isGranted('ROLE_EXPORTATEUR'))
            {
                $doc_bcbp = $docs_bcbp->find($id_bcbp);
                if($doc_bcbp){
                    $pages_bcbp = $registry->getRepository(Pagebcbp::class)->findBy(['code_docbcbp'=>$doc_bcbp], ['id'=>'ASC']);
                    $my_bcbp_pages = array();

                    foreach ($pages_bcbp as $page){
                        $my_bcbp_pages[] = array(
                            'id_page'=>$page->getId(),
                            'numero_page'=>$page->getNumeroPagebcbp()
                        );
                    }
                    return  new JsonResponse(json_encode($my_bcbp_pages));
                }
            } else {
                return $this->redirectToRoute('app_no_permission_user_active');
            }

        }
    }

    #[Route('/snvlt/docbcbp/op/pages_bcbp/data/{id_page}', name: 'affichage_page_data_bcbp_json')]
    public function affiche_page_courante(
        Request $request,
        int $id_page,
        MenuRepository $menus,
        MenuPermissionRepository $permissions,
        GroupeRepository $groupeRepository,
        UserRepository $userRepository,
        User $user = null,
        NotificationRepository $notification,
        PagebcbpRepository $pages_bcbp,
        ManagerRegistry $registry
    ): Response
    {
        if(!$request->getSession()->has('user_session')){
            return $this->redirectToRoute('app_login');
        } else {
            if ($this->isGranted('ROLE_EXPLOITANT') or  $this->isGranted('ROLE_INDUSTRIEL') or $this->isGranted('ROLE_EXPORTATEUR'))
            {
                $page_bcbp = $pages_bcbp->find($id_page);
                if($page_bcbp){
                    $my_bcbp_page = array();
                    $my_bcbp_page[] = array(
                        'id_page'=>$page_bcbp->getId(),
                        'numero_page'=>$page_bcbp->getNumeroPagebcbp(),
                        'date_chargement'=>$page_bcbp->getDateChargement()->format("d m Y"),
                        'destination'=>$page_bcbp->getDestination(),
                        'parc_usine'=>$page_bcbp->getParcUsine()->getId(),
                        'transporteur'=>$page_bcbp->getTransporteur(),
                        'cout'=>$page_bcbp->getCout(),
                        'essence'=>$page_bcbp->getEssence()
                    );

                    return  new JsonResponse(json_encode($my_bcbp_page));
                }
            } else {
                return $this->redirectToRoute('app_no_permission_user_active');
            }

        }
    }

    #[Route('/snvlt/docbcbp/op/lignes_bcbp/data/{id_page}', name: 'affichage_ligne_bcbp_data_bcbp_json')]
    public function affiche_lignes_bcbp_courante(
        Request $request,
        int $id_page,
        MenuRepository $menus,
        MenuPermissionRepository $permissions,
        GroupeRepository $groupeRepository,
        UserRepository $userRepository,
        User $user = null,
        NotificationRepository $notification,
        PagebcbpRepository $pages_bcbp,
        ManagerRegistry $registry
    ): Response
    {
        if(!$request->getSession()->has('user_session')){
            return $this->redirectToRoute('app_login');
        } else {
            if ($this->isGranted('ROLE_EXPLOITANT') or  $this->isGranted('ROLE_INDUSTRIEL') or $this->isGranted('ROLE_EXPORTATEUR'))
            {
                $page_bcbp = $pages_bcbp->find($id_page);
                if($page_bcbp){
                    $lignes_bcbp = $registry->getRepository(Lignepagebcbp::class)->findBy(['code_pagebcbp'=>$page_bcbp]);
                    $my_bcbp_page = array();
                    foreach ($lignes_bcbp as $lignebcbp){
                        $my_bcbp_page[] = array(
                            'id_ligne'=>$lignebcbp->getId(),
                            'numero_ligne'=>$lignebcbp->getNumeroLignepagebcbp(),
                            'essence'=>$lignebcbp->getNomEssencebcbp()->getNomVernaculaire(),
                            'x_bcbp'=>$lignebcbp->getXLignepagebcbp(),
                            'y_bcbp'=>$lignebcbp->getYLignepagebcbp(),
                            'zh_bcbp'=>$lignebcbp->getZhLignepagebcbp()->getZone(),
                            'lng_bcbp'=>$lignebcbp->getLongeurLignepagebcbp(),
                            'dm_bcbp'=>$lignebcbp->getDiametreLignepagebcbp(),
                            'cubage_bcbp'=>$lignebcbp->getCubageLignepagebcbp()
                        );
                    }


                    return  new JsonResponse(json_encode($my_bcbp_page));
                }
            } else {
                return $this->redirectToRoute('app_no_permission_user_active');
            }

        }
    }


    function getVolumebcbp(Documentbcbp $documentbcbp):float
    {
        $volumebcbp = 0;
        if($documentbcbp){
            $pagebcbp =$this->m->getRepository(Pagebcbp::class)->findBy(['code_docbcbp'=>$documentbcbp]);
            foreach ($pagebcbp as $page){
                $lignepages = $this->m->getRepository(Lignepagebcbp::class)->findBy(['code_pagebcbp'=>$page]);
                foreach ($lignepages as $ligne){
                    $volumebcbp = $volumebcbp +  $ligne->getVolume();
                }
            }
            return $volumebcbp;
        } else {
            return $volumebcbp;
        }
    }
}
