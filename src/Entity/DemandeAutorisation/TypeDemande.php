<?php

/*
    - master data
    - shema: metier
    - table: aut_type_demande
    - Gestion des TypeDemande
    - Cette entité nous permet de CRUD les types de demande (Ex: Reprise d'activite, etc...)
*/

namespace App\Entity\DemandeAutorisation;

use App\Entity\DemandeAutorisation\Traits\AuditTrait;
use App\Repository\DemandeAutorisation\TypeDemandeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

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

    #[ORM\OneToMany(mappedBy: 'categorie_activite', targetEntity: \App\Entity\Paiement\CatalogueServices::class)]
    private Collection $catalogueServices;

    public function __construct()
    {
        $this->modeleCommunications = new ArrayCollection();
        $this->catalogueServices = new ArrayCollection();
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
            $catalogueService->setCategorieActivite($this);
        }

        return $this;
    }

    public function removeCatalogueService(\App\Entity\Paiement\CatalogueServices $catalogueService): static
    {
        if ($this->catalogueServices->removeElement($catalogueService)) {
            // set the owning side to null (unless already changed)
            if ($catalogueService->getCategorieActivite() === $this) {
                $catalogueService->setCategorieActivite(null);
            }
        }

        return $this;
    }
}
