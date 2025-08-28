<?php
namespace App\Controller;

use App\Controller\Services\ProjectionQuery;
use App\Entity\References\Usine;
use Doctrine\DBAL\Connection;
use GuzzleHttp\Psr7\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use App\Entity\Admin\Exercice;
use App\Entity\DocStats\Saisie\Lignepagecp;
use App\Entity\Vues\EssenceVolumeTop10;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\References\Essence;
use App\Entity\References\Exploitant;
use App\Entity\Autorisation\Attribution;
use App\Entity\Autorisation\Reprise;
use App\Entity\DocStats\Saisie\Lignepagebrh;

class TestController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private $requestStack;
    private $registry;
    public function __construct(EntityManagerInterface $entityManager,RequestStack $requestStack,ManagerRegistry $registry)
    {
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;
        $this->registry = $registry;
    }

    #[Route('/test', name: 'app_test')]
    public function index(
        //Request $request,
        //DynamicQueryController $dynamicQueryController,
        //Connection $connection,
        //RequestStack  $requestStack,
        ProjectionQuery $projectionQuery,
        ManagerRegistry $registry,
        
    ): Response

    {

        $exercice = $this->registry->getRepository(Exercice::class)->findOneBy([],['id'=>'DESC'])->getId();
        $exercice = $this->registry->getRepository(Exercice::class)->find($exercice);

        $exercice = $this->registry->getRepository(Exercice::class)->findOneBy([], ['id' => 'DESC'])->getID();
        //dd($exercice);

        $top10_essences = $this->registry->getRepository(Lignepagecp::class)
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
        ->setParameter('anneeExercice',2024)
        ->groupBy('e.id, e.nom_vernaculaire')  
        ->orderBy('cubage', 'DESC')     
        ->setMaxResults(10)
        ->getQuery()
        ->getResult();

        //var_dump(expression)

        $dql = "SELECT COUNT(l) AS Essence
            FROM App\Entity\Lignepagebrh l
            JOIN l.codePagebrh p
            JOIN l.nomEssencebrh e
            WHERE l.exercice = :exerciceId";

            $query = $registry->createQuery($dql);
            $query->setParameter('exerciceId', $exercice);
            $result = $query->getSingleScalarResult();

        dd($result);


        $nbEssence = $this->registry
            ->getRepository(Lignepagebrh::class)
            ->createQueryBuilder('l')
            ->select('COUNT(l) AS Essence')
            ->innerJoin('l.codePagebrh', 'p') // nom réel de la propriété
            ->innerJoin('l.nomEssencebrh', 'e')
            ->where('l.exercice = :exercice')
            ->setParameter('exercice', $exercice)
            ->getQuery()
            ->getSingleScalarResult();

        var_dump($nbEssence);

        $countEssence = $this->registry->getRepository(Lignepagebrh::class)
        ->createQueryBuilder('l')
        ->select('COUNT(l) as Essence')
        ->innerJoin('l.codePagebrh', 'p')  // Jointure avec Pagebrh
        ->innerJoin('l.nomEssencebrh', 'e') // Jointure avec Essence
        ->where('l.exercice = :exerciceId')
        ->setParameter('exerciceId', $exercice)
        ->getQuery()
        ->getSingleScalarResult();

        var_dump($nbEssence);

        //dd($top10_essences);

        $totalExploitantExercice = 0;
        $exploitantList = $this->registry->getRepository(Exploitant::class)->findAll();
        $totalExploitant = count($this->registry->getRepository(Exploitant::class)->findAll());

        
        

        foreach ($exploitantList as $exploitant) {
            $numeroExploitant = $exploitant->getNumeroExploitant();//Retourne de code exploitant: vérifié, c'est ok

            $attributions = $this->registry->getRepository(Attribution::class)->count([
                    'exercice' => $exercice,
                    'code_exploitant' => $numeroExploitant,
                ]);

            if($attributions > 0){
                $totalExploitantExercice ++;
            }
        }


        dd($totalExploitantExercice);

        $totalUsineExercice = 0;
        $UsineList = $this->registry->getRepository(Usine::class)->findAll();

        foreach ($UsineList as $usine) {
            $codeExploitant = $usine->getCodeExploitant() ? $usine->getCodeExploitant()->getId(): null; //Vérifié:ok

           $attributions = $this->registry->getRepository(Attribution::class)
               ->count(['exercice'=>$exercice,'code_exploitant'=>$codeExploitant]);

           if($attributions>0){
                $totalUsineExercice ++;
           }

        }

        $exerciceEnAnnee = $this->registry->getRepository(Exercice::class)->findOneBy([],['id'=>'DESC'])->getAnnee();
        $totalEssenceExercice = 0;
        $EssenceList = $this->registry->getRepository(Essence::class)->findAll();

        foreach ($EssenceList as $essence) {
            $codeEssence = $essence->getId(); //Vérifié:ok

            $attributions = $this->registry->getRepository(Lignepagecp::class)
               ->count(['exercice'=>$exerciceEnAnnee,'nom_essencecp'=>$codeEssence]);
            if($attributions>0){
                $totalEssenceExercice ++;
            }
        }

        //dd($totalEssenceExercice);

        $totalArbresExercice = $this->registry->getRepository(Lignepagecp::class)
               ->count(['exercice'=>$exerciceEnAnnee]);

        $totalVolumeExercice = $this->registry->getRepository(Lignepagecp::class)
               ->createQueryBuilder('v')
               ->select('SUM(v.volume_arbrecp)')
               ->where('v.exercice=:exercice')
               ->setParameter('exercice',$exerciceEnAnnee)
               ->getQuery()
               ->getSingleScalarResult();

        $totalRepriseExercice = $this->registry->getRepository(Reprise::class)
               ->count(['exercice'=>$exercice]);
       

        //Stats en fonction de l'année d'exercice en cours...
        
        $stats[] = array(
            'nbExploitants'=>$totalExploitantExercice,
            'nbUsines'=>$totalUsineExercice,
            'nbEssence'=>$totalEssenceExercice,
            'nbArbres'=>$totalArbresExercice,
            'volumeArbres'=>$totalVolumeExercice,
            'reprises'=>$totalRepriseExercice
        );


        

        $totalArbreAbattu = [];
        $totalArbresExercice = $this->registry->getRepository(Lignepagecp::class)
               ->findBy(['exercice'=>$exerciceEnAnnee]);

        foreach ($totalArbresExercice as $abre){
            $totalArbreAbattu[] = [
                'numeroArbre' => $abre->getNumeroArbrecp(),
                'nomVernaculaire' =>$abre->getNomEssencecp()->getNomVernaculaire(),
                'x'=>$abre->getXArbrecp(),
                'y'=>$abre->getYArbrecp(),
                'long'=>$abre->getLongeurArbrecp(),
                'diam'=>$abre->getDiametreArbrecp(),
            ];
        }
               
        dd($totalArbreAbattu);

        
    }
}