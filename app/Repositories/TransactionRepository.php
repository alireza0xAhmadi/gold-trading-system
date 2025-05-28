<?php

namespace App\Repositories;

use App\Models\Transaction;
use App\Repositories\Interfaces\TransactionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class TransactionRepository implements TransactionRepositoryInterface
{
    public function create(array $data): Transaction
    {
        return Transaction::query()->create($data);
    }

    public function getUserTransactions(int $userId): Collection
    {
        return Transaction::query()
            ->where('buyer_id', $userId)
            ->orWhere('seller_id', $userId)
            ->with(['buyer', 'seller', 'buyOrder', 'sellOrder'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getTransactionHistory(): Collection
    {
        return Transaction::query()
            ->with(['buyer', 'seller', 'buyOrder', 'sellOrder'])
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
