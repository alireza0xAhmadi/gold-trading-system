<?php

namespace App\Repositories;

use App\Models\Order;
use App\Repositories\Interfaces\OrderRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class OrderRepository implements OrderRepositoryInterface
{
    public function create(array $data): Order
    {
        return Order::create($data);
    }

    public function findById(int $id): ?Order
    {
        return Order::with('user')->find($id);
    }

    public function getActiveOrders(string $type): Collection
    {
        return Order::query()->where('type', $type)
            ->where('status', 'active')
            ->where('remaining_quantity', '>', 0)
            ->with('user')
            ->orderBy($type === 'buy' ? 'price_per_gram' : 'price_per_gram', $type === 'buy' ? 'desc' : 'asc')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function getMatchingOrders(string $type, int $price): Collection
    {
        $operator = $type === 'buy' ? '>=' : '<=';

        return Order::query()->where('type', $type)
            ->where('status', 'active')
            ->where('remaining_quantity', '>', 0)
            ->where('price_per_gram', $operator, $price)
            ->with('user')
            ->orderBy($type === 'buy' ? 'price_per_gram' : 'price_per_gram', $type === 'buy' ? 'desc' : 'asc')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function getUserOrders(int $userId): Collection
    {
        return Order::query()->where('user_id', $userId)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function updateRemainingQuantity(int $orderId, float $newQuantity): bool
    {
        $order = Order::query()->find($orderId);
        if (!$order) return false;

        $order->remaining_quantity = $newQuantity;
        if ($newQuantity <= 0) {
            $order->status = 'completed';
        }

        return $order->save();
    }

    public function cancelOrder(int $orderId): bool
    {
        return Order::query()->where('id', $orderId)
            ->update(['status' => 'cancelled']);
    }
}
