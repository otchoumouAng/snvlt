<?php

namespace App\Controller\Paiement;

use App\Repository\DemandeAutorisation\TypeDemandeRepository;
use App\Repository\Paiement\CatalogueServicesRepository;
use App\Repository\Paiement\TransactionRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class ApiController extends AbstractController
{
    #[Route('/categories_activite', name: 'api_get_categories_activite', methods: ['GET'])]
    public function getCategoriesActivite(TypeDemandeRepository $repo): JsonResponse
    {
        $categories = $repo->findAll();
        $data = [];
        foreach ($categories as $categorie) {
            $data[] = [
                'id' => $categorie->getId(),
                'label' => $categorie->getLibelle(),
            ];
        }
        return $this->json($data);
    }

    #[Route('/services_by_type_and_category', name: 'api_get_services_by_type_and_category', methods: ['GET'])]
 
    public function getServicesByTypeAndCategory(Request $request, CatalogueServicesRepository $repo): JsonResponse
    {
        $criteria = [];
        if ($request->query->get('type_paiement_id')) {
            $criteria['typePaiement'] = $request->query->get('type_paiement_id');
        }
        if ($request->query->get('type_demande_id')) {
            $criteria['typeDemande'] = $request->query->get('type_demande_id');
        }
        if ($request->query->get('type_demandeur_id')) {
            $criteria['type_demandeur'] = $request->query->get('type_demandeur_id');
        }

        if ($request->query->get('pef_id')) {
            // tu peux adapter ici selon la logique métier
            $services = $repo->findBy(['typeDemande' => $criteria['typeDemande'] ?? null]);
        } else {
            $services = $repo->findBy($criteria);
        }

        $data = [];
        foreach ($services as $service) {
            $data[] = [
                'id' => $service->getId(),
                'label' => $service->getDesignation(),
                'montant' => $service->getMontantFcfa(),
            ];
        }

        return $this->json($data);
    }

    #[Route('/transaction/{id}', name: 'api_get_transaction_details', methods: ['GET'])]
    public function getTransactionDetails(int $id, TransactionRepository $transactionRepository, UserRepository $userRepository): JsonResponse
    {
        $transaction = $transactionRepository->find($id);

        if (!$transaction) {
            return $this->json(['error' => 'Transaction not found'], 404);
        }

        $user = $userRepository->findOneBy(['email' => $transaction->getCreatedBy()]);
        $company = null;
        if ($user) {
            if ($user->getCodeexploitant()) {
                $company = $user->getCodeexploitant()->getRaisonSociale();
            } elseif ($user->getCodeindustriel()) {
                $company = $user->getCodeindustriel()->getRaisonSociale();
            } elseif ($user->getCodeExportateur()) {
                $company = $user->getCodeExportateur()->getNomExportateur();
            } elseif ($user->getCodeCommercant()) {
                $company = $user->getCodeCommercant()->getNomCommercant();
            }
        }

        $data = [
            'identifiant' => $transaction->getIdentifiant(),
            'service' => $transaction->getService() ? $transaction->getService()->getDesignation() : 'N/A',
            'montant_fcfa' => $transaction->getMontantFcfa(),
            'client_nom' => $transaction->getClientNom(),
            'client_prenom' => $transaction->getClientPrenom(),
            'telephone' => $transaction->getTelephone(),
            'statut' => $transaction->getStatut(),
            'tresorpay_receipt_reference' => $transaction->getTresorpayReceiptReference(),
            'paid_at' => $transaction->getPaidAt() ? $transaction->getPaidAt()->format('d/m/Y H:i') : 'N/A',
            'payer_phone' => $transaction->getPayerPhone(),
            'paid_amount' => $transaction->getPaidAmount(),
            'created_at' => $transaction->getCreatedAt()->format('d/m/Y H:i'),
            'created_by' => $transaction->getCreatedBy(),
            'company' => $company,
        ];

        return $this->json($data);
    }
}
