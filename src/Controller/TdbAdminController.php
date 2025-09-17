<?php
namespace App\Controller;
ini_set('memory_limit', '256M');

use App\Controller\Services\AdministrationService;
use App\Entity\Admin\Exercice;
use App\Entity\Admin\LogSnvlt;
use App\Entity\Autorisation\AgreementExportateur;
use App\Entity\Autorisation\AgreementPs;
use App\Entity\Autorisation\Attribution;
use App\Entity\Autorisation\AttributionPv;
use App\Entity\Autorisation\AutorisationExportateur;
use App\Entity\Autorisation\AutorisationPs;
use App\Entity\Autorisation\AutorisationPv;
use App\Entity\Autorisation\Reprise;
use App\Entity\DisponibiliteParcBilles;
use App\Entity\DisponibiliteParcBillons;
use App\Entity\DocStats\Entetes\Documentbrh;
use App\Entity\DocStats\Entetes\Documentcp;
use App\Entity\DocStats\Entetes\Documentlje;
use App\Entity\DocStats\Pages\Pagebrh;
use App\Entity\DocStats\Pages\Pagecp;
use App\Entity\DocStats\Pages\Pagelje;
use App\Entity\DocStats\Saisie\Lignepagebrh;
use App\Entity\DocStats\Saisie\Lignepagecp;
use App\Entity\DocStats\Saisie\Lignepagelje;
use App\Entity\Observateur\PublicationRapport;
use App\Entity\Observateur\Ticket;
use App\Entity\References\Cantonnement;
use App\Entity\References\Dr;
use App\Entity\References\Essence;
use App\Entity\References\Exploitant;
use App\Entity\References\Foret;
use App\Entity\References\TypeDocumentStatistique;
use App\Entity\References\TypeOperateur;
use App\Entity\References\Usine;
use App\Entity\Requetes\PerformanceBrhJour;
use App\Entity\Transformation\Billon;
use App\Entity\User;
use App\Repository\Administration\NotificationRepository;
use App\Repository\GroupeRepository;
use App\Repository\MenuPermissionRepository;
use App\Repository\MenuRepository;
use App\Repository\UserRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

use App\Controller\DynamicQueryController;
use Doctrine\DBAL\Connection;


class TdbAdminController extends AbstractController
{   
    private $requestStack;
    private $translator;
    public function __construct(TranslatorInterface $translator, private AdministrationService $administrationService,RequestStack $requestStack)
    {
        $this->translator = $translator;
        $this->requestStack = $requestStack;
        
    }

    #[Route('/snvlt/admin', name: 'app_tdb_admin')]
    public function index(
        Request $request,
        MenuRepository $menus,
        ManagerRegistry $registry,
        MenuPermissionRepository $permissions,
        GroupeRepository $groupeRepository,
        UserRepository $userRepository,
        User $user = null,
        NotificationRepository $notification,
        DynamicQueryController $dynamicQueryController,
        Connection $connection
    ): Response
    {   
        //$request = new Request(['exercice'=>2024]);
        $request = $this->requestStack->getCurrentRequest();

        // Ajoutez des paramètres à la requête
        $request->query->set('exercice','2024');

        $response_exp = $dynamicQueryController->getDataDynamickly('total_exploitation', $request, $connection);
        $response_exp = json_decode($response_exp->getContent(),true)[0];

        $response_trans = $dynamicQueryController->getDataDynamickly('volume_transformation', $request, $connection);
        $response_trans = json_decode($response_trans->getContent(),true)[0];




        if(!$request->getSession()->has('user_session')){
            return $this->redirectToRoute('app_login');
        } else {
            $user = $userRepository->find($this->getUser());
            $request->getSession()->set('id_session', $user->getId());
        }
        if (!$user->isVerified()){
            return $this->render('exceptions/non_verifie.html.twig');
        } elseif (!$user->getActif()){
            return $this->redirectToRoute('user_not_active');
        }
        $exo = $request->getSession()->get("exercice");

        $exercice = $registry->getRepository(Exercice::class)->find($exo);

        //dd($exo);

        $code_groupe = $user->getCodeGroupe()->getId();
        //$logsnvlt = $registry->getRepository(LogSnvlt::class)->findBy([], ['created_at'=>'DESC'], 20, 0);

        $page_brh = $registry->getRepository(Pagebrh::class)->findBy(['fini' => true]);

        //Déclarations
        $liste_essences_exploitees = array();
        $liste_essences_admin = array();

        $liste_chargements = array();
        $chargements_cef = array();
        $liste_essences_vol = array();
        $liste_doc_brh= array();
        $liste_quotas= array();
        $point_dr = array();
        $quotas_dr = array();
        $volume_foret = array();
        $essence_disponibles = array();
        $billons_disponibles = array();
        $liste_chr_industriel = array();
        $liste_billes_industriel = array();
        $reprises_count = 0;
        $nb_brh_op = 0;
        $nb_cp_op = 0;
        $nb_bcbp_op = 0;
        $nb_etatb_op = 0;
        //$nb_users = 0;
        //$nb_autorisations = 0;
        //$total_exploitation = 0;
        //$total_transformation= 0;
        $total_agreements = 0;
        $total_autorisations = 0;
        $arbres_abattus = 0;
        $volume_brh = 0;
        $nb_utilisateurs = 0;



// Interfaces Admin
        if ($user->getCodeGroupe()->getId() == 1){
            //Nombre d'utilisateurs

            $nb_users = $registry->getRepository(User::class)->count([]);

            //Nombre d'autorisations

            $nb_reprises = $registry->getRepository(Reprise::class)->count(['exercice' => $exercice]);
            $nb_autops = $registry->getRepository(AutorisationPs::class)->count(['exercice'=>$exercice]);
            $nb_autopv = $registry->getRepository(AutorisationPv::class)->count(['exercice'=>$exercice]);
            $nb_autorisations = $nb_reprises+$nb_autops+$nb_autopv;

            //Exploitation en m3

            $total_exploitation= intval($response_exp['lignepagebrh_cubage_lignepagebrh']);


            //Transformation en m3

            $total_transformation= intval($response_trans['billon_volume']);


            // Chargements en cours
            $chargements = $registry->getRepository(Pagebrh::class)->findBy(
                [
                    'fini'=>true,
                    'exercice'=>$exercice,
                    'entre_lje'=>false
                ],
                [
                    'date_chargementbrh'=>'DESC'
                ]
            );

            
            foreach ($chargements as $chargement){

                $volume_chargement = 0;
                $ligne_pagebrh = $registry->getRepository(Lignepagebrh::class)->findBy(['code_pagebrh'=>$chargement]);

                foreach ($ligne_pagebrh as $ligne){
                    $volume_chargement = $volume_chargement + $ligne->getCubageLignepagebrh();
                }


                $destinataire = '-';
                if ($chargement->getParcUsineBrh()){
                    if ($chargement->getParcUsineBrh()->getSigle()){
                        $destinataire = $chargement->getParcUsineBrh()->getSigle();
                    } else {
                        $destinataire = $chargement->getParcUsineBrh()->getRaisonSocialeUsine();
                    }
                }

                $liste_chargements[] = array(
                    'date_chargement'=>$chargement->getDateChargementbrh()->format('d/m/Y'),
                    'numero_bille'=>$chargement->getNumeroPagebrh(),
                    'volume'=>round($volume_chargement, 3),
                    'expediteur'=>$chargement->getCodeDocbrh()->getCodeReprise()->getCodeAttribution()->getCodeExploitant()->getSigle(),
                    'immatriculation'=>$chargement->getImmatcamion(),
                    'destinataire'=>$destinataire
                );
            }

            rsort($liste_chargements);


            //Agreements et Autorisations


            // Agreement
            $pef = 0;
            $pv = 0;
            $ps = 0;
            $export = 0;
            foreach ($registry->getRepository(Attribution::class)->findBy(['statut'=>true, 'exercice'=>$exercice]) as $att){
                $pef = $pef + 1;
            }
            foreach ($registry->getRepository(AttributionPv::class)->findAll() as $att){
                $pv = $pv + 1;
            }
            foreach ($registry->getRepository(AgreementPs::class)->findAll() as $att){
                $ps = $ps + 1;
            }
            foreach ($registry->getRepository(AgreementExportateur::class)->findAll() as $att){
                $export = $export + 1;
            }
            $total_agreements  =$pef + $pv + $ps + $export;




            // Autorisations
            $pef_auto = 0;
            $pv_auto = 0;
            $ps_auto = 0;
            $export_auto = 0;
            foreach ($registry->getRepository(Reprise::class)->findBy(['statut'=>true,'exercice'=>$exercice]) as $att){
                $pef_auto = $pef_auto + 1;
            }
            foreach ($registry->getRepository(AutorisationPv::class)->findBy(['exercice'=>$exercice]) as $att){
                $pv_auto = $pv_auto + 1;
            }
            foreach ($registry->getRepository(AutorisationPs::class)->findBy(['exercice'=>$exercice]) as $att){
                $ps_auto = $ps_auto + 1;
            }
            foreach ($registry->getRepository(AutorisationExportateur::class)->findBy(['exercice'=>$exercice]) as $att){
                $export_auto = $export_auto + 1;
            }
            $total_autorisations  =$pef_auto + $pv_auto + $ps_auto + $export_auto;

            // essences et cubages
            $liste_essences = $registry->getRepository(Essence::class)->findAll();

            foreach($liste_essences as $ess){
                $linge_brh_essences_admin = $registry->getRepository(Lignepagebrh::class)->findBy(['nom_essencebrh'=>$ess, 'exercice'=>$exercice]);
                $vol_ess_brh = 0;
                foreach($linge_brh_essences_admin as $essence_brh){
                    $vol_ess_brh = $vol_ess_brh + $essence_brh->getCubageLignepagebrh();
                }
                $liste_essences_admin[] = array(
                    'volume'=>$vol_ess_brh,
                    'essence'=>$ess->getNomVernaculaire()
                );
            }
            arsort($liste_essences_admin);







        }

// Interface DR
        if ($user->getCodeDr()){
            $codedr = $user->getCodeDr();
            $cantonnements = $registry->getRepository(Cantonnement::class)->findBy(['code_dr' => $user->getCodeDr()]);
            foreach ($cantonnements as $cantonnement) {
                $exploitants = $registry->getRepository(Exploitant::class)->findBy(['code_cantonnement' => $cantonnement]);
                foreach ($exploitants as $exploitant) {
                    $attributions = $registry->getRepository(Attribution::class)->findBy(['code_exploitant' => $exploitant]);
                    foreach ($attributions as $attribution) {
                        $reprises = $registry->getRepository(Reprise::class)->findBy(['code_attribution' => $attribution, 'exercice'=>$exercice]);
                        foreach ($reprises as $reprise) {
                            $docbrh = $registry->getRepository(Documentbrh::class)->findBy(['code_reprise' => $reprise]);
                            foreach ($docbrh as $doc) {
                                $page_brh = $registry->getRepository(Pagebrh::class)->findBy(['code_docbrh' => $doc, 'fini' => true]);
                            }
                        }
                    }
                }
            }


            //Point Opérateurs

            $cantons = $registry->getRepository(Cantonnement::class)->findBy(['code_dr'=>$codedr]);
            foreach ($cantons as $canton ){
                $forets = $canton->getForets();
                foreach ($forets as $foret){
                    $exploitant = "-";
                    $nb_cp = 0;
                    $nb_brh = 0;
                    $arbre_abattus = 0;
                    $volume_abattage = 0;
                    $volume_brh = 0;
                    $decision_reprise = "-";
                    $decision_attribution = "-";

                    $attributions = $registry->getRepository(Attribution::class)->findBy(['code_foret'=>$foret, 'statut'=>true]);
                    foreach($attributions as $attribution){

                        if ($attribution->getCodeExploitant()->getSigle()){
                            $exploitant = $attribution->getCodeExploitant()->getSigle();
                        } else {
                            $exploitant = $attribution->getCodeExploitant()->getRaisonSocialeExploitant();
                        }

                        $decision_attribution = $attribution->getNumeroDecision(). " du ". $attribution->getDateDecision()->format('d/m/Y');
                        $rep = $registry->getRepository(Reprise::class)->findOneBy(['code_attribution'=>$attribution, 'exercice'=>$exercice]);

                        if ($rep){
                            $decision_reprise = $rep->getNumeroAutorisation(). " du ". $rep->getDateAutorisation()->format('d/m/Y');
                        }

                        $doc_cps = $registry->getRepository(Documentcp::class)->findBy(['code_reprise'=>$rep]);
                        $doc_brhs = $registry->getRepository(Documentbrh::class)->findBy(['code_reprise'=>$rep]);
                        foreach ($doc_cps as $doc_cp){
                            $nb_cp = $nb_cp + 1;
                            $arbres_abattus = $nb_cp;
                            $pagescp = $registry->getRepository(Pagecp::class)->findBy(['code_doccp'=>$doc_cp]);
                            foreach ($pagescp as $page){
                                $lignecps = $registry->getRepository(Lignepagecp::class)->findBy(['code_pagecp'=>$page]);
                                foreach($lignecps as $lignecp){
                                    $volume_abattage = $volume_abattage + $lignecp->getVolumeArbrecp();
                                    $arbre_abattus = $arbre_abattus + 1;
                                }
                            }
                        }
                        foreach ($doc_brhs as $doc_brh){
                            $nb_brh = $nb_brh + 1;
                            $pagesbrh = $registry->getRepository(Pagebrh::class)->findBy(['code_docbrh'=>$doc_brh]);
                            foreach ($pagesbrh as $page){
                                $lignebrhs = $registry->getRepository(Lignepagebrh::class)->findBy(['code_pagebrh'=>$page]);
                                foreach($lignebrhs as $lignebrh){
                                    $volume_brh = $volume_brh + $lignebrh->getCubageLignepagebrh();
                                }
                            }
                        }
                        $point_dr[] = array(
                            'foret'=>$foret->getDenomination(),
                            'cantonnement'=>$canton,
                            'exploitant'=>$exploitant,
                            'decision_attribution'=>$decision_attribution,
                            'decision_reprise'=>$decision_reprise,
                            'nb_cp'=>$nb_cp,
                            'nb_brh'=>$nb_brh,
                            'nb_arbres_abattus'=>$arbre_abattus,
                            'volume_abattage'=>round($volume_abattage,3),
                            'volume_brh'=>round($volume_brh,3)
                        );
                    }
                }
            }


            // Quotas DR

            //$codedr = $user->getCodeDr();
            //$cantonnements = $registry->getRepository(Cantonnement::class)->findBy(['code_dr' => $user->getCodeDr()]);
            foreach ($cantonnements as $cantonnement) {
                $forets = $registry->getRepository(Foret::class)->findBy(['code_cantonnement' => $cantonnement]);
                foreach ($forets as $foret) {
                    $attributions = $registry->getRepository(Attribution::class)->findBy(['code_foret' => $foret]);
                    foreach($attributions as $attrib){
                        $reprises  = $registry->getRepository(Reprise::class)->findBy(['code_attribution'=>$attrib, 'exercice'=>$exercice]);
                        foreach($reprises as $reprise){
                            $vol_quota = 0;
                            //$reprises_count = $reprises_count +1;
                            $doccps  = $registry->getRepository(Documentcp::class)->findBy(['code_reprise'=>$reprise]);
                            foreach($doccps as $doccp){
                                //$vol = 0;

                                $pagecps  = $registry->getRepository(Pagecp::class)->findBy(['code_doccp'=>$doccp]);
                                foreach($pagecps as $pagecp){
                                    $lignepagecps  =$registry->getRepository(Lignepagecp::class)->findBy(['code_pagecp'=>$pagecp]);
                                    foreach($lignepagecps as $lignecp){
                                        $vol_quota  = $vol_quota  + $lignecp->getVolumeArbrecp();
                                        // $vol = $vol + $lignecp->getCubageLignepagebrh();
                                    }
                                }

                            }
                            $liste_quotas[] = array(
                                'foret'=>$attrib->getCodeForet()->getDenomination(),
                                'quota_val'=>round(($attrib->getCodeForet()->getSuperficie() / 4),3),
                                'cubage'=>round($vol_quota, 3)
                            );
                        }
                    }
                }
            }

            // Cubage BRH Total
            foreach ($cantonnements as $cantonnement) {
                $forets = $registry->getRepository(Foret::class)->findBy(['code_cantonnement' => $cantonnement]);
                foreach ($forets as $foret) {
                    $attributions = $registry->getRepository(Attribution::class)->findBy(['code_foret' => $foret]);
                    foreach($attributions as $attrib){
                        $reprises  = $registry->getRepository(Reprise::class)->findBy(['code_attribution'=>$attrib, 'exercice'=>$exercice]);
                        foreach($reprises as $reprise){
                            $vol_quota = 0;
                            //$reprises_count = $reprises_count +1;
                            $docbrhs  = $registry->getRepository(Documentbrh::class)->findBy(['code_reprise'=>$reprise]);
                            foreach($docbrhs as $docbrh){
                                //$vol = 0;

                                $pagebrhs  = $registry->getRepository(Pagebrh::class)->findBy(['code_docbrh'=>$docbrh]);
                                foreach($pagebrhs as $pagebrh){
                                    $lignepagebrhs  =$registry->getRepository(Lignepagebrh::class)->findBy(['code_pagebrh'=>$pagebrh]);
                                    foreach($lignepagebrhs as $lignebrh){
                                        $volume_brh = $volume_brh + $lignebrh->getCubageLignepagebrh();
                                        // $vol = $vol + $lignecp->getCubageLignepagebrh();
                                    }
                                }

                            }
                            $liste_quotas[] = array(
                                'foret'=>$attrib->getCodeForet()->getDenomination(),
                                'quota_val'=>round(($attrib->getCodeForet()->getSuperficie() / 4),3),
                                'cubage'=>round($vol_quota, 3)
                            );
                        }
                    }
                }
            }

            // Nb Agents
            foreach($user->getCodeDr()->getUsers() as $nb_dr){
                $nb_utilisateurs = $nb_utilisateurs + 1;
            }

            foreach($user->getCodeDr()->getDdefs() as $nb_ddef){
                $nb_utilisateurs = $nb_utilisateurs + $nb_ddef->getUsers()->count();
            }
            foreach($user->getCodeDr()->getCantonnements() as $nb_cef){
                $nb_utilisateurs = $nb_utilisateurs + $nb_cef->getUsers()->count();
                foreach($nb_cef->getPosteForestiers() as $nb_pf){
                    $nb_utilisateurs = $nb_utilisateurs + $nb_pf->getUsers()->count();
                }
            }
        }

// Interfaces CEF
        if ($user->getCodeCantonnement()){
            // Point Opérateurs

            $forets = $user->getCodeCantonnement()->getForets();
            foreach ($forets as $foret){
                $exploitant = "-";
                $nb_cp = 0;
                $nb_brh = 0;
                $arbre_abattus = 0;
                $volume_abattage = 0;
                $volume_brh = 0;
                $decision_reprise = "-";
                $decision_attribution = "-";

                $attributions = $registry->getRepository(Attribution::class)->findBy(['code_foret'=>$foret, 'statut'=>true]);
                foreach($attributions as $attribution){

                    if ($attribution->getCodeExploitant()->getSigle()){
                        $exploitant = $attribution->getCodeExploitant()->getSigle();
                    } else {
                        $exploitant = $attribution->getCodeExploitant()->getRaisonSocialeExploitant();
                    }

                    $decision_attribution = $attribution->getNumeroDecision(). " du ". $attribution->getDateDecision()->format('d/m/Y');
                    $rep = $registry->getRepository(Reprise::class)->findOneBy(['code_attribution'=>$attribution, 'exercice'=>$exercice]);

                    if ($rep){
                        $decision_reprise = $rep->getNumeroAutorisation(). " du ". $rep->getDateAutorisation()->format('d/m/Y');
                    }

                    $doc_cps = $registry->getRepository(Documentcp::class)->findBy(['code_reprise'=>$rep]);
                    $doc_brhs = $registry->getRepository(Documentbrh::class)->findBy(['code_reprise'=>$rep]);
                    foreach ($doc_cps as $doc_cp){
                        $nb_cp = $nb_cp + 1;
                        $arbres_abattus = $nb_cp;
                        $pagescp = $registry->getRepository(Pagecp::class)->findBy(['code_doccp'=>$doc_cp]);
                        foreach ($pagescp as $page){
                            $lignecps = $registry->getRepository(Lignepagecp::class)->findBy(['code_pagecp'=>$page]);
                            foreach($lignecps as $lignecp){
                                $volume_abattage = $volume_abattage + $lignecp->getVolumeArbrecp();
                                $arbre_abattus = $arbre_abattus + 1;
                            }
                        }
                    }
                    foreach ($doc_brhs as $doc_brh){
                        $nb_brh = $nb_brh + 1;
                        $pagesbrh = $registry->getRepository(Pagebrh::class)->findBy(['code_docbrh'=>$doc_brh]);
                        foreach ($pagesbrh as $page){
                            $lignebrhs = $registry->getRepository(Lignepagebrh::class)->findBy(['code_pagebrh'=>$page]);
                            foreach($lignebrhs as $lignebrh){
                                $volume_brh = $volume_brh + $lignebrh->getCubageLignepagebrh();
                            }
                        }
                    }

                }
                $volume_foret[] = array(
                    'foret_cef'=>$foret->getDenomination(),
                    'volume_brh'=>round($volume_brh,3)
                );
            }



            // Chargements en cours dans le CEF
            // chargement depuis mon cantonnement
            foreach ($forets as $foret){
                $attributions_cef = $registry->getRepository(Attribution::class)->findBy(['code_foret'=>$foret, 'statut'=>true]);
                foreach($attributions as $attribution){
                    $rep_cef = $registry->getRepository(Reprise::class)->findOneBy(['code_attribution'=>$attribution, 'exercice'=>$exercice]);
                    if ($rep_cef){
                        foreach ($rep_cef as $rep){

                            $docbrh_cefs = $registry->getRepository(Documentbrh::class)->findBy(['$rep_cef'=>$rep]);
                            foreach ($docbrh_cefs as $doc){

                                $page_brh_cefs = $registry->getRepository(Pagebrh::class)->findBy(
                                    [
                                        'code_docbrh'=>$doc,
//                                    'fini'=>true,
//                                    'confirmation_usine'=>false
                                    ]
                                );
                                //dd($page_brh_cefs);
                                foreach ($page_brh_cefs as $page){
                                    $ligne_cef = $registry->getRepository(Lignepagebrh::class)->findBy(['code_pagebrh'=>$page]);
                                    $vol_brh_cef = 0;
                                    $nb_billes_cef = 0;
                                    foreach ($ligne_cef as $ligne){
                                        $vol_brh_cef = $vol_brh_cef + $ligne->getCubageLignepagebrh();
                                        $nb_billes_cef = $nb_billes_cef + 1;
                                    }
                                    $destinataire = '-';
                                    $date_chr = '-';
                                    if ($page->getDateChargementbrh()){
                                        $date_chr = $page->getDateChargementbrh()->format('d/m/Y');
                                    }
                                    if ($page->getParcUsineBrh()){
                                        if ($page->getParcUsineBrh()->getSigle()){
                                            $destinataire = $page->getParcUsineBrh()->getSigle();
                                        } else {
                                            $destinataire = $page->getParcUsineBrh()->getRaisonSocialeUsine();
                                        }
                                    }

                                    $chargements_cef[] = array(
                                        'date_chargement'=>$date_chr,
                                        'numero_page'=>$page->getNumeroPagebrh(),
                                        'volume'=>round($vol_brh_cef, 3),
                                        'foret'=>$page->getCodeDocbrh()->getCodeReprise()->getCodeAttribution()->getCodeForet()->getDenomination(),
                                        'nb_billes'=>$nb_billes_cef,
                                        'expediteur'=>$page->getCodeDocbrh()->getCodeReprise()->getCodeAttribution()->getCodeExploitant()->getSigle(),
                                        'immatriculation'=>$page->getImmatcamion(),
                                        'destinataire'=>$destinataire,
                                        'mon_cef'=>1
                                    );

                                }
                            }
                        }
                    }
                }
            }

            // Chargements en partance pour mes CEF
            $usines_cef = $registry->getRepository(Usine::class)->findBy(['code_cantonnement'=>$user->getCodeCantonnement()]);
            foreach ($usines_cef as $usine){
                $toutes_les_pages = $registry->getRepository(Pagebrh::class)->findBy(
                    [
                        'parc_usine_brh'=>$usine,
                        'exercice'=>$exercice->getId()
//                        'fini'=>true,
//                        'confirmation_usine'=>false
                    ]
                );

                foreach ($toutes_les_pages as $page){
                    $ligne_cef = $registry->getRepository(Lignepagebrh::class)->findBy(['code_pagebrh'=>$page]);
                    $vol_brh_cef = 0;
                    $nb_billes_cef = 0;
                    foreach ($ligne_cef as $ligne){
                        $vol_brh_cef = $vol_brh_cef + $ligne->getCubageLignepagebrh();
                        $nb_billes_cef = $nb_billes_cef + 1;
                    }
                    $destinataire = '-';
                    $date_chr = '-';
                    if ($page->getDateChargementbrh()){
                        $date_chr = $page->getDateChargementbrh()->format('d/m/Y');
                    }
                    if ($page->getParcUsineBrh()){
                        if ($page->getParcUsineBrh()->getSigle()){
                            $destinataire = $page->getParcUsineBrh()->getSigle();
                        } else {
                            $destinataire = $page->getParcUsineBrh()->getRaisonSocialeUsine();
                        }
                    }

                    $chargements_cef[] = array(
                        'date_chargement'=>$date_chr,
                        'numero_page'=>$page->getNumeroPagebrh(),
                        'volume'=>round($vol_brh_cef, 3),
                        'nb_billes'=>$nb_billes_cef,
                        'foret'=>$page->getCodeDocbrh()->getCodeReprise()->getCodeAttribution()->getCodeForet()->getDenomination(),
                        'expediteur'=>$page->getCodeDocbrh()->getCodeReprise()->getCodeAttribution()->getCodeExploitant()->getSigle(),
                        'immatriculation'=>$page->getImmatcamion(),
                        'destinataire'=>$destinataire,
                        'mon_cef'=>0
                    );

                }
            }

        }


        // Interface Exploitant
        if ($this->isGranted('ROLE_EXPLOITANT')){
            //Quotas d'exploitation

            $attributions = $registry->getRepository(Attribution::class)->findBy(['code_exploitant'=>$user->getCodeexploitant(), 'exercice'=>$this->administrationService->getAnnee(), 'statut'=>true]);
            foreach($attributions as $attrib){
                $repris = $attrib->getReprises()->count(['exercice'=>$exercice, 'statut'=>true]);
                $reprises_count = $reprises_count + $repris;
            }
            //dd($reprises_count);
            //dd();
            foreach($attributions as $attrib){
                $reprises  = $registry->getRepository(Reprise::class)->findBy(['code_attribution'=>$attrib, 'statut'=>true]);
                foreach($reprises as $reprise){
                    $vol_quota = 0;
                    //$reprises_count = $reprises_count +1;
                    $docbrhs  = $registry->getRepository(Documentbrh::class)->findBy(['code_reprise'=>$reprise]);
                    foreach($docbrhs as $docbrh){
                        //$vol = 0;

                        $pagebrhs  = $registry->getRepository(Pagebrh::class)->findBy(['code_docbrh'=>$docbrh]);
                        foreach($pagebrhs as $pagebrh){
                            $lignepagebrhs  =$registry->getRepository(Lignepagebrh::class)->findBy(['code_pagebrh'=>$pagebrh]);

                            foreach($lignepagebrhs as $lignepagebrh){
                                $vol_quota  = $vol_quota  + $lignepagebrh->getCubageLignepagebrh();

                                // $vol = $vol + $lignecp->getCubageLignepagebrh();
                            }
                        }

                    }
                    $liste_quotas[] = array(
                        'foret'=>$attrib->getCodeForet()->getDenomination(),
                        'quota_val'=>round($attrib->getCodeForet()->getSuperficie() / 4),
                        'cubage'=>$vol_quota
                    );
                }
            }

            //Disponibilité Parc Chantier
            $attributions = $registry->getRepository(Attribution::class)->findBy(['code_exploitant'=>$user->getCodeexploitant()]);

            foreach ($attributions as $attribution){
                $reprises = $registry->getRepository(Reprise::class)->findBy(['code_attribution'=>$attribution, 'exercice'=>$exercice]);
                foreach ($reprises as $reprise){
                    $document_cp = $registry->getRepository(Documentcp::class)->findBy(['code_reprise'=>$reprise]);
                    foreach ($document_cp as $doc){
                        $pagecps = $registry->getRepository(Pagecp::class)->findBy(['code_doccp'=>$doc]);
                        foreach ($pagecps as $page){
                            $lignecps = $registry->getRepository(Lignepagecp::class)->findBy(['code_pagecp'=>$page]);
                            foreach ($lignecps as $lignecp){


                                if (!$lignecp->isAUtlise() && $lignecp->getLongeuraBillecp()){}
                                $zh = "-";
                                $essence = "-";
                                if ($lignecp->getZhArbrecp()) {
                                    $zh = $lignecp->getZhArbrecp()->getZone();
                                }
                                if ($lignecp->getNomEssencecp()) {
                                    $essence = $lignecp->getNomEssencecp()->getNomVernaculaire();
                                }
                                $liste_arbres[] = array(
                                    'id_ligne'=>$lignecp->getId(),
                                    'numero_ligne'=>$lignecp->getNumeroArbrecp(),
                                    'essence'=>$essence,
                                    'x_arbre'=>$lignecp->getXArbrecp(),
                                    'y_arbre'=>$lignecp->getYArbrecp(),
                                    'zh_arbre'=>$zh,
                                    'jour'=>$lignecp->getJourAbattage(),
                                    'lng_arbre'=>$lignecp->getLongeurArbrecp(),
                                    'dm_arbre'=>$lignecp->getDiametreArbrecp(),
                                    'cubage_arbre'=>$lignecp->getVolumeArbrecp(),
                                    'lng_billea'=>$lignecp->getLongeuraBillecp(),
                                    'dm_billea'=>$lignecp->getDiametreaBillecp(),
                                    'cubage_billea'=>$lignecp->getVolumeaBillecp(),
                                    'lng_billeb'=>$lignecp->getLongeurbBillecp(),
                                    'dm_billeb'=>$lignecp->getDiametrebBillecp(),
                                    'cubage_billeb'=>$lignecp->getVolumebBillecp(),
                                    'lng_billec'=>$lignecp->getLongeurcBillecp(),
                                    'dm_billec'=>$lignecp->getDiametrecBillecp(),
                                    'cubage_billec'=>$lignecp->getVolumecBillecp(),
                                    'a_utilise'=>$lignecp->isAUtlise(),
                                    'b_utilise'=>$lignecp->isBUtilise(),
                                    'c_utilise'=>$lignecp->isCUtilise(),
                                    'a_abandon'=>$lignecp->isAAbandon(),
                                    'b_abandon'=>$lignecp->isBAbandon(),
                                    'c_abandon'=>$lignecp->isCAbandon(),
                                    'fut_abandon'=>$lignecp->isFutAbandon(),
                                    'exploitant'=>$attribution->getCodeExploitant()->getSigle(),
                                    'cantonnement'=>$attribution->getCodeForet()->getCodeCantonnement()->getNomCantonnement(),
                                    'dr'=>$attribution->getCodeForet()->getCodeCantonnement()->getCodeDr()->getDenomination()

                                );
                            }
                        }
                    }
                }
            }


            //CP
            $attributions = $registry->getRepository(Attribution::class)->findBy(['code_exploitant'=>$user->getCodeexploitant()]);
            foreach($attributions as $attrib){
                $reprises  = $registry->getRepository(Reprise::class)->findBy(['code_attribution'=>$attrib, 'exercice'=>$exercice]);
                foreach($reprises as $reprise){
                    //$reprises_count = $reprises_count +1;
                    $doccps  = $registry->getRepository(Documentcp::class)->findBy(['code_reprise'=>$reprise]);
                    foreach($doccps as $doccp){
                        $nb_cp_op = $nb_cp_op + 1;
                        $vol = 0;
                    }
                }
            }

            //Liste Essences exploitées

            $attributions = $registry->getRepository(Attribution::class)->findBy(['code_exploitant'=>$user->getCodeexploitant()]);
            foreach($attributions as $attrib){
                $reprises  = $registry->getRepository(Reprise::class)->findBy(['code_attribution'=>$attrib, 'exercice'=>$exercice]);
                foreach($reprises as $reprise){
                    //$reprises_count = $reprises_count +1;
                    $docbrhs  = $registry->getRepository(Documentbrh::class)->findBy(['code_reprise'=>$reprise]);
                    foreach($docbrhs as $docbrh){
                        $nb_brh_op = $nb_brh_op + 1;
                        $vol = 0;
                        $pagebrhs  = $registry->getRepository(Pagebrh::class)->findBy(['code_docbrh'=>$docbrh]);
                        foreach($pagebrhs as $page){
                            $lignepagebrhs  = $registry->getRepository(Lignepagebrh::class)->findBy(['code_pagebrh'=>$page]);

                            foreach($lignepagebrhs as $lignebrh){
                                if ($lignebrh->getNomEssencebrh()){
                                    $liste_essences_exploitees[] = array(
                                        'id_essence'=>$lignebrh->getNomEssencebrh()->getId(),
                                        'nom_essence'=>$lignebrh->getNomEssencebrh()->getNomVernaculaire(),
                                        'cubage'=>$lignebrh->getCubageLignepagebrh()
                                    );
                                }

                                $vol = $vol + $lignebrh->getCubageLignepagebrh();
                            }
                        }
                        $liste_doc_brh[] = array(
                            'id_brh'=>$docbrh->getId(),
                            'numero_brh'=>$docbrh->getNumeroDocbrh(),
                            'volume'=>$vol
                        );
                    }
                }
            }

            $essences = $registry->getRepository(Essence::class)->findAll();
            foreach ($essences as $essence){
                $vol = 0;
                for ($i=0; $i < count($liste_essences_exploitees); $i++){
                    if ((int)$liste_essences_exploitees[$i]['id_essence'] == $essence->getId()){
                        $vol = $vol + (float)$liste_essences_exploitees[$i]['cubage'];
                    }
                }

                if ($vol > 0) {
                    $liste_essences_vol[] = array(
                        'vol'=>$vol,
                        'nom_vernaculaire'=>$essence->getNomVernaculaire()
                    );
                }
            }

            arsort($liste_essences_vol);


        }

        // Interface Industriel
        if ($this->isGranted('ROLE_INDUSTRIEL')){
            // Derniers chargements
            $chrs_usine = $registry->getRepository(Pagebrh::class)->findBy([
                'parc_usine_brh'=>$user->getCodeindustriel(),
                'confirmation_usine'=>false,
                'fini'=>true
            ]);
            foreach ($chrs_usine as $chr){
                $exp = $chr->getCodeDocbrh()->getCodeReprise()->getCodeAttribution()->getCodeExploitant()->getRaisonSocialeExploitant();
                if ($chr->getCodeDocbrh()->getCodeReprise()->getCodeAttribution()->getCodeExploitant()->getSigle()){
                    $exp = $chr->getCodeDocbrh()->getCodeReprise()->getCodeAttribution()->getCodeExploitant()->getSigle();
                }

                $nb_billes = 0;
                $volume_billes = 0;
                $ess = "-";

                $billes_chr = $registry->getRepository(Lignepagebrh::class)->findBy(['code_pagebrh'=>$chr]);
                foreach ($billes_chr as $bille){
                    $nb_billes = $nb_billes + 1;
                    $volume_billes = $volume_billes + $bille->getCubageLignepagebrh();
                }
                foreach ($registry->getRepository(Essence::class)->findAll() as $essence){
                    foreach ($billes_chr as $bille){
                        if ($essence->getId() == $bille->getNomEssencebrh()->getId()){
                            $ess = $ess . " - " . $bille->getNomEssencebrh()->getNomVernaculaire();
                            break;
                        }
                    }
                }
                $liste_chr_industriel[] = array(
                    'date_chr'=>$chr->getDateChargementbrh()->format('d/m/Y'),
                    'id'=>$chr->getId(),
                    'numero'=>$chr->getNumeroPagebrh(),
                    'immat'=>$chr->getImmatcamion(),
                    'foret'=>$chr->getCodeDocbrh()->getCodeReprise()->getCodeAttribution()->getCodeForet()->getDenomination(),
                    'document'=>$chr->getCodeDocbrh()->getNumeroDocbrh(),
                    'exploitant'=>$exp,
                    'essences'=>$ess,
                    'nb_billes'=>$nb_billes,
                    'volume'=>round($volume_billes,3),
                    'source'=>'BRH'
                );
            }

            rsort($liste_chr_industriel);

            // Disponibilité des billes sur le parc chantier;
            $dispoBilles = $registry->getRepository(DisponibiliteParcBilles::class)->findBy([
                'code_usine'=>$user->getCodeindustriel()->getId()
            ]);
            foreach ($dispoBilles as $bille){

                    $essence_disponibles[] = array(
                        'essence'=>$bille->getNomVernaculaire(),
                        'nb'=>$bille->getNbBilles(),
                        'volume'=>$bille->getVolume()
                    );
            }

            sort($essence_disponibles);

            // Disponibilité des billons sur le parc chantier;
            $dispoBillons = $registry->getRepository(DisponibiliteParcBillons::class)->findBy([
                'code_usine'=>$user->getCodeindustriel()->getId()
            ]);
            foreach ($dispoBillons as $bille){

                $billons_disponibles[] = array(
                    'essence'=>$bille->getNomVernaculaire(),
                    'nb'=>$bille->getNbBillons(),
                    'volume'=>$bille->getVolume()
                );
            }

            sort($billons_disponibles);
        }



        $lignecp = $registry->getRepository(Lignepagecp::class)->findBy(['code_exercice'=>$exercice]);
        //dd($registry->getRepository(PublicationRapport::class)->findByStatut());
        return $this->render('tdb_admin/index.html.twig',
            [
                'rapport_oi'=>$registry->getRepository(PublicationRapport::class)->findByStatut(),
                'tickets_oi'=>$registry->getRepository(Ticket::class)->findBy([],['id'=>'DESC'],5,0),
                'operateurs'=>$registry->getRepository(TypeOperateur::class)->findAll(),
                'type_rapports_oi'=>$registry->getRepository(PublicationRapport::class)->findAll(),
                'chargements' => $page_brh,
                'documents'=>$registry->getRepository(TypeDocumentStatistique::class)->findAll(),
                //'log_snvlt'=>$logsnvlt,
                'liste_menus'=>$menus->findOnlyParent(),
                'liste_parent'=>$permissions,
                "all_menus"=>$menus->findAll(),
                'menus'=>$permissions->findBy(['code_groupe_id'=>$code_groupe]),
                'mes_notifs'=>$notification->findBy(['to_user'=>$user, 'lu'=>false],['created_at'=>'DESC'],5,0),
                'groupe'=>$code_groupe,
                'arbres'=>$lignecp,
                'essences_vol'=>array_slice($liste_essences_vol, 0, 5),
                'essences_admin'=>array_slice($liste_essences_admin, 0, 10),
                'doc_brh_op'=>array_slice($liste_doc_brh, 0, 3),
                'quotas'=>$liste_quotas,
                'nb_reprises'=>$reprises_count,
                'nb_doc_brh_op'=>$nb_brh_op,
                'nb_doc_cp_op'=>$nb_cp_op,
                'nb_users'=>$nb_users,
                'nb_autorisations'=>$nb_autorisations,
                'total_volume_exploite'=>round($total_exploitation),
                'total_transformation'=>round($total_transformation),
                'chargements_en_cours'=>array_slice($liste_chargements, 0, 10),
                'point_dr'=>$point_dr,
                'drs'=>$registry->getRepository(Dr::class)->findAll(),
                'total_agreement'=>$total_agreements,
                'total_autorisation'=>$total_autorisations,
                'suivi_saisies'=>$registry->getRepository(PerformanceBrhJour::class)->findBy([],['created_at'=>'ASC']),
                'arbres_abattus'=>$arbres_abattus,
                'vol_brh'=>round($volume_brh,3),
                'nb_utilisateurs'=>$nb_utilisateurs,
                'forets_cef'=>$volume_foret,
                'chr_cef'=>$chargements_cef,
                'chr_usine'=>$liste_chr_industriel,
                'essences_dispo'=>$essence_disponibles,
                'billons_dispo'=>$billons_disponibles,
                'parc_usine_stats'=>array_slice($liste_billes_industriel, 0, 10),
                'exercice'=>$exercice
            ]);
    }

}
