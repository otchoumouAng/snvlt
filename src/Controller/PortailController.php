<?php

namespace App\Controller;

use App\Controller\Services\AdministrationService;
use App\Controller\Services\Utils;
use App\Entity\Admin\Exercice;
use App\Entity\Autorisation\Reprise;
use App\Entity\Blog\ArticleBlog;
use App\Entity\Blog\CategoryPublication;
use App\Entity\DocStats\Saisie\Lignepagebrh;
use App\Entity\DocStats\Saisie\pagebrh;
use App\Entity\DocStats\Saisie\Lignepagecp;
use App\Entity\Observateur\PublicationRapport;
use App\Entity\References\Essence;
use App\Entity\References\Exploitant;
use App\Entity\References\TypeDocumentStatistique;
use App\Entity\References\Usine;
use App\Entity\Transformation\Billon;
use App\Repository\Blog\ArticleBlogRepository;
use App\Repository\Blog\AutresRubriquesRepository;
use App\Repository\Blog\CategorieRepository;
use App\Entity\Vues\EssenceVolumeTop10;
use App\Entity\Autorisation\Attribution;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PortailController extends AbstractController 
{
    public function __construct(
        private AdministrationService $administrationService,
        private Utils $utils)
    {
    }

    #[Route('/', name: 'app_portail')]
    public function index(
        ManagerRegistry $registry,
        Request                                $request,
        ManagerRegistry                        $manager,
        ArticleBlogRepository $articleRepository,
        ArticleBlogRepository                      $uniqueArticle,
        CategorieRepository                    $categorieRepository,
        AutresRubriquesRepository $rubriquesRepository,
        int $idArticle = 0): Response
    {
        $lastArticle = new ArticleBlog();

        $idArticle = $uniqueArticle->findLastArticleBlogs();

        $articles =  $articleRepository->findAll();

        $lastArticle = $manager->getRepository(ArticleBlog::class)->find($idArticle[1]);

        //Top 10 Essences
       
        $exercice = $registry->getRepository(Exercice::class)->findOneBy([],['id'=>'DESC'])->getAnnee();
        

        $top10_essences = $registry->getRepository(Lignepagecp::class)
        ->createQueryBuilder('l')
        ->select([
            'e.id',
            'e.nom_vernaculaire as nomVernaculaire',
            'SUM(l.volume_arbrecp) as cubage',
            'COUNT(l.id) as nombre_arbres'
        ])
        ->join('l.nom_essencecp', 'e')
        ->where('l.exercice =:anneeExercice')
        ->andWhere('l.volume_arbrecp IS NOT NULL')
        ->setParameter('anneeExercice',2024 )
        ->groupBy('e.id, e.nom_vernaculaire')  
        ->orderBy('cubage', 'DESC')     
        ->setMaxResults(10)
        ->getQuery()
        ->getResult();

        // Données Transfo
        $donnees_production = array();
        $liste_mois = ['1', '2', '3', '4', '5', '6','7', '8', '9', '10', '11', '12'];


        foreach ($liste_mois as $mois){
            $billons = $registry->getRepository(Billon::class)->findAll();

            $vol_sciage = 0;
            $vol_deroulage = 0;
            $vol_tranchage = 0;

            foreach ($billons as $billon){
                $mois_decoupage =(int) $billon->getDateBillonnage()->format('m');
                //dd($mois_decoupage);
                if ((int)$mois == $mois_decoupage && $billon->getTypeTransformation()){
                    if ($billon->getTypeTransformation()->getId() == 1){
                        $vol_sciage = $vol_sciage + round($billon->getVolume(), 3);
                    } elseif ($billon->getTypeTransformation()->getId() == 2){
                        $vol_deroulage = $vol_deroulage + round($billon->getVolume(), 3);
                    } elseif ($billon->getTypeTransformation()->getId() == 3){
                        $vol_tranchage = $vol_tranchage + round($billon->getVolume(), 3);
                    }
                }
            }
            $donnees_production[] = array(
                'mois'=>$mois,
                'sciage'=>$vol_sciage,
                'deroulage'=>$vol_deroulage,
                'tranchage'=>$vol_tranchage
            );

        }
        sort($donnees_production);

        // Documents délivrés
        $doc_snvlt = array();
        $liste_docs = $registry->getRepository(TypeDocumentStatistique::class)->findBy(['statut'=>'ACTIF']);
        foreach ($liste_docs as $doc){
            $nb_doc = 0;
            if ($doc->getId() == 1){
                $nb_doc = $doc->getDocumentcps()->count();
            }elseif ($doc->getId() == 2){
                $nb_doc = $doc->getDocumentbrhs()->count();
            }elseif ($doc->getId() == 3){
                $nb_doc = $doc->getDocumentbcbps()->count();
            }elseif ($doc->getId() == 4){
                $nb_doc = $doc->getDocumentetatbs()->count();
            }elseif ($doc->getId() == 5){
                $nb_doc = $doc->getDocumentljes()->count();
            }elseif ($doc->getId() == 6){
                $nb_doc = $doc->getDocumentbtgus()->count();
            }elseif ($doc->getId() == 7){
                $nb_doc = $doc->getDocumentfps()->count();
            }elseif ($doc->getId() == 9){
                $nb_doc = $doc->getDocumentetate2s()->count();
            }elseif ($doc->getId() == 10){
                $nb_doc = $doc->getDocumentetatgs()->count();
            }elseif ($doc->getId() == 11){
                $nb_doc = $doc->getDocumentetaths()->count();
            }elseif ($doc->getId() == 12){
                $nb_doc = $doc->getDocumentdmps()->count();
            }elseif ($doc->getId() == 13){
                $nb_doc = $doc->getDocumentdmvs()->count();
            }elseif ($doc->getId() == 14){
                $nb_doc = $doc->getDocumentbths()->count();
            }elseif ($doc->getId() == 15){
                $nb_doc = $doc->getDocumentpdtdrvs()->count();
            }elseif ($doc->getId() == 18){
                $nb_doc = $doc->getDocumentbcburbs()->count();
            }elseif ($doc->getId() == 19){
                $nb_doc = $doc->getDocumentbrepfs()->count();
            }elseif ($doc->getId() == 20){
                $nb_doc = $doc->getDocumentrsdpfs()->count();
            }

            $doc_snvlt[] = array(
                'docname'=>$doc->getAbv(),
                'nb_doc'=>$nb_doc
            );
        }
        sort($doc_snvlt);

        // Chiffres clés 
        $stats = [];

        $exercice = $registry->getRepository(Exercice::class)->findOneBy([], ['id' => 'DESC']);

        $totalExploitantExercice = 0;
        $exploitantList = $registry->getRepository(Exploitant::class)->findAll();
        $totalExploitant = count($registry->getRepository(Exploitant::class)->findAll());

        foreach ($exploitantList as $exploitant) {
            $numeroExploitant = $exploitant->getNumeroExploitant();//Retourne de code exploitant: vérifié, c'est ok

            $attributions = $registry->getRepository(Attribution::class)->count([
                    'exercice' => $exercice,
                    'code_exploitant' => $numeroExploitant,
                ]);

            if($attributions > 0){
                $totalExploitantExercice ++;
            }
        }

        $totalUsineExercice = 0;
        $UsineList = $registry->getRepository(Usine::class)->findAll();
        $totalUsine = count($UsineList);

        foreach ($UsineList as $usine) {
            $codeExploitant = $usine->getCodeExploitant() ? $usine->getCodeExploitant()->getId(): null; //Vérifié:ok

           $attributions = $registry->getRepository(Attribution::class)
               ->count(['exercice'=>$exercice,'code_exploitant'=>$codeExploitant]);

           if($attributions>0){
                $totalUsineExercice ++;
           }

        }

        $exerciceEnAnnee = $registry->getRepository(Exercice::class)->findOneBy([],['id'=>'DESC'])->getAnnee();

        $nbEssence = 0;
        $EssenceList = $registry->getRepository(Essence::class)->findAll();
        $totalEssence = count($EssenceList);


        foreach ($EssenceList as $essence) {
            //$codeEssence = $essence->getId(); //Vérifié:ok

            $lignes = $registry->getRepository(Lignepagebrh::class)
            ->findOneBy(['exercice'=>$exercice,'nom_essencebrh'=>$essence]);

            if ($lignes) {
                    $nbEssence++;
                }
        }





        $nbTotalArbres = $registry->getRepository(Lignepagebrh::class)
            ->createQueryBuilder('l')
            ->select('COUNT(l) as Essence')
            ->innerJoin('l.code_pagebrh', 'p') 
            ->innerJoin('l.nom_essencebrh', 'e') 
            ->getQuery()
            ->getSingleScalarResult();


        $totalArbresExercice = $registry->getRepository(Lignepagebrh::class)
            ->createQueryBuilder('l')
            ->select('COUNT(l) as Essence')
            ->innerJoin('l.code_pagebrh', 'p') 
            ->innerJoin('l.nom_essencebrh', 'e') 
            ->where('l.exercice = :exerciceId')
            ->setParameter('exerciceId', $exercice->getId())
            ->getQuery()
            ->getSingleScalarResult();



        

        
        $totalArbresExercice = $registry->getRepository(Lignepagebrh::class)
               ->count(['exercice'=>$exercice,'nom_essencebrh'=>$essence]);

        $totalEssence = count($registry->getRepository(Essence::class)->findAll());

        $nbtotalVolume = $registry->getRepository(Lignepagecp::class)
               ->createQueryBuilder('v')
               ->select('SUM(v.volume_arbrecp)')
               ->getQuery()
               ->getSingleScalarResult();

        $sumResult = $registry->getRepository(Lignepagebrh::class)
            ->createQueryBuilder('l')
            ->select('SUM(l.cubage_lignepagebrh) as total_volume')
            ->where('l.exercice = :exercice')
            ->setParameter('exercice', $exercice->getId())
            ->getQuery()
            ->getSingleScalarResult();

        $totalVolumeExercice = round($sumResult ?? 0, 3);

        


        $nbTotalReprises = $registry->getRepository(Reprise::class)->count([]);
        $totalRepriseExercice = $registry->getRepository(Reprise::class)
               ->count(['exercice'=>$exercice]);
       
       
        //Stats en fonction de l'année d'exercice en cours...
        
        $stats[] = array(
            'nbExploitants'=>$totalExploitantExercice,
            'nbTotalExploitant'=>$totalExploitant,
            'nbUsines'=>$totalUsineExercice,
            'nbTotalUsine'=>$totalUsine,
            'nbEssence'=>$nbEssence,
            'nbTotalEssence'=>$totalEssence,
            'nbTotalArbres'=>$nbTotalArbres,
            'nbArbres'=>$totalArbresExercice,
            'nbtotalVolume'=>number_format(intval($nbtotalVolume), 0, ',','.'),
            'volumeArbres'=>number_format($totalVolumeExercice),
            'reprises'=>$totalRepriseExercice,
            'nbTotalReprises'=>$nbTotalReprises
        );


        return $this->render('portail/index.html.twig',[
            'liste_essences'=>$top10_essences,
            'donnees_transformation'=>$donnees_production,
            'docs_delivres'=>$doc_snvlt,
            /*'exercices'=>$exploitation->findAll(),*/
            'articles_blog'=>$articles,
            'categories'=>$categorieRepository->findAll(),
            'last_articles'=>$lastArticle,
            'stats'=>$stats,
            'infos_ministre'=>$rubriquesRepository->find(1),
            'exercice_en_cours'=>$registry->getRepository(Exercice::class)->findOneBy(['cloture'=>false],['id'=>'DESC']),
            'infos_publiques'=>$registry->getRepository(CategoryPublication::class)->findAll(),
            'oi_rapports'=>$registry->getRepository(PublicationRapport::class)->findBy(['statut'=>'PUBLIE'],['created_at'=>'DESC'],5,0),
        ]);
    }
}
