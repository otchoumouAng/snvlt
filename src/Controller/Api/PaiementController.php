<?php

namespace App\Controller\Api;

use App\Entity\Paiement\CatalogueServices;
use App\Entity\Paiement\Transaction;
use App\Service\Paiement\TresorPayService;
use App\Entity\Paiement\TypePaiement;
use App\Repository\Paiement\TypePaiementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\User;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use Symfony\Component\HttpClient\HttpClient;

#[Route('/api')]
class PaiementController extends AbstractController
{
    private $em;
    private $tresorPayService;

    public function __construct(EntityManagerInterface $em, TresorPayService $tresorPayService)
    {
        $this->em = $em;
        $this->tresorPayService = $tresorPayService;
    }

    #[Route('/user/pefs', name: 'api_user_pefs', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function userPefs(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $exploitant = $user->getCodeexploitant();

        if (!$exploitant) {
            return $this->json([]);
        }

        $pefs = [];
        /** @var Attribution $attribution */
        foreach ($exploitant->getAttributions() as $attribution) {
            // We only care about active attributions that haven't been withdrawn or abandoned
            if ($attribution->isStatut() && !$attribution->isRetire() && !$attribution->isAbandonne()) {
                 if ($foret = $attribution->getCodeForet()) {
                    $pefs[] = [
                        'id' => $attribution->getId(), // The ID of the attribution is likely what's needed
                        'label' => $foret->getDenomination() . ' (N°' . $attribution->getNumeroDecision() . ')'
                    ];
                }
            }
        }

        return $this->json($pefs);
    }

    #[Route('/type-paiements', name: 'api_type_paiements', methods: ['GET'])]
    public function typePaiements(TypePaiementRepository $typePaiementRepository): JsonResponse
    {
        $types = $typePaiementRepository->findBy(['active' => true], ['libelle' => 'ASC']);
        $data = array_map(function (TypePaiement $tp) {
            return [
                'id' => $tp->getId(),
                'label' => $tp->getLibelle(),
            ];
        }, $types);

        return $this->json($data);
    }


    /**
     * @Route("/admin/user/pef", name="app_user_pef_data", methods={"GET"})
     */
    /*public function getUserPefs(EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser(); // Récupère l'utilisateur connecté

        // Sécurité : Vérifier si l'utilisateur est bien un Exploitant Forestier
        //["ROLE_EXPLOITANT","EXPLOITANT FORESTIER"]
        if (!$user || !in_array('ROLE_EXPLOITANT', $user->getRoles())) {
            return new JsonResponse(['error' => 'Accès non autorisé ou utilisateur non valide'], 403);
        }

        $exploitant = $user->getCodeexploitant();
        if (!$exploitant) {
            return new JsonResponse([]);
        }

        $conn = $em->getConnection();
        $sql = '
            SELECT p.numero_pef as libelle, p.gid as id
            FROM public.pef p
            JOIN metier.attribution a ON p.gid = a.code_foret_id
            WHERE a.code_exploitant_id = :code_exploitant_id
        ';

        $stmt = $conn->prepare($sql);
        $resultSet = $stmt->executeQuery(['code_exploitant_id' => $exploitant->getId()]);
        $pefs = $resultSet->fetchAllAssociative();

        return new JsonResponse($pefs);
    }*/

    public function getUserPefs(): JsonResponse
{
    $user = $this->getUser();

    if (!$user || !in_array('ROLE_EXPLOITANT', $user->getRoles())) {
        return new JsonResponse(['error' => 'Accès non autorisé ou utilisateur non valide'], 403);
    }

    $userId = $user->getId();

    //dd($user->getId());
    if (!$userId) {
        return new JsonResponse([]);
    }

    $httpClient = HttpClient::create();
    $response = $httpClient->request('GET', 'https://boislegal.ci/snvlt/users/getPefs/'.$userId);

    if (200 !== $response->getStatusCode()) {
        return new JsonResponse(['error' => 'Erreur lors de la récupération des données'], 500);
    }

    $content = $response->getContent();
    $data = json_decode($content, true);

    // Vérification que le décodage JSON a réussi
    if (json_last_error() !== JSON_ERROR_NONE) {
        return new JsonResponse(['error' => 'Erreur de décodage des données'], 500);
    }

    // Vérification de la structure des données
    if (!is_array($data) || !isset($data['code']) || 'SUCCESS' !== $data['code'] || !isset($data['data'])) {
        //var_dump($data['data']);
        return new JsonResponse([]);
    }

    // Transformation des données et suppression des doublons
    $uniquePefs = [];
    foreach ($data['data'] as $pef) {
        $id = $pef['id_foret'];
        $uniquePefs[$id] = [
            'id' => $id,
            'libelle' => $pef['numero_foret']
        ];
    }

    return new JsonResponse(array_values($uniquePefs));
}


    /**
     * @Route("/paiement/transactions", name="api_create_transaction", methods={"POST"})
     */
    public function createTransaction(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json(['success' => false, 'message' => 'JSON invalide'], 400);
        }

        $serviceId = $data['service_id'] ?? null;
        $pefId = $data['pef_id'] ?? null;
        $clientNom = $data['client_nom'] ?? null;
        $clientPrenom = $data['client_prenom'] ?? null;
        $telephone = $data['telephone'] ?? null;

        if (!$serviceId || !$clientNom || !$clientPrenom) {
            return $this->json(['success' => false, 'message' => 'Données manquantes: service_id, client_nom et client_prenom sont requis'], 400);
        }

        $service = $this->em->getRepository(CatalogueServices::class)->find($serviceId);
        if (!$service) {
            return $this->json(['success' => false, 'message' => 'Service non trouvé'], 404);
        }

        $transaction = new Transaction();
        $transaction->setService($service);
        $transaction->setMontantFcfa($service->getMontantFcfa());
        $transaction->setClientNom($clientNom);
        $transaction->setClientPrenom($clientPrenom);
        $transaction->setTelephone($telephone);
        $transaction->setTypePaiement($service->getTypePaiement());

        $transaction->setStatut('EN_ATTENTE_AVIS');

        $identifiant = 'SNVLT-' . date('Y') . '-' . time() . rand(100, 999);
        $transaction->setIdentifiant($identifiant);

        $this->em->persist($transaction);
        $this->em->flush();


        $tresorPayResponse = $this->tresorPayService->genererAvisRecette(
            $identifiant,
            (float) $service->getMontantFcfa(),
            $clientNom,
            $clientPrenom,
            $service->getDesignation(),
            $telephone
        );

        //dd($tresorPayResponse);

        $responseCode = $tresorPayResponse['response_code'] ?? -1;
        $responseMessage = $tresorPayResponse['response_message'] ?? 'Réponse invalide de l\'API';

        $transaction->setTresorpayResponseCode($responseCode);
        $transaction->setTresorpayResponseMessage($responseMessage);

        if ($responseCode == 1) {
            $transaction->setStatut('AVIS_GENERE');
        } else {
            $transaction->setStatut('ECHEC_AVIS');
        }

        $this->em->flush();

        if ($transaction->getStatut() === 'AVIS_GENERE') {
            return $this->json([
                'success' => true,
                'message' => 'Avis de recette généré avec succès.',
                'identifiant_transaction' => $identifiant,
                'transaction_id' => $transaction->getId(),
                'tresorpay_response' => $tresorPayResponse
            ]);
        } else {
            return $this->json([
                'success' => false,
                'message' => 'Échec de la génération de l\'avis de recette.',
                'error_details' => $responseMessage,
                'tresorpay_response' => $tresorPayResponse
            ], 500);
        }
    }
}
