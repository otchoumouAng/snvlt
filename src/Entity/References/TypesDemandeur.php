<?php

namespace App\Entity\References;

use App\Entity\DemandeAutorisation\Traits\AuditTrait;
use App\Repository\References\TypesDemandeurRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TypesDemandeurRepository::class)]
#[ORM\Table(name: 'pay_ref_types_demandeur', schema: 'metier')]
#[ORM\HasLifecycleCallbacks]
class TypesDemandeur
{
    use AuditTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $libelle = null;

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

    public function __toString(): string
    {
        return $this->libelle;
    }
}
