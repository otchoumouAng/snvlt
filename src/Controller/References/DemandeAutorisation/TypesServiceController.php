<?php

namespace App\Controller\References\DemandeAutorisation;

use App\Controller\References\BaseReferenceController;
use App\Entity\References\TypesService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\MenuRepository;
use App\Repository\Administration\NotificationRepository;
use App\Repository\MenuPermissionRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

#[Route('/admin/reference/types-service')]
class TypesServiceController extends BaseReferenceController
{
    public function __construct()
    {
        $this->entityClass = TypesService::class;
        $this->entityName = 'app_types_service';
        $this->title = 'Types de Gatégorie';
    }

    #[Route('/', name: 'app_types_service_index', methods: ['GET'])]
    public function index(MenuRepository $menus, NotificationRepository $notification, MenuPermissionRepository $permissions, UserRepository $userRepository): Response
    {
        return parent::index($menus, $notification, $permissions, $userRepository);
    }

    #[Route('/data', name: 'app_types_service_data', methods: ['GET'])]
    public function getData(EntityManagerInterface $em): JsonResponse
    {
        return parent::getData($em);
    }

    #[Route('/save', name: 'app_types_service_save', methods: ['POST'])]
    public function save(Request $request, EntityManagerInterface $em): JsonResponse
    {
        return parent::save($request, $em);
    }

    #[Route('/delete/{id}', name: 'app_types_service_delete', methods: ['DELETE'])]
    public function delete(int $id, EntityManagerInterface $em): JsonResponse
    {
        return parent::delete($id, $em);
    }
}
