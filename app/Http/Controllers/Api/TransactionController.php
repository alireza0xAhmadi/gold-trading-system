<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\Interfaces\TransactionRepositoryInterface;
use Illuminate\Http\JsonResponse;

class TransactionController extends Controller
{
    public function __construct(
        private TransactionRepositoryInterface $transactionRepository
    ) {}

    public function getUserTransactions(int $userId): JsonResponse
    {
        $transactions = $this->transactionRepository->getUserTransactions($userId);
        return response()->json($transactions);
    }

    public function getTransactionHistory(): JsonResponse
    {
        $transactions = $this->transactionRepository->getTransactionHistory();
        return response()->json($transactions);
    }
}
