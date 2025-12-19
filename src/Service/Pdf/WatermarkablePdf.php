<?php

namespace App\Service\Pdf;

use setasign\Fpdi\Fpdi;

/**
 * Cette classe étend Fpdi pour ajouter une fonctionnalité de rotation de texte.
 */
class WatermarkablePdf extends Fpdi
{
    protected $angle = 0;

    /**
     * Applique une rotation sur le texte à venir.
     */
    public function rotate(float $angle, float $x = -1, float $y = -1): void
    {
        if ($x == -1) {
            $x = $this->x;
        }
        if ($y == -1) {
            $y = $this->y;
        }
        if ($this->angle != 0) {
            $this->_out('Q');
        }
        $this->angle = $angle;
        if ($angle != 0) {
            $angle_rad = $angle * M_PI / 180;
            $c = cos($angle_rad);
            $s = sin($angle_rad);
            $cx = $x * $this->k;
            $cy = ($this->h - $y) * $this->k;
            $this->_out(sprintf('q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm', $c, $s, -$s, $c, $cx, $cy, -$cx, -$cy));
        }
    }

    /**
     * Assure que la rotation est réinitialisée à la fin de chaque page.
     */
    public function _endpage()
    {
        if ($this->angle != 0) {
            $this->angle = 0;
            $this->_out('Q');
        }
        parent::_endpage();
    }
}