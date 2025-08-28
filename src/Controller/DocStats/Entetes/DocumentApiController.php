<?php

namespace App\Controller\DocStats\Entetes;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\User;
use App\Repository\Administration\NotificationRepository;
use App\Repository\GroupeRepository;
use App\Repository\MenuPermissionRepository;
use App\Repository\MenuRepository;
use App\Repository\UserRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\DocStats\Entetes\DocumentbrhRepository;
use App\Entity\References\Usine;
use App\Entity\References\Foret;
use App\Entity\References\TypeForet;
use League\Csv\Reader;
use App\Entity\DocStats\Pages\Pagebrh;
use App\Entity\DocStats\Saisie\Lignepagebrh;
use App\Entity\References\Essence;
use App\Entity\References\ZoneHemispherique;
use App\Controller\Services\Utils;
use App\Entity\Admin\Exercice;


class DocumentApiController extends AbstractController
{
    #[Route('/doc/stats/entetes/document/api', name: 'app_doc_stats_entetes_document_api')]
    public function index(
        Request $request,
        MenuRepository $menus,
        MenuPermissionRepository $permissions,
        GroupeRepository $groupeRepository,
        UserRepository $userRepository,
        User $user = null,
        NotificationRepository $notification,
        DocumentbrhRepository $docs_brh,
        ManagerRegistry $registry)
    {
        if(!$request->getSession()->has('user_session')){
            return $this->redirectToRoute('app_login');
        } else {
            if ($this->isGranted('ROLE_EXPLOITANT') or  $this->isGranted('ROLE_ADMINISTRATIF') or $this->isGranted('ROLE_MINEF') or $this->isGranted('ROLE_ADMIN') or $this->isGranted('ROLE_DPIF_SAISIE') )
            {
                $user = $userRepository->find($this->getUser());
                $code_groupe = $user->getCodeGroupe()->getId();
                //doc_stats/entetes/document_api/index.html.twig
                return $this->render('doc_stats/entetes/document_api/index.html.twig', [
                    'liste_menus'=>$menus->findOnlyParent(),
                    "all_menus"=>$menus->findAll(),
                    'menus'=>$permissions->findBy(['code_groupe_id'=>$code_groupe]),
                    'mes_notifs'=>$notification->findBy(['to_user'=>$user, 'lu'=>false],[],5,0),
                    'groupe'=>$code_groupe,
                    'liste_parent'=>$permissions,
                    'usines_dest'=>$registry->getRepository(Usine::class)->findOnlyManager(),
                    'liste_forets'=>$registry->getRepository(Foret::class)->findBy(['code_type_foret'=>$registry->getRepository(TypeForet::class)->find(1)], ['numero_foret'=>'ASC']),
                ]);
            } else {
                return $this->redirectToRoute('app_no_permission_user_active');
            }

        }
    }

    #[Route('/doc/stats/entetes/document/api/add-brh', name: 'app_doc_stats_entetes_document_api_add_brh')]
    public function addBrh(Request $request,ManagerRegistry $registry,Utils $service):JsonResponse
    {
        // 1. Récupération des données de la requête
        try {
            $uploadedFile = $request->files->get('file'); 

            // Vérification explicite avant utilisation
            if ($uploadedFile === null) {
                throw new \RuntimeException('Aucun fichier téléversé ou format non pris en charge');
            }

            // Limite à 4 Mo
            $maxSize = 4 * 1024 * 1024; 
            
            if ($uploadedFile->getSize() > $maxSize) {
                return $this->json([
                    'success' => false,
                    'message' => 'Le fichier ne doit pas dépasser 4 Mo'
                ], 413);
            }

        } catch (\Throwable $e) { 
            return $this->json([
                'success' => false,
                'message' => 'Erreur de traitement : ' . $e->getMessage()
            ], $e instanceof \RuntimeException ? 400 : 500);
        }


        if (!$uploadedFile) {
            return $this->json([
                'success' => false,
                'message' => 'Veuillez choisir un fichier CSV pour commencer le traitement'
            ], 400);
        }

        $filepath = $uploadedFile->getPathname();

        // Vérification du type MIME
        $mime = mime_content_type($filepath);
        $allowedMimeTypes = ['text/csv', 'application/csv', 'text/plain'];
        if (!in_array($mime, $allowedMimeTypes)) {
            return $this->json([
                'success' => false,
                'message' => 'Le format de fichier attendu est CSV !'
            ], 415); 
        }

        try {
            // Configuration de la lecture CSV
            $csv = Reader::createFromPath($filepath, 'r');
            $csv->setDelimiter(';'); 
            $csv->setHeaderOffset(0); 

            // Récupération des en-têtes du fichier
            $csvHeaders = $csv->getHeader();
            
            // En-têtes attendus
            $requiredHeaders = [
                "numero_perimetre", "numero_brh", "dm", "numero_feuillet", "destination",
                "code_usine", "date_chargement", "chauffeur", "immat_camion", "cout",
                "village", "numero_bille", "code_essence", "zone", "x", "y", "lng", 
                "cubage", "observations"
            ];



            // Vérification des en-têtes manquants
            $missingHeaders = array_diff($requiredHeaders, $csvHeaders);
            
            if (!empty($missingHeaders)) {
                return $this->json([
                    'success' => false,
                    'message' => 'En-têtes manquants dans le CSV: ' . implode(', ', $missingHeaders)
                ], 400); 
            }

            // Traitement des données
            $finalData = [];
            foreach ($csv->getRecords() as $record) {
                $formattedRecord = [];
                
                foreach ($requiredHeaders as $header) {
                    $formattedRecord[$header] = $record[$header] ?? null;
                }
                
                $finalData[] = $formattedRecord;
            }  

            

            /*return $this->json([
                'success' => true,
                'message' => 'Testing data',
                'data' => $finalData
            ], 200);*/
            $existingData = [];
            foreach($finalData as $data){
                try {
                    $brhId = (int)$data["numero_perimetre"];
                } catch (Exception $e) {
                    throw new \RuntimeException('Une erreur détectée lors de la recupération du BRH concernée: '.$data["numero_perimetre"]);
                }


                $checkBrh = $registry->getRepository(Pagebrh::class)->findBy(['id' => $brhId])[0];

                

                if ($checkBrh) {
                    // Brh infos
                    $brhId = $checkBrh->getId();
                    $usineId = $checkBrh->getParcUsineBrh();
                    $brhnumeroPage = $checkBrh->getNumeroPagebrh();
                    $brhExercice = $checkBrh->getExercice();


                    #Vérification du BRH => Ajout si inexistant | A demander
                    #Si BRH existe: Vérification des lignes
                    #Si Ligne existe -> ignore la ligne
                    #Si Ligne n'existe pas -> ajoute la ligne

                    $LigneBrh = $registry->getRepository(Lignepagebrh::class)->findBy([
                            "x_lignepagebrh" => $data["x"],
                            "y_lignepagebrh"=>$data['y'],
                            "lettre_lignepagebrh"=>$data["numero_bille"][-1]
                        ]
                    );

                    if ($LigneBrh) {
                        $existingData [] = [
                            "numero_perimetre"=>$data["numero_perimetre"],
                            "numero_brh"=>$data["numero_brh"],
                            "numero_feuillet"=>$data["numero_feuillet"],
                            "x"=>$data['x'],
                            "y"=>$data['y'],
                            "lettre"=>$data["numero_bille"][-1],
                        ] ;
                        continue;
                    }

                    $zone = $service->nettoyerChaine($data["zone"]);

                    


                    $dataLigneBrh = [
                        "nom_essencebrh_id" => $registry->getRepository(Essence::class)->find($data["code_essence"]),
                        "zh_lignepagebrh_id" => $registry->getRepository(ZoneHemispherique::class)->findBy(['zone'=>$zone])[0],
                        "code_pagebrh_id" => $registry->getRepository(Pagebrh::class)->find($brhId),
                        "numero_lignepagebrh" => $data["numero_feuillet"],
                        "x_lignepagebrh" => $data["x"],
                        "y_lignepagebrh" => $data["y"],
                        "lettre_lignepagebrh" => $data["numero_bille"][-1],
                        "longeur_lignepagebrh" => $data["lng"],
                        "diametre_lignepagebrh" => $data["dm"],
                        "cubage_lignepagebrh" => $data["cubage"],
                        "observationbrh" => $data["observations"],
                        "created_at"=>date("Y-m-d H:i:s"),
                        "created_by"=>'System',
                        "exercice_id"=>$registry->getRepository(Exercice::class)->find($brhExercice) ,
                    ];

                    $entityManager = $registry->getManager(); // Pas besoin de passer la classe ici

                    $ligne = new Lignepagebrh();
                    $ligne->setNomEssencebrh($dataLigneBrh["nom_essencebrh_id"]);
                    $ligne->setZhLignepagebrh($dataLigneBrh["zh_lignepagebrh_id"]);
                    $ligne->setCodePagebrh($dataLigneBrh["code_pagebrh_id"]);
                    $ligne->setNumeroLignepagebrh($dataLigneBrh["numero_lignepagebrh"]);
                    $ligne->setXLignepagebrh($dataLigneBrh["x_lignepagebrh"]);
                    $ligne->setYLignepagebrh($dataLigneBrh["y_lignepagebrh"]);
                    $ligne->setLettreLignepagebrh($dataLigneBrh["lettre_lignepagebrh"]); // à vérifier si la dernière lettre
                    $ligne->setLongeurLignepagebrh($dataLigneBrh["longeur_lignepagebrh"]);
                    $ligne->setDiametreLignepagebrh($dataLigneBrh["diametre_lignepagebrh"]);
                    $ligne->setCubageLignepagebrh($dataLigneBrh["cubage_lignepagebrh"]);
                    $ligne->setObservationbrh($dataLigneBrh["observationbrh"]);
                    $ligne->setCreatedAt(new \DateTimeImmutable());
                    $ligne->setCreatedBy('System');
                    $ligne->setExercice($dataLigneBrh["exercice_id"]);

                    $entityManager->persist($ligne);
                    $entityManager->flush();


                }  
                       

            }


            return $this->json([
                'success' => true,
                'message' => 'Traitement effectué avec succès !',
                'data' => $finalData,
                'existingData'=>$existingData
            ], 200);



        } catch (Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors du traitement du CSV: ' . $e->getMessage()
            ], 500); 
        }

    }
}
