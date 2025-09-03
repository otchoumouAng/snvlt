<?php

namespace App\Entity\References;

use App\Entity\DemandeAutorisation\Traits\AuditTrait;
use App\Entity\DemandeAutorisation\TypeDemande;
use App\Repository\References\CatalogueServicesRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CatalogueServicesRepository::class)]
#[ORM\Table(name: 'pay_trans_catalogue_services', schema: 'metier')]
#[ORM\HasLifecycleCallbacks]
class CatalogueServices
{
    use AuditTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $code_service = null;

    #[ORM\Column(length: 255)]
    private ?string $designation = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 0)]
    private ?string $montant_fcfa = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $note = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?TypesService $type_service = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?CategoriesActivite $categorie_activite = null;

    #[ORM\ManyToOne]
    private ?TypesDemandeur $type_demandeur = null;

    #[ORM\ManyToOne]
    private ?TypeDemande $type_demande = null;

    #[ORM\ManyToOne]
    private ?RegimesFiscaux $regime_fiscal = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCodeService(): ?string
    {
        return $this->code_service;
    }

    public function setCodeService(string $code_service): static
    {
        $this->code_service = $code_service;

        return $this;
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

    public function getMontantFcfa(): ?string
    {
        return $this->montant_fcfa;
    }

    public function setMontantFcfa(string $montant_fcfa): static
    {
        $this->montant_fcfa = $montant_fcfa;

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

    public function getTypeService(): ?TypesService
    {
        return $this->type_service;
    }

    public function setTypeService(?TypesService $type_service): static
    {
        $this->type_service = $type_service;

        return $this;
    }

    public function getCategorieActivite(): ?CategoriesActivite
    {
        return $this->categorie_activite;
    }

    public function setCategorieActivite(?CategoriesActivite $categorie_activite): static
    {
        $this->categorie_activite = $categorie_activite;

        return $this;
    }

    public function getTypeDemandeur(): ?TypesDemandeur
    {
        return $this->type_demandeur;
    }

    public function setTypeDemandeur(?TypesDemandeur $type_demandeur): static
    {
        $this->type_demandeur = $type_demandeur;

        return $this;
    }

    public function getTypeDemande(): ?TypeDemande
    {
        return $this->type_demande;
    }

    public function setTypeDemande(?TypeDemande $type_demande): static
    {
        $this->type_demande = $type_demande;

        return $this;
    }

    public function getRegimeFiscal(): ?RegimesFiscaux
    {
        return $this->regime_fiscal;
    }

    public function setRegimeFiscal(?RegimesFiscaux $regime_fiscal): static
    {
        $this->regime_fiscal = $regime_fiscal;

        return $this;
    }
}
