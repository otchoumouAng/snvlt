<?php

namespace App\Entity\Requetes;

use App\Repository\Requetes\QtRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'metier.qt')]
#[ORM\Entity(repositoryClass: QtRepository::class)]
class Qt
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?int $id_exp = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?int $numero_exp = null;

    #[ORM\Column(length: 255, nullable: true)]
        private ?string $rs_exp = null;

    #[ORM\Column(length: 255, nullable: true)]
        private ?string $mrt_exp = null;

    #[ORM\Column(length: 255, nullable: true)]
        private ?string $numero_foret = null;

    #[ORM\Column(length: 255, nullable: true)]
        private ?int $id_usine = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $date_chargementbrh = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?float $cubage = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?float $quota = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $rs_usine = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?float $tiers_quota = null;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int|null $id
     */
    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return int|null
     */
    public function getIdExp(): ?int
    {
        return $this->id_exp;
    }

    /**
     * @param int|null $id_exp
     */
    public function setIdExp(?int $id_exp): void
    {
        $this->id_exp = $id_exp;
    }

    /**
     * @return int|null
     */
    public function getNumeroExp(): ?int
    {
        return $this->numero_exp;
    }

    /**
     * @param int|null $numero_exp
     */
    public function setNumeroExp(?int $numero_exp): void
    {
        $this->numero_exp = $numero_exp;
    }

    /**
     * @return string|null
     */
    public function getRsExp(): ?string
    {
        return $this->rs_exp;
    }

    /**
     * @param string|null $rs_exp
     */
    public function setRsExp(?string $rs_exp): void
    {
        $this->rs_exp = $rs_exp;
    }

    /**
     * @return string|null
     */
    public function getMrtExp(): ?string
    {
        return $this->mrt_exp;
    }

    /**
     * @param string|null $mrt_exp
     */
    public function setMrtExp(?string $mrt_exp): void
    {
        $this->mrt_exp = $mrt_exp;
    }

    /**
     * @return string|null
     */
    public function getNumeroForet(): ?string
    {
        return $this->numero_foret;
    }

    /**
     * @param string|null $numero_foret
     */
    public function setNumeroForet(?string $numero_foret): void
    {
        $this->numero_foret = $numero_foret;
    }

    /**
     * @return int|null
     */
    public function getIdUsine(): ?int
    {
        return $this->id_usine;
    }

    /**
     * @param int|null $id_usine
     */
    public function setIdUsine(?int $id_usine): void
    {
        $this->id_usine = $id_usine;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getDateChargementbrh(): ?\DateTimeInterface
    {
        return $this->date_chargementbrh;
    }

    /**
     * @param \DateTimeInterface|null $date_chargementbrh
     */
    public function setDateChargementbrh(?\DateTimeInterface $date_chargementbrh): void
    {
        $this->date_chargementbrh = $date_chargementbrh;
    }

    /**
     * @return float|null
     */
    public function getCubage(): ?float
    {
        return $this->cubage;
    }

    /**
     * @param float|null $cubage
     */
    public function setCubage(?float $cubage): void
    {
        $this->cubage = $cubage;
    }

    /**
     * @return float|null
     */
    public function getQuota(): ?float
    {
        return $this->quota;
    }

    /**
     * @param float|null $quota
     */
    public function setQuota(?float $quota): void
    {
        $this->quota = $quota;
    }

    /**
     * @return string|null
     */
    public function getRsUsine(): ?string
    {
        return $this->rs_usine;
    }

    /**
     * @param string|null $rs_usine
     */
    public function setRsUsine(?string $rs_usine): void
    {
        $this->rs_usine = $rs_usine;
    }

    /**
     * @return float|null
     */
    public function getTiersQuota(): ?float
    {
        return $this->tiers_quota;
    }

    /**
     * @param float|null $tiers_quota
     */
    public function setTiersQuota(?float $tiers_quota): void
    {
        $this->tiers_quota = $tiers_quota;
    }


}
