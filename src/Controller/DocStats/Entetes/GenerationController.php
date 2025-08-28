<?php

namespace App\Controller\DocStats\Entetes;

use App\Controller\Services\AdministrationService;
use App\Controller\Services\Utils;
use App\Entity\Admin\Exercice;
use App\Entity\Autorisation\AutorisationExportateur;
use App\Entity\Autorisation\AutorisationPs;
use App\Entity\Autorisation\AutorisationPv;
use App\Entity\Autorisation\ContratBcbgfh;
use App\Entity\Autorisation\Reprise;
use App\Entity\DocStats\Entetes\Documentbcbgfh;
use App\Entity\DocStats\Entetes\Documentbcbp;
use App\Entity\DocStats\Entetes\Documentbcburb;
use App\Entity\DocStats\Entetes\Documentbrepf;
use App\Entity\DocStats\Entetes\Documentbrh;
use App\Entity\DocStats\Entetes\Documentbtgu;
use App\Entity\DocStats\Entetes\Documentcp;
use App\Entity\DocStats\Entetes\Documentdmp;
use App\Entity\DocStats\Entetes\Documentdmv;
use App\Entity\DocStats\Entetes\Documentetatb;
use App\Entity\DocStats\Entetes\Documentetate;
use App\Entity\DocStats\Entetes\Documentetate2;
use App\Entity\DocStats\Entetes\Documentetatg;
use App\Entity\DocStats\Entetes\Documentetath;
use App\Entity\DocStats\Entetes\Documentfp;
use App\Entity\DocStats\Entetes\Documentlje;
use App\Entity\DocStats\Entetes\Documentpdtdrv;
use App\Entity\DocStats\Entetes\Documentrsdpf;
use App\Entity\DocStats\Entetes\SuiviDoc;
use App\Entity\DocStats\Pages\Pagebcbgfh;
use App\Entity\DocStats\Pages\Pagebcbp;
use App\Entity\DocStats\Pages\Pagebcburb;
use App\Entity\DocStats\Pages\Pagebrepf;
use App\Entity\DocStats\Pages\Pagebrh;
use App\Entity\DocStats\Pages\Pagebtgu;
use App\Entity\DocStats\Pages\Pagecp;
use App\Entity\DocStats\Pages\Pagedmp;
use App\Entity\DocStats\Pages\Pagedmv;
use App\Entity\DocStats\Pages\Pageetatb;
use App\Entity\DocStats\Pages\Pageetate;
use App\Entity\DocStats\Pages\Pageetate2;
use App\Entity\DocStats\Pages\Pageetatg;
use App\Entity\DocStats\Pages\Pageetath;
use App\Entity\DocStats\Pages\Pagefp;
use App\Entity\DocStats\Pages\Pagelje;
use App\Entity\DocStats\Pages\Pagepdtdrv;
use App\Entity\DocStats\Pages\Pagersdpf;
use App\Entity\References\Commercant;
use App\Entity\References\Exploitant;
use App\Entity\References\Exportateur;
use App\Entity\References\Foret;
use App\Entity\References\TypeDocumentStatistique;
use App\Entity\References\TypeOperateur;
use App\Entity\References\Usine;
use App\Entity\User;
use App\Repository\Administration\NotificationRepository;
use App\Repository\DocStats\Entetes\DocumentbrhRepository;
use App\Repository\DocStats\Entetes\SuiviDocRepository;
use App\Repository\GroupeRepository;
use App\Repository\MenuPermissionRepository;
use App\Repository\MenuRepository;
use App\Repository\UserRepository;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GenerationController extends AbstractController
{
    public function __construct(private AdministrationService $administrationService, private RequestStack $request)
    {
    }

    #[Route('/doc/stats/entetes/generation/docs', name: 'app_doc_stats_entetes_generation')]
    public function index(
        Request $request,
        MenuRepository $menus,
        MenuPermissionRepository $permissions,
        GroupeRepository $groupeRepository,
        UserRepository $userRepository,
        User $user = null,
        NotificationRepository $notification,
        DocumentbrhRepository $docs_brh,
        ManagerRegistry $registry
    ): Response
    {
        if(!$request->getSession()->has('user_session')){
            return $this->redirectToRoute('app_login');
        } else {
            if ($this->isGranted('ROLE_ADMINISTRATIF') or  $this->isGranted('ROLE_MINEF') or $this->isGranted('ROLE_ADMIN') )
            {
                $user = $userRepository->find($this->getUser());
                $code_groupe = $user->getCodeGroupe()->getId();

                $exo = $request->getSession()->get("exercice");
                $exercice = $registry->getRepository(Exercice::class)->find($exo);

                return $this->render('doc_stats/entetes/generation/index.html.twig', [
                    'liste_menus'=>$menus->findOnlyParent(),
                    "all_menus"=>$menus->findAll(),
                    'menus'=>$permissions->findBy(['code_groupe_id'=>$code_groupe]),
                    'mes_notifs'=>$notification->findBy(['to_user'=>$user, 'lu'=>false],[],5,0),
                    'groupe'=>$code_groupe,
                    'liste_parent'=>$permissions,
                    'type_doc'=>$registry->getRepository(TypeDocumentStatistique::class)->findBy(['statut'=>'ACTIF'], ['abv'=>'ASC']),
                    'usines'=>$registry->getRepository(Usine::class)->findBy([],['raison_sociale_usine'=>'ASC']),//findOnlyManager(),
                    'exploitants'=>$registry->getRepository(Exploitant::class)->findOnlyManager(),
                    'reprises'=>$registry->getRepository(Reprise::class)->findBy(['exercice'=>$exercice, 'statut'=>true]),
                    'contrats'=>$registry->getRepository(ContratBcbgfh::class)->findAll(),
                    'parcelles'=>$registry->getRepository(AutorisationPv::class)->findAll(),
                    'commercants'=>$registry->getRepository(Commercant::class)->findAll(),
                    'autos_export'=>$registry->getRepository(AutorisationExportateur::class)->findAll(),
                    'annee'=>$exercice->getAnnee(),
                ]);
            } else {
                return $this->redirectToRoute('app_no_permission_user_active');
            }

        }
    }

    #[Route('/snvlt/add_doc_admin/{data}', name : 'generate_doc_admin')]
    public function generate_doc_admin(
        Request $request,
        GroupeRepository $groupeRepository,
        UserRepository $userRepository,
        string $data,
        User $user = null,
        ManagerRegistry $registry
    ): Response
    {
        if(!$request->getSession()->has('user_session')){
            return $this->redirectToRoute('app_login');
        } else {
            if ($this->isGranted('ROLE_ADMINISTRATIF') or  $this->isGranted('ROLE_MINEF') or $this->isGranted('ROLE_ADMIN') )
            {
                $user = $userRepository->find($this->getUser());
                $code_groupe = $user->getCodeGroupe()->getId();

                $reponse = array();
                if ($data){

                    $arraydata = json_decode($data);

                    $type_doc = $registry->getRepository(TypeDocumentStatistique::class)->find((int) $arraydata->type_doc);
                    if ($type_doc){

                        $id_doc = 0;
                        $nbPages = 0;
                        $num_doc = "";
                        $date_doc = "";

                        // Le document a générer est un CP
                        if ($type_doc->getId() == 1){
                            $numdoc = $registry->getRepository(Documentcp::class)->findOneBy(["numero_doccp"=>str_replace("-", "/", $arraydata->numero)]);
                            // dd($numdoc);
                            if ($numdoc){
                                $reponse[] = array(
                                    'code'=>'NUMBER_EXISTS'
                                );
                            } else {
                                $doccp = new Documentcp(); // Je crée un nouveau document CP

                                $date_delivrance =  $arraydata->date_delivrance;
                                $doccp->setNumeroDoccp(str_replace("-", "/", $arraydata->numero) );
                                $doccp->setCreatedAt(new \DateTimeImmutable());
                                $doccp->setCreatedBy($user);
                                $doccp->setSignatureCef(true);
                                $doccp->setSignatureDr(true);
                                $doccp->setExercice($this->administrationService->getAnnee());
                                $doccp->setTypeDocument($type_doc);
                                $doccp->setTransmission(true);
                                $doccp->setEtat(true);
                                $doccp->setDelivreDoccp(\DateTime::createFromFormat('Y-m-d', $date_delivrance));
                                $doccp->setCodeReprise($registry->getRepository(Reprise::class)->find($arraydata->reprise));

                                $registry->getManager()->persist($doccp);

                                // Création des pages
                                // Recuprérer le nombre de pages à créer
                                $nb_pmages = $type_doc->getNbPages();

                                // Création des pages à partir du numéro de la première page
                                for ($i=0; $i < $nb_pmages; $i++){
                                    $pagecp = new Pagecp(); // Création de la page CP N° $i
                                    $pagecp->setNumeroPagecp(((int) $arraydata->premiere_page) + $i) ;
                                    $pagecp->setCreatedAt(new \DateTimeImmutable());
                                    $pagecp->setCreatedBy($user);
                                    $pagecp->setFini(false);
                                    $pagecp->setIndex($i + 1);
                                    $pagecp->setCodeDoccp($doccp);

                                    $registry->getManager()->persist($pagecp);
                                }

                                $id_doc = $doccp->getId();
                                $num_doc = $doccp->getNumeroDoccp();
                                $op = $doccp->getCodeReprise()->getCodeAttribution()->getCodeExploitant()->getSigle();

                                $nb = 0;


                                $registry->getManager()->flush();
                                $nbPages =$registry->getRepository(Pagecp::class)->findBy(['code_doccp'=>$doccp]);
                                foreach ($nbPages as $page){
                                    $nb = $nb +1 ;
                                }
                                //dd($nbPages);
                                $this->suivi_documents(
                                    $registry,
                                    $user,
                                    $id_doc,
                                    $num_doc,
                                    $date_delivrance,
                                    $nb,
                                    $type_doc,
                                    $op
                                );

                                $reponse[] = array(
                                    'code'=>'SUCCESS'
                                );

                            }


                        }
                        // Le document a générer est un BRH
                        elseif ($type_doc->getId() == 2){
                            $numdoc = $registry->getRepository(Documentbrh::class)->findOneBy(["numero_docbrh"=>str_replace("-", "/", $arraydata->numero)]);
                            // dd($numdoc);
                            if ($numdoc){
                                $reponse[] = array(
                                    'code'=>'NUMBER_EXISTS'
                                );
                            } else {


                                $docbrh = new Documentbrh(); // Je crée un nouveau document BRH

                                $date_delivrance =  $arraydata->date_delivrance;
                                $docbrh->setNumeroDocbrh(str_replace("-", "/", $arraydata->numero) );
                                $docbrh->setCreatedAt(new \DateTimeImmutable());
                                $docbrh->setCreatedBy($user);
                                $docbrh->setSignatureCef(true);
                                $docbrh->setSignatureDr(true);
                                $docbrh->setExercice($this->administrationService->getAnnee());
                                $docbrh->setTypeDocument($type_doc);
                                $docbrh->setTransmission(true);
                                $docbrh->setEtat(true);
                                $docbrh->setDelivreDocbrh(\DateTime::createFromFormat('Y-m-d', $date_delivrance));
                                $docbrh->setCodeReprise($registry->getRepository(Reprise::class)->find($arraydata->reprise));

                                $registry->getManager()->persist($docbrh);

                                // Création des pages
                                // Recuprérer le nombre de pages à créer
                                $nb_pmages = $type_doc->getNbPages();

                                // Création des pages à partir du numéro de la première page
                                for ($i=0; $i < $nb_pmages; $i++){
                                    $pagebrh = new Pagebrh(); // Création de la page BRH N° $i
                                    $pagebrh->setNumeroPagebrh(((int) $arraydata->premiere_page) + $i) ;
                                    $pagebrh->setCreatedAt(new \DateTimeImmutable());
                                    $pagebrh->setCreatedBy($user);
                                    $pagebrh->setFini(false);
                                    $pagebrh->setindex_page($i+1);
                                    $pagebrh->setCodeDocbrh($docbrh);
                                    $pagebrh->setConfirmationUsine(false);

                                    $registry->getManager()->persist($pagebrh);
                                }

                                $id_doc = $docbrh->getId();
                                $num_doc = $docbrh->getNumeroDocbrh();
                                $op = $docbrh->getCodeReprise()->getCodeAttribution()->getCodeExploitant()->getSigle();

                                $nb = 0;


                                $registry->getManager()->flush();
                                $nbPages =$registry->getRepository(Pagebrh::class)->findBy(['code_docbrh'=>$docbrh]);
                                foreach ($nbPages as $page){
                                    $nb = $nb +1 ;
                                }
                                //dd($nbPages);
                                $this->suivi_documents(
                                    $registry,
                                    $user,
                                    $id_doc,
                                    $num_doc,
                                    $date_delivrance,
                                    $nb,
                                    $type_doc,
                                    $op
                                );

                                $reponse[] = array(
                                    'code'=>'SUCCESS'
                                );
                            }
                        }
                        // Le document a générer est un BCBP
                        elseif ($type_doc->getId() == 3){
                            $numdoc = $registry->getRepository(Documentbcbp::class)->findOneBy(["numero_docbcbp"=>str_replace("-", "/", $arraydata->numero)]);
                            // dd($numdoc);
                            if ($numdoc){
                                $reponse[] = array(
                                    'code'=>'NUMBER_EXISTS'
                                );
                            } else {
                                $docbcbp = new Documentbcbp(); // Je crée un nouveau document BCBP

                                $date_delivrance =  $arraydata->date_delivrance;
                                $docbcbp->setNumeroDocbcbp(str_replace("-", "/", $arraydata->numero) );
                                $docbcbp->setCreatedAt(new \DateTimeImmutable());
                                $docbcbp->setCreatedBy($user);
                                $docbcbp->setSignatureCef(true);
                                $docbcbp->setSignatureDr(true);
                                $docbcbp->setExercice($this->administrationService->getAnnee());
                                $docbcbp->setTypeDocument($type_doc);
                                $docbcbp->setTransmission(true);
                                $docbcbp->setEtat(true);
                                $docbcbp->setDelivreDocbcbp(\DateTime::createFromFormat('Y-m-d', $date_delivrance));
                                $docbcbp->setCodeAutorisationPv($registry->getRepository(AutorisationPv::class)->find($arraydata->parcelles));

                                $registry->getManager()->persist($docbcbp);

                                // Création des pages
                                // Recuprérer le nombre de pages à créer
                                $nb_pmages = $type_doc->getNbPages();

                                // Création des pages à partir du numéro de la première page
                                for ($i=0; $i < $nb_pmages; $i++){
                                    $pagebcbp = new Pagebcbp(); // Création de la page BCBP N° $i
                                    $pagebcbp->setNumeroPagebcbp(((int) $arraydata->premiere_page) + $i) ;
                                    $pagebcbp->setCreatedAt(new \DateTimeImmutable());
                                    $pagebcbp->setCreatedBy($user);
                                    $pagebcbp->setFini(false);
                                    $pagebcbp->setIndexPagebcbp($i+1);
                                    $pagebcbp->setCodeDocbcbp($docbcbp);
                                    $pagebcbp->setConfirmationUsine(false);

                                    $registry->getManager()->persist($pagebcbp);
                                }
                                $id_doc = $docbcbp->getId();
                                $num_doc = $docbcbp->getNumeroDocbcbp();
                                $op = $docbcbp->getCodeAutorisationPv()->getCodeExploitant()->getSigle();

                                $nb = 0;


                                $registry->getManager()->flush();
                                $nbPages =$registry->getRepository(Pagebcbp::class)->findBy(['code_docbcbp'=>$docbcbp]);
                                foreach ($nbPages as $page){
                                    $nb = $nb +1 ;
                                }
                                //dd($nbPages);
                                $this->suivi_documents(
                                    $registry,
                                    $user,
                                    $id_doc,
                                    $num_doc,
                                    $date_delivrance,
                                    $nb,
                                    $type_doc,
                                    $op
                                );

                                $reponse[] = array(
                                    'code'=>'SUCCESS'
                                );
                            }
                        }


                        // Le document a générer est un ETAT B
                        elseif ($type_doc->getId() == 4){
                            $numdoc = $registry->getRepository(Documentetatb::class)->findOneBy(["numero_docetatb"=>str_replace("-", "/", $arraydata->numero)]);
                            // dd($numdoc);
                            if ($numdoc){
                                $reponse[] = array(
                                    'code'=>'NUMBER_EXISTS'
                                );
                            } else {
                                $docetatb = new Documentetatb(); // Je crée un nouveau document ETAT B

                                $date_delivrance =  $arraydata->date_delivrance;
                                $docetatb->setNumeroDocetatb(str_replace("-", "/", $arraydata->numero) );
                                $docetatb->setCreatedAt(new \DateTimeImmutable());
                                $docetatb->setCreatedBy($user);
                                $docetatb->setSignatureCef(true);
                                $docetatb->setSignatureDr(true);
                                $docetatb->setExercice($this->administrationService->getAnnee());
                                $docetatb->setTypeDocument($type_doc);
                                $docetatb->setTransmission(true);
                                $docetatb->setEtat(true);
                                $docetatb->setDelivreDocetatb(\DateTime::createFromFormat('Y-m-d', $date_delivrance));
                                $code_foret = $registry->getRepository(Reprise::class)->find($arraydata->reprise)->getCodeAttribution()->getCodeForet();
                                $docetatb->setCodeForet($code_foret);

                                $registry->getManager()->persist($docetatb);

                                // Création des pages
                                // Recuprérer le nombre de pages à créer
                                $nb_pmages = $type_doc->getNbPages();

                                // Création des pages à partir du numéro de la première page
                                for ($i=0; $i < $nb_pmages; $i++){
                                    $pageetatb = new Pageetatb(); // Création de la page ETAT B N° $i
                                    $pageetatb->setNumeroPageetatb(((int) $arraydata->premiere_page) + $i) ;
                                    $pageetatb->setCreatedAt(new \DateTimeImmutable());
                                    $pageetatb->setCreatedBy($user);
                                    $pageetatb->setIndexPageetatb($i+1);
                                    $pageetatb->setCodeDocetatb($docetatb);

                                    $registry->getManager()->persist($pageetatb);
                                }

                                $id_doc = $docetatb->getId();
                                $num_doc = $docetatb->getNumeroDocetatb();
                                $op = $docetatb->getCodeForet()->getDenomination();

                                $nb = 0;


                                $registry->getManager()->flush();
                                $nbPages =$registry->getRepository(Pageetatb::class)->findBy(['code_docetatb'=>$docetatb]);
                                foreach ($nbPages as $page){
                                    $nb = $nb +1 ;
                                }
                                //dd($nbPages);
                                $this->suivi_documents(
                                    $registry,
                                    $user,
                                    $id_doc,
                                    $num_doc,
                                    $date_delivrance,
                                    $nb,
                                    $type_doc,
                                    $op
                                );

                                $reponse[] = array(
                                    'code'=>'SUCCESS'
                                );
                            }
                        }


                        // Le document a générer est un LJE
                        elseif ($type_doc->getId() == 5){
                            $numdoc = $registry->getRepository(Documentlje::class)->findOneBy(["numero_doclje"=>str_replace("-", "/", $arraydata->numero)]);
                            // dd($numdoc);
                            if ($numdoc){
                                $reponse[] = array(
                                    'code'=>'NUMBER_EXISTS'
                                );
                            } else {
                                $doclje = new Documentlje(); // Je crée un nouveau document LJE

                                $date_delivrance =  $arraydata->date_delivrance;
                                $doclje->setNumeroDoclje(str_replace("-", "/", $arraydata->numero) );
                                $doclje->setCreatedAt(new \DateTimeImmutable());
                                $doclje->setCreatedBy($user);
                                $doclje->setSignatureCef(true);
                                $doclje->setSignatureDr(true);
                                $doclje->setExercice($this->administrationService->getAnnee());
                                $doclje->setTypeDocument($type_doc);
                                $doclje->setTransmission(true);
                                $doclje->setEtat(true);
                                $doclje->setDelivreDoclje(\DateTime::createFromFormat('Y-m-d', $date_delivrance));
                                $doclje->setCodeUsine($registry->getRepository(Usine::class)->find($arraydata->usine));

                                $registry->getManager()->persist($doclje);

                                // Création des pages
                                // Recuprérer le nombre de pages à créer
                                $nb_pmages = $type_doc->getNbPages();

                                // Création des pages à partir du numéro de la première page
                                for ($i=0; $i < $nb_pmages; $i++){
                                    $pagelje = new Pagelje(); // Création de la page LJE N° $i
                                    $pagelje->setNumeroPagelje(((int) $arraydata->premiere_page) + $i) ;
                                    $pagelje->setCreatedAt(new \DateTimeImmutable());
                                    $pagelje->setCreatedBy($user);
                                    $pagelje->setIndexPagelje($i+1);
                                    $pagelje->setCodeDoclje($doclje);

                                    $registry->getManager()->persist($pagelje);
                                }
                                $id_doc = $doclje->getId();
                                $num_doc = $doclje->getNumeroDoclje();
                                $op = $doclje->getCodeUsine()->getSigle();

                                $nb = 0;


                                $registry->getManager()->flush();
                                $nbPages =$registry->getRepository(Pagelje::class)->findBy(['code_doclje'=>$doclje]);
                                foreach ($nbPages as $page){
                                    $nb = $nb +1 ;
                                }
                                //dd($nbPages);
                                $this->suivi_documents(
                                    $registry,
                                    $user,
                                    $id_doc,
                                    $num_doc,
                                    $date_delivrance,
                                    $nb,
                                    $type_doc,
                                    $op
                                );

                                $reponse[] = array(
                                    'code'=>'SUCCESS'
                                );
                            }
                        }


                        // Le document a générer est un BTGU
                        elseif ($type_doc->getId() == 6){
                            $numdoc = $registry->getRepository(Documentbtgu::class)->findOneBy(["numero_docbtgu"=>str_replace("-", "/", $arraydata->numero)]);
                            // dd($numdoc);
                            if ($numdoc){
                                $reponse[] = array(
                                    'code'=>'NUMBER_EXISTS'
                                );
                            } else {
                                $docbtgu = new Documentbtgu(); // Je crée un nouveau document BTGU

                                $date_delivrance =  $arraydata->date_delivrance;
                                $docbtgu->setNumeroDocbtgu(str_replace("-", "/", $arraydata->numero) );
                                $docbtgu->setCreatedAt(new \DateTimeImmutable());
                                $docbtgu->setCreatedBy($user);
                                $docbtgu->setSignatureCef(true);
                                $docbtgu->setSignatureDr(true);
                                $docbtgu->setExercice($this->administrationService->getAnnee());
                                $docbtgu->setTypeDocument($type_doc);
                                $docbtgu->setTransmission(true);
                                $docbtgu->setEtat(true);
                                $docbtgu->setDelivreDocbtgu(\DateTime::createFromFormat('Y-m-d', $date_delivrance));
                                $docbtgu->setCodeUsine($registry->getRepository(Usine::class)->find($arraydata->usine));

                                $registry->getManager()->persist($docbtgu);

                                // Création des pages
                                // Recuprérer le nombre de pages à créer
                                $nb_pmages = $type_doc->getNbPages();

                                // Création des pages à partir du numéro de la première page
                                for ($i=0; $i < $nb_pmages; $i++){
                                    $pagebtgu = new Pagebtgu(); // Création de la page BTGU N° $i
                                    $pagebtgu->setNumeroPagebtgu(((int) $arraydata->premiere_page) + $i) ;
                                    $pagebtgu->setCreatedAt(new \DateTimeImmutable());
                                    $pagebtgu->setCreatedBy($user);
                                    $pagebtgu->setIndexPagebtgu($i+1);
                                    $pagebtgu->setCodeDocbtgu($docbtgu);

                                    $registry->getManager()->persist($pagebtgu);
                                }

                                $id_doc = $docbtgu->getId();
                                $num_doc = $docbtgu->getNumeroDocbtgu();
                                if($docbtgu->getCodeUsine()){
                                    $op = $docbtgu->getCodeUsine()->getRaisonSocialeUsine();
                                } else {$op = "-";}


                                $nb = 0;


                                $registry->getManager()->flush();
                                $nbPages =$registry->getRepository(Pagebtgu::class)->findBy(['code_docbtgu'=>$docbtgu]);
                                foreach ($nbPages as $page){
                                    $nb = $nb +1 ;
                                }
                                //dd($nbPages);
                                $this->suivi_documents(
                                    $registry,
                                    $user,
                                    $id_doc,
                                    $num_doc,
                                    $date_delivrance,
                                    $nb,
                                    $type_doc,
                                    $op
                                );

                                $reponse[] = array(
                                    'code'=>'SUCCESS'
                                );
                            }
                        }


                        // Le document a générer est un FP
                        elseif ($type_doc->getId() == 7){
                            $numdoc = $registry->getRepository(Documentfp::class)->findOneBy(["numero_docfp"=>str_replace("-", "/", $arraydata->numero)]);
                            // dd($numdoc);
                            if ($numdoc){
                                $reponse[] = array(
                                    'code'=>'NUMBER_EXISTS'
                                );
                            } else {
                                $docfp = new Documentfp(); // Je crée un nouveau document FP

                                $date_delivrance =  $arraydata->date_delivrance;
                                $docfp->setNumeroDocfp(str_replace("-", "/", $arraydata->numero) );
                                $docfp->setCreatedAt(new \DateTimeImmutable());
                                $docfp->setCreatedBy($user);
                                $docfp->setSignatureCef(true);
                                $docfp->setSignatureDr(true);
                                $docfp->setExercice($this->administrationService->getAnnee());
                                $docfp->setTypeDocument($type_doc);
                                $docfp->setTransmission(true);
                                $docfp->setEtat(true);
                                $docfp->setDelivreDocfp(\DateTime::createFromFormat('Y-m-d', $date_delivrance));
                                $docfp->setCodeUsin($registry->getRepository(Usine::class)->find($arraydata->usine));

                                $registry->getManager()->persist($docfp);

                                // Création des pages
                                // Recuprérer le nombre de pages à créer
                                $nb_pmages = $type_doc->getNbPages();

                                // Création des pages à partir du numéro de la première page
                                for ($i=0; $i < $nb_pmages; $i++){
                                    $pagefp = new Pagefp(); // Création de la page FP N° $i
                                    $pagefp->setNumeroPagefp(((int) $arraydata->premiere_page) + $i) ;
                                    $pagefp->setCreatedAt(new \DateTimeImmutable());
                                    $pagefp->setCreatedBy($user);
                                    $pagefp->setIndexPage($i+1);
                                    $pagefp->setCodeDocfp($docfp);

                                    $registry->getManager()->persist($pagefp);
                                }
                                $id_doc = $docfp->getId();
                                $num_doc = $docfp->getNumeroDocfp();
                                //dd($docfp);
                                $op = $docfp->getCodeUsin()->getSigle();

                                $nb = 0;


                                $registry->getManager()->flush();
                                $nbPages =$registry->getRepository(Pagefp::class)->findBy(['code_docfp'=>$docfp]);
                                foreach ($nbPages as $page){
                                    $nb = $nb +1 ;
                                }
                                //dd($nbPages);
                                $this->suivi_documents(
                                    $registry,
                                    $user,
                                    $id_doc,
                                    $num_doc,
                                    $date_delivrance,
                                    $nb,
                                    $type_doc,
                                    $op
                                );
                                $reponse[] = array(
                                    'code'=>'SUCCESS'
                                );
                            }
                        }


                        // Le document a générer est un ETAT E
                        elseif ($type_doc->getId() == 8){
                            $numdoc = $registry->getRepository(Documentetate::class)->findOneBy(["numero_docetate"=>str_replace("-", "/", $arraydata->numero)]);
                            // dd($numdoc);
                            if ($numdoc){
                                $reponse[] = array(
                                    'code'=>'NUMBER_EXISTS'
                                );
                            } else {
                                $docetate = new Documentetate(); // Je crée un nouveau document ETATE

                                $date_delivrance =  $arraydata->date_delivrance;
                                $docetate->setNumeroDocetate(str_replace("-", "/", $arraydata->numero) );
                                $docetate->setCreatedAt(new \DateTimeImmutable());
                                $docetate->setCreatedBy($user);
                                $docetate->setSignatureCef(true);
                                $docetate->setSignatureDr(true);
                                $docetate->setExercice($this->administrationService->getAnnee());
                                $docetate->setTypeDocument($type_doc);
                                $docetate->setTransmission(true);
                                $docetate->setEtat(true);
                                $docetate->setDelivreDocetate(\DateTime::createFromFormat('Y-m-d', $date_delivrance));
                                $docetate->setCodeUsine($registry->getRepository(Usine::class)->find($arraydata->usine));
                                //dd($date_delivrance);
                                $registry->getManager()->persist($docetate);

                                // Création des pages
                                // Recuprérer le nombre de pages à créer
                                $nb_pmages = $type_doc->getNbPages();

                                // Création des pages à partir du numéro de la première page
                                for ($i=0; $i < $nb_pmages; $i++){
                                    $pageetate = new Pageetate(); // Création de la page ETATE N° $i
                                    $pageetate->setNumeroPageetate(((int) $arraydata->premiere_page) + $i) ;
                                    $pageetate->setCreatedAt(new \DateTimeImmutable());
                                    $pageetate->setCreatedBy($user);
                                    $pageetate->setIndexPageetate($i+1);
                                    $pageetate->setCodeDocetate($docetate);

                                    $registry->getManager()->persist($pageetate);
                                }
                                $id_doc = $docetate->getId();
                                $num_doc = $docetate->getNumeroDocetate();
                                $op = $docetate->getCodeUsine()->getSigle();

                                $nb = 0;


                                $registry->getManager()->flush();
                                $nbPages =$registry->getRepository(Pageetate::class)->findBy(['code_docetate'=>$docetate]);
                                foreach ($nbPages as $page){
                                    $nb = $nb +1 ;
                                }
                                //dd($nbPages);
                                $this->suivi_documents(
                                    $registry,
                                    $user,
                                    $id_doc,
                                    $num_doc,
                                    $date_delivrance,
                                    $nb,
                                    $type_doc,
                                    $op
                                );

                                $reponse[] = array(
                                    'code'=>'SUCCESS'
                                );
                            }
                        }


                        // Le document a générer est un ETAT E2
                        elseif ($type_doc->getId() == 9){
                            $numdoc = $registry->getRepository(Documentetate2::class)->findOneBy(["numero_docetate2"=>str_replace("-", "/", $arraydata->numero)]);
                            // dd($numdoc);
                            if ($numdoc){
                                $reponse[] = array(
                                    'code'=>'NUMBER_EXISTS'
                                );
                            } else {
                                $docetate2 = new Documentetate2(); // Je crée un nouveau document BRH

                                $date_delivrance =  $arraydata->date_delivrance;
                                $docetate2->setNumeroDocetate2(str_replace("-", "/", $arraydata->numero) );
                                $docetate2->setCreatedAt(new \DateTimeImmutable());
                                $docetate2->setCreatedBy($user);
                                $docetate2->setSignatureCef(true);
                                $docetate2->setSignatureDr(true);
                                $docetate2->setExercice($this->administrationService->getAnnee());
                                $docetate2->setTypeDocument($type_doc);
                                $docetate2->setTransmission(true);
                                $docetate2->setEtat(true);
                                $docetate2->setDelivreDocetate2(\DateTime::createFromFormat('Y-m-d', $date_delivrance));
                                $docetate2->setCodeUsine($registry->getRepository(Usine::class)->find($arraydata->usine));

                                $registry->getManager()->persist($docetate2);

                                // Création des pages
                                // Recuprérer le nombre de pages à créer
                                $nb_pmages = $type_doc->getNbPages();

                                // Création des pages à partir du numéro de la première page
                                for ($i=0; $i < $nb_pmages; $i++){
                                    $pageetate2 = new Pageetate2(); // Création de la page BRH N° $i
                                    $pageetate2->setNumeroPageetate2(((int) $arraydata->premiere_page) + $i) ;
                                    $pageetate2->setCreatedAt(new \DateTimeImmutable());
                                    $pageetate2->setCreatedBy($user);
                                    $pageetate2->setIndexPageetate2($i+1);
                                    $pageetate2->setCodeDocetate2($docetate2);

                                    $registry->getManager()->persist($pageetate2);
                                }

                                $id_doc = $docetate2->getId();
                                $num_doc = $docetate2->getNumeroDocetate2();
                                $op = $docetate2->getCodeUsine()->getSigle();

                                $nb = 0;


                                $registry->getManager()->flush();
                                $nbPages =$registry->getRepository(Pageetate2::class)->findBy(['code_docetate2'=>$docetate2]);
                                foreach ($nbPages as $page){
                                    $nb = $nb +1 ;
                                }
                                //dd($nbPages);
                                $this->suivi_documents(
                                    $registry,
                                    $user,
                                    $id_doc,
                                    $num_doc,
                                    $date_delivrance,
                                    $nb,
                                    $type_doc,
                                    $op
                                );

                                $reponse[] = array(
                                    'code'=>'SUCCESS'
                                );
                            }
                        }


                        // Le document a générer est un ETAT G1
                        elseif ($type_doc->getId() == 10){
                            $numdoc = $registry->getRepository(Documentetatg::class)->findOneBy(["numero_docetatg"=>str_replace("-", "/", $arraydata->numero)]);
                            // dd($numdoc);
                            if ($numdoc){
                                $reponse[] = array(
                                    'code'=>'NUMBER_EXISTS'
                                );
                            } else {
                                $docetatg = new Documentetatg(); // Je crée un nouveau document ETAT G1

                                $date_delivrance =  $arraydata->date_delivrance;
                                $docetatg->setNumeroDocetatg(str_replace("-", "/", $arraydata->numero) );
                                $docetatg->setCreatedAt(new \DateTimeImmutable());
                                $docetatg->setCreatedBy($user);
                                $docetatg->setSignatureCef(true);
                                $docetatg->setSignatureDr(true);
                                $docetatg->setExercice($this->administrationService->getAnnee());
                                $docetatg->setTypeDocument($type_doc);
                                $docetatg->setTransmission(true);
                                $docetatg->setEtat(true);
                                $docetatg->setDelivreDocetatg(\DateTime::createFromFormat('Y-m-d', $date_delivrance));
                                $docetatg->setCodeUsine($registry->getRepository(Usine::class)->find($arraydata->usine));

                                $registry->getManager()->persist($docetatg);

                                // Création des pages
                                // Recuprérer le nombre de pages à créer
                                $nb_pmages = $type_doc->getNbPages();

                                // Création des pages à partir du numéro de la première page
                                for ($i=0; $i < $nb_pmages; $i++){
                                    $pageetatg = new Pageetatg(); // Création de la page ETAT G1 N° $i
                                    $pageetatg->setNumeroPageetatg(((int) $arraydata->premiere_page) + $i) ;
                                    $pageetatg->setCreatedAt(new \DateTimeImmutable());
                                    $pageetatg->setCreatedBy($user);
                                    $pageetatg->setIndexPageetag($i+1);
                                    $pageetatg->setCodeDocetatg($docetatg);

                                    $registry->getManager()->persist($pageetatg);
                                }

                                $id_doc = $docetatg->getId();
                                $num_doc = $docetatg->getNumeroDocetatg();
                                $op = $docetatg->getCodeUsine()->getSigle();

                                $nb = 0;


                                $registry->getManager()->flush();
                                $nbPages =$registry->getRepository(Pageetatg::class)->findBy(['code_docetatg'=>$docetatg]);
                                foreach ($nbPages as $page){
                                    $nb = $nb +1 ;
                                }
                                //dd($nbPages);
                                $this->suivi_documents(
                                    $registry,
                                    $user,
                                    $id_doc,
                                    $num_doc,
                                    $date_delivrance,
                                    $nb,
                                    $type_doc,
                                    $op
                                );

                                $reponse[] = array(
                                    'code'=>'SUCCESS'
                                );
                            }
                        }


                        // Le document a générer est un ETAT H
                        elseif ($type_doc->getId() == 11){
                            $numdoc = $registry->getRepository(Documentetath::class)->findOneBy(["numero_docetath"=>str_replace("-", "/", $arraydata->numero)]);
                            // dd($numdoc);
                            if ($numdoc){
                                $reponse[] = array(
                                    'code'=>'NUMBER_EXISTS'
                                );
                            } else {
                                $docetath = new Documentetath(); // Je crée un nouveau document ETAT H

                                $date_delivrance =  $arraydata->date_delivrance;
                                $docetath->setNumeroDocetath(str_replace("-", "/", $arraydata->numero) );
                                $docetath->setCreatedAt(new \DateTimeImmutable());
                                $docetath->setCreatedBy($user);
                                $docetath->setSignatureCef(true);
                                $docetath->setSignatureDr(true);
                                $docetath->setExercice($this->administrationService->getAnnee());
                                $docetath->setTypeDocument($type_doc);
                                $docetath->setTransmission(true);
                                $docetath->setEtat(true);
                                $docetath->setDelivreDocetath(\DateTime::createFromFormat('Y-m-d', $date_delivrance));
                                $docetath->setCodeUsine($registry->getRepository(Usine::class)->find($arraydata->usine));

                                $registry->getManager()->persist($docetath);

                                // Création des pages
                                // Recuprérer le nombre de pages à créer
                                $nb_pmages = $type_doc->getNbPages();

                                // Création des pages à partir du numéro de la première page
                                for ($i=0; $i < $nb_pmages; $i++){
                                    $pageetath = new Pageetath(); // Création de la page ETAT H N° $i
                                    $pageetath->setNumeroPageetath(((int) $arraydata->premiere_page) + $i) ;
                                    $pageetath->setCreatedAt(new \DateTimeImmutable());
                                    $pageetath->setCreatedBy($user);
                                    $pageetath->setIndexPageetath($i+1);
                                    $pageetath->setCodeDocetath($docetath);

                                    $registry->getManager()->persist($pageetath);
                                }
                                $id_doc = $docetath->getId();
                                $num_doc = $docetath->getNumeroDocetath();
                                $op = $docetath->getCodeUsine()->getSigle();

                                $nb = 0;


                                $registry->getManager()->flush();
                                $nbPages =$registry->getRepository(Pageetath::class)->findBy(['code_docetath'=>$docetath]);
                                foreach ($nbPages as $page){
                                    $nb = $nb +1 ;
                                }
                                //dd($nbPages);
                                $this->suivi_documents(
                                    $registry,
                                    $user,
                                    $id_doc,
                                    $num_doc,
                                    $date_delivrance,
                                    $nb,
                                    $type_doc,
                                    $op
                                );

                                $reponse[] = array(
                                    'code'=>'SUCCESS'
                                );
                            }
                        }


                        // Le document a générer est un DMP
                        elseif ($type_doc->getId() == 12){
                            $numdoc = $registry->getRepository(Documentdmp::class)->findOneBy(["numero_docdmp"=>str_replace("-", "/", $arraydata->numero)]);
                            // dd($numdoc);
                            if ($numdoc){
                                $reponse[] = array(
                                    'code'=>'NUMBER_EXISTS'
                                );
                            } else {
                                $docdmp = new Documentdmp(); // Je crée un nouveau document DMP

                                $date_delivrance =  $arraydata->date_delivrance;
                                $docdmp->setNumeroDocdmp(str_replace("-", "/", $arraydata->numero) );
                                $docdmp->setCreatedAt(new \DateTimeImmutable());
                                $docdmp->setCreatedBy($user);
                                $docdmp->setSignatureCef(true);
                                $docdmp->setSignatureDr(true);
                                $docdmp->setExercice($this->administrationService->getAnnee());
                                $docdmp->setTypeDocument($type_doc);
                                $docdmp->setTransmission(true);
                                $docdmp->setEtat(true);
                                $docdmp->setDelivreDocdmp(\DateTime::createFromFormat('Y-m-d', $date_delivrance));
                                $docdmp->setCodeUsine($registry->getRepository(Usine::class)->find($arraydata->usine));

                                $registry->getManager()->persist($docdmp);

                                // Création des pages
                                // Recuprérer le nombre de pages à créer
                                $nb_pmages = $type_doc->getNbPages();

                                // Création des pages à partir du numéro de la première page
                                for ($i=0; $i < $nb_pmages; $i++){
                                    $pagedmp = new Pagedmp(); // Création de la page DMP N° $i
                                    $pagedmp->setNumeroPagedmp(((int) $arraydata->premiere_page) + $i) ;
                                    $pagedmp->setCreatedAt(new \DateTimeImmutable());
                                    $pagedmp->setCreatedBy($user);
                                    $pagedmp->setIndexPagedmp($i+1);
                                    $pagedmp->setCodeDocdmp($docdmp);

                                    $registry->getManager()->persist($pagedmp);
                                }

                                $id_doc = $docdmp->getId();
                                $num_doc = $docdmp->getNumeroDocdmp();
                                $op = $docdmp->getCodeUsine()->getSigle();

                                $nb = 0;


                                $registry->getManager()->flush();
                                $nbPages =$registry->getRepository(Pagedmp::class)->findBy(['code_docdmp'=>$docdmp]);
                                foreach ($nbPages as $page){
                                    $nb = $nb +1 ;
                                }
                                //dd($nbPages);
                                $this->suivi_documents(
                                    $registry,
                                    $user,
                                    $id_doc,
                                    $num_doc,
                                    $date_delivrance,
                                    $nb,
                                    $type_doc,
                                    $op
                                );

                                $reponse[] = array(
                                    'code'=>'SUCCESS'
                                );
                            }}


                        // Le document a générer est un DMV
                        elseif ($type_doc->getId() == 13){
                            $numdoc = $registry->getRepository(Documentdmv::class)->findOneBy(["numero_docdmv"=>str_replace("-", "/", $arraydata->numero)]);
                            // dd($numdoc);
                            if ($numdoc){
                                $reponse[] = array(
                                    'code'=>'NUMBER_EXISTS'
                                );
                            } else {
                                $docdmv = new Documentdmv(); // Je crée un nouveau document DMV

                                $date_delivrance =  $arraydata->date_delivrance;
                                $docdmv->setNumeroDocdmv(str_replace("-", "/", $arraydata->numero) );
                                $docdmv->setCreatedAt(new \DateTimeImmutable());
                                $docdmv->setCreatedBy($user);
                                $docdmv->setSignatureCef(true);
                                $docdmv->setSignatureDr(true);
                                $docdmv->setExercice($this->administrationService->getAnnee());
                                $docdmv->setTypeDocument($type_doc);
                                $docdmv->setTransmission(true);
                                $docdmv->setEtat(true);
                                $docdmv->setDelivreDocdmv(\DateTime::createFromFormat('Y-m-d', $date_delivrance));
                                $docdmv->setCodeUsine($registry->getRepository(Usine::class)->find($arraydata->usine));

                                $registry->getManager()->persist($docdmv);

                                // Création des pages
                                // Recuprérer le nombre de pages à créer
                                $nb_pmages = $type_doc->getNbPages();

                                // Création des pages à partir du numéro de la première page
                                for ($i=0; $i < $nb_pmages; $i++){
                                    $pagedmv = new Pagedmv(); // Création de la page DMV N° $i
                                    $pagedmv->setNumeroPagedmv(((int) $arraydata->premiere_page) + $i) ;
                                    $pagedmv->setCreatedAt(new \DateTimeImmutable());
                                    $pagedmv->setCreatedBy($user);
                                    $pagedmv->setIndexPagedmv($i+1);

                                    $pagedmv->setCodeDocdmv($docdmv);

                                    $registry->getManager()->persist($pagedmv);
                                }

                                $id_doc = $docdmv->getId();
                                $num_doc = $docdmv->getNumeroDocdmv();
                                $op = $docdmv->getCodeUsine()->getSigle();

                                $nb = 0;


                                $registry->getManager()->flush();
                                $nbPages =$registry->getRepository(Pagedmv::class)->findBy(['code_docdmv'=>$docdmv]);
                                foreach ($nbPages as $page){
                                    $nb = $nb +1 ;
                                }
                                //dd($nbPages);
                                $this->suivi_documents(
                                    $registry,
                                    $user,
                                    $id_doc,
                                    $num_doc,
                                    $date_delivrance,
                                    $nb,
                                    $type_doc,
                                    $op
                                );

                                $reponse[] = array(
                                    'code'=>'SUCCESS'
                                );
                            }}


                        // Le document a générer est un PDT DRV
                        elseif ($type_doc->getId() == 15){
                            $numdoc = $registry->getRepository(Documentpdtdrv::class)->findOneBy(["numero_docpdtdrv"=>str_replace("-", "/", $arraydata->numero)]);
                            // dd($numdoc);
                            if ($numdoc){
                                $reponse[] = array(
                                    'code'=>'NUMBER_EXISTS'
                                );
                            } else {
                                $docpdtdrv = new Documentpdtdrv(); // Je crée un nouveau document PDT DRV

                                $date_delivrance =  $arraydata->date_delivrance;
                                $docpdtdrv->setNumeroDocpdtdrv(str_replace("-", "/", $arraydata->numero) );
                                $docpdtdrv->setCreatedAt(new \DateTimeImmutable());
                                $docpdtdrv->setCreatedBy($user);
                                $docpdtdrv->setSignatureCef(true);
                                $docpdtdrv->setSignatureDr(true);
                                $docpdtdrv->setExercice($this->administrationService->getAnnee());
                                $docpdtdrv->setTypeDocument($type_doc);
                                $docpdtdrv->setTransmission(true);
                                $docpdtdrv->setEtat(true);
                                $docpdtdrv->setDelivreDocpdtdrv(\DateTime::createFromFormat('Y-m-d', $date_delivrance));
                                $docpdtdrv->setCodeUsine($registry->getRepository(Usine::class)->find($arraydata->usine));

                                $registry->getManager()->persist($docpdtdrv);

                                // Création des pages
                                // Recuprérer le nombre de pages à créer
                                $nb_pmages = $type_doc->getNbPages();

                                // Création des pages à partir du numéro de la première page
                                for ($i=0; $i < $nb_pmages; $i++){
                                    $pagepdtdrv = new Pagepdtdrv(); // Création de la page PDT DRV N° $i
                                    $pagepdtdrv->setNumeroPagepdtdrv(((int) $arraydata->premiere_page) + $i) ;
                                    $pagepdtdrv->setCreatedAt(new \DateTimeImmutable());
                                    $pagepdtdrv->setCreatedBy($user);
                                    $pagepdtdrv->setIndexPagepdtdrv($i+1);
                                    $pagepdtdrv->setCodeDocpdtdrv($docpdtdrv);

                                    $registry->getManager()->persist($pagepdtdrv);
                                }

                                $id_doc = $docpdtdrv->getId();
                                $num_doc = $docpdtdrv->getNumeroDocpdtdrv();
                                $op = $docpdtdrv->getCodeUsine()->getSigle();

                                $nb = 0;


                                $registry->getManager()->flush();
                                $nbPages =$registry->getRepository(Pagepdtdrv::class)->findBy(['code_docpdtdrv'=>$docpdtdrv]);
                                foreach ($nbPages as $page){
                                    $nb = $nb +1 ;
                                }
                                //dd($nbPages);
                                $this->suivi_documents(
                                    $registry,
                                    $user,
                                    $id_doc,
                                    $num_doc,
                                    $date_delivrance,
                                    $nb,
                                    $type_doc,
                                    $op
                                );

                                $reponse[] = array(
                                    'code'=>'SUCCESS'
                                );
                            }
                        }


                        // Le document a générer est un BCBURB
                        elseif ($type_doc->getId() == 18){
                            $numdoc = $registry->getRepository(Documentbcburb::class)->findOneBy(["numero_docbcburb"=>str_replace("-", "/", $arraydata->numero)]);
                            // dd($numdoc);
                            if ($numdoc){
                                $reponse[] = array(
                                    'code'=>'NUMBER_EXISTS'
                                );
                            } else {
                                $docbcburb = new Documentbcburb(); // Je crée un nouveau document BCBURB

                                $date_delivrance =  $arraydata->date_delivrance;
                                $docbcburb->setNumeroDocbcburb(str_replace("-", "/", $arraydata->numero) );
                                $docbcburb->setCreatedAt(new \DateTimeImmutable());
                                $docbcburb->setCreatedBy($user);
                                $docbcburb->setSignatureCef(true);
                                $docbcburb->setSignatureDr(true);
                                $docbcburb->setExercice($this->administrationService->getAnnee());
                                $docbcburb->setTypeDocument($type_doc);
                                $docbcburb->setTransmission(true);
                                $docbcburb->setEtat(true);
                                $docbcburb->setDelivreDocbcburb(\DateTime::createFromFormat('Y-m-d', $date_delivrance));
                                $docbcburb->setPermis($registry->getRepository(AutorisationPs::class)->find($arraydata->commercant));

                                $registry->getManager()->persist($docbcburb);

                                // Création des pages
                                // Recuprérer le nombre de pages à créer
                                $nb_pmages = $type_doc->getNbPages();

                                // Création des pages à partir du numéro de la première page
                                for ($i=0; $i < $nb_pmages; $i++){
                                    $pagebcburb = new Pagebcburb(); // Création de la page BCBURB N° $i
                                    $pagebcburb->setNumeroPage(((int) $arraydata->premiere_page) + $i) ;
                                    $pagebcburb->setNbProduits(0);
                                    $pagebcburb->setVolume(0);
                                    $pagebcburb->setCreatedAt(new \DateTimeImmutable());
                                    $pagebcburb->setCreatedBy($user);
                                    $pagebcburb->setIndexPage($i+1);
                                    $pagebcburb->setCodeDocbcburb($docbcburb);

                                    $registry->getManager()->persist($pagebcburb);
                                }

                                $id_doc = $docbcburb->getId();
                                $num_doc = $docbcburb->getNumeroDocbcburb();
                                $op = $docbcburb->getPermis()->getCodeDossier()->getCodeAttributairePs()->getRaisonSociale();

                                $nb = 0;


                                $registry->getManager()->flush();
                                $nbPages =$registry->getRepository(Pagebcburb::class)->findBy(['code_docbcburb'=>$docbcburb]);
                                foreach ($nbPages as $page){
                                    $nb = $nb +1 ;
                                }
                                //dd($nbPages);
                                $this->suivi_documents(
                                    $registry,
                                    $user,
                                    $id_doc,
                                    $num_doc,
                                    $date_delivrance,
                                    $nb,
                                    $type_doc,
                                    $op
                                );

                                $reponse[] = array(
                                    'code'=>'SUCCESS'
                                );
                            }
                        }


                        // Le document a générer est un BREPF
                        elseif ($type_doc->getId() == 19){
                            $numdoc = $registry->getRepository(Documentbrepf::class)->findOneBy(["numero_docbrepf"=>str_replace("-", "/", $arraydata->numero)]);
                            // dd($numdoc);
                            if ($numdoc){
                                $reponse[] = array(
                                    'code'=>'NUMBER_EXISTS'
                                );
                            } else {
                                $docbrepf = new Documentbrepf(); // Je crée un nouveau document BREPF

                                $date_delivrance =  $arraydata->date_delivrance;
                                $docbrepf->setNumeroDocbrepf(str_replace("-", "/", $arraydata->numero) );
                                $docbrepf->setCreatedAt(new \DateTimeImmutable());
                                $docbrepf->setCreatedBy($user);
                                $docbrepf->setSignatureCef(true);
                                $docbrepf->setSignatureDr(true);
                                $docbrepf->setExercice($this->administrationService->getAnnee());
                                $docbrepf->setTypeDocument($type_doc);
                                $docbrepf->setTransmission(true);
                                $docbrepf->setEtat(true);
                                $docbrepf->setDelivreDocbrepf(\DateTime::createFromFormat('Y-m-d', $date_delivrance));
                                $docbrepf->setCodeAutorisationExportateur($registry->getRepository(AutorisationExportateur::class)->find($arraydata->exportateur));

                                $registry->getManager()->persist($docbrepf);

                                // Création des pages
                                // Recuprérer le nombre de pages à créer
                                $nb_pmages = $type_doc->getNbPages();

                                // Création des pages à partir du numéro de la première page
                                for ($i=0; $i < $nb_pmages; $i++){
                                    $pagebrepf = new Pagebrepf(); // Création de la page BREPF N° $i
                                    $pagebrepf->setNumeroPagebrepf(((int) $arraydata->premiere_page) + $i) ;
                                    $pagebrepf->setCreatedAt(new \DateTimeImmutable());
                                    $pagebrepf->setCreatedBy($user);
                                    $pagebrepf->setIndexPage($i+1);
                                    $pagebrepf->setCodeDocbrepf($docbrepf);

                                    $registry->getManager()->persist($pagebrepf);
                                }

                                $id_doc = $docbrepf->getId();
                                $num_doc = $docbrepf->getNumeroDocbrepf();
                                $op = $docbrepf->getCodeAutorisationExportateur()->getCodeAgreement()->getCodeExportateur()->getSigle();

                                $nb = 0;


                                $registry->getManager()->flush();
                                $nbPages =$registry->getRepository(Pagebrepf::class)->findBy(['code_docbrepf'=>$docbrepf]);
                                foreach ($nbPages as $page){
                                    $nb = $nb +1 ;
                                }
                                //dd($nbPages);
                                $this->suivi_documents(
                                    $registry,
                                    $user,
                                    $id_doc,
                                    $num_doc,
                                    $date_delivrance,
                                    $nb,
                                    $type_doc,
                                    $op
                                );

                                $reponse[] = array(
                                    'code'=>'SUCCESS'
                                );
                            }
                        }


                        // Le document a générer est un RSDPF
                        elseif ($type_doc->getId() == 20){
                            $numdoc = $registry->getRepository(Documentrsdpf::class)->findOneBy(["numero_docrsdpf"=>str_replace("-", "/", $arraydata->numero)]);
                            // dd($numdoc);
                            if ($numdoc){
                                $reponse[] = array(
                                    'code'=>'NUMBER_EXISTS'
                                );
                            } else {
                                $docrsdpf = new Documentrsdpf(); // Je crée un nouveau document RSDPF

                                $date_delivrance =  $arraydata->date_delivrance;
                                $docrsdpf->setNumeroDocrsdpf(str_replace("-", "/", $arraydata->numero) );
                                $docrsdpf->setCreatedAt(new \DateTimeImmutable());
                                $docrsdpf->setCreatedBy($user);
                                $docrsdpf->setSignatureCef(true);
                                $docrsdpf->setSignatureDr(true);
                                $docrsdpf->setExercice($this->administrationService->getAnnee());
                                $docrsdpf->setTypeDocument($type_doc);
                                $docrsdpf->setTransmission(true);
                                $docrsdpf->setEtat(true);
                                $docrsdpf->setDelivreDocresdpf(\DateTime::createFromFormat('Y-m-d', $date_delivrance));
                                $docrsdpf->setCodeCommercant($registry->getRepository(Reprise::class)->find($arraydata->commercant));

                                $registry->getManager()->persist($docrsdpf);

                                // Création des pages
                                // Recuprérer le nombre de pages à créer
                                $nb_pmages = $type_doc->getNbPages();

                                // Création des pages à partir du numéro de la première page
                                for ($i=0; $i < $nb_pmages; $i++){
                                    $pagersdpf = new Pagersdpf(); // Création de la page RSDPF N° $i
                                    $pagersdpf->setNumeroPage(((int) $arraydata->premiere_page) + $i) ;
                                    $pagersdpf->setCreatedAt(new \DateTimeImmutable());
                                    $pagersdpf->setCreatedBy($user);
                                    $pagersdpf->setIndexPage($i+1);
                                    $pagersdpf->setCodeDocrsdpf($docrsdpf);

                                    $registry->getManager()->persist($pagersdpf);
                                }

                                $id_doc = $docrsdpf->getId();
                                $num_doc = $docrsdpf->getNumeroDocrsdpf();
                                $op = $docrsdpf->getCodeCommercant()->getPrenoms(). " ". $docrsdpf->getCodeCommercant()->getNom() ;

                                $nb = 0;


                                $registry->getManager()->flush();
                                $nbPages =$registry->getRepository(Pagersdpf::class)->findBy(['code_docrsdpf'=>$docrsdpf]);
                                foreach ($nbPages as $page){
                                    $nb = $nb +1 ;
                                }
                                //dd($nbPages);
                                $this->suivi_documents(
                                    $registry,
                                    $user,
                                    $id_doc,
                                    $num_doc,
                                    $date_delivrance,
                                    $nb,
                                    $type_doc,
                                    $op
                                );

                                $reponse[] = array(
                                    'code'=>'SUCCESS'
                                );
                            }
                        } elseif ($type_doc->getId() == 21){
                            $numdoc = $registry->getRepository(Documentbcbgfh::class)->findOneBy(["numero_docbcbgfh"=>str_replace("-", "/", $arraydata->numero)]);
// dd($numdoc);
                            if ($numdoc){
                                $reponse[] = array(
                                    'code'=>'NUMBER_EXISTS'
                                );
                            } else {


                                $docbcbgfh = new Documentbcbgfh(); // Je crée un nouveau document bcbgfh

                                $date_delivrance =  $arraydata->date_delivrance;
                                $docbcbgfh->setNumeroDocbcbgfh(str_replace("-", "/", $arraydata->numero) );
                                $docbcbgfh->setCreatedAt(new \DateTimeImmutable());
                                $docbcbgfh->setCreatedBy($user);
                                $docbcbgfh->setSignatureCef(true);
                                $docbcbgfh->setSignatureDr(true);
                                $docbcbgfh->setExercice($this->administrationService->getAnnee());
                                $docbcbgfh->setTypeDocument($type_doc);
                                $docbcbgfh->setTransmission(true);
                                $docbcbgfh->setEtat(true);
                                $docbcbgfh->setDelivreDocbcbgfh(\DateTime::createFromFormat('Y-m-d', $date_delivrance));
                                $docbcbgfh->setCodeContrat($registry->getRepository(ContratBcbgfh::class)->find($arraydata->contrat));

                                $registry->getManager()->persist($docbcbgfh);

                                // Création des pages
                                // Recuprérer le nombre de pages à créer
                                $nb_pmages = $type_doc->getNbPages();

                                // Création des pages à partir du numéro de la première page
                                for ($i=0; $i < $nb_pmages; $i++){
                                    $pagebcbgfh = new Pagebcbgfh(); // Création de la page bcbgfh N° $i
                                    $pagebcbgfh->setNumeroPagebcbgfh(((int) $arraydata->premiere_page) + $i) ;
                                    $pagebcbgfh->setCreatedAt(new \DateTimeImmutable());
                                    $pagebcbgfh->setCreatedBy($user);
                                    $pagebcbgfh->setFini(false);
                                    $pagebcbgfh->setindex_page($i+1);
                                    $pagebcbgfh->setCodeDocbcbgfh($docbcbgfh);
                                    $pagebcbgfh->setConfirmationUsine(false);

                                    $registry->getManager()->persist($pagebcbgfh);
                                }

                                $id_doc = $docbcbgfh->getId();
                                $num_doc = $docbcbgfh->getNumeroDocbcbgfh();
                                if ($docbcbgfh->getCodeContrat()->getCodeExploitant()->getSigle()){
                                    $op = $docbcbgfh->getCodeContrat()->getCodeExploitant()->getSigle();
                                } else {
                                    $op = $docbcbgfh->getCodeContrat()->getCodeExploitant()->getRaisonSocialeExploitant();
                                }


                                $nb = 0;


                                $registry->getManager()->flush();
                                $nbPages =$registry->getRepository(Pagebcbgfh::class)->findBy(['code_docbcbgfh'=>$docbcbgfh]);
                                foreach ($nbPages as $page){
                                    $nb = $nb +1 ;
                                }
                                //dd($nbPages);
                                $this->suivi_documents(
                                    $registry,
                                    $user,
                                    $id_doc,
                                    $num_doc,
                                    $date_delivrance,
                                    $nb,
                                    $type_doc,
                                    $op
                                );

                                $reponse[] = array(
                                    'code'=>'SUCCESS'
                                );
                            }
                        }



                    } else {
                        $reponse[] = array(
                            'code'=>'ERROR_DOC'
                        );
                    }


                }

                return new JsonResponse(json_encode($reponse));
            } else {
                return $this->redirectToRoute('app_no_permission_user_active');
            }

        }
    }


    public function suivi_documents(
        ManagerRegistry $registry,
        User $user,
        int $id_doc,
        string $num_doc,
        string $date_doc,
        int $nbPages,
        TypeDocumentStatistique $type_doc,
        string $operateur
    ){
        $exo = $this->request->getSession()->get("exercice");
        $exercice = $registry->getRepository(Exercice::class)->find((int) $exo);

        // Enregistrement du document dans la table Suivi de document
        $doc_suivi = new SuiviDoc();

        $doc_suivi->setCreatedAt(new \DateTime());
        $doc_suivi->setCreatedBy($user);
        $doc_suivi->setIdDocGenere($id_doc);
        $doc_suivi->setNumeroDoc($num_doc);
        $doc_suivi->setDateDelivrance(DateTime::createFromFormat('Y-m-d', $date_doc));
        $doc_suivi->setNbPagesGenerees($nbPages);
        $doc_suivi->setDocumentType($type_doc);
        $doc_suivi->setOperateur($operateur);
        $doc_suivi->setExercice($exercice);

        $registry->getManager()->persist($doc_suivi);
        $registry->getManager()->flush();

        //Enregistrement LOG SNVLT
        $this->administrationService->save_action(
            $user,
            'GENERATION_DOC',
            'CREATION DOCUMENT '. $type_doc->getAbv() ,
            new \DateTimeImmutable(),
            "Document " . $type_doc->getAbv() . " N° " . $num_doc . " généré par ". $user
        );
    }

    #[Route('/snvlt/foret/suivi_doc/', name: 'suivi_doc')]
    public function suivi_doc(
        Request $request,
        GroupeRepository $groupeRepository,
        UserRepository $userRepository,
        User $user = null,
        SuiviDocRepository $suivi,
        ManagerRegistry $registry
    ): Response
    {
        if(!$request->getSession()->has('user_session')){
            return $this->redirectToRoute('app_login');
        } else {
            if ($this->isGranted('ROLE_MINEF') or $this->isGranted('ROLE_ADMINISTRATIF') or $this->isGranted('ROLE_ADMIN'))
            {
                $user = $userRepository->find($this->getUser());
                $code_groupe = $user->getCodeGroupe()->getId();

                //$page_cp = $pages_cp->find($id_page);
                $exo = $request->getSession()->get("exercice");
                $exercice = $registry->getRepository(Exercice::class)->find((int) $exo);

                $suivi_docs = $suivi->findBy(['exercice'=>$exercice], ['created_at'=>'DESC']);

                $documents = array();
                foreach($suivi_docs as $doc){
                    $documents[] = array(
                        'id_doc_suivi'=>$doc->getDocumentType()->getId(). "-".$doc->getId(),
                        'type_doc'=> $doc->getDocumentType()->getAbv(),
                        'operateur'=>$doc->getDocumentType()->getCodeTypeOperateur()->getId(),
                        'numero'=> $doc->getNumeroDoc(),
                        'nb_pages'=> $doc->getNbPagesGenerees(),
                        'date_delivrance'=> $doc->getDateDelivrance()->format('d/m/Y'),
                        'cree_le'=> $doc->getCreatedAt()->format('d/m/Y'),
                        'cree_par'=> $doc->getCreatedBy(),
                        'operator'=>$doc->getOperateur()
                    );
                }



                return  new JsonResponse(json_encode($documents));
            } else {
                return $this->redirectToRoute('app_no_permission_user_active');
            }

        }
    }

    #[Route('/snvlt/generate_doc_exploitant/{id_reprise}', name: 'exploitant_by_reprise')]
    public function exploitant_by_reprise(
        Request $request,
        UserRepository $userRepository,
        User $user = null,
        int $id_reprise,
        ManagerRegistry $registry
    ): Response
    {
        if(!$request->getSession()->has('user_session')){
            return $this->redirectToRoute('app_login');
        } else {
            if ($this->isGranted('ROLE_ADMINISTRATIF') or  $this->isGranted('ROLE_MINEF') or $this->isGranted('ROLE_ADMIN') )
            {
                $user = $userRepository->find($this->getUser());
                $code_groupe = $user->getCodeGroupe()->getId();

                $reprise = $registry->getRepository(Reprise::class)->find($id_reprise);
                $infos = array();
                if ($reprise){
                    $infos[] = array(
                        'rs'=>$reprise->getCodeAttribution()->getCodeExploitant()->getSigle(),
                        'code'=>$reprise->getCodeAttribution()->getCodeExploitant()->getNumeroExploitant(),
                        'marteau'=>$reprise->getCodeAttribution()->getCodeExploitant()->getMarteauExploitant(),
                        'cef'=>$reprise->getCodeAttribution()->getCodeForet()->getCodeCantonnement()->getNomCantonnement(),
                        'dr'=>$reprise->getCodeAttribution()->getCodeForet()->getCodeCantonnement()->getCodeDr()->getDenomination()
                    );

                    return new JsonResponse(json_encode($infos));
                }
            } else {
                return $this->redirectToRoute('app_no_permission_user_active');
            }

        }
    }

    #[Route('/snvlt/generate_doc_usine/{id_usine}', name: 'usine_by_reprise')]
    public function usine_by_reprise(
        Request $request,
        UserRepository $userRepository,
        User $user = null,
        int $id_usine,
        ManagerRegistry $registry
    ): Response
    {
        if(!$request->getSession()->has('user_session')){
            return $this->redirectToRoute('app_login');
        } else {
            if ($this->isGranted('ROLE_ADMINISTRATIF') or  $this->isGranted('ROLE_MINEF') or $this->isGranted('ROLE_ADMIN') )
            {
                $user = $userRepository->find($this->getUser());
                $code_groupe = $user->getCodeGroupe()->getId();

                $usine = $registry->getRepository(Usine::class)->find($id_usine);
                $infos = array();
                $cantonnement = "-";
                $dr = "-";
                if ($usine){
                    if ($cantonnement = $usine->getCodeCantonnement()){
                        $cantonnement = $usine->getCodeCantonnement()->getNomCantonnement();

                        if ($dr = $usine->getCodeCantonnement()->getCodeDr()){
                            $dr = $usine->getCodeCantonnement()->getCodeDr()->getDenomination();
                        }
                    }



                    $infos[] = array(
                        'rs'=>$usine->getRaisonSocialeUsine(),
                        'code'=>$usine->getNumeroUsine(),
                        'cef'=>$cantonnement,
                        'dr'=>$dr
                    );

                    return new JsonResponse(json_encode($infos));
                }
            } else {
                return $this->redirectToRoute('app_no_permission_user_active');
            }

        }
    }
    #[Route('/snvlt/generate_doc_export/{id_auto_export}', name: 'exportateur_by_reprise')]
    public function exportateur_by_reprise(
        Request $request,
        UserRepository $userRepository,
        User $user = null,
        int $id_auto_export,
        ManagerRegistry $registry
    ): Response
    {
        if(!$request->getSession()->has('user_session')){
            return $this->redirectToRoute('app_login');
        } else {
            if ($this->isGranted('ROLE_ADMINISTRATIF') or  $this->isGranted('ROLE_MINEF') or $this->isGranted('ROLE_ADMIN') )
            {
                $user = $userRepository->find($this->getUser());
                $code_groupe = $user->getCodeGroupe()->getId();

                $autorisation = $registry->getRepository(AutorisationExportateur::class)->find($id_auto_export);
                $infos = array();
                $cantonnement = "-";
                $dr = "-";
                if ($autorisation){
                    if ($autorisation->getCodeAgreement()->getCodeExportateur()->getCodeCantonnement()){
                        $cantonnement = $autorisation->getCodeAgreement()->getCodeExportateur()->getCodeCantonnement()->getNomCantonnement();

                        if ($autorisation->getCodeAgreement()->getCodeExportateur()->getCodeCantonnement()->getCodeDr()){
                            $dr = $autorisation->getCodeAgreement()->getCodeExportateur()->getCodeCantonnement()->getCodeDr()->getDenomination();
                        }
                    }
                    if ($autorisation->getCodeAgreement()->getCodeExportateur()->getSigle()){
                        $rs = $autorisation->getCodeAgreement()->getCodeExportateur()->getSigle();
                    } else {
                        $rs = $autorisation->getCodeAgreement()->getCodeExportateur()->getRaisonSocialeExportateur();
                    }
                    $infos[] = array(
                        'rs'=>$rs,
                        'code'=>$autorisation->getCodeAgreement()->getCodeExportateur()->getCodeExportateur(),
                        'agreement'=>$autorisation->getCodeAgreement()->getNumeroDecision(). " du ".$autorisation->getCodeAgreement()->getDateDecision()->format('d/m/Y') ,
                        'autorisation'=>$autorisation->getNumeroAutorisation(). " du ".$autorisation->getDateAutorisation()->format('d/m/Y') ,
                        'cef'=>$cantonnement,
                        'dr'=>$dr
                    );

                    return new JsonResponse(json_encode($infos));
                }
            } else {
                return $this->redirectToRoute('app_no_permission_user_active');
            }

        }
    }
    #[Route('/snvlt/generate_doc_parcelle/{id_parcelle}', name: 'parcelle_by_auto')]
    public function parcelle_by_auto(
        Request $request,
        UserRepository $userRepository,
        User $user = null,
        int $id_parcelle,
        ManagerRegistry $registry
    ): Response
    {
        if(!$request->getSession()->has('user_session')){
            return $this->redirectToRoute('app_login');
        } else {
            if ($this->isGranted('ROLE_ADMINISTRATIF') or  $this->isGranted('ROLE_MINEF') or $this->isGranted('ROLE_ADMIN') )
            {
                $user = $userRepository->find($this->getUser());
                $code_groupe = $user->getCodeGroupe()->getId();

                $parcelle = $registry->getRepository(AutorisationPv::class)->find($id_parcelle);
                $infos = array();
                if ($parcelle){
                    $infos[] = array(
                        'attributaire'=>$parcelle->getCodeAttributionPv()->getRaisonSociale(),
                        'rs'=>$parcelle->getCodeExploitant()->getSigle(),
                        'code'=>$parcelle->getCodeExploitant()->getNumeroExploitant(),
                        'marteau'=>$parcelle->getCodeExploitant()->getMarteauExploitant(),
                        'cef'=>$parcelle->getCodeAttributionPv()->getCodeParcelle()->getCodeCantonnement()->getNomCantonnement(),
                        'dr'=>$parcelle->getCodeAttributionPv()->getCodeParcelle()->getCodeCantonnement()->getCodeDr()->getDenomination()
                    );

                    return new JsonResponse(json_encode($infos));
                }
            } else {
                return $this->redirectToRoute('app_no_permission_user_active');
            }

        }
    }

    #[Route('/snvlt/generate_display_lettre/{id_doc}', name: 'generate_display_lettre')]
    public function generate_display_lettre(
        Request $request,
        UserRepository $userRepository,
        User $user = null,
        int $id_doc,
        ManagerRegistry $registry
    ): Response
    {
        if(!$request->getSession()->has('user_session')){
            return $this->redirectToRoute('app_login');
        } else {
            if ($this->isGranted('ROLE_ADMINISTRATIF') or  $this->isGranted('ROLE_MINEF') or $this->isGranted('ROLE_ADMIN') )
            {
                $user = $userRepository->find($this->getUser());
                $code_groupe = $user->getCodeGroupe()->getId();

                $doc_stat = $registry->getRepository(TypeDocumentStatistique::class)->find($id_doc);
                $infos = array();
                if ($doc_stat){
                    if($doc_stat->isLettre()){
                        $lettre = "OUI";
                    } else {
                        $lettre = "NON";
                    }


                    $infos[] = array(
                        'lettre'=>$lettre
                    );

                    return new JsonResponse(json_encode($infos));
                }
            } else {
                return $this->redirectToRoute('app_no_permission_user_active');
            }

        }
    }

    #[Route('/snvlt/generate_doc/supprimer/{id_doc}', name: 'generate_delete')]
    public function generate_delete(
        Request $request,
        UserRepository $userRepository,
        User $user = null,
        int $id_doc,
        ManagerRegistry $registry
    ): Response
    {
        if(!$request->getSession()->has('user_session')){
            return $this->redirectToRoute('app_login');
        } else {
            if ($this->isGranted('ROLE_ADMINISTRATIF') or  $this->isGranted('ROLE_MINEF') or $this->isGranted('ROLE_ADMIN') )
            {
                $user = $userRepository->find($this->getUser());
                $code_groupe = $user->getCodeGroupe()->getId();

                $id_type_doc = explode("-", $id_doc)[0];
                $id_doc = explode("-", $id_doc)[1];

                //Rechercher le document et son contenu
                // type document
                $doc_type = $registry->getRepository(TypeDocumentStatistique::class)->find((int) $id_type_doc);
                $infos = array();
                if($doc_type){
                    //Switcher les documents par ID

                    if ($doc_type == 1){
                        // Recherche du CP
                        $doc = $registry->getRepository(Documentcp::class)->find((int) $id_doc);
                        if ($doc){
                            // Recherche les enregistrement de ce CP
                            $nb_pages = 0;
                            $nb_lignes = 0;
                            $volume = 0;

                            $pages_cp = $registry->getRepository(Pagecp::class)->findBy(['code_doccp'=>$doc]);
                            foreach ($pages_cp as $page){
                                $lignes_cp = $registry->getRepository(Lignepagecp::class)->findBy(['code_pagecp'=>$page]);
                                if($lignes_cp){
                                    $nb_pages = $nb_pages +1;
                                    foreach ($lignes_cp as $ligne){
                                        if($ligne){
                                            $nb_lignes = $nb_lignes +1;
                                            $volume = $volume + $ligne->getVolumeArbrecp();
                                        }
                                    }
                                }
                            }

                            $infos[] = array(
                                'id_doc'=>$doc->getId(),
                                'nb_pages'=>$nb_pages,
                                'nb_lignes'=>$nb_lignes,
                                'volume'=>$volume,
                            );
                            return new JsonResponse(json_encode($infos));
                        }
                    }

                } else {
                    return $this->redirectToRoute('app_no_permission_user_active');
                }

            }
        }
    }

    #[Route('/snvlt/generate_doc_bcbgfh/{id_contrat}', name: 'exploitant_bcbgfh')]
    public function exploitant_bcbgfh(
        Request $request,
        UserRepository $userRepository,
        User $user = null,
        int $id_contrat,
        ManagerRegistry $registry
    ): Response
    {
        if(!$request->getSession()->has('user_session')){
            return $this->redirectToRoute('app_login');
        } else {
            if ($this->isGranted('ROLE_ADMINISTRATIF') or  $this->isGranted('ROLE_MINEF') or $this->isGranted('ROLE_ADMIN') )
            {
                $user = $userRepository->find($this->getUser());
                $code_groupe = $user->getCodeGroupe()->getId();

                $contrat = $registry->getRepository(ContratBcbgfh::class)->find($id_contrat);
                $infos = array();
                if ($contrat){
                    $infos[] = array(
                        'rs'=>$contrat->getCodeExploitant()->getSigle(),
                        'code'=>$contrat->getCodeExploitant()->getNumeroExploitant(),
                        'marteau'=>$contrat->getCodeExploitant()->getMarteauExploitant(),
                        'cef'=>$contrat->getCodeForet()->getCodeCantonnement()->getNomCantonnement(),
                        'dr'=>$contrat->getCodeForet()->getCodeCantonnement()->getCodeDr()->getDenomination()
                    );

                    return new JsonResponse(json_encode($infos));
                }
            } else {
                return $this->redirectToRoute('app_no_permission_user_active');
            }

        }
    }
}
