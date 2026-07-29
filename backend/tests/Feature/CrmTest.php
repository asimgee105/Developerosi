<?php

namespace Tests\Feature;

use App\Models\CrmContact;
use App\Models\CrmCustomField;
use App\Models\CrmDeal;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test contacts index fetches and seeds Clark Kent and Bruce Wayne.
     */
    public function test_crm_contacts_index_seeds_leads(): void
    {
        $user = User::factory()->create();
        $organization = Organization::create([
            'name' => 'Wayne Enterprises LLC',
            'slug' => 'wayne-ent',
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/workspaces/{$organization->id}/crm/contacts");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'contacts' => [
                '*' => ['id', 'workspace_id', 'first_name', 'last_name', 'email', 'phone', 'company', 'status']
            ]
        ]);

        $this->assertDatabaseHas('crm_contacts', [
            'workspace_id' => $organization->id,
            'first_name' => 'Bruce',
            'last_name' => 'Wayne',
        ]);
    }

    /**
     * Test creating a lead contact.
     */
    public function test_lead_contact_creation(): void
    {
        $user = User::factory()->create();
        $organization = Organization::create([
            'name' => 'Daily Planet Pub',
            'slug' => 'daily-planet',
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/crm/contacts', [
                'workspace_id' => $organization->id,
                'first_name' => 'Lois',
                'last_name' => 'Lane',
                'email' => 'lois@dailyplanet.com',
                'phone' => '+1-555-9876',
                'company' => 'Daily Planet',
            ]);

        $response->assertStatus(201);
        $response->assertJsonFragment([
            'first_name' => 'Lois',
            'status' => 'lead',
        ]);

        $this->assertDatabaseHas('crm_contacts', [
            'workspace_id' => $organization->id,
            'first_name' => 'Lois',
        ]);
    }

    /**
     * Test moving deal pipelines columns updates probability.
     */
    public function test_deal_pipeline_stage_transitions(): void
    {
        $user = User::factory()->create();
        $organization = Organization::create([
            'name' => 'Stark Industries',
            'slug' => 'stark-ind',
        ]);

        $contact = CrmContact::create([
            'workspace_id' => $organization->id,
            'first_name' => 'Tony',
            'last_name' => 'Stark',
            'email' => 'tony@stark.com',
        ]);

        $deal = CrmDeal::create([
            'workspace_id' => $organization->id,
            'contact_id' => $contact->id,
            'title' => 'Arc Reactor Tech Licensing',
            'amount' => 1000000.00,
            'stage' => 'lead',
            'probability_percentage' => 10,
        ]);

        // Move stage to proposal
        $response = $this->actingAs($user)
            ->putJson("/api/v1/crm/deals/{$deal->id}/stage", [
                'stage' => 'proposal',
            ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'stage' => 'proposal',
            'probability_percentage' => 70, // 70% probability for proposal
        ]);

        // Move stage to won
        $responseWon = $this->actingAs($user)
            ->putJson("/api/v1/crm/deals/{$deal->id}/stage", [
                'stage' => 'won',
            ]);

        $responseWon->assertStatus(200);
        $responseWon->assertJsonFragment([
            'stage' => 'won',
            'probability_percentage' => 100, // 100% probability for won
        ]);
    }

    /**
     * Test custom field definitions and values mappings.
     */
    public function test_dynamic_custom_crm_fields(): void
    {
        $user = User::factory()->create();
        $organization = Organization::create([
            'name' => 'Stark Industries',
            'slug' => 'stark-ind',
        ]);

        $contact = CrmContact::create([
            'workspace_id' => $organization->id,
            'first_name' => 'Pepper',
            'last_name' => 'Potts',
            'email' => 'pepper@stark.com',
        ]);

        // Define a custom field
        $responseField = $this->actingAs($user)
            ->postJson('/api/v1/crm/custom-fields', [
                'workspace_id' => $organization->id,
                'field_name' => 'preferred_drink',
                'field_type' => 'text',
                'entity_type' => 'contact',
            ]);

        $responseField->assertStatus(201);
        $fieldId = $responseField->json('custom_field.id');

        // Set value
        $responseVal = $this->actingAs($user)
            ->postJson("/api/v1/crm/entities/{$contact->id}/custom-field-values", [
                'custom_field_id' => $fieldId,
                'field_value' => 'Matcha Latte',
            ]);

        $responseVal->assertStatus(200);

        $this->assertDatabaseHas('crm_custom_field_values', [
            'custom_field_id' => $fieldId,
            'entity_id' => $contact->id,
            'field_value' => 'Matcha Latte',
        ]);
    }

    /**
     * Test recording interactions log.
     */
    public function test_crm_interaction_logs_audit_trail(): void
    {
        $user = User::factory()->create();
        $organization = Organization::create([
            'name' => 'Stark Industries',
            'slug' => 'stark-ind',
        ]);

        $contact = CrmContact::create([
            'workspace_id' => $organization->id,
            'first_name' => 'Happy',
            'last_name' => 'Hogan',
            'email' => 'happy@stark.com',
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/crm/interactions', [
                'workspace_id' => $organization->id,
                'contact_id' => $contact->id,
                'type' => 'call',
                'notes' => 'Discussed logistics and pickup times.',
            ]);

        $response->assertStatus(201);
        
        $this->assertDatabaseHas('crm_interactions', [
            'workspace_id' => $organization->id,
            'contact_id' => $contact->id,
            'type' => 'call',
            'notes' => 'Discussed logistics and pickup times.',
        ]);
    }
}
