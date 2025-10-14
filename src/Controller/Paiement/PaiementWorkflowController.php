<?php

namespace App\Controller\Paiement;

use App\Repository\Paiement\TransactionRepository;
use App\Repository\DemandeAutorisation\NouvelleDemandeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\Administration\NotificationRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use App\Repository\MenuPermissionRepository;
use App\Repository\MenuRepository;
use App\Repository\UserRepository;
use App\Service\Paiement\PdfService;
use App\Entity\Paiement\Transaction;
use App\Entity\User;


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
    public function notice(Transaction $transaction, Request $request): Response
    {
        $pefLabel = $request->query->get('pef_label');

        $html = $this->renderView('paiement/notice.html.twig', [
            'transaction' => $transaction,
            'pef_label' => $pefLabel
        ]);
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
        UrlGeneratorInterface $urlGenerator,
        MenuRepository $menus,
        NotificationRepository $notification,
        MenuPermissionRepository $permissions,
        UserRepository $userRepository
    ): Response
    {
        $user = $userRepository->find($this->getUser());
        $code_groupe = $user->getCodeGroupe()->getId();


        $rolesAutorises = ['ROLE_EXPLOITANT', 'ROLE_EXPORTATEUR','ROLE_INDUSTRIEL','ROLE_ADMIN'];

        if (!$user || empty(array_intersect($rolesAutorises, $user->getRoles()))) {
            return new JsonResponse(['error' => 'Accès non autorisé ou utilisateur non valide'], 403);
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
            'user_info' => $userInfo,
            'suivi_url' => $urlGenerator->generate('app_paiement_suivi'),
        ]);
    }

    #[Route("/admin/paiements", name: "app_admin_paiements")]
    public function adminPaiements(
        MenuRepository $menus,
        NotificationRepository $notification,
        MenuPermissionRepository $permissions,
        UserRepository $userRepository
    ): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $transactions = $this->transactionRepository->findAll();
        $transactionsWithDetails = [];

        foreach ($transactions as $transaction) {
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

            $transactionsWithDetails[] = [
                'transaction' => $transaction,
                'company' => $company,
            ];
        }

        return $this->render('paiement/admin_suivi.html.twig', [
            'transactions' => $transactionsWithDetails,
            'liste_menus' => $menus->findOnlyParent(),
            "all_menus" => $menus->findAll(),
            'mes_notifs' => $notification->findBy(['to_user' => $this->getUser(), 'lu' => false], [], 5, 0),
            'menus' => $permissions->findBy(['code_groupe_id' => $this->getUser()->getCodeGroupe()->getId()]),
            'groupe' => $this->getUser()->getCodeGroupe()->getId(),
            'titre' => 'Suivi des paiements',
            'liste_parent' => $permissions,
        ]);
    }
}
