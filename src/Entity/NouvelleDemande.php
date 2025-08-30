<?php

namespace App\Entity;

use App\Repository\NouvelleDemandeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NouvelleDemandeRepository::class)]
#[ORM\Table(name: "nouvelle_demande", schema: "metier")]
class NouvelleDemande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $raison_social = null;

    #[ORM\Column]
    private ?int $type_demande_id = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $type_demande = null;

    #[ORM\Column]
    private ?int $operateur_id = null;

    #[ORM\Column(length: 20)]
    private ?string $code_suivie = null;

    #[ORM\Column]
    private ?int $statut = null;

    #[ORM\Column]
    private ?int $desactivate = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column(length: 255)]
    private ?string $created_by = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $update_by = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $update_at = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $titre = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRaisonSocial(): ?string
    {
        return $this->raison_social;
    }

    public function setRaisonSocial(string $raison_social): static
    {
        $this->raison_social = $raison_social;

        return $this;
    }

    public function getTypeDemande(): ?string
    {
        return $this->type_demande;
    }

    public function setTypeDemande(?string $type_demande): static
    {
        $this->type_demande = $type_demande;

        return $this;
    }

    public function getTypeDemandeId(): ?int
    {
        return $this->type_demande_id;
    }

    public function setTypeDemandeId(int $type_demande_id): static
    {
        $this->type_demande_id = $type_demande_id;

        return $this;
    }

    public function getOperateurId(): ?int
    {
        return $this->operateur_id;
    }

    public function setOperateurId(int $operateur_id): static
    {
        $this->operateur_id = $operateur_id;

        return $this;
    }

    public function getCodeSuivie(): ?string
    {
        return $this->code_suivie;
    }

    public function setCodeSuivie(string $code_suivie): static
    {
        $this->code_suivie = $code_suivie;

        return $this;
    }

    public function getStatut(): ?int
    {
        return $this->statut;
    }

    public function setStatut(int $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getDesactivate(): ?int
    {
        return $this->desactivate;
    }

    public function setDesactivate(int $desactivate): static
    {
        $this->desactivate = $desactivate;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getCreatedBy(): ?string
    {
        return $this->created_by;
    }

    public function setCreatedBy(string $created_by): static
    {
        $this->created_by = $created_by;

        return $this;
    }

    public function getUpdateBy(): ?string
    {
        return $this->update_by;
    }

    public function setUpdateBy(?string $update_by): static
    {
        $this->update_by = $update_by;

        return $this;
    }

    public function getUpdateAt(): ?\DateTimeImmutable
    {
        return $this->update_at;
    }

    public function setUpdateAt(?\DateTimeImmutable $update_at): static
    {
        $this->update_at = $update_at;

        return $this;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(?string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    #[ORM\OneToMany(mappedBy: 'demande', targetEntity: EtapeValidation::class, cascade: ['persist', 'remove'])]
    private Collection $etapesValidation;

    public function __construct()
    {
        $this->etapesValidation = new ArrayCollection();
    }

    /**
     * @return Collection<int, EtapeValidation>
     */
    public function getEtapesValidation(): Collection
    {
        return $this->etapesValidation;
    }

    public function addEtapeValidation(EtapeValidation $etapeValidation): self
    {
        if (!$this->etapesValidation->contains($etapeValidation)) {
            $this->etapesValidation[] = $etapeValidation;
            $etapeValidation->setDemande($this);
        }

        return $this;
    }

    public function removeEtapeValidation(EtapeValidation $etapeValidation): self
    {
        if ($this->etapesValidation->removeElement($etapeValidation)) {
            // set the owning side to null (unless already changed)
            if ($etapeValidation->getDemande() === $this) {
                $etapeValidation->setDemande(null);
            }
        }

        return $this;
    }
}
