<?php

namespace App\Controller\Api\Webhook;

use App\Entity\References\Transactions;
use App\Repository\References\TransactionsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/webhooks/tresorpay')]
class TresorPayWebhookController extends AbstractController
{
    /**
     * The secret token shared with TrésorPay.
     * This value is injected from the TRESORPAY_WEBHOOK_SECRET environment variable.
     * Make sure to add 'TRESORPAY_WEBHOOK_SECRET=your_secret_token' to your .env file.
     */
    private string $tresorPayWebhookSecret;

    public function __construct(
        private TransactionsRepository $transactionRepository,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
        string $tresorPayWebhookSecret
    ) {
        $this->tresorPayWebhookSecret = $tresorPayWebhookSecret;
    }

    #[Route('/confirmation', name: 'api_webhook_tresorpay_confirmation', methods: ['POST'])]
    public function handleConfirmation(Request $request): JsonResponse
    {
        // --- a. Security ---
        $authToken = $request->headers->get('Authorization');
        if ('Bearer ' . $this->tresorPayWebhookSecret !== $authToken) {
            $this->logger->warning('Unauthorized access attempt to TrésorPay webhook.');
            return new JsonResponse(['error' => 'Unauthorized access.'], 403);
        }

        // --- b. Data Validation ---
        $data = $request->toArray();
        $requiredFields = ['numero_avis', 'montant_paiement', 'reference', 'date_paiement'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                return new JsonResponse(['error' => "Missing field: '$field'"], 400);
            }
        }

        try {
            // --- c. Find the Transaction ---
            $transaction = $this->transactionRepository->findOneBy(['identifiant' => $data['numero_avis']]);

            if (!$transaction) {
                $this->logger->error("TrésorPay Webhook: Transaction not found for avis " . $data['numero_avis']);
                return new JsonResponse(['error' => 'Transaction not found.'], 404);
            }

            // --- d. Essential Checks ---
            if ($transaction->getStatut() !== 'AVIS_GENERE') {
                $this->logger->warning("TrésorPay Webhook: Unexpected status '{$transaction->getStatut()}' for avis " . $data['numero_avis']);
                // Return 200 OK so TrésorPay doesn't retry.
                return new JsonResponse(['status' => 'ok', 'message' => 'Notification ignored: invalid status.']);
            }

            if ((float)$transaction->getMontantFcfa() !== (float)$data['montant_paiement']) {
                 $this->logger->error("TrésorPay Webhook: Amount mismatch for avis " . $data['numero_avis']);
                 // Handle this critical case (e.g., notify admin)
                 return new JsonResponse(['error' => 'Amount mismatch.'], 400);
            }

            // --- e. Update the Entity ---
            $transaction->setStatut('PAYE');
            $transaction->setPaidAt(new \DateTime($data['date_paiement']));
            $transaction->setTresorpayReceiptReference($data['reference']);
            $transaction->setPaidAmount($data['montant_paiement']);
            $transaction->setPayerPhone($data['payment_phone'] ?? null);

            // --- f. Persist Data ---
            $this->entityManager->flush();

            // --- g. Post-Payment Actions (Example) ---
            // $this->dispatch(new PaymentConfirmationEvent($transaction->getId()));
            $this->logger->info("TrésorPay Webhook: Payment confirmed for avis " . $data['numero_avis']);

            // --- h. Success Response ---
            return new JsonResponse(['status' => 'ok', 'message' => 'Notification processed successfully.']);

        } catch (\Exception $e) {
            $this->logger->critical('Critical error in TrésorPay webhook: ' . $e->getMessage(), ['exception' => $e]);
            return new JsonResponse(['error' => 'Internal Server Error.'], 500);
        }
    }
}
