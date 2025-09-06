<?php

namespace App\Controller\Paiement;

use App\Entity\Paiement\CatalogueServices;
use App\Entity\Paiement\Transaction;
use App\Service\Paiement\TresorPayService;
use App\Entity\Paiement\TypePaiement;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class PaiementController extends AbstractController
{
    private $em;
    private $tresorPayService;

    public function __construct(EntityManagerInterface $em, TresorPayService $tresorPayService)
    {
        $this->em = $em;
        $this->tresorPayService = $tresorPayService;
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
        $clientNom = $data['client_nom'] ?? null;
        $clientPrenom = $data['client_prenom'] ?? null;
        $telephone = $data['telephone'] ?? null;
        $typePaiementId = $data['type_paiement_id'] ?? null;

        if (!$serviceId || !$clientNom || !$clientPrenom || !$typePaiementId) {
            return $this->json(['success' => false, 'message' => 'Données manquantes: service_id, client_nom, client_prenom et type_paiement_id sont requis'], 400);
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

        $typePaiement = $this->em->getRepository(TypePaiement::class)->find($typePaiementId);
        if (!$typePaiement) {
            return $this->json(['success' => false, 'message' => 'Type de paiement non trouvé'], 404);
        }
        $transaction->setTypePaiement($typePaiement);

        $transaction->setStatut('EN_ATTENTE_AVIS');

        $identifiant = 'FORET-' . date('Y') . '-' . time() . rand(100, 999);
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
