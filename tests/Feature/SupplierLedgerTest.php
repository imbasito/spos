<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierLedgerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Supplier $supplier;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->supplier = Supplier::factory()->create();
        $this->product = Product::factory()->create(['quantity' => 100]);
    }

    /**
     * Test purchase with full payment marks status as 'paid'.
     */
    public function test_full_payment_marks_purchase_as_paid(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('backend.admin.purchase.store'), [
            'supplierId' => $this->supplier->id,
            'products' => [
                ['id' => $this->product->id, 'qty' => 10, 'purchase_price' => 50, 'price' => 100],
            ],
            'totals' => [
                'subTotal' => 500,
                'tax' => 0,
                'discount' => 0,
                'shipping' => 0,
                'grandTotal' => 500,
                'paidAmount' => 500, // Full payment
            ],
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('purchases', [
            'grand_total' => 500,
            'paid_amount' => 500,
            'payment_status' => 'paid',
        ]);
    }

    /**
     * Test purchase with partial payment marks status as 'partial'.
     */
    public function test_partial_payment_marks_purchase_as_partial(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('backend.admin.purchase.store'), [
            'supplierId' => $this->supplier->id,
            'products' => [
                ['id' => $this->product->id, 'qty' => 10, 'purchase_price' => 50, 'price' => 100],
            ],
            'totals' => [
                'subTotal' => 500,
                'tax' => 0,
                'discount' => 0,
                'shipping' => 0,
                'grandTotal' => 500,
                'paidAmount' => 200, // Partial payment
            ],
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('purchases', [
            'grand_total' => 500,
            'paid_amount' => 200,
            'payment_status' => 'partial',
        ]);
    }

    /**
     * Test purchase with no payment marks status as 'unpaid'.
     */
    public function test_no_payment_marks_purchase_as_unpaid(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('backend.admin.purchase.store'), [
            'supplierId' => $this->supplier->id,
            'products' => [
                ['id' => $this->product->id, 'qty' => 10, 'purchase_price' => 50, 'price' => 100],
            ],
            'totals' => [
                'subTotal' => 500,
                'tax' => 0,
                'discount' => 0,
                'shipping' => 0,
                'grandTotal' => 500,
                // paidAmount not provided = 0
            ],
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('purchases', [
            'grand_total' => 500,
            'paid_amount' => 0,
            'payment_status' => 'unpaid',
        ]);
    }

    /**
     * Test Purchase model due_amount attribute.
     */
    public function test_purchase_due_amount_calculation(): void
    {
        $purchase = Purchase::create([
            'supplier_id' => $this->supplier->id,
            'user_id' => $this->user->id,
            'sub_total' => 1000,
            'grand_total' => 1000,
            'paid_amount' => 300,
            'payment_status' => 'partial',
            'date' => now(),
            'status' => 1,
        ]);

        $this->assertEquals(700, $purchase->due_amount);
    }
}
