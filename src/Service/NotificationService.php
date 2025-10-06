<?php

namespace App\Service;

use App\Entity\Administration\Notification;
use App\Entity\DemandeAutorisation\NouvelleDemande;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class NotificationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private MailerInterface $mailer,
        private Environment $twig,
        private WhatsappService $whatsappService
    ) {
    }

    public function createNotification(User $toUser, string $subject, string $description, ?string $emailTemplate = null, array $emailContext = []): void
    {
        // 1. In-app notification
        $notification = new Notification();
        $notification->setSujet($subject);
        $notification->setDescription($description);
        $notification->setToUser($toUser);
        $notification->setCreatedAt(new \DateTimeImmutable());
        $notification->setLu(false);
        // It might be useful to set fromUser and related entity if available
        // $notification->setFromUser($this->getUser());
        // $notification->setRelatedToEntity('NouvelleDemande');
        // $notification->setRelatedToId($demande->getId());
        $this->entityManager->persist($notification);

        // 2. Email notification
        if ($emailTemplate) {
            $html = $this->twig->render($emailTemplate, $emailContext);
            //snvlt@system2is.com
            $email = (new Email())
                ->from('no-reply@snvlt.com')
                ->to($toUser->getEmail())
                ->subject($subject)
                ->html($html);

            $this->mailer->send($email);
        }

        // 3. WhatsApp notification
        if ($toUser->getMobile()) {
            $this->whatsappService->sendMessage($toUser->getMobile(), $description);
        }

        $this->entityManager->flush();
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
