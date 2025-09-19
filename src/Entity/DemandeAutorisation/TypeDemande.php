<?php

namespace App\Entity\DemandeAutorisation;

use App\Entity\DemandeAutorisation\Traits\AuditTrait;
use App\Repository\DemandeAutorisation\TypeDemandeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: TypeDemandeRepository::class)]
#[ORM\Table(name: "aut_type_demande", schema: "metier")]
#[ORM\HasLifecycleCallbacks]
class TypeDemande
{
    use AuditTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $designation = null;

    #[ORM\OneToMany(mappedBy: 'typeDemande', targetEntity: \App\Entity\References\ModeleCommunication::class)]
    private Collection $modeleCommunications;

    /**
     * CORRIGÉ: L'attribut mappedBy pointe maintenant vers 'typeDemande' 
     * dans l'entité CatalogueServices.
     */
    #[ORM\OneToMany(mappedBy: 'typeDemande', targetEntity: \App\Entity\Paiement\CatalogueServices::class)]
    private Collection $catalogueServices;

    #[ORM\OneToMany(mappedBy: 'typeDemande', targetEntity: TypeDemandeDetail::class, orphanRemoval: true)]
    #[Groups(['demande:details'])]
    private Collection $typeDemandeDetails;

    public function __construct()
    {
        $this->modeleCommunications = new ArrayCollection();
        $this->catalogueServices = new ArrayCollection();
        $this->typeDemandeDetails = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDesignation(): ?string
    {
        return $this->designation;
    }

    public function setDesignation(string $designation): static
    {
        $this->designation = $designation;
        return $this;
    }

    public function getLibelle(): ?string
    {
        return $this->designation;
    }

    public function setLibelle(string $libelle): static
    {
        $this->setDesignation($libelle);
        return $this;
    }

    /**
     * @return Collection<int, \App\Entity\References\ModeleCommunication>
     */
    public function getModeleCommunications(): Collection
    {
        return $this->modeleCommunications;
    }

    public function addModeleCommunication(\App\Entity\References\ModeleCommunication $modeleCommunication): static
    {
        if (!$this->modeleCommunications->contains($modeleCommunication)) {
            $this->modeleCommunications->add($modeleCommunication);
            $modeleCommunication->setTypeDemande($this);
        }

        return $this;
    }

    public function removeModeleCommunication(\App\Entity\References\ModeleCommunication $modeleCommunication): static
    {
        if ($this->modeleCommunications->removeElement($modeleCommunication)) {
            // set the owning side to null (unless already changed)
            if ($modeleCommunication->getTypeDemande() === $this) {
                $modeleCommunication->setTypeDemande(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, \App\Entity\Paiement\CatalogueServices>
     */
    public function getCatalogueServices(): Collection
    {
        return $this->catalogueServices;
    }

    public function addCatalogueService(\App\Entity\Paiement\CatalogueServices $catalogueService): static
    {
        if (!$this->catalogueServices->contains($catalogueService)) {
            $this->catalogueServices->add($catalogueService);
            // CORRIGÉ: Utilise le nouveau setter 'setTypeDemande'
            $catalogueService->setTypeDemande($this);
        }

        return $this;
    }

    public function removeCatalogueService(\App\Entity\Paiement\CatalogueServices $catalogueService): static
    {
        if ($this->catalogueServices->removeElement($catalogueService)) {
            // set the owning side to null (unless already changed)
            // CORRIGÉ: Utilise le nouveau getter et setter 'getTypeDemande' et 'setTypeDemande'
            if ($catalogueService->getTypeDemande() === $this) {
                $catalogueService->setTypeDemande(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, TypeDemandeDetail>
     */
    public function getTypeDemandeDetails(): Collection
    {
        return $this->typeDemandeDetails;
    }

    public function addTypeDemandeDetail(TypeDemandeDetail $typeDemandeDetail): static
    {
        if (!$this->typeDemandeDetails->contains($typeDemandeDetail)) {
            $this->typeDemandeDetails->add($typeDemandeDetail);
            $typeDemandeDetail->setTypeDemande($this);
        }

        return $this;
    }

    public function removeTypeDemandeDetail(TypeDemandeDetail $typeDemandeDetail): static
    {
        if ($this->typeDemandeDetails->removeElement($typeDemandeDetail)) {
            // set the owning side to null (unless already changed)
            if ($typeDemandeDetail->getTypeDemande() === $this) {
                $typeDemandeDetail->setTypeDemande(null);
            }
        }

        return $this;
    }
}