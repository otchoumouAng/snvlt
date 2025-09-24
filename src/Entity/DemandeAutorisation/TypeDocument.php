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
use Symfony\Component\Serializer\Annotation\Groups;

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
    #[Groups(['document:list', 'demande:details'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['document:list', 'demande:details'])]
    private ?string $designation = null;

    #[ORM\Column(name: 'fichier_special', type: 'boolean', options: ['default' => false])]
    private ?bool $fichierSpecial = false;

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

    public function isFichierSpecial(): ?bool
    {
        return $this->fichierSpecial;
    }

    public function setFichierSpecial(bool $fichierSpecial): static
    {
        $this->fichierSpecial = $fichierSpecial;

        return $this;
    }
}

