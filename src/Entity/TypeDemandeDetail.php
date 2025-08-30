<?php

namespace App\Entity;

use App\Repository\TypeDemandeDetailRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TypeDemandeDetailRepository::class)]
#[ORM\Table(name: "type_demande_detail", schema: "metier")]
class TypeDemandeDetail
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $type_demande_id = null;

    #[ORM\Column]
    private ?int $type_document_id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: "type_document_id", referencedColumnName: "id")]
    private ?TypeDocument $typeDocument = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getTypeDocumentId(): ?int
    {
        return $this->type_document_id;
    }

    public function setTypeDocumentId(int $type_document_id): static
    {
        $this->type_document_id = $type_document_id;

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
