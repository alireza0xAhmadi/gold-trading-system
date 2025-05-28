<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TradingService;
use App\Repositories\Interfaces\OrderRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function __construct(
        private TradingService $tradingService,
        private OrderRepositoryInterface $orderRepository
    ) {}

    public function placeBuyOrder(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'quantity' => 'required|numeric|min:0.001',
            'price_per_gram' => 'required|integer|min:1000', // Minimum 100 Toman
        ]);

        try {
            $result = $this->tradingService->placeBuyOrder(
                $request->user_id,
                $request->quantity,
                $request->price_per_gram
            );

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function placeSellOrder(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'quantity' => 'required|numeric|min:0.001',
            'price_per_gram' => 'required|integer|min:1000',
        ]);

        try {
            $result = $this->tradingService->placeSellOrder(
                $request->user_id,
                $request->quantity,
                $request->price_per_gram
            );

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function getUserOrders(int $userId): JsonResponse
    {
        $orders = $this->orderRepository->getUserOrders($userId);
        return response()->json($orders);
    }

    public function getActiveOrders(string $type): JsonResponse
    {
        $orders = $this->orderRepository->getActiveOrders($type);
        return response()->json($orders);
    }

    public function cancelOrder(Request $request, int $orderId): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        try {
            $result = $this->tradingService->cancelOrder($orderId, $request->user_id);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
