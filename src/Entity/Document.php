<?php

namespace App\Entity;

use App\Repository\DocumentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DocumentRepository::class)]
#[ORM\Table(name: "document", schema: "metier")]
class Document
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    private ?string $statut = null;

    #[ORM\Column]
    private ?\DateTime $dateAjout = null;

    #[ORM\ManyToOne(inversedBy: 'documents')]
    #[ORM\JoinColumn(name: "demande_id", referencedColumnName: "id", nullable: false)]
    private ?NouvelleDemande $demande = null;

    #[ORM\Column]
    private ?int $typeDocumentId = null;

    #[ORM\Column(length: 255)]
    private ?string $filePath = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

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

    public function getDateAjout(): ?\DateTime
    {
        return $this->dateAjout;
    }

    public function setDateAjout(\DateTime $dateAjout): static
    {
        $this->dateAjout = $dateAjout;

        return $this;
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

    public function getTypeDocumentId(): ?int
    {
        return $this->typeDocumentId;
    }

    public function setTypeDocumentId(int $typeDocumentId): static
    {
        $this->typeDocumentId = $typeDocumentId;

        return $this;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function setFilePath(string $filePath): static
    {
        $this->filePath = $filePath;

        return $this;
    }
}
