<?php

namespace App\Services;

class CommissionService
{
    const MIN_COMMISSION = 500000; // 50,000 Toman = 500,000 Rial
    const MAX_COMMISSION = 50000000; // 5,000,000 Toman = 50,000,000 Rial

    public function calculateCommission(float $quantity, int $totalAmount): int
    {
        $rate = $this->getCommissionRate($quantity);
        $commission = $totalAmount * $rate; // Commission based on total transaction amount

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
