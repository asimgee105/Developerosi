<?php

namespace App\Http\Controllers;

use App\Models\CrmContact;
use App\Models\CrmCustomField;
use App\Models\CrmCustomFieldValue;
use App\Models\CrmDeal;
use App\Models\CrmInteraction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CrmController extends Controller
{
    /**
     * Get all contacts for a workspace (seeding mock entries if none exist).
     */
    public function getContacts(Request $request, string $workspaceId): JsonResponse
    {
        $hasContacts = CrmContact::where('workspace_id', $workspaceId)->exists();

        if (!$hasContacts) {
            $this->seedContacts($workspaceId);
        }

        $contacts = CrmContact::where('workspace_id', $workspaceId)
            ->orderBy('first_name', 'asc')
            ->get();

        return response()->json(['contacts' => $contacts]);
    }

    /**
     * Create a new contact.
     */
    public function createContact(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'workspace_id' => ['required', 'string'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'company' => ['nullable', 'string', 'max:255'],
            'source' => ['nullable', 'string', 'max:50'],
        ]);

        $contact = CrmContact::create([
            'workspace_id' => $validated['workspace_id'],
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'company' => $validated['company'] ?? null,
            'status' => 'lead',
            'source' => $validated['source'] ?? 'web',
        ]);

        return response()->json([
            'message' => 'Contact created successfully.',
            'contact' => $contact,
        ], 201);
    }

    /**
     * Get all deals for a workspace.
     */
    public function getDeals(Request $request, string $workspaceId): JsonResponse
    {
        $hasDeals = CrmDeal::where('workspace_id', $workspaceId)->exists();

        if (!$hasDeals) {
            // Seed deals requires contacts to exist, make sure contacts exist first
            $hasContacts = CrmContact::where('workspace_id', $workspaceId)->exists();
            if (!$hasContacts) {
                $this->seedContacts($workspaceId);
            }
            $this->seedDeals($workspaceId);
        }

        $deals = CrmDeal::where('workspace_id', $workspaceId)
            ->with(['contact'])
            ->orderBy('amount', 'desc')
            ->get();

        return response()->json(['deals' => $deals]);
    }

    /**
     * Create a new deal.
     */
    public function createDeal(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'workspace_id' => ['required', 'string'],
            'contact_id' => ['required', 'string'],
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'stage' => ['required', 'string', 'in:lead,contacted,qualified,proposal,won,lost'],
            'probability_percentage' => ['required', 'integer', 'between:0,100'],
            'expected_close_date' => ['nullable', 'date'],
        ]);

        $deal = CrmDeal::create([
            'workspace_id' => $validated['workspace_id'],
            'contact_id' => $validated['contact_id'],
            'title' => $validated['title'],
            'amount' => $validated['amount'],
            'stage' => $validated['stage'],
            'probability_percentage' => $validated['probability_percentage'],
            'expected_close_date' => $validated['expected_close_date'] ?? null,
        ]);

        return response()->json([
            'message' => 'Deal created successfully.',
            'deal' => $deal,
        ], 201);
    }

    /**
     * Move deal stages in the sales pipeline columns.
     */
    public function updateDealStage(Request $request, string $dealId): JsonResponse
    {
        $validated = $request->validate([
            'stage' => ['required', 'string', 'in:lead,contacted,qualified,proposal,won,lost'],
        ]);

        $deal = CrmDeal::findOrFail($dealId);
        $newStage = $validated['stage'];

        // Automatically configure probability based on standard sales triggers
        $probability = $deal->probability_percentage;
        if ($newStage === 'won') {
            $probability = 100;
        } elseif ($newStage === 'lost') {
            $probability = 0;
        } elseif ($newStage === 'proposal') {
            $probability = 70;
        } elseif ($newStage === 'qualified') {
            $probability = 40;
        }

        $deal->update([
            'stage' => $newStage,
            'probability_percentage' => $probability,
        ]);

        return response()->json([
            'message' => 'Deal stage transitioned successfully.',
            'deal' => $deal,
        ]);
    }

    /**
     * Add a custom field definition for the workspace.
     */
    public function createCustomField(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'workspace_id' => ['required', 'string'],
            'field_name' => ['required', 'string', 'max:255'],
            'field_type' => ['required', 'string', 'in:text,number,boolean,select'],
            'entity_type' => ['required', 'string', 'in:contact,deal'],
        ]);

        $field = CrmCustomField::create([
            'workspace_id' => $validated['workspace_id'],
            'field_name' => $validated['field_name'],
            'field_type' => $validated['field_type'],
            'entity_type' => $validated['entity_type'],
        ]);

        return response()->json([
            'message' => 'Custom CRM field defined successfully.',
            'custom_field' => $field,
        ], 201);
    }

    /**
     * Save dynamic value mapping for a custom field attribute.
     */
    public function setCustomFieldValue(Request $request, string $entityId): JsonResponse
    {
        $validated = $request->validate([
            'custom_field_id' => ['required', 'string'],
            'field_value' => ['required', 'string'],
        ]);

        $value = CrmCustomFieldValue::updateOrCreate(
            [
                'custom_field_id' => $validated['custom_field_id'],
                'entity_id' => $entityId,
            ],
            [
                'field_value' => $validated['field_value'],
            ]
        );

        return response()->json([
            'message' => 'Custom field value saved successfully.',
            'custom_field_value' => $value,
        ]);
    }

    /**
     * Retrieve interactions audit logs list for a contact.
     */
    public function getInteractions(Request $request, string $contactId): JsonResponse
    {
        $interactions = CrmInteraction::where('contact_id', $contactId)
            ->orderBy('occurred_at', 'desc')
            ->get();

        return response()->json(['interactions' => $interactions]);
    }

    /**
     * Register a new client interaction log.
     */
    public function createInteraction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'workspace_id' => ['required', 'string'],
            'contact_id' => ['required', 'string'],
            'deal_id' => ['nullable', 'string'],
            'type' => ['required', 'string', 'in:email,call,note,meeting'],
            'notes' => ['required', 'string'],
        ]);

        $interaction = CrmInteraction::create([
            'workspace_id' => $validated['workspace_id'],
            'contact_id' => $validated['contact_id'],
            'deal_id' => $validated['deal_id'] ?? null,
            'type' => $validated['type'],
            'notes' => $validated['notes'],
            'occurred_at' => now(),
        ]);

        return response()->json([
            'message' => 'Interaction log recorded successfully.',
            'interaction' => $interaction,
        ], 201);
    }

    /**
     * Seed default contacts.
     */
    private function seedContacts(string $workspaceId): void
    {
        DB::transaction(function () use ($workspaceId) {
            CrmContact::create([
                'workspace_id' => $workspaceId,
                'first_name' => 'Clark',
                'last_name' => 'Kent',
                'email' => 'clark@dailyplanet.com',
                'phone' => '+1-555-0143',
                'company' => 'Daily Planet',
                'status' => 'lead',
                'source' => 'referral',
            ]);

            CrmContact::create([
                'workspace_id' => $workspaceId,
                'first_name' => 'Bruce',
                'last_name' => 'Wayne',
                'email' => 'bruce@wayne.com',
                'phone' => '+1-555-0199',
                'company' => 'Wayne Enterprises',
                'status' => 'contacted',
                'source' => 'web',
            ]);
        });
    }

    /**
     * Seed default deals.
     */
    private function seedDeals(string $workspaceId): void
    {
        $wayne = CrmContact::where('workspace_id', $workspaceId)
            ->where('first_name', 'Bruce')
            ->first();

        $kent = CrmContact::where('workspace_id', $workspaceId)
            ->where('first_name', 'Clark')
            ->first();

        DB::transaction(function () use ($workspaceId, $wayne, $kent) {
            if ($wayne) {
                CrmDeal::create([
                    'workspace_id' => $workspaceId,
                    'contact_id' => $wayne->id,
                    'title' => 'Wayne Enterprise Infrastructure Tech Build',
                    'amount' => 50000.00,
                    'stage' => 'qualified',
                    'probability_percentage' => 40,
                    'expected_close_date' => now()->addMonths(3),
                ]);
            }

            if ($kent) {
                CrmDeal::create([
                    'workspace_id' => $workspaceId,
                    'contact_id' => $kent->id,
                    'title' => 'Daily Planet AI Integration Subscription',
                    'amount' => 5000.00,
                    'stage' => 'proposal',
                    'probability_percentage' => 70,
                    'expected_close_date' => now()->addWeeks(2),
                ]);
            }
        });
    }
}
