<?php

namespace App\Service;

use App\Entity\Administration\Notification;
use App\Entity\DemandeAutorisation\NouvelleDemande;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class NotificationService
{
    private $entityManager;
    private $userRepository;

    public function __construct(EntityManagerInterface $entityManager, UserRepository $userRepository)
    {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
    }

    public function sendNotificationForStep(NouvelleDemande $demande, $detail, $currentUser): void
    {
        $usersToNotify = [];
        if ($detail->getTypeService() === 'DIRECTION' && $detail->getCodeDirection()) {
            $usersToNotify = $this->userRepository->findBy(['code_direction' => $detail->getCodeDirection()]);
        } elseif ($detail->getTypeService() === 'SERVICE' && $detail->getCodeService()) {
            $usersToNotify = $this->userRepository->findBy(['code_service' => $detail->getCodeService()]);
        }

        foreach ($usersToNotify as $user) {
            $notification = new Notification();
            $notification->setSujet('Nouvelle demande à traiter');
            $notification->setDescription('Une nouvelle demande de type "' . $demande->getTypeDemande()->getDesignation() . '" a été créée et nécessite votre attention.');
            $notification->setFromUser($currentUser->getUserIdentifier());
            $notification->setToUser($user);
            $notification->setCreatedAt(new \DateTimeImmutable());
            $notification->setLu(false);
            $notification->setRelatedToEntity('NouvelleDemande');
            $notification->setRelatedToId($demande->getId());
            $this->entityManager->persist($notification);
        }

        $this->entityManager->flush();
    }
}
