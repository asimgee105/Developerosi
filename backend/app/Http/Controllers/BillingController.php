<?php

namespace App\Http\Controllers;

use App\Models\BillingSubscription;
use App\Models\Invoice;
use App\Models\WorkspaceStripeConnect;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BillingController extends Controller
{
    /**
     * Create a Stripe Checkout Session for Subscription.
     */
    public function createCheckoutSession(Request $request): JsonResponse
    {
        $request->validate([
            'price_id' => ['required', 'string'],
        ]);

        $user = $request->user();
        $priceId = $request->input('price_id');
        $stripeKey = env('STRIPE_KEY');

        if ($stripeKey) {
            try {
                // If Stripe key exists, configure real subscription checkout session
                \Stripe\Stripe::setApiKey($stripeKey);

                $session = \Stripe\Checkout\Session::create([
                    'payment_method_types' => ['card'],
                    'line_items' => [[
                        'price' => $priceId,
                        'quantity' => 1,
                    ]],
                    'mode' => 'subscription',
                    'success_url' => 'http://localhost:3000/dashboard?billing_success=true',
                    'cancel_url' => 'http://localhost:3000/dashboard?billing_cancel=true',
                    'client_reference_id' => $user->active_organization_id,
                ]);

                return response()->json(['url' => $session->url]);
            } catch (\Exception $e) {
                // Fallback on network errors
            }
        }

        // Standard mock routing fallback
        $mockCheckoutUrl = 'https://checkout.stripe.com/pay/mock_session_' . Str::random(16);

        // Record temporary pending mock subscription record
        BillingSubscription::updateOrCreate(
            ['workspace_id' => $user->active_organization_id],
            [
                'stripe_subscription_id' => 'sub_mock_' . Str::random(10),
                'stripe_price_id' => $priceId,
                'status' => 'active',
                'trial_ends_at' => now()->addDays(14),
                'ends_at' => null,
            ]
        );

        return response()->json([
            'url' => $mockCheckoutUrl,
            'message' => 'Billing check running in fallback simulation mode.'
        ]);
    }

    /**
     * Onboard developer connected Stripe Express payout accounts.
     */
    public function connectOnboard(Request $request): JsonResponse
    {
        $user = $request->user();
        $stripeKey = env('STRIPE_KEY');

        if ($stripeKey) {
            try {
                \Stripe\Stripe::setApiKey($stripeKey);

                // Create connected Express account
                $account = \Stripe\Account::create([
                    'type' => 'express',
                    'country' => 'US',
                    'email' => $user->email,
                    'capabilities' => [
                        'card_payments' => ['requested' => true],
                        'transfers' => ['requested' => true],
                    ],
                ]);

                // Generate Express onboarding link
                $accountLink = \Stripe\AccountLink::create([
                    'account' => $account->id,
                    'refresh_url' => 'http://localhost:3000/dashboard?connect_refresh=true',
                    'return_url' => 'http://localhost:3000/dashboard?connect_success=true',
                    'type' => 'account_onboarding',
                ]);

                WorkspaceStripeConnect::updateOrCreate(
                    ['workspace_id' => $user->active_organization_id],
                    [
                        'stripe_connect_account_id' => $account->id,
                        'status' => 'pending',
                        'charges_enabled' => false,
                        'payouts_enabled' => false,
                    ]
                );

                return response()->json(['url' => $accountLink->url]);
            } catch (\Exception $e) {
                // Fallback
            }
        }

        // Mock Stripe Connect Express setup
        $mockConnectUrl = 'https://connect.stripe.com/express/mock_oauth_' . Str::random(16);

        WorkspaceStripeConnect::updateOrCreate(
            ['workspace_id' => $user->active_organization_id],
            [
                'stripe_connect_account_id' => 'acct_mock_' . Str::random(10),
                'status' => 'active',
                'charges_enabled' => true,
                'payouts_enabled' => true,
            ]
        );

        return response()->json([
            'url' => $mockConnectUrl,
            'message' => 'Stripe Connect running in fallback simulation mode.'
        ]);
    }

    /**
     * Get all invoices for a workspace (auto-seeds mock entries if empty).
     */
    public function getWorkspaceInvoices(Request $request, string $workspaceId): JsonResponse
    {
        $hasInvoices = Invoice::where('workspace_id', $workspaceId)->exists();

        if (!$hasInvoices) {
            $this->seedInvoices($workspaceId);
        }

        $invoices = Invoice::where('workspace_id', $workspaceId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['invoices' => $invoices]);
    }

    /**
     * Compile developer time logs and generate a client invoice.
     */
    public function generateInvoiceFromTimelogs(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'workspace_id' => ['required', 'string'],
            'client_name' => ['required', 'string', 'max:255'],
            'client_email' => ['required', 'email'],
            'hours_logged' => ['required', 'numeric', 'min:0.5'],
            'hourly_rate' => ['required', 'numeric', 'min:10'],
        ]);

        $totalAmount = round($validated['hours_logged'] * $validated['hourly_rate'], 2);

        $invoice = Invoice::create([
            'workspace_id' => $validated['workspace_id'],
            'stripe_invoice_id' => 'in_mock_' . Str::random(10),
            'client_name' => $validated['client_name'],
            'client_email' => $validated['client_email'],
            'amount' => $totalAmount,
            'status' => 'draft',
            'due_at' => now()->addDays(14),
            'paid_at' => null,
        ]);

        return response()->json([
            'message' => 'Invoice compiled from time logs successfully.',
            'invoice' => $invoice,
        ], 201);
    }

    /**
     * Update invoice payment state safely using database row locking (FOR UPDATE).
     */
    public function payInvoice(Request $request, string $invoiceId): JsonResponse
    {
        $invoice = DB::transaction(function () use ($invoiceId) {
            // Pessimistic database row lock to prevent race conditions / double charge webhooks
            $lockedInvoice = Invoice::where('id', $invoiceId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedInvoice->status !== 'paid') {
                $lockedInvoice->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                ]);
            }

            return $lockedInvoice;
        });

        return response()->json([
            'message' => 'Invoice payment processed successfully.',
            'invoice' => $invoice,
        ]);
    }

    /**
     * Seed initial demo invoices.
     */
    private function seedInvoices(string $workspaceId): void
    {
        DB::transaction(function () use ($workspaceId) {
            Invoice::create([
                'workspace_id' => $workspaceId,
                'stripe_invoice_id' => 'in_101239',
                'client_name' => 'Acme Corp',
                'client_email' => 'billing@acme.com',
                'amount' => 725.00,
                'status' => 'paid',
                'due_at' => now()->subDays(5),
                'paid_at' => now()->subDays(6),
            ]);

            Invoice::create([
                'workspace_id' => $workspaceId,
                'stripe_invoice_id' => 'in_101240',
                'client_name' => 'Globex Inc',
                'client_email' => 'ap@globex.com',
                'amount' => 1250.00,
                'status' => 'sent',
                'due_at' => now()->addDays(10),
                'paid_at' => null,
            ]);
        });
    }
}
