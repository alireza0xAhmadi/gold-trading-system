<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class Transaction
 *
 * @property int $id
 * @property int $buy_order_id          // ID of the related buy order
 * @property int $sell_order_id         // ID of the related sell order
 * @property int $buyer_id              // ID of the buyer user
 * @property int $seller_id             // ID of the seller user
 * @property float $quantity            // traded quantity in grams
 * @property int $price_per_gram        // price per gram in Rials
 * @property int $total_amount          // total transaction amount in Rials
 * @property int $commission            // commission fee in Rials
 *
 * @property-read float $total_amount_in_toman    // total amount in Toman (converted)
 * @property-read float $commission_in_toman      // commission in Toman (converted)
 *
 * @property-read \App\Models\Order $buyOrder
 * @property-read \App\Models\Order $sellOrder
 * @property-read \App\Models\User $buyer
 * @property-read \App\Models\User $seller
 */
class Transaction extends Model
{
    protected $fillable = [
        'buy_order_id', 'sell_order_id', 'buyer_id', 'seller_id',
        'quantity', 'price_per_gram', 'total_amount', 'commission'
    ];

    protected $casts = [
        'quantity' => 'decimal:3',     // traded quantity with 3 decimal precision
        'price_per_gram' => 'integer', // price in Rials
        'total_amount' => 'integer',   // total amount in Rials
        'commission' => 'integer'      // commission in Rials
    ];

    /**
     * Get the associated buy order.
     */
    public function buyOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'buy_order_id');
    }

    /**
     * Get the associated sell order.
     */
    public function sellOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'sell_order_id');
    }

    /**
     * Get the user who is the buyer.
     */
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    /**
     * Get the user who is the seller.
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    /**
     * Accessor: Convert total amount from Rials to Toman.
     */
    public function getTotalAmountInTomanAttribute(): float|int
    {
        return $this->total_amount / 10;
    }

    /**
     * Accessor: Convert commission from Rials to Toman.
     */
    public function getCommissionInTomanAttribute(): float|int
    {
        return $this->commission / 10;
    }
}
