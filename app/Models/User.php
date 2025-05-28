<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class User
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property float $gold_balance        // user's gold balance in grams
 * @property int $rial_balance         // user's balance in Rials
 * @property-read float $rial_balance_in_toman  // rial balance converted to Toman
 *
 * @property-read Collection|Order[] $orders
 * @property-read Collection|Transaction[] $buyTransactions
 * @property-read Collection|Transaction[] $sellTransactions
 */
class User extends Model
{
    protected $fillable = [
        'name', 'email', 'gold_balance', 'rial_balance'
    ];

    protected $casts = [
        'gold_balance' => 'decimal:3', // store up to 3 decimal places (e.g., grams)
        'rial_balance' => 'integer'    // store monetary value in Rials
    ];

    /**
     * Get all orders placed by the user.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get all transactions where the user is the buyer.
     */
    public function buyTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'buyer_id');
    }

    /**
     * Get all transactions where the user is the seller.
     */
    public function sellTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'seller_id');
    }

    /**
     * Accessor: Get rial balance converted to Toman for display purposes.
     */
    public function getRialBalanceInTomanAttribute(): float|int
    {
        return $this->rial_balance / 10;
    }
}
