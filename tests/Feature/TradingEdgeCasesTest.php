<?php

// tests/Feature/TradingEdgeCasesTest.php
namespace Tests\Feature;

use App\Models\User;
use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class TradingEdgeCasesTest extends TestCase
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

    #[Test]
    public function complete_trading_scenario()
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

        // CORRECTED: Akbar's gold balance logic
        // Initial: 15g
        // Order placed: 10g reserved (deducted immediately) → balance becomes 5g
        // Trading: 7g sold (no additional deduction, already reserved)
        // Current balance: 5g (15 - 10 = 5g)
        $this->assertEquals($akbarInitialGoldBalance - 10.0, $this->akbar->gold_balance); // 15 - 10 = 5g

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

        // CORRECTED: Verify Akbar's remaining gold was refunded
        // Before cancel: 5g (15 - 10)
        // After cancel: 5g + 3g (refund) = 8g
        $this->akbar->refresh();
        $this->assertEquals(8.0, $this->akbar->gold_balance); // 15 - 10 + 3 = 8g (CORRECT)

        // Verify order status changed to cancelled
        $akbarOrder->refresh();
        $this->assertEquals('cancelled', $akbarOrder->status);

        // Step 10: Verify no active sell orders remain
        $activeSellOrders = $this->getJson('/api/v1/orders/active/sell');
        $activeSellOrders->assertStatus(200);
        $this->assertCount(0, $activeSellOrders->json());
    }

    #[Test]
    public function commission_calculation_in_scenario()
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
            $totalAmount = $transaction->total_amount;

            // Calculate expected commission based on quantity
            if ($quantity <= 1) {
                $expectedRate = 0.02; // 2%
            } elseif ($quantity <= 10) {
                $expectedRate = 0.015; // 1.5%
            } else {
                $expectedRate = 0.01; // 1%
            }

            $expectedCommission = max(
                $totalAmount * $expectedRate, // CORRECTED: Based on total amount
                500000 // Minimum commission: 50,000 Toman = 500,000 Rial
            );

            $expectedCommission = min($expectedCommission, 50000000); // Maximum: 5,000,000 Toman = 50,000,000 Rial

            $this->assertEquals((int)$expectedCommission, $transaction->commission);
        }
    }

    #[Test]
    public function user_transaction_history()
    {
        // Create separate users for this test to avoid interference
        $testAhmad = User::create([
            'name' => 'Test Ahmad',
            'email' => 'test_ahmad@test.com',
            'gold_balance' => 0.0,
            'rial_balance' => 500000000000,
        ]);

        $testReza = User::create([
            'name' => 'Test Reza',
            'email' => 'test_reza@test.com',
            'gold_balance' => 0.0,
            'rial_balance' => 800000000000,
        ]);

        $testAkbar = User::create([
            'name' => 'Test Akbar',
            'email' => 'test_akbar@test.com',
            'gold_balance' => 15.0,
            'rial_balance' => 100000000,
        ]);

        // Execute a mini trading scenario
        $this->postJson('/api/v1/orders/buy', [
            'user_id' => $testAhmad->id,
            'quantity' => 2.0,
            'price_per_gram' => $this->pricePerGram,
        ]);

        $this->postJson('/api/v1/orders/buy', [
            'user_id' => $testReza->id,
            'quantity' => 3.0,
            'price_per_gram' => $this->pricePerGram,
        ]);

        $this->postJson('/api/v1/orders/sell', [
            'user_id' => $testAkbar->id,
            'quantity' => 8.0,
            'price_per_gram' => $this->pricePerGram,
        ]);

        // Test Ahmad's transaction history
        $ahmadHistory = $this->getJson("/api/v1/transactions/user/{$testAhmad->id}");
        $ahmadHistory->assertStatus(200);
        $ahmadTransactions = $ahmadHistory->json();

        $this->assertCount(1, $ahmadTransactions);
        $this->assertEquals($testAhmad->id, $ahmadTransactions[0]['buyer']['id']);
        $this->assertEquals($testAkbar->id, $ahmadTransactions[0]['seller']['id']);
        $this->assertEquals(2.0, $ahmadTransactions[0]['quantity']);

        // Test Reza's transaction history
        $rezaHistory = $this->getJson("/api/v1/transactions/user/{$testReza->id}");
        $rezaHistory->assertStatus(200);
        $rezaTransactions = $rezaHistory->json();

        $this->assertCount(1, $rezaTransactions);
        $this->assertEquals($testReza->id, $rezaTransactions[0]['buyer']['id']);
        $this->assertEquals($testAkbar->id, $rezaTransactions[0]['seller']['id']);
        $this->assertEquals(3.0, $rezaTransactions[0]['quantity']);

        // Test Akbar's transaction history (should appear in both as seller)
        $akbarHistory = $this->getJson("/api/v1/transactions/user/{$testAkbar->id}");
        $akbarHistory->assertStatus(200);
        $akbarTransactions = $akbarHistory->json();

        $this->assertCount(2, $akbarTransactions); // Two transactions as seller

        foreach ($akbarTransactions as $transaction) {
            $this->assertEquals($testAkbar->id, $transaction['seller']['id']);
        }
    }

    #[Test]
    public function partial_order_fulfillment()
    {
        // Create separate users for this test
        $buyer = User::create([
            'name' => 'Partial Buyer',
            'email' => 'partial_buyer@test.com',
            'gold_balance' => 0.0,
            'rial_balance' => 500000000000,
        ]);

        $seller = User::create([
            'name' => 'Partial Seller',
            'email' => 'partial_seller@test.com',
            'gold_balance' => 15.0,
            'rial_balance' => 100000000,
        ]);

        // Buyer wants to buy 2g
        $this->postJson('/api/v1/orders/buy', [
            'user_id' => $buyer->id,
            'quantity' => 2.0,
            'price_per_gram' => $this->pricePerGram,
        ]);

        // Seller sells only 1g (partial fulfillment of buyer's order)
        $this->postJson('/api/v1/orders/sell', [
            'user_id' => $seller->id,
            'quantity' => 1.0,
            'price_per_gram' => $this->pricePerGram,
        ]);

        // Check that buyer's order is partially filled
        $buyerOrder = Order::where('user_id', $buyer->id)->first();
        $this->assertEquals('active', $buyerOrder->status);
        $this->assertEquals(1.0, $buyerOrder->remaining_quantity); // 2 - 1 = 1g remaining

        // Check that seller's order is completed
        $sellerOrder = Order::where('user_id', $seller->id)->first();
        $this->assertEquals('completed', $sellerOrder->status);
        $this->assertEquals(0.0, $sellerOrder->remaining_quantity);

        // Verify balances
        $buyer->refresh();
        $seller->refresh();

        $this->assertEquals(1.0, $buyer->gold_balance); // Received 1g
        $this->assertEquals(14.0, $seller->gold_balance); // Had 15g, reserved 1g, now has 14g (15-1=14)
    }

    #[Test]
    public function sequential_order_execution()
    {
        // Test that orders are executed in the correct sequence (FIFO)
        $buyer1 = User::create([
            'name' => 'First Buyer',
            'email' => 'first@test.com',
            'gold_balance' => 0.0,
            'rial_balance' => 300000000000,
        ]);

        $buyer2 = User::create([
            'name' => 'Second Buyer',
            'email' => 'second@test.com',
            'gold_balance' => 0.0,
            'rial_balance' => 300000000000,
        ]);

        $seller = User::create([
            'name' => 'Sequential Seller',
            'email' => 'seq_seller@test.com',
            'gold_balance' => 20.0,
            'rial_balance' => 0,
        ]);

        // First buyer places order at higher price
        $this->postJson('/api/v1/orders/buy', [
            'user_id' => $buyer1->id,
            'quantity' => 2.0,
            'price_per_gram' => 110000000, // 11M Toman (higher price)
        ]);

        // Add small delay to ensure different timestamps
        usleep(10000); // 10ms

        // Second buyer places order at lower price
        $this->postJson('/api/v1/orders/buy', [
            'user_id' => $buyer2->id,
            'quantity' => 3.0,
            'price_per_gram' => 100000000, // 10M Toman (lower price)
        ]);

        // Seller places order at 10M Toman (should match with first buyer due to higher price)
        $this->postJson('/api/v1/orders/sell', [
            'user_id' => $seller->id,
            'quantity' => 5.0,
            'price_per_gram' => 100000000,
        ]);

        // Should create two transactions:
        // 1. First buyer gets 2g at seller's price (10M Toman)
        // 2. Second buyer gets 3g at seller's price (10M Toman)
        $this->assertDatabaseCount('transactions', 2);

        // Verify first buyer got their order filled first
        $firstTransaction = Transaction::orderBy('id')->first();
        $this->assertEquals($buyer1->id, $firstTransaction->buyer_id);
        $this->assertEquals(2.0, $firstTransaction->quantity);

        // Verify second buyer got the remaining quantity
        $secondTransaction = Transaction::orderBy('id')->skip(1)->first();
        $this->assertEquals($buyer2->id, $secondTransaction->buyer_id);
        $this->assertEquals(3.0, $secondTransaction->quantity);

        // Both should be at seller's price (100M Rial)
        $this->assertEquals(100000000, $firstTransaction->price_per_gram);
        $this->assertEquals(100000000, $secondTransaction->price_per_gram);
    }

    #[Test]
    public function complex_multi_step_scenario()
    {
        // Test a more complex scenario with multiple steps
        $buyers = [];
        $sellers = [];

        // Create multiple buyers
        for ($i = 1; $i <= 3; $i++) {
            $buyers[$i] = User::create([
                'name' => "Buyer $i",
                'email' => "buyer$i@complex.com",
                'gold_balance' => 0.0,
                'rial_balance' => 1000000000000, // 100 billion Rial
            ]);
        }

        // Create multiple sellers
        for ($i = 1; $i <= 2; $i++) {
            $sellers[$i] = User::create([
                'name' => "Seller $i",
                'email' => "seller$i@complex.com",
                'gold_balance' => 20.0,
                'rial_balance' => 0,
            ]);
        }

        // Step 1: Buyers place orders
        $this->postJson('/api/v1/orders/buy', [
            'user_id' => $buyers[1]->id,
            'quantity' => 5.0,
            'price_per_gram' => 120000000, // 12M Toman
        ]);

        $this->postJson('/api/v1/orders/buy', [
            'user_id' => $buyers[2]->id,
            'quantity' => 3.0,
            'price_per_gram' => 110000000, // 11M Toman
        ]);

        $this->postJson('/api/v1/orders/buy', [
            'user_id' => $buyers[3]->id,
            'quantity' => 4.0,
            'price_per_gram' => 100000000, // 10M Toman
        ]);

        // Step 2: First seller places order
        $this->postJson('/api/v1/orders/sell', [
            'user_id' => $sellers[1]->id,
            'quantity' => 7.0,
            'price_per_gram' => 105000000, // 10.5M Toman
        ]);

        // Should match with buyer 1 (5g) and buyer 2 (2g out of 3g)
        $this->assertDatabaseCount('transactions', 2);

        // Step 3: Second seller places order
        $this->postJson('/api/v1/orders/sell', [
            'user_id' => $sellers[2]->id,
            'quantity' => 10.0,
            'price_per_gram' => 100000000, // 10M Toman
        ]);

        // Should match with remaining buyer 2 (1g) and buyer 3 (4g)
        // Total should be 4 transactions now
        $this->assertDatabaseCount('transactions', 4);

        // Verify all buy orders are completed
        foreach ($buyers as $buyer) {
            $this->assertDatabaseHas('orders', [
                'user_id' => $buyer->id,
                'status' => 'completed',
                'remaining_quantity' => 0,
            ]);
        }

        // Verify sellers have correct remaining quantities
        $seller1Order = Order::where('user_id', $sellers[1]->id)->first();
        $seller2Order = Order::where('user_id', $sellers[2]->id)->first();

        $this->assertEquals('completed', $seller1Order->status); // 7g all sold
        $this->assertEquals('active', $seller2Order->status); // 5g remaining (10 - 5)
        $this->assertEquals(5.0, $seller2Order->remaining_quantity);
    }

    #[Test]
    public function commission_accuracy_test()
    {
        // Test different commission rates with precise calculations
        $testCases = [
            ['quantity' => 0.5, 'expected_rate' => 0.02], // 2% for <= 1g
            ['quantity' => 1.0, 'expected_rate' => 0.02], // 2% for <= 1g
            ['quantity' => 5.0, 'expected_rate' => 0.015], // 1.5% for 1-10g
            ['quantity' => 10.0, 'expected_rate' => 0.015], // 1.5% for 1-10g
            ['quantity' => 15.0, 'expected_rate' => 0.01], // 1% for > 10g
        ];

        foreach ($testCases as $index => $testCase) {
            $buyer = User::create([
                'name' => "Commission Buyer $index",
                'email' => "comm_buyer$index@test.com",
                'gold_balance' => 0.0,
                'rial_balance' => 5000000000000, // 500 billion Rial
            ]);

            $seller = User::create([
                'name' => "Commission Seller $index",
                'email' => "comm_seller$index@test.com",
                'gold_balance' => 50.0,
                'rial_balance' => 0,
            ]);

            $this->postJson('/api/v1/orders/buy', [
                'user_id' => $buyer->id,
                'quantity' => $testCase['quantity'],
                'price_per_gram' => $this->pricePerGram,
            ]);

            $this->postJson('/api/v1/orders/sell', [
                'user_id' => $seller->id,
                'quantity' => $testCase['quantity'],
                'price_per_gram' => $this->pricePerGram,
            ]);

            $transaction = Transaction::where('buyer_id', $buyer->id)->first();

            $totalAmount = $testCase['quantity'] * $this->pricePerGram;
            $expectedCommission = max(
                $totalAmount * $testCase['expected_rate'], // CORRECTED: Based on total amount
                500000 // Minimum 50K Toman = 500K Rial
            );
            $expectedCommission = min($expectedCommission, 50000000); // Max 5M Toman = 50M Rial

            $this->assertEquals(
                (int)$expectedCommission,
                $transaction->commission,
                "Commission mismatch for quantity {$testCase['quantity']} at rate {$testCase['expected_rate']}"
            );

            // Clean up for next iteration
            Transaction::where('buyer_id', $buyer->id)->delete();
            Order::where('user_id', $buyer->id)->delete();
            Order::where('user_id', $seller->id)->delete();
        }
    }
}
