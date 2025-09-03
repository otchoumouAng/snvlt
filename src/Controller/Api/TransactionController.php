<?php

namespace App\Controller\Api;

use App\Entity\References\CatalogueServices;
use App\Entity\References\Transactions;
use App\Services\TresorPayService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api")
 */
class TransactionController extends AbstractController
{
    private $em;
    private $tresorPayService;

    public function __construct(EntityManagerInterface $em, TresorPayService $tresorPayService)
    {
        $this->em = $em;
        $this->tresorPayService = $tresorPayService;
    }

    /**
     * @Route("/transactions", name="api_create_transaction", methods={"POST"})
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
        $typeDemandeId = $data['type_demande_id'] ?? null;

        if (!$serviceId || !$clientNom || !$clientPrenom || !$typeDemandeId) {
            return $this->json(['success' => false, 'message' => 'Données manquantes: service_id, client_nom, client_prenom et type_demande_id sont requis'], 400);
        }

        $service = $this->em->getRepository(CatalogueServices::class)->find($serviceId);
        if (!$service) {
            return $this->json(['success' => false, 'message' => 'Service non trouvé'], 404);
        }

        $transaction = new Transactions();
        $transaction->setService($service);
        $transaction->setMontantFcfa($service->getMontantFcfa());
        $transaction->setClientNom($clientNom);
        $transaction->setClientPrenom($clientPrenom);
        $transaction->setTelephone($telephone);

        $typeDemande = $this->em->getRepository(\App\Entity\References\TypeDemande::class)->find($typeDemandeId);
        if (!$typeDemande) {
            return $this->json(['success' => false, 'message' => 'Type de demande non trouvé'], 404);
        }
        $transaction->setTypeDemande($typeDemande);

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
