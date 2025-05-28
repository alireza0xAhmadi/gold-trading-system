<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TradingScenarioTest extends TestCase
{
    use RefreshDatabase;

    private $ahmad;
    private $reza;
    private $akbar;
    private $pricePerGram;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users with initial balances
        $this->ahmad = User::create([
            'name' => 'Ahmad Alizadeh',
            'email' => 'ahmad@test.com',
            'gold_balance' => 0.0,
            'rial_balance' => 500000000000, // 50 billion Rial (5 billion Toman)
        ]);

        $this->reza = User::create([
            'name' => 'Reza Mohammadi',
            'email' => 'reza@test.com',
            'gold_balance' => 0.0,
            'rial_balance' => 800000000000, // 80 billion Rial (8 billion Toman)
        ]);

        $this->akbar = User::create([
            'name' => 'Akbar Hosseini',
            'email' => 'akbar@test.com',
            'gold_balance' => 15.0, // 15 grams of gold
            'rial_balance' => 100000000, // 100 million Rial (10 million Toman)
        ]);

        // Price: 10,000,000 Toman = 100,000,000 Rial per gram
        $this->pricePerGram = 100000000;
    }

    /**
     * @test
     * Complete trading scenario test
     */
    public function test_complete_trading_scenario()
    {
        // Step 1: Ahmad places buy order for 2 grams at 10,000,000 Toman per gram
        $ahmadOrderResponse = $this->postJson('/api/v1/orders/buy', [
            'user_id' => $this->ahmad->id,
            'quantity' => 2.0,
            'price_per_gram' => $this->pricePerGram,
        ]);

        $ahmadOrderResponse->assertStatus(200)
            ->assertJson(['success' => true]);

        // Verify Ahmad's order was created
        $ahmadOrder = Order::where('user_id', $this->ahmad->id)->first();
        $this->assertNotNull($ahmadOrder);
        $this->assertEquals('buy', $ahmadOrder->type);
        $this->assertEquals(2.0, $ahmadOrder->quantity);
        $this->assertEquals(2.0, $ahmadOrder->remaining_quantity);
        $this->assertEquals($this->pricePerGram, $ahmadOrder->price_per_gram);
        $this->assertEquals('active', $ahmadOrder->status);

        // Verify Ahmad's balance was deducted
        $this->ahmad->refresh();
        $expectedDeduction = 2.0 * $this->pricePerGram; // 200,000,000 Rial
        $this->assertEquals(500000000000 - $expectedDeduction, $this->ahmad->rial_balance);

        // Step 2: Reza places buy order for 5 grams at same price
        $rezaOrderResponse = $this->postJson('/api/v1/orders/buy', [
            'user_id' => $this->reza->id,
            'quantity' => 5.0,
            'price_per_gram' => $this->pricePerGram,
        ]);

        $rezaOrderResponse->assertStatus(200)
            ->assertJson(['success' => true]);

        // Verify Reza's order was created
        $rezaOrder = Order::where('user_id', $this->reza->id)->first();
        $this->assertNotNull($rezaOrder);
        $this->assertEquals('buy', $rezaOrder->type);
        $this->assertEquals(5.0, $rezaOrder->quantity);
        $this->assertEquals(5.0, $rezaOrder->remaining_quantity);

        // Verify Reza's balance was deducted
        $this->reza->refresh();
        $expectedDeduction = 5.0 * $this->pricePerGram; // 500,000,000 Rial
        $this->assertEquals(800000000000 - $expectedDeduction, $this->reza->rial_balance);

        // Step 3: Check active buy orders before Akbar's sell order
        $activeBuyOrders = $this->getJson('/api/v1/orders/active/buy');
        $activeBuyOrders->assertStatus(200);
        $buyOrdersData = $activeBuyOrders->json();
        $this->assertCount(2, $buyOrdersData); // Ahmad and Reza's orders

        // Step 4: Akbar places sell order for 10 grams at same price
        // This should automatically match with Ahmad's 2g and Reza's 5g orders
        $akbarInitialGoldBalance = $this->akbar->gold_balance;
        $akbarInitialRialBalance = $this->akbar->rial_balance;

        $akbarOrderResponse = $this->postJson('/api/v1/orders/sell', [
            'user_id' => $this->akbar->id,
            'quantity' => 10.0,
            'price_per_gram' => $this->pricePerGram,
        ]);

        $akbarOrderResponse->assertStatus(200)
            ->assertJson(['success' => true]);

        // Step 5: Verify transactions were created
        $transactions = Transaction::all();
        $this->assertCount(2, $transactions); // Two separate transactions

        // First transaction: Ahmad's 2g buy matched with Akbar's sell
        $ahmadTransaction = Transaction::where('buyer_id', $this->ahmad->id)->first();
        $this->assertNotNull($ahmadTransaction);
        $this->assertEquals($this->ahmad->id, $ahmadTransaction->buyer_id);
        $this->assertEquals($this->akbar->id, $ahmadTransaction->seller_id);
        $this->assertEquals(2.0, $ahmadTransaction->quantity);
        $this->assertEquals($this->pricePerGram, $ahmadTransaction->price_per_gram);
        $this->assertEquals(2.0 * $this->pricePerGram, $ahmadTransaction->total_amount);

        // Second transaction: Reza's 5g buy matched with Akbar's sell
        $rezaTransaction = Transaction::where('buyer_id', $this->reza->id)->first();
        $this->assertNotNull($rezaTransaction);
        $this->assertEquals($this->reza->id, $rezaTransaction->buyer_id);
        $this->assertEquals($this->akbar->id, $rezaTransaction->seller_id);
        $this->assertEquals(5.0, $rezaTransaction->quantity);
        $this->assertEquals($this->pricePerGram, $rezaTransaction->price_per_gram);
        $this->assertEquals(5.0 * $this->pricePerGram, $rezaTransaction->total_amount);

        // Step 6: Verify order statuses after matching
        $this->ahmad->refresh();
        $this->reza->refresh();
        $this->akbar->refresh();

        // Ahmad's and Reza's orders should be completed
        $ahmadOrder->refresh();
        $rezaOrder->refresh();
        $this->assertEquals('completed', $ahmadOrder->status);
        $this->assertEquals(0.0, $ahmadOrder->remaining_quantity);
        $this->assertEquals('completed', $rezaOrder->status);
        $this->assertEquals(0.0, $rezaOrder->remaining_quantity);

        // Akbar's order should still be active with 3g remaining (10 - 2 - 5 = 3)
        $akbarOrder = Order::where('user_id', $this->akbar->id)->first();
        $this->assertEquals('active', $akbarOrder->status);
        $this->assertEquals(3.0, $akbarOrder->remaining_quantity);
        $this->assertEquals(10.0, $akbarOrder->quantity); // Original quantity unchanged

        // Step 7: Verify balances after trading
        // Ahmad should have received 2g gold
        $this->assertEquals(2.0, $this->ahmad->gold_balance);
        // Ahmad's Rial balance should remain the same (already deducted when placing order)
        $this->assertEquals(500000000000 - (2.0 * $this->pricePerGram), $this->ahmad->rial_balance);

        // Reza should have received 5g gold
        $this->assertEquals(5.0, $this->reza->gold_balance);
        // Reza's Rial balance should remain the same (already deducted when placing order)
        $this->assertEquals(800000000000 - (5.0 * $this->pricePerGram), $this->reza->rial_balance);

        // Akbar should have lost 7g gold (2g + 5g sold)
        $this->assertEquals($akbarInitialGoldBalance - 7.0, $this->akbar->gold_balance); // 15 - 7 = 8g

        // Akbar should have received payment for 7g minus commissions
        $totalSoldAmount = 7.0 * $this->pricePerGram; // 700,000,000 Rial
        $totalCommission = $ahmadTransaction->commission + $rezaTransaction->commission;
        $expectedRialIncrease = $totalSoldAmount - $totalCommission;
        $this->assertEquals($akbarInitialRialBalance + $expectedRialIncrease, $this->akbar->rial_balance);

        // Step 8: Test remaining active orders
        $activeBuyOrders = $this->getJson('/api/v1/orders/active/buy');
        $activeBuyOrders->assertStatus(200);
        $this->assertCount(0, $activeBuyOrders->json()); // No active buy orders

        $activeSellOrders = $this->getJson('/api/v1/orders/active/sell');
        $activeSellOrders->assertStatus(200);
        $sellOrdersData = $activeSellOrders->json();
        $this->assertCount(1, $sellOrdersData); // Akbar's remaining order
        $this->assertEquals(3.0, $sellOrdersData[0]['remaining_quantity']);

        // Step 9: Test order cancellation
        // Akbar decides to cancel his remaining 3g sell order
        $cancelResponse = $this->patchJson("/api/v1/orders/{$akbarOrder->id}/cancel", [
            'user_id' => $this->akbar->id,
        ]);

        $cancelResponse->assertStatus(200)
            ->assertJson(['success' => true]);

        // Verify Akbar's remaining gold was refunded
        $this->akbar->refresh();
        $this->assertEquals($akbarInitialGoldBalance - 7.0 + 3.0, $this->akbar->gold_balance); // 15 - 7 + 3 = 11g

        // Verify order status changed to cancelled
        $akbarOrder->refresh();
        $this->assertEquals('cancelled', $akbarOrder->status);

        // Step 10: Verify no active sell orders remain
        $activeSellOrders = $this->getJson('/api/v1/orders/active/sell');
        $activeSellOrders->assertStatus(200);
        $this->assertCount(0, $activeSellOrders->json());
    }

    /**
     * @test
     * Test commission calculation in the scenario
     */
    public function test_commission_calculation_in_scenario()
    {
        // Place orders as in main scenario
        $this->postJson('/api/v1/orders/buy', [
            'user_id' => $this->ahmad->id,
            'quantity' => 2.0,
            'price_per_gram' => $this->pricePerGram,
        ]);

        $this->postJson('/api/v1/orders/buy', [
            'user_id' => $this->reza->id,
            'quantity' => 5.0,
            'price_per_gram' => $this->pricePerGram,
        ]);

        $this->postJson('/api/v1/orders/sell', [
            'user_id' => $this->akbar->id,
            'quantity' => 10.0,
            'price_per_gram' => $this->pricePerGram,
        ]);

        $transactions = Transaction::all();

        foreach ($transactions as $transaction) {
            $quantity = $transaction->quantity;

            // Calculate expected commission based on quantity
            if ($quantity <= 1) {
                $expectedRate = 0.02; // 2%
            } elseif ($quantity <= 10) {
                $expectedRate = 0.015; // 1.5%
            } else {
                $expectedRate = 0.01; // 1%
            }

            $expectedCommission = max(
                $quantity * $expectedRate * $transaction->price_per_gram,
                500000 // Minimum commission: 50,000 Toman = 500,000 Rial
            );

            $expectedCommission = min($expectedCommission, 50000000); // Maximum: 5,000,000 Toman = 50,000,000 Rial

            $this->assertEquals((int)$expectedCommission, $transaction->commission);
        }
    }

    /**
     * @test
     * Test user transaction history after scenario
     */
    public function test_user_transaction_history()
    {
        // Execute the main scenario
        $this->test_complete_trading_scenario();

        // Test Ahmad's transaction history
        $ahmadHistory = $this->getJson("/api/v1/transactions/user/{$this->ahmad->id}");
        $ahmadHistory->assertStatus(200);
        $ahmadTransactions = $ahmadHistory->json();

        $this->assertCount(1, $ahmadTransactions);
        $this->assertEquals($this->ahmad->id, $ahmadTransactions[0]['buyer']['id']);
        $this->assertEquals($this->akbar->id, $ahmadTransactions[0]['seller']['id']);
        $this->assertEquals(2.0, $ahmadTransactions[0]['quantity']);

        // Test Reza's transaction history
        $rezaHistory = $this->getJson("/api/v1/transactions/user/{$this->reza->id}");
        $rezaHistory->assertStatus(200);
        $rezaTransactions = $rezaHistory->json();

        $this->assertCount(1, $rezaTransactions);
        $this->assertEquals($this->reza->id, $rezaTransactions[0]['buyer']['id']);
        $this->assertEquals($this->akbar->id, $rezaTransactions[0]['seller']['id']);
        $this->assertEquals(5.0, $rezaTransactions[0]['quantity']);

        // Test Akbar's transaction history (should appear in both as seller)
        $akbarHistory = $this->getJson("/api/v1/transactions/user/{$this->akbar->id}");
        $akbarHistory->assertStatus(200);
        $akbarTransactions = $akbarHistory->json();

        $this->assertCount(2, $akbarTransactions); // Two transactions as seller

        foreach ($akbarTransactions as $transaction) {
            $this->assertEquals($this->akbar->id, $transaction['seller']['id']);
        }
    }

    /**
     * @test
     * Test partial order fulfillment
     */
    public function test_partial_order_fulfillment()
    {
        // Ahmad wants to buy 2g
        $this->postJson('/api/v1/orders/buy', [
            'user_id' => $this->ahmad->id,
            'quantity' => 2.0,
            'price_per_gram' => $this->pricePerGram,
        ]);

        // Akbar sells only 1g (partial fulfillment of Ahmad's order)
        $this->postJson('/api/v1/orders/sell', [
            'user_id' => $this->akbar->id,
            'quantity' => 1.0,
            'price_per_gram' => $this->pricePerGram,
        ]);

        // Check that Ahmad's order is partially filled
        $ahmadOrder = Order::where('user_id', $this->ahmad->id)->first();
        $this->assertEquals('active', $ahmadOrder->status);
        $this->assertEquals(1.0, $ahmadOrder->remaining_quantity); // 2 - 1 = 1g remaining

        // Check that Akbar's order is completed
        $akbarOrder = Order::where('user_id', $this->akbar->id)->first();
        $this->assertEquals('completed', $akbarOrder->status);
        $this->assertEquals(0.0, $akbarOrder->remaining_quantity);

        // Verify balances
        $this->ahmad->refresh();
        $this->akbar->refresh();

        $this->assertEquals(1.0, $this->ahmad->gold_balance); // Received 1g
        $this->assertEquals(14.0, $this->akbar->gold_balance); // Lost 1g (15 - 1)
    }

    /**
     * Helper method to display scenario results
     */
    private function displayScenarioResults()
    {
        $this->ahmad->refresh();
        $this->reza->refresh();
        $this->akbar->refresh();

        echo "\n=== Trading Scenario Results ===\n";
        echo "Ahmad - Gold: {$this->ahmad->gold_balance}g, Rial: " . ($this->ahmad->rial_balance / 10) . " Toman\n";
        echo "Reza - Gold: {$this->reza->gold_balance}g, Rial: " . ($this->reza->rial_balance / 10) . " Toman\n";
        echo "Akbar - Gold: {$this->akbar->gold_balance}g, Rial: " . ($this->akbar->rial_balance / 10) . " Toman\n";

        echo "\nTransactions:\n";
        $transactions = Transaction::with(['buyer', 'seller'])->get();
        foreach ($transactions as $transaction) {
            echo "- {$transaction->buyer->name} bought {$transaction->quantity}g from {$transaction->seller->name} ";
            echo "at " . ($transaction->price_per_gram / 10) . " Toman per gram\n";
            echo "  Total: " . ($transaction->total_amount / 10) . " Toman, Commission: " . ($transaction->commission / 10) . " Toman\n";
        }

        echo "\nActive Orders:\n";
        $activeOrders = Order::where('status', 'active')->with('user')->get();
        foreach ($activeOrders as $order) {
            echo "- {$order->user->name}: {$order->type} {$order->remaining_quantity}g at " . ($order->price_per_gram / 10) . " Toman per gram\n";
        }
    }
}
