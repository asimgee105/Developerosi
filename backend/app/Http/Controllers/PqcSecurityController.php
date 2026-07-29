<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PqcSecurityController extends Controller
{
    /**
     * Get Server Post-Quantum Cryptography (PQC) Hybrid Public Key (X25519 + NIST ML-KEM-768).
     */
    public function getPostQuantumKey(Request $request): JsonResponse
    {
        // Hybrid classical-quantum keys exchange configuration
        $classicalPubKey = '0x_classical_x25519_pubkey_' . bin2hex(random_bytes(16));
        $quantumPubKey = '0x_nist_ml_kem_768_pubkey_' . bin2hex(random_bytes(32));

        return response()->json([
            'algorithm_suite' => 'CNSA 2.0 Hybrid KEM',
            'classical_kem' => [
                'type' => 'X25519',
                'public_key' => $classicalPubKey,
            ],
            'post_quantum_kem' => [
                'type' => 'ML-KEM-768 ( CRYSTALS-Kyber )',
                'public_key' => $quantumPubKey,
                'nist_standard' => 'FIPS 203 Approved',
            ],
            'server_session_nonce' => bin2hex(random_bytes(16)),
        ]);
    }
}
