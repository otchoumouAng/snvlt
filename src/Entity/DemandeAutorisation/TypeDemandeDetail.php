<?php

/*
    - shema: metier
    - table: aut_type_demande_detail
    - Gestion des TypeDocument en fonction du TypeDemande
    - Cette entité nous permet de savoir les differents type de fichiers requis pour une demande donnée
*/

namespace App\Entity\DemandeAutorisation;
use App\Entity\DemandeAutorisation\Traits\AuditTrait;
use App\Repository\TypeDemandeDetailRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TypeDemandeDetailRepository::class)]
#[ORM\Table(name: "aut_type_demande_detail", schema: "metier")]
class TypeDemandeDetail
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: "type_demande_id", referencedColumnName: "id", nullable: false)]
    private ?TypeDemande $typeDemande = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: "type_document_id", referencedColumnName: "id", nullable: false)]
    private ?TypeDocument $typeDocument = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getTypeDocument(): ?TypeDocument
    {
        return $this->typeDocument;
    }

    public function setTypeDocument(?TypeDocument $typeDocument): static
    {
        $this->typeDocument = $typeDocument;
        return $this;
    }
}
