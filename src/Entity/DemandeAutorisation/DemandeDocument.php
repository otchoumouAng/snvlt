<?php
/*
    - shema: metier
    - table: aut_demande_document
    - Entité de liaison pour la relation ManyToMany entre NouvelleDemande et Document
*/

namespace App\Entity\DemandeAutorisation;

use App\Repository\DemandeDocumentRepository;
use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity(repositoryClass: DemandeDocumentRepository::class)]
#[ORM\Table(name: "aut_demande_document", schema: "metier")]
class DemandeDocument
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'demandeDocuments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?NouvelleDemande $demande = null;

    #[ORM\ManyToOne(inversedBy: 'demandeDocuments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Document $document = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDemande(): ?NouvelleDemande
    {
        return $this->demande;
    }

    public function setDemande(?NouvelleDemande $demande): static
    {
        $this->demande = $demande;

        return $this;
    }

    public function getDocument(): ?Document
    {
        return $this->document;
    }

    public function setDocument(?Document $document): static
    {
        $this->document = $document;

        return $this;
    }
}
