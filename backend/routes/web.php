<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => 'DevOS Platform API Gateway is active.',
        'version' => '1.0.0',
        'status' => 'healthy',
        'timestamp' => now()
    ]);
});

// Custom Authenticated Session routes with 2-device check & session termination
Route::post('/api/auth/login', [AuthController::class, 'login']);
Route::post('/api/auth/logout', [AuthController::class, 'logout']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/api/auth/sessions', [AuthController::class, 'getActiveSessions']);
    Route::delete('/api/auth/sessions/{id}', [AuthController::class, 'terminateSession']);

    // Custom Two-Factor activation endpoints
    Route::post('/api/auth/two-factor/setup', [AuthController::class, 'setupTwoFactor']);
    Route::post('/api/auth/two-factor/verify', [AuthController::class, 'verifyTwoFactor']);
    Route::post('/api/auth/two-factor/disable', [AuthController::class, 'disableTwoFactor']);
});

// Super Admin platform metrics reporting endpoint
Route::get('/api/admin/metrics', function () {
    $totalUsers = \App\Models\User::count();
    $totalWorkspaces = \App\Models\Organization::count();
    $totalSubscriptions = \App\Models\BillingSubscription::where('status', 'active')->count();
    $paidAmount = \App\Models\Invoice::where('status', 'paid')->sum('amount');
    
    $subscriptions = \DB::table('billing_subscriptions')
        ->join('organizations', 'billing_subscriptions.workspace_id', '=', 'organizations.id')
        ->select('organizations.name as workspace_name', 'billing_subscriptions.stripe_price_id', 'billing_subscriptions.status')
        ->get();

    return response()->json([
        'total_registered_users' => $totalUsers,
        'total_workspaces' => $totalWorkspaces,
        'total_active_subscribers' => $totalSubscriptions,
        'total_invoices_paid_amount' => (float)$paidAmount,
        'active_subscriptions' => $subscriptions
    ]);
});

