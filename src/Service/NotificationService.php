<?php

namespace App\Service;

use App\Entity\Administration\Notification;
use App\Entity\DemandeAutorisation\NouvelleDemande;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class NotificationService
{
    private $entityManager;
    private $userRepository;
    private $mailer;
    private $twig;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        MailerInterface $mailer,
        Environment $twig
    ) {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->mailer = $mailer;
        $this->twig = $twig;
    }

    /**
     * Notifies an applicant about a change in their request status via in-app notification and email.
     */
    public function notifyApplicant(NouvelleDemande $demande, string $subject, string $emailTemplate, array $emailContext = []): void
    {
        $applicant = $demande->getOperateur();
        if (!$applicant) {
            return;
        }

        // 1. Create in-app notification
        $notification = new Notification();
        $notification->setSujet($subject);
        $notification->setDescription("Votre demande n°" . $demande->getCodeSuivie() . " a été mise à jour.");
        $notification->setFromUser('system'); // Or a specific admin user
        $notification->setToUser($applicant);
        $notification->setCreatedAt(new \DateTimeImmutable());
        $notification->setLu(false);
        $notification->setRelatedToEntity('NouvelleDemande');
        $notification->setRelatedToId($demande->getId());
        $this->entityManager->persist($notification);

        // 2. Send email
        if ($applicant->getEmail()) {
            try {
                $emailContext['demande'] = $demande; // Ensure demand is always available in the template

                $emailBody = $this->twig->render($emailTemplate, $emailContext);

                $email = (new Email())
                    ->from($this->twig->getGlobals()['app_email'] ?? 'no-reply@localhost')
                    ->to($applicant->getEmail())
                    ->subject($subject)
                    ->html($emailBody);

                $this->mailer->send($email);
            } catch (\Exception $e) {
                // Log the error, but don't block the process
                // e.g., use Psr\Log\LoggerInterface
            }
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
