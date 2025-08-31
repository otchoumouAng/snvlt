<?php

/*
 * - shema: metier
 * - table: aut_document
 * - Gestion des documents uploadés lors d'une NouvelleDemande d'autorisation à un acte
 */

namespace App\Entity\DemandeAutorisation;

use App\Entity\DemandeAutorisation\Traits\AuditTrait;
use App\Repository\DocumentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DocumentRepository::class)]
#[ORM\Table(name: "aut_document", schema: "metier")]
#[ORM\HasLifecycleCallbacks]
class Document
{
    // Utilisation du Trait pour inclure tous les champs d'audit (createdAt, createdBy, etc.)
    use AuditTrait;
    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    private ?string $statut = null;

    #[ORM\Column(length: 255)]
    private ?string $path = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: "type_document_id", referencedColumnName: "id", nullable: false)]
    private ?TypeDocument $typeDocument = null;

    #[ORM\OneToMany(mappedBy: 'document', targetEntity: DemandeDocument::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $demandeDocuments;

    public function __construct()
    {
        $this->demandeDocuments = new ArrayCollection();
    }

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

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(string $path): static
    {
        $this->path = $path;
        return $this;
    }

    public function getTypeDocument(): ?TypeDocument
    {
        return $this->typeDocument;
    }

    public function setTypeDocument(?TypeDocument $typeDocument): static
    {
        $this->typeDocument = $typeDocument;
        return $this;
    }

    /**
     * @return Collection<int, DemandeDocument>
     */
    public function getDemandeDocuments(): Collection
    {
        return $this->demandeDocuments;
    }

    public function addDemandeDocument(DemandeDocument $demandeDocument): static
    {
        if (!$this->demandeDocuments->contains($demandeDocument)) {
            $this->demandeDocuments->add($demandeDocument);
            $demandeDocument->setDocument($this);
        }
        return $this;
    }

    public function removeDemandeDocument(DemandeDocument $demandeDocument): static
    {
        if ($this->demandeDocuments->removeElement($demandeDocument)) {
            // La logique de suppression est gérée par orphanRemoval=true
            // et la relation non-nullable dans DemandeDocument.
        }
        return $this;
    }
}

