<?php

namespace App\Services;

use App\Models\User;
use App\Models\Order;
use App\Repositories\Interfaces\OrderRepositoryInterface;
use App\Repositories\Interfaces\TransactionRepositoryInterface;
use Illuminate\Support\Facades\DB;

class TradingService
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private TransactionRepositoryInterface $transactionRepository,
        private CommissionService $commissionService
    ) {}

    public function placeBuyOrder(int $userId, float $quantity, int $pricePerGram): array
    {
        return DB::transaction(function () use ($userId, $quantity, $pricePerGram) {
            $user = User::findOrFail($userId);
            $totalCost = $quantity * $pricePerGram;

            // Check balance
            if ($user->rial_balance < $totalCost) {
                throw new \Exception('Insufficient balance');
            }

            // Deduct balance
            $user->decrement('rial_balance', $totalCost);

            // Create order
            $order = $this->orderRepository->create([
                'user_id' => $userId,
                'type' => 'buy',
                'quantity' => $quantity,
                'remaining_quantity' => $quantity,
                'price_per_gram' => $pricePerGram,
            ]);

            // Match with sell orders
            $this->matchOrders($order);

            return ['success' => true, 'order' => $order];
        });
    }

    public function placeSellOrder(int $userId, float $quantity, int $pricePerGram): array
    {
        return DB::transaction(function () use ($userId, $quantity, $pricePerGram) {
            $user = User::findOrFail($userId);

            // Check gold balance
            if ($user->gold_balance < $quantity) {
                throw new \Exception('Insufficient gold balance');
            }

            // Reserve gold by deducting from balance (like we do with Rial for buy orders)
            $user->decrement('gold_balance', $quantity);

            // Create order
            $order = $this->orderRepository->create([
                'user_id' => $userId,
                'type' => 'sell',
                'quantity' => $quantity,
                'remaining_quantity' => $quantity,
                'price_per_gram' => $pricePerGram,
            ]);

            // Match with buy orders
            $this->matchOrders($order);

            return ['success' => true, 'order' => $order];
        });
    }

    private function matchOrders(Order $newOrder): void
    {
        $oppositeType = $newOrder->type === 'buy' ? 'sell' : 'buy';
        $matchingOrders = $this->orderRepository->getMatchingOrders(
            $oppositeType,
            $newOrder->price_per_gram
        );

        foreach ($matchingOrders as $existingOrder) {
            if ($newOrder->remaining_quantity <= 0) break;

            $tradeQuantity = min($newOrder->remaining_quantity, $existingOrder->remaining_quantity);

            if ($tradeQuantity > 0) {
                $this->executeTrade($newOrder, $existingOrder, $tradeQuantity);

                // Refresh the order to get updated remaining_quantity
                $newOrder->refresh();
            }
        }
    }

    private function executeTrade(Order $order1, Order $order2, float $quantity): void
    {
        $buyOrder = $order1->type === 'buy' ? $order1 : $order2;
        $sellOrder = $order1->type === 'sell' ? $order1 : $order2;

        $pricePerGram = $sellOrder->price_per_gram; // Seller's price
        $totalAmount = $quantity * $pricePerGram;

        // Fixed: Pass both quantity and totalAmount to commission calculation
        $commission = $this->commissionService->calculateCommission($quantity, $totalAmount);

        // Create transaction
        $this->transactionRepository->create([
            'buy_order_id' => $buyOrder->id,
            'sell_order_id' => $sellOrder->id,
            'buyer_id' => $buyOrder->user_id,
            'seller_id' => $sellOrder->user_id,
            'quantity' => $quantity,
            'price_per_gram' => $pricePerGram,
            'total_amount' => $totalAmount,
            'commission' => $commission,
        ]);

        // Update balances
        $buyer = $buyOrder->user;
        $seller = $sellOrder->user;

        // Buyer receives gold
        $buyer->increment('gold_balance', $quantity);

        // Seller receives money minus commission
        // Note: Seller's gold was already deducted when order was placed
        $seller->increment('rial_balance', $totalAmount - $commission);

        // Update orders remaining quantities
        $newRemainingBuy = $buyOrder->remaining_quantity - $quantity;
        $newRemainingSell = $sellOrder->remaining_quantity - $quantity;

        $this->orderRepository->updateRemainingQuantity($buyOrder->id, $newRemainingBuy);
        $this->orderRepository->updateRemainingQuantity($sellOrder->id, $newRemainingSell);

        // Reload the orders to ensure fresh data
        $buyOrder->refresh();
        $sellOrder->refresh();
    }

    public function cancelOrder(int $orderId, int $userId): array
    {
        return DB::transaction(function () use ($orderId, $userId) {
            $order = $this->orderRepository->findById($orderId);

            if (!$order || $order->user_id !== $userId) {
                throw new \Exception('Order not found');
            }

            if ($order->status !== 'active') {
                throw new \Exception('Order cannot be cancelled');
            }

            // Refund balance for remaining quantity
            $user = $order->user;
            if ($order->type === 'buy') {
                // For buy orders: refund the reserved Rial for remaining quantity
                $refundAmount = $order->remaining_quantity * $order->price_per_gram;
                $user->increment('rial_balance', $refundAmount);
            } else {
                // For sell orders: refund the reserved gold for remaining quantity
                $user->increment('gold_balance', $order->remaining_quantity);
            }

            $this->orderRepository->cancelOrder($orderId);

            return ['success' => true, 'message' => 'Order cancelled successfully'];
        });
    }
}
