<?php

namespace App\Service;

use App\Entity\Oeuvre;

class CommissionCalculator
{
    private float $taux;

    public function __construct(float $tauxCommission = 0.20)
    {
        $this->taux = $tauxCommission;
    }

    public function calculerCommission(Oeuvre $oeuvre): float
    {
        // Using getPrice() instead of getPrix() as per Entity definition
        return (float) $oeuvre->getPrice() * $this->taux;
    }
}
