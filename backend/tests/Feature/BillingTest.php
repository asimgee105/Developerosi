<?php

namespace Tests\Feature;

use App\Models\BillingSubscription;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test checkout session creation returns redirect URL.
     */
    public function test_checkout_session_returns_redirect_url_and_persists(): void
    {
        $user = User::factory()->create();
        $organization = Organization::create([
            'name' => 'Acme Corp',
            'slug' => 'acme-corp',
        ]);
        
        $organization->members()->attach($user->id, ['role' => 'admin']);
        $user->update(['active_organization_id' => $organization->id]);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/billing/subscriptions/checkout', [
                'price_id' => 'price_premium_123'
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['url', 'message']);

        // Assert mock subscription was persisted
        $this->assertDatabaseHas('billing_subscriptions', [
            'workspace_id' => $organization->id,
            'stripe_price_id' => 'price_premium_123',
            'status' => 'active',
        ]);
    }

    /**
     * Test Stripe Connect Express onboarding link generation.
     */
    public function test_connect_onboarding_returns_express_redirect_url(): void
    {
        $user = User::factory()->create();
        $organization = Organization::create([
            'name' => 'Acme Corp',
            'slug' => 'acme-corp',
        ]);
        
        $organization->members()->attach($user->id, ['role' => 'admin']);
        $user->update(['active_organization_id' => $organization->id]);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/billing/connect/onboard');

        $response->assertStatus(200);
        $response->assertJsonStructure(['url', 'message']);

        // Assert connected Express record was persisted
        $this->assertDatabaseHas('workspace_stripe_connects', [
            'workspace_id' => $organization->id,
            'status' => 'active',
            'charges_enabled' => 1,
        ]);
    }

    /**
     * Test invoices list query seeds dynamic ledger.
     */
    public function test_get_workspace_invoices_auto_seeds_history(): void
    {
        $user = User::factory()->create();
        $organization = Organization::create([
            'name' => 'Acme Corp',
            'slug' => 'acme-corp',
        ]);
        
        $organization->members()->attach($user->id, ['role' => 'admin']);
        $user->update(['active_organization_id' => $organization->id]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/workspaces/{$organization->id}/invoices");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'invoices' => [
                '*' => ['id', 'workspace_id', 'stripe_invoice_id', 'client_name', 'client_email', 'amount', 'status']
            ]
        ]);

        $this->assertDatabaseHas('invoices', [
            'workspace_id' => $organization->id,
            'client_name' => 'Acme Corp',
        ]);
    }

    /**
     * Test compiling time logs generates draft invoice successfully.
     */
    public function test_generate_invoice_from_timelogs_calculates_bill(): void
    {
        $user = User::factory()->create();
        $organization = Organization::create([
            'name' => 'Acme Corp',
            'slug' => 'acme-corp',
        ]);
        
        $organization->members()->attach($user->id, ['role' => 'admin']);
        $user->update(['active_organization_id' => $organization->id]);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/invoices/generate-from-timelogs', [
                'workspace_id' => $organization->id,
                'client_name' => 'Wayne Enterprises',
                'client_email' => 'finance@wayne.com',
                'hours_logged' => 10.5,
                'hourly_rate' => 80.00,
            ]);

        $response->assertStatus(201);
        $response->assertJsonFragment([
            'client_name' => 'Wayne Enterprises',
            'amount' => 840,
            'status' => 'draft',
        ]);

        $this->assertDatabaseHas('invoices', [
            'workspace_id' => $organization->id,
            'client_name' => 'Wayne Enterprises',
            'amount' => 840.00,
        ]);
    }
}
