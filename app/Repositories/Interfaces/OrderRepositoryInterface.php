<?php

namespace App\Repositories\Interfaces;

use App\Models\Order;
use Illuminate\Database\Eloquent\Collection;

interface OrderRepositoryInterface
{
    public function create(array $data): Order;
    public function findById(int $id): ?Order;
    public function getActiveOrders(string $type): Collection;
    public function getMatchingOrders(string $type, int $price): Collection;
    public function getUserOrders(int $userId): Collection;
    public function updateRemainingQuantity(int $orderId, float $newQuantity): bool;
    public function cancelOrder(int $orderId): bool;
}
