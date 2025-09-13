<?php

namespace App\Controller\Paiement;

use App\Repository\Paiement\TransactionRepository;
use App\Repository\Paiement\TypePaiementRepository;
use App\Repository\DemandeAutorisation\NouvelleDemandeRepository;
use App\Repository\References\TypesDemandeurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\Administration\NotificationRepository;
use App\Repository\MenuPermissionRepository;
use App\Repository\MenuRepository;
use App\Repository\UserRepository;
use App\Service\Paiement\PdfService;
use App\Entity\Paiement\Transaction;

class PaiementWorkflowController extends AbstractController
{
    public function __construct(
        private TransactionRepository $transactionRepository,
        private PdfService $pdfService
    ) {}

    #[Route('/paiement/transaction/{id}/receipt', name: 'app_paiement_receipt')]
    public function receipt(Transaction $transaction): Response
    {
        $html = $this->renderView('paiement/receipt.html.twig', ['transaction' => $transaction]);
        return new Response($this->pdfService->generateBinaryPDF($html), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="recu-paiement-'.$transaction->getIdentifiant().'.pdf"'
        ]);
    }

    #[Route('/paiement/transaction/{id}/notice', name: 'app_paiement_notice')]
    public function notice(Transaction $transaction): Response
    {
        $html = $this->renderView('paiement/notice.html.twig', ['transaction' => $transaction]);
        return new Response($this->pdfService->generateBinaryPDF($html), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="avis-recette-'.$transaction->getIdentifiant().'.pdf"'
        ]);
    }

    #[Route("/suivi-mes-paiements", name: "app_paiement_suivi")]
    public function suiviPaiements(
        MenuRepository $menus,
        NotificationRepository $notification,
        MenuPermissionRepository $permissions,
        UserRepository $userRepository
    ): Response
    {
        $user = $this->getUser();
        $code_groupe = $user->getCodeGroupe()->getId();

        $transactions = [];
        if ($user->getMobile()) {
            $transactions = $this->transactionRepository->findByUserIdentifier($user->getMobile());
        }

        return $this->render('paiement/suivi_paiements.html.twig', [
            'liste_menus' => $menus->findOnlyParent(),
            "all_menus" => $menus->findAll(),
            'mes_notifs' => $notification->findBy(['to_user' => $this->getUser(), 'lu' => false], [], 5, 0),
            'menus' => $permissions->findBy(['code_groupe_id' => $code_groupe]),
            'groupe' => $code_groupe,
            'titre' => 'Suivi de mes paiements',
            'liste_parent' => $permissions,
            'transactions' => $transactions,
        ]);
    }

    /**
     * @Route("/paiement/new", name="app_paiement_workflow")
     */
    public function index(
        Request $request,
        TypePaiementRepository $typePaiementRepository,
        NouvelleDemandeRepository $nouvelleDemandeRepository,
        TypesDemandeurRepository $typesDemandeurRepository,
        MenuRepository $menus,
        NotificationRepository $notification,
        MenuPermissionRepository $permissions,
        UserRepository $userRepository
    ): Response
    {
        $user = $userRepository->find($this->getUser());
        $code_groupe = $user->getCodeGroupe()->getId();

        $demandeId = $request->query->get('demandeId');
        $demande = null;
        if ($demandeId) {
            $demande = $nouvelleDemandeRepository->find($demandeId);
        }

        $userInfo = [
            'nom' => $user->getNomUtilisateur(),
            'prenom' => $user->getPrenomsUtilisateur(),
            'telephone' => $user->getMobile()
        ];

        return $this->render('paiement/index.html.twig', [
            'liste_menus' => $menus->findOnlyParent(),
            "all_menus" => $menus->findAll(),
            'mes_notifs' => $notification->findBy(['to_user' => $this->getUser(), 'lu' => false], [], 5, 0),
            'menus' => $permissions->findBy(['code_groupe_id' => $code_groupe]),
            'groupe' => $code_groupe,
            'titre' => 'Initier une Transaction',
            'liste_parent' => $permissions,
            'type_paiements' => $typePaiementRepository->findAll(),
            'types_demandeur' => $typesDemandeurRepository->findAll(),
            'user_info' => $userInfo,
            'demande' => $demande,
        ]);
    }
}
