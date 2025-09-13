<?php

/*
    - shema: metier
    - table: aut_nouvelle_demande
    - Gestion des NouvelleDemande
    - Cette entité nous permet de CRUD les nouvelles demandes d'autorisation, de suivre l'évolution d'un demande(EtapeValidation), consulter les documents liés à une demande et de gérer le fichier uploadé pour une nouvelle demande créée.

*/

namespace App\Entity\DemandeAutorisation;
use App\Entity\DemandeAutorisation\Traits\AuditTrait;
use App\Repository\DemandeAutorisation\NouvelleDemandeRepository;
use App\Entity\Paiement\TypePaiement;
use App\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NouvelleDemandeRepository::class)]
#[ORM\Table(name: "aut_nouvelle_demande", schema: "metier")]
#[ORM\HasLifecycleCallbacks]
class NouvelleDemande
{
    use AuditTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: "operateur_id", referencedColumnName: "id", nullable: false)]
    private ?User $operateur = null;

    #[ORM\Column(name: "code_suivie", length: 20)]
    private ?string $codeSuivie = null;

    #[ORM\Column(length: 255)]
    private ?string $statut = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: "type_demande_id", referencedColumnName: "id", nullable: false)]
    private ?TypeDemande $typeDemande = null;

    #[ORM\ManyToOne(targetEntity: TypePaiement::class, inversedBy: 'nouvelleDemandes')]
    #[ORM\JoinColumn(name: "type_paiement_id", referencedColumnName: "id", nullable: true)]
    private ?TypePaiement $typePaiement = null;

    #[ORM\OneToMany(mappedBy: 'demande', targetEntity: EtapeValidation::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $etapesValidation;
    
    #[ORM\OneToMany(mappedBy: 'demande', targetEntity: DemandeDocument::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $demandeDocuments;

    public function __construct()
    {
        $this->etapesValidation = new ArrayCollection();
        $this->demandeDocuments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOperateur(): ?User
    {
        return $this->operateur;
    }

    public function setOperateur(?User $operateur): static
    {
        $this->operateur = $operateur;
        return $this;
    }

    public function getCodeSuivie(): ?string
    {
        return $this->codeSuivie;
    }

    public function setCodeSuivie(string $codeSuivie): static
    {
        $this->codeSuivie = $codeSuivie;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getTypeDemande(): ?TypeDemande
    {
        return $this->typeDemande;
    }

    public function setTypeDemande(?TypeDemande $typeDemande): static
    {
        $this->typeDemande = $typeDemande;
        return $this;
    }

    public function getTypePaiement(): ?TypePaiement
    {
        return $this->typePaiement;
    }

    public function setTypePaiement(?TypePaiement $typePaiement): static
    {
        $this->typePaiement = $typePaiement;
        return $this;
    }

    /**
     * @return Collection<int, EtapeValidation>
     */
    public function getEtapesValidation(): Collection
    {
        return $this->etapesValidation;
    }

    public function addEtapeValidation(EtapeValidation $etapeValidation): static
    {
        if (!$this->etapesValidation->contains($etapeValidation)) {
            $this->etapesValidation->add($etapeValidation);
            $etapeValidation->setDemande($this);
        }
        return $this;
    }

    public function removeEtapeValidation(EtapeValidation $etapeValidation): static
    {
        if ($this->etapesValidation->removeElement($etapeValidation)) {
            if ($etapeValidation->getDemande() === $this) {
                $etapeValidation->setDemande(null);
            }
        }
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
            $demandeDocument->setDemande($this);
        }
        return $this;
    }

    public function removeDemandeDocument(DemandeDocument $demandeDocument): static
    {
        if ($this->demandeDocuments->removeElement($demandeDocument)) {
            if ($demandeDocument->getDemande() === $this) {
                $demandeDocument->setDemande(null);
            }
        }
        return $this;
    }
}
