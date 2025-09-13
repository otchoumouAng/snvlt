<?php

namespace App\Entity\Paiement;

use App\Repository\Paiement\TypePaiementRepository;
use App\Entity\DemandeAutorisation\NouvelleDemande;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TypePaiementRepository::class)]
#[ORM\Table(name: 'pay_type_paiement', schema: 'metier')]
class TypePaiement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $libelle = null;

    #[ORM\OneToMany(mappedBy: 'typePaiement', targetEntity: NouvelleDemande::class)]
    private Collection $nouvelleDemandes;

    public function __construct()
    {
        $this->nouvelleDemandes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        $this->libelle = $libelle;

        return $this;
    }

    /**
     * @return Collection<int, NouvelleDemande>
     */
    public function getNouvelleDemandes(): Collection
    {
        return $this->nouvelleDemandes;
    }

    public function addNouvelleDemande(NouvelleDemande $nouvelleDemande): static
    {
        if (!$this->nouvelleDemandes->contains($nouvelleDemande)) {
            $this->nouvelleDemandes->add($nouvelleDemande);
            $nouvelleDemande->setTypePaiement($this);
        }

        return $this;
    }

    public function removeNouvelleDemande(NouvelleDemande $nouvelleDemande): static
    {
        if ($this->nouvelleDemandes->removeElement($nouvelleDemande)) {
            // set the owning side to null (unless already changed)
            if ($nouvelleDemande->getTypePaiement() === $this) {
                $nouvelleDemande->setTypePaiement(null);
            }
        }

        return $this;
    }
}
