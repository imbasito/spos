<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerValidationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /**
     * Test Pakistani phone number validation accepts valid format.
     */
    public function test_accepts_valid_pakistani_phone_number(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('backend.admin.customers.store'), [
            'name' => 'Test Customer',
            'phone' => '03001234567',
            'address' => 'Test Address',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('customers', ['phone' => '03001234567']);
    }

    /**
     * Test Pakistani phone number validation rejects invalid format.
     */
    public function test_rejects_invalid_phone_number(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('backend.admin.customers.store'), [
            'name' => 'Test Customer',
            'phone' => '1234567890', // Invalid: doesn't start with 03
            'address' => 'Test Address',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['phone']);
    }

    /**
     * Test CNIC validation accepts valid format.
     */
    public function test_accepts_valid_cnic_format(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('backend.admin.customers.store'), [
            'name' => 'Test Customer',
            'phone' => '03009876543',
            'cnic' => '12345-1234567-1',
            'address' => 'Test Address',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('customers', ['cnic' => '12345-1234567-1']);
    }

    /**
     * Test CNIC validation rejects invalid format.
     */
    public function test_rejects_invalid_cnic_format(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('backend.admin.customers.store'), [
            'name' => 'Test Customer',
            'phone' => '03005551234',
            'cnic' => '123456789012', // Invalid format
            'address' => 'Test Address',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['cnic']);
    }

    /**
     * Test customer total_due attribute calculation.
     */
    public function test_customer_total_due_calculation(): void
    {
        $customer = Customer::factory()->create();
        
        // Create orders with due amounts
        $customer->orders()->create([
            'user_id' => $this->user->id,
            'sub_total' => 1000,
            'discount' => 0,
            'total' => 1000,
            'paid' => 500,
            'due' => 500,
            'status' => 1,
        ]);

        $customer->refresh();
        $this->assertEquals(500, $customer->total_due);
    }
}
