<?php

namespace App\Controller\Api;

use App\Entity\Paiement\Transaction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/webhooks')]
class WebhookController extends AbstractController
{
    #[Route('/tresorpay/confirmation', name: 'api_webhook_tresorpay_confirmation', methods: ['POST'])]
    public function tresorpayConfirmation(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Basic validation
        if (!isset($data['identifiant']) || !isset($data['statut'])) {
            return $this->json(['success' => false, 'message' => 'Données invalides'], 400);
        }

        $identifiant = $data['identifiant'];
        $statut = $data['statut'];

        $transaction = $em->getRepository(Transaction::class)->findOneBy(['identifiant' => $identifiant]);

        if (!$transaction) {
            return $this->json(['success' => false, 'message' => 'Transaction non trouvée'], 404);
        }

        // Update transaction status based on webhook data
        if ($statut === 'PAIEMENT_EFFECTUE') {
            $transaction->setStatut('PAID');
        } else {
            $transaction->setStatut('FAILED');
        }

        $em->flush();

        return $this->json(['success' => true, 'message' => 'Webhook traité avec succès']);
    }
}
