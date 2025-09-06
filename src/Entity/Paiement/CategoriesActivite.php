<?php

namespace App\Entity\Paiement;

use App\Repository\Paiement\CategoriesActiviteRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\DemandeAutorisation\Traits\AuditTrait;

#[ORM\Entity(repositoryClass: CategoriesActiviteRepository::class)]
#[ORM\Table(name: 'pay_ref_categories_activite', schema: 'metier')]
#[ORM\HasLifecycleCallbacks]
class CategoriesActivite
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
