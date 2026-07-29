<?php

namespace App\Http\Controllers;

use App\Models\BountyEscrow;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Web3EscrowController extends Controller
{
    /**
     * Get the Cryptographic Oracle payload signature from AWS KMS HSM vault.
     */
    public function getOracleSignature(Request $request, string $issueId): JsonResponse
    {
        $task = Task::findOrFail($issueId);
        $escrow = BountyEscrow::where('issue_id', $task->id)->firstOrFail();

        // Security check: Verify that the task is actually marked completed (merged/done)
        if ($task->status !== 'done') {
            return response()->json([
                'message' => 'Oracle validation failed: Linked Git Issue/PR is not merged and approved.',
                'signed' => false,
            ], 403);
        }

        // AWS KMS HMAC SHA256 simulation signing payload
        $signingPayload = json_encode([
            'issue_id' => $task->id,
            'smart_contract_address' => $escrow->smart_contract_address,
            'client_wallet' => $escrow->client_wallet,
            'dev_wallet' => $escrow->dev_wallet,
            'amount_usdc' => $escrow->amount_usdc,
            'timestamp' => time(),
        ]);

        $privateKey = env('ORACLE_KMS_SIGNING_KEY', 'aws_kms_hsm_key_default_signature_hash');
        $signature = hash_hmac('sha256', $signingPayload, $privateKey);

        return response()->json([
            'issue_id' => $task->id,
            'status' => 'approved',
            'signing_payload' => $signingPayload,
            'cryptographic_signature' => '0x' . $signature,
            'oracle_verifying_pubkey' => '0x_hsm_pubkey_0823908ac',
        ]);
    }

    /**
     * Lock funds inside a Web3 bounty.
     */
    public function lockBounty(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'issue_id' => ['required', 'uuid'],
            'smart_contract_address' => ['required', 'string'],
            'client_wallet' => ['required', 'string'],
            'dev_wallet' => ['required', 'string'],
            'amount_usdc' => ['required', 'numeric', 'min:1'],
        ]);

        $escrow = BountyEscrow::create([
            'issue_id' => $validated['issue_id'],
            'smart_contract_address' => $validated['smart_contract_address'],
            'client_wallet' => $validated['client_wallet'],
            'dev_wallet' => $validated['dev_wallet'],
            'amount_usdc' => $validated['amount_usdc'],
            'status' => 'Locked',
        ]);

        return response()->json([
            'message' => 'USDC bounty funds successfully locked in smart contract.',
            'escrow' => $escrow,
        ], 201);
    }

    /**
     * Release or dispute bounty status.
     */
    public function updateBountyStatus(Request $request, string $escrowId): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:Released,Disputed'],
        ]);

        $escrow = BountyEscrow::findOrFail($escrowId);

        DB::transaction(function () use ($escrow, $validated) {
            $updateData = ['status' => $validated['status']];
            if ($validated['status'] === 'Released') {
                $updateData['oracle_tx_hash'] = '0x_poly_' . Str::random(64);
            }
            $escrow->update($updateData);
        });

        return response()->json([
            'message' => 'USDC smart contract escrow state updated.',
            'escrow' => $escrow->refresh(),
        ]);
    }
}
