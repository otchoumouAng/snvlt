<?php

namespace App\Entity;

use App\Repository\EtapeValidationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EtapeValidationRepository::class)]
#[ORM\Table(name: "etape_validation", schema: "metier")]
class EtapeValidation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    private $nom;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private $dateTraitement;

    #[ORM\Column(type: 'string', length: 50)]
    private $statut;

    #[ORM\Column(type: 'integer')]
    private $ordre;

    #[ORM\Column(type: 'text', nullable: true)]
    private $details;

    #[ORM\ManyToOne(targetEntity: NouvelleDemande::class, inversedBy: 'etapesValidation')]
    #[ORM\JoinColumn(nullable: false)]
    private $demande;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }

    public function getDateTraitement(): ?\DateTimeInterface
    {
        return $this->dateTraitement;
    }

    public function setDateTraitement(?\DateTimeInterface $dateTraitement): self
    {
        $this->dateTraitement = $dateTraitement;
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): self
    {
        $this->statut = $statut;
        return $this;
    }

    public function getOrdre(): ?int
    {
        return $this->ordre;
    }

    public function setOrdre(int $ordre): self
    {
        $this->ordre = $ordre;
        return $this;
    }

    public function getDetails(): ?string
    {
        return $this->details;
    }

    public function setDetails(?string $details): self
    {
        $this->details = $details;
        return $this;
    }

    public function getDemande(): ?NouvelleDemande
    {
        return $this->demande;
    }

    public function setDemande(?NouvelleDemande $demande): self
    {
        $this->demande = $demande;
        return $this;
    }
}
