<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TradingEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * Test insufficient balance scenarios
     */
    public function test_insufficient_balance_scenarios()
    {
        $user = User::create([
            'name' => 'Poor User',
            'email' => 'poor@test.com',
            'gold_balance' => 0.5, // Only 0.5g gold
            'rial_balance' => 50000000, // Only 5 million Toman
        ]);

        // Try to buy more than user can afford
        $response = $this->postJson('/api/v1/orders/buy', [
            'user_id' => $user->id,
            'quantity' => 2.0,
            'price_per_gram' => 100000000, // 10 million Toman per gram
        ]);

        $response->assertStatus(400)
            ->assertJsonFragment(['success' => false]);

        // Try to sell more gold than user has
        $response = $this->postJson('/api/v1/orders/sell', [
            'user_id' => $user->id,
            'quantity' => 1.0, // User only has 0.5g
            'price_per_gram' => 100000000,
        ]);

        $response->assertStatus(400)
            ->assertJsonFragment(['success' => false]);
    }

    /**
     * @test
     * Test order cancellation edge cases
     */
    public function test_order_cancellation_edge_cases()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@test.com',
            'gold_balance' => 10.0,
            'rial_balance' => 1000000000,
        ]);

        // Create and immediately try to cancel non-existent order
        $response = $this->patchJson('/api/v1/orders/999/cancel', [
            'user_id' => $user->id,
        ]);

        $response->assertStatus(400)
            ->assertJsonFragment(['success' => false]);

        // Create order and try to cancel with wrong user
        $this->postJson('/api/v1/orders/buy', [
            'user_id' => $user->id,
            'quantity' => 1.0,
            'price_per_gram' => 100000000,
        ]);

        $order = Order::where('user_id', $user->id)->first();

        $wrongUser = User::create([
            'name' => 'Wrong User',
            'email' => 'wrong@test.com',
            'gold_balance' => 5.0,
            'rial_balance' => 500000000,
        ]);

        $response = $this->patchJson("/api/v1/orders/{$order->id}/cancel", [
            'user_id' => $wrongUser->id,
        ]);

        $response->assertStatus(400)
            ->assertJsonFragment(['success' => false]);
    }

    /**
     * @test
     * Test price matching logic
     */
    public function test_price_matching_logic()
    {
        $buyer = User::create([
            'name' => 'Buyer',
            'email' => 'buyer@test.com',
            'gold_balance' => 0.0,
            'rial_balance' => 2000000000, // 200 million Toman
        ]);

        $seller = User::create([
            'name' => 'Seller',
            'email' => 'seller@test.com',
            'gold_balance' => 10.0,
            'rial_balance' => 0,
        ]);

        // Buyer wants to buy at 150 million Toman per gram
        $this->postJson('/api/v1/orders/buy', [
            'user_id' => $buyer->id,
            'quantity' => 1.0,
            'price_per_gram' => 1500000000, // 150 million Toman
        ]);

        // Seller offers to sell at 100 million Toman per gram (lower than buyer's offer)
        // This should match because seller's price is acceptable to buyer
        $this->postJson('/api/v1/orders/sell', [
            'user_id' => $seller->id,
            'quantity' => 1.0,
            'price_per_gram' => 1000000000, // 100 million Toman
        ]);

        // Verify transaction was created with seller's price
        $this->assertDatabaseHas('transactions', [
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'quantity' => 1.0,
            'price_per_gram' => 1000000000, // Seller's lower price should be used
        ]);

        // Verify both orders are completed
        $this->assertDatabaseHas('orders', [
            'user_id' => $buyer->id,
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('orders', [
            'user_id' => $seller->id,
            'status' => 'completed',
        ]);
    }
}
