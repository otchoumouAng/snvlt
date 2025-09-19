<?php

namespace App\Entity;

use App\Entity\Paiement\CatalogueServices;
use App\Entity\References\ServiceMinef;
use App\Repository\DetailsCircuitActesRepository;
use Doctrine\ORM\Mapping as ORM;
#[ORM\Table(name: 'metier.details_circuit_actes')]
#[ORM\Entity(repositoryClass: DetailsCircuitActesRepository::class)]
class DetailsCircuitActes
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'detailsCircuitActes')]
    private ?CatalogueServices $code_acte = null;

    #[ORM\ManyToOne(inversedBy: 'detailsCircuitActes')]
    private ?ServiceMinef $code_service = null;

    #[ORM\Column]
    private ?int $ordre = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCodeActe(): ?CatalogueServices
    {
        return $this->code_acte;
    }

    public function setCodeActe(?CatalogueServices $code_acte): static
    {
        $this->code_acte = $code_acte;

        return $this;
    }

    public function getCodeService(): ?ServiceMinef
    {
        return $this->code_service;
    }

    public function setCodeService(?ServiceMinef $code_service): static
    {
        $this->code_service = $code_service;

        return $this;
    }

    public function getOrdre(): ?int
    {
        return $this->ordre;
    }

    public function setOrdre(int $ordre): static
    {
        $this->ordre = $ordre;

        return $this;
    }
}
