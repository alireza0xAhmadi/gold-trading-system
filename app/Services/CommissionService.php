<?php
namespace App\Services;

class CommissionService
{
    const MIN_COMMISSION = 50000; // 50 thousand Toman in Rial
    const MAX_COMMISSION = 5000000; // 5 million Toman in Rial

    public function calculateCommission(float $quantity): int
    {
        $rate = $this->getCommissionRate($quantity);
        $commission = $quantity * $rate * 1000000; // Convert percentage to Rial (assuming average price)

        // Apply minimum and maximum limits
        $commission = max($commission, self::MIN_COMMISSION);
        $commission = min($commission, self::MAX_COMMISSION);

        return (int) $commission;
    }

    private function getCommissionRate(float $quantity): float
    {
        if ($quantity <= 1) {
            return 0.02; // 2%
        } elseif ($quantity <= 10) {
            return 0.015; // 1.5%
        } else {
            return 0.01; // 1%
        }
    }
}
