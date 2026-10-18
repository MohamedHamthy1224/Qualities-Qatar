<?php

namespace Tests\Feature;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_codes_follow_existing_number_suffixes_instead_of_database_ids(): void
    {
        Customer::create(['customer_code' => 'CUST-QAT-900', 'name' => 'Existing Client']);
        $this->postJson('/customers', ['name' => 'New Client', 'email' => 'new@example.test'])
            ->assertOk()
            ->assertJsonPath('customer.customer_code', 'CUST-QAT-901');
        $this->assertDatabaseHas('customers', ['name' => 'New Client', 'customer_code' => 'CUST-QAT-901']);
    }

    public function test_invalid_customer_profile_preserves_input_and_does_not_change_saved_details(): void
    {
        $customer = Customer::create(['customer_code' => 'CUST-QAT-001', 'name' => 'Saved Client', 'email' => 'saved@example.test']);
        $url = "/customers/{$customer->id}";
        $this->from($url)->put($url, [
            'name' => 'Edited Client', 'phone' => '+974 12345678', 'email' => 'invalid-email',
        ])->assertRedirect($url)->assertSessionHasErrors('email', null, 'customerProfile');

        $this->assertSame('Saved Client', $customer->fresh()->name);
        $this->assertSame('saved@example.test', $customer->fresh()->email);
        $this->get($url)->assertOk()->assertSee('value="Edited Client"', false)->assertSee('value="invalid-email"', false)
            ->assertSee('profile-email-error', false);
    }

    public function test_customer_search_and_balance_filter_survive_pagination(): void
    {
        foreach (range(1, 16) as $number) {
            Customer::create(['customer_code' => "CUST-QAT-{$number}", 'name' => "Search Client {$number}", 'balance' => 50]);
        }
        Customer::create(['customer_code' => 'CUST-QAT-CLEARED', 'name' => 'Search Cleared', 'balance' => 0]);
        Customer::create(['customer_code' => 'CUST-QAT-OTHER', 'name' => 'Different Customer', 'balance' => 100]);

        $this->get('/customers?search=Search&filter=balance')->assertOk()
            ->assertViewHas('customers', fn ($customers) => $customers->total() === 16 && $customers->count() === 15)
            ->assertSee('search=Search', false)->assertSee('filter=balance', false)->assertDontSee('Search Cleared');
        $this->get('/customers?search=Search&filter=balance&page=2')->assertOk()
            ->assertViewHas('customers', fn ($customers) => $customers->count() === 1);
    }
}
