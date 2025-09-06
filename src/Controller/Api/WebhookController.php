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

        // Use 'numero_avis' as the identifier
        $identifiant = $data['numero_avis'] ?? null;

        if (!$identifiant) {
            return $this->json(['success' => false, 'message' => 'Identifiant (numero_avis) manquant'], 400);
        }

        $transaction = $em->getRepository(Transaction::class)->findOneBy(['identifiant' => $identifiant]);

        if (!$transaction) {
            return $this->json(['success' => false, 'message' => 'Transaction non trouvée'], 404);
        }

        if ($transaction->getStatut() === "PAYE") {
            return $this->json(['success' => false, 'message' => 'Transaction déjà traîtée'], 404);
        }

        if ($transaction->getStatut() === "PAYE") {
            return $this->json(['success' => false, 'message' => 'Transaction déjà traîtée'], 404);
        }

        if ($transaction->getStatut() != "AVIS_GENERE") {
            return $this->json(['success' => false, 'message' => 'Cet avis de recette a rencontré un problème lors de sa création'], 404);
        }

        // Assume this webhook is always a success confirmation
        $transaction->setStatut('PAYE');
        $transaction->setTresorpayReceiptReference($data['reference'] ?? null);
        $transaction->setPaidAt(new \DateTime($data['date_paiement'] ?? 'now'));
        $transaction->setPayerPhone($data['payment_phone'] ?? null);
        $transaction->setPaidAmount($data['montant_paiement'] ?? null);

        $em->flush();

        return $this->json(['success' => true, 'message' => 'Webhook traité avec succès']);
    }
}
