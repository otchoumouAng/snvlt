<?php

/*
    - master data
    - shema: metier
    - table: aut_type_document
    - Gestion des TypeDocument
    - Cette entité nous permet de CRUD un type de document
*/

namespace App\Entity\DemandeAutorisation;

use App\Entity\DemandeAutorisation\Traits\AuditTrait;
use App\Repository\DemandeAutorisation\TypeDocumentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TypeDocumentRepository::class)]
#[ORM\Table(name: "aut_type_document", schema: "metier")]
#[ORM\HasLifecycleCallbacks]
class TypeDocument
{
    // Utilisation du Trait pour inclure tous les champs d'audit
    use AuditTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $designation = null;

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
}

