<?php

/*
    - shema: metier
    - table: aut_etape_validation
    - Gestion des EtapeValidation
    - Cette entité nous permet de suivre l'évolution d'un demande(EtapeValidation) et gérer les détails d'une demande d'autorisation (NouvelleDemande)
    - Ex: pour une demande choisie, on peut voir les étapes de validations franchises, l'étape en cours et les etapes restantes
    - Pour chaque Etape, nous pouvons consulter et éditer les détails

*/

namespace App\Entity\DemandeAutorisation;

use App\Repository\EtapeValidationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EtapeValidationRepository::class)]
#[ORM\Table(name: "aut_etape_validation", schema: "metier")]
class EtapeValidation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(name: "date_traitement", type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateTraitement = null;

    #[ORM\Column(length: 50)]
    private ?string $statut = null;

    #[ORM\Column]
    private ?int $ordre = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $details = null;

    #[ORM\ManyToOne(inversedBy: 'etapesValidation')]
    #[ORM\JoinColumn(nullable: false)]
    private ?NouvelleDemande $demande = null;

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
