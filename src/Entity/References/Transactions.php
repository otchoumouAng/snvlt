<?php

namespace App\Entity\References;

use App\Entity\DemandeAutorisation\Traits\AuditTrait;
use App\Repository\References\TransactionsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TransactionsRepository::class)]
#[ORM\Table(name: 'trans_transactions', schema: 'metier')]
#[ORM\HasLifecycleCallbacks]
class Transactions
{
    use AuditTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $identifiant = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?CatalogueServices $service = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: '0')]
    private ?string $montant_fcfa = null;

    #[ORM\Column(length: 100)]
    private ?string $client_nom = null;

    #[ORM\Column(length: 150)]
    private ?string $client_prenom = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(length: 255)]
    private ?string $statut = null;

    #[ORM\Column(nullable: true)]
    private ?int $tresorpay_response_code = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $tresorpay_response_message = null;

    #[ORM\ManyToOne]
    private ?TypeDemande $typeDemande = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdentifiant(): ?string
    {
        return $this->identifiant;
    }

    public function setIdentifiant(string $identifiant): static
    {
        $this->identifiant = $identifiant;

        return $this;
    }

    public function getService(): ?CatalogueServices
    {
        return $this->service;
    }

    public function setService(?CatalogueServices $service): static
    {
        $this->service = $service;

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

    public function getClientNom(): ?string
    {
        return $this->client_nom;
    }

    public function setClientNom(string $client_nom): static
    {
        $this->client_nom = $client_nom;

        return $this;
    }

    public function getClientPrenom(): ?string
    {
        return $this->client_prenom;
    }

    public function setClientPrenom(string $client_prenom): static
    {
        $this->client_prenom = $client_prenom;

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): static
    {
        $this->telephone = $telephone;

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

    public function getTresorpayResponseCode(): ?int
    {
        return $this->tresorpay_response_code;
    }

    public function setTresorpayResponseCode(?int $tresorpay_response_code): static
    {
        $this->tresorpay_response_code = $tresorpay_response_code;

        return $this;
    }

    public function getTresorpayResponseMessage(): ?string
    {
        return $this->tresorpay_response_message;
    }

    public function setTresorpayResponseMessage(?string $tresorpay_response_message): static
    {
        $this->tresorpay_response_message = $tresorpay_response_message;

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
}
