<?php


/*
    - shema: metier
    - table: aut_validation_action
    - Gestion des ValidationAction
    - Cette entité nous permet gérer les étapes de validation d'une NouvelleDemande
*/

namespace App\Entity\DemandeAutorisation;
use App\Entity\User;
use App\Entity\DemandeAutorisation\Traits\AuditTrait;
use App\Repository\ValidationActionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ValidationActionRepository::class)]
#[ORM\Table(name: "aut_validation_action", schema: "metier")]
#[ORM\HasLifecycleCallbacks]
class ValidationAction
{
    use AuditTrait;
    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?NouvelleDemande $demande = null;

    #[ORM\ManyToOne] // Assurez-vous que l'entité User existe bien dans App\Entity
    #[ORM\JoinColumn(nullable: false)]
    private ?User $validator = null;

    #[ORM\Column(length: 255)]
    private ?string $statut = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $note = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $signaturePath = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDemande(): ?NouvelleDemande
    {
        return $this->demande;
    }

    public function setDemande(?NouvelleDemande $demande): static
    {
        $this->demande = $demande;
        return $this;
    }

    public function getValidator(): ?User
    {
        return $this->validator;
    }

    public function setValidator(?User $validator): static
    {
        $this->validator = $validator;
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): static
    {
        $this->note = $note;
        return $this;
    }

    public function getSignaturePath(): ?string
    {
        return $this->signaturePath;
    }

    public function setSignaturePath(?string $signaturePath): static
    {
        $this->signaturePath = $signaturePath;
        return $this;
    }
}
