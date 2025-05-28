<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Order
 *
 * @property int $id
 * @property int $user_id
 * @property string $type                  // either 'buy' or 'sell'
 * @property float $quantity              // total quantity in grams
 * @property float $remaining_quantity    // remaining quantity in grams
 * @property int $price_per_gram          // price per gram in Rials
 * @property string $status               // order status: active, completed, or cancelled
 * @property-read float $price_per_gram_in_toman   // accessor: price converted to Toman
 * @property-read float $total_amount_in_toman     // accessor: total value in Toman
 *
 * @property-read User $user
 * @property-read Collection|Transaction[] $buyTransactions
 * @property-read Collection|Transaction[] $sellTransactions
 */
class Order extends Model
{
    protected $fillable = [
        'user_id', 'type', 'quantity', 'remaining_quantity',
        'price_per_gram', 'status'
    ];

    protected $casts = [
        'quantity' => 'decimal:3',           // store gold quantity with 3 decimal precision
        'remaining_quantity' => 'decimal:3', // remaining quantity with 3 decimal precision
        'price_per_gram' => 'integer'        // price in Rials
    ];

    /**
     * Get the user who placed the order.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get transactions where this order was the buy side.
     */
    public function buyTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'buy_order_id');
    }

    /**
     * Get transactions where this order was the sell side.
     */
    public function sellTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'sell_order_id');
    }

    /**
     * Accessor: Convert price per gram to Toman for display.
     */
    public function getPricePerGramInTomanAttribute(): float|int
    {
        return $this->price_per_gram / 10;
    }

    /**
     * Accessor: Calculate total order value in Toman.
     */
    public function getTotalAmountInTomanAttribute(): float|int
    {
        return ($this->quantity * $this->price_per_gram) / 10;
    }
}
