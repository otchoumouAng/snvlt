<?php

namespace App\Controller\Requetes\sousRequetes;

use App\Entity\DocStats\Saisie\Lignepagebrh;
use App\Entity\References\Essence;
use App\Entity\References\Exploitant;
use App\Entity\References\Foret;
use App\Entity\References\TypeDocumentStatistique;
use App\Entity\Requetes\Qt;
use App\Entity\Requetes\QuotaTransferable;
use App\Entity\User;
use App\Repository\Administration\NotificationRepository;
use App\Repository\GroupeRepository;
use App\Repository\MenuPermissionRepository;
use App\Repository\MenuRepository;
use App\Repository\UserRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ReqQuotaTransferableController extends AbstractController
{
    #[Route('/requetes/sous/requetes/req/qtr', name: 'app_req_qtr')]
    public function app_req_qtr(
        Request $request,
        UserRepository $userRepository,
        ManagerRegistry $registry,
        MenuRepository $menus,
        MenuPermissionRepository $permissions,
        GroupeRepository $groupeRepository,
        User $user = null,
        NotificationRepository $notification): Response
    { if(!$request->getSession()->has('user_session')){
        return $this->redirectToRoute('app_login');
    } else {
        if (
            $this->isGranted('ROLE_ADMIN') //&&  $this->isGranted('ROLE_DPIF')
        ) {
            $user = $userRepository->find($this->getUser());
            $code_groupe = $user->getCodeGroupe()->getId();

            $exploitants_quota = array();
            $exploitants = $registry->getRepository(Exploitant::class)->findBy([],['raison_sociale_exploitant'=>'ASC']);

                $exp_quotas = $registry->getRepository(QuotaTransferable::class)->findAll();

                foreach ($exp_quotas as $quota){
                    if ($quota->getCubage() > 0){
                        $exploitants_quota[] =array(
                            'id'=>$quota->getId(),
                            'foret'=>$quota->getNumeroForet(),
                            'exploitant'=>$quota->getRsExp(),
                            'quota'=>round($quota->getQuota(),0),
                            'qt'=>round($quota->getTiersQuota(),0),
                            'cubage'=>round($quota->getCubage(),0),
                            'ecart'=>round($quota->getTiersQuota() - $quota->getCubage(),0)
                        );
                    }

                }




        return $this->render('requetes/sous_requetes/qtr/index.html.twig', [
            'liste_menus'=>$menus->findOnlyParent(),
            "all_menus"=>$menus->findAll(),
            'mes_notifs'=>$notification->findBy(['to_user'=>$user, 'lu'=>false],[],5,0),
            'menus'=>$permissions->findBy(['code_groupe_id'=>$code_groupe]),
            'groupe'=>$code_groupe,
            'liste_parent'=>$permissions,
            'donnees'=>$registry->getRepository(Qt::class)->findAll(),
            'exploitants'=>$exploitants,
            'quotas'=>$exploitants_quota,
        ]);

        } else {
            return $this->redirectToRoute('app_no_permission_user_active');
        }
      }
    }

    #[Route('snvlt/qtr/details/{numero_foret}', name: 'qtr_numero_foret')]
    public function qtr_numero_foret(
        Request $request,
        UserRepository $userRepository,
        ManagerRegistry $registry,
        string $numero_foret
    ): Response
    {
        if(!$request->getSession()->has('user_session')){
            return $this->redirectToRoute('app_login');
        } else {
            if (
                $this->isGranted('ROLE_ADMINISTRATIF') or
                $this->isGranted('ROLE_MINEF') or
                $this->isGranted('ROLE_ADMIN')
            ) {
                $user = $userRepository->find($this->getUser());
                $code_groupe = $user->getCodeGroupe()->getId();


                $details= array();


                $qts = $registry->getRepository(Qt::class)->findBy(['numero_foret'=>$numero_foret]);

                foreach ($qts as $qt){

                    $details[] = array(
                        'exploitant'=>$qt->getRsExp(),
                        'usine'=>$qt->getRsUsine(),
                        'date_chargement'=>$qt->getDateChargementbrh()->format('d/m/Y'),
                        'cubage'=>round($qt->getCubage(),3)
                    );

                }

                return  new JsonResponse(json_encode($details));


            } else {
                return $this->redirectToRoute('app_no_permission_user_active');
            }
        }

    }
}