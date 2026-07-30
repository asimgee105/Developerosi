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
Route::get('/api/admin/metrics', function (Request $request) {
    $secret = $request->header('X-Admin-Secret') ?? $request->input('secret');
    $expectedSecret = env('ADMIN_SECRET_KEY', 'devos_admin_secret_2026');
    if ($secret !== $expectedSecret) {
        return response()->json(['message' => 'Unauthorized: Invalid admin secret passcode.'], 401);
    }

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

// Admin File Manager - Get file tree (excluding vendor, node_modules, caches)
Route::get('/api/admin/files', function (Request $request) {
    $secret = $request->header('X-Admin-Secret') ?? $request->input('secret');
    $expectedSecret = env('ADMIN_SECRET_KEY', 'devos_admin_secret_2026');
    if ($secret !== $expectedSecret) {
        return response()->json(['message' => 'Unauthorized: Invalid admin secret passcode.'], 401);
    }

    $basePath = realpath(base_path()); // Browse backend codebase
    $files = [];
    
    if ($basePath && file_exists($basePath)) {
        $directory = new RecursiveDirectoryIterator($basePath, RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator = new RecursiveIteratorIterator($directory);
        
        foreach ($iterator as $file) {
            $realPath = $file->getRealPath();
            $relativePath = str_replace($basePath . DIRECTORY_SEPARATOR, '', $realPath);
            $normalizedPath = str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);
            
            // Skip massive caches and libraries
            if (Str::startsWith($normalizedPath, ['vendor/', 'storage/', '.git/', 'bootstrap/cache/'])) {
                continue;
            }
            
            $files[] = [
                'name' => $file->getFilename(),
                'path' => $normalizedPath,
                'size' => $file->getSize(),
            ];
        }
    }
    
    return response()->json(['files' => $files]);
});

// Admin File Manager - Get single file content
Route::get('/api/admin/files/content', function (Request $request) {
    $secret = $request->header('X-Admin-Secret') ?? $request->input('secret');
    $expectedSecret = env('ADMIN_SECRET_KEY', 'devos_admin_secret_2026');
    if ($secret !== $expectedSecret) {
        return response()->json(['message' => 'Unauthorized: Invalid admin secret passcode.'], 401);
    }

    $path = $request->query('path');
    $basePath = realpath(base_path());
    $fullPath = realpath($basePath . '/' . $path);
    
    // Safety check: prevent directory traversal
    if (!$fullPath || !Str::startsWith($fullPath, $basePath)) {
        return response()->json(['message' => 'Unauthorized path access.'], 403);
    }
    
    if (!file_exists($fullPath)) {
        return response()->json(['message' => 'File not found.'], 404);
    }
    
    return response()->json([
        'content' => file_get_contents($fullPath),
        'path' => $path
    ]);
});

// Admin File Manager - Save/write file changes
Route::post('/api/admin/files/content', function (Request $request) {
    $secret = $request->header('X-Admin-Secret') ?? $request->input('secret');
    $expectedSecret = env('ADMIN_SECRET_KEY', 'devos_admin_secret_2026');
    if ($secret !== $expectedSecret) {
        return response()->json(['message' => 'Unauthorized: Invalid admin secret passcode.'], 401);
    }

    $path = $request->input('path');
    $content = $request->input('content');
    $basePath = realpath(base_path());
    $fullPath = $basePath . '/' . $path;
    
    // Safety check: prevent directory traversal
    $realParent = realpath(dirname($fullPath));
    if (!$realParent || !Str::startsWith($realParent, $basePath)) {
        return response()->json(['message' => 'Unauthorized path access.'], 403);
    }
    
    file_put_contents($fullPath, $content);
    
    return response()->json(['message' => 'File saved successfully.']);
});

// Admin DB Executor - Run raw SQL queries
Route::post('/api/admin/db/query', function (Request $request) {
    $secret = $request->header('X-Admin-Secret') ?? $request->input('secret');
    $expectedSecret = env('ADMIN_SECRET_KEY', 'devos_admin_secret_2026');
    if ($secret !== $expectedSecret) {
        return response()->json(['message' => 'Unauthorized: Invalid admin secret passcode.'], 401);
    }

    $query = $request->input('query');
    
    try {
        $trimmedQuery = strtolower(trim($query));
        if (Str::startsWith($trimmedQuery, 'select') || Str::startsWith($trimmedQuery, 'show') || Str::startsWith($trimmedQuery, 'desc') || Str::startsWith($trimmedQuery, 'explain')) {
            $results = DB::select($query);
        } else {
            // Run statement (INSERT, UPDATE, DELETE, CREATE, ALTER)
            $affected = DB::statement($query);
            $results = [['status' => 'Query executed successfully.', 'affected' => $affected]];
        }
        return response()->json(['results' => $results]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 400);
    }
});

// Admin Route Catalog - Get all active Laravel API routes
Route::get('/api/admin/routes', function (Request $request) {
    $secret = $request->header('X-Admin-Secret') ?? $request->input('secret');
    $expectedSecret = env('ADMIN_SECRET_KEY', 'devos_admin_secret_2026');
    if ($secret !== $expectedSecret) {
        return response()->json(['message' => 'Unauthorized: Invalid admin secret passcode.'], 401);
    }

    $routeCollection = Route::getRoutes();
    $routes = [];
    
    foreach ($routeCollection as $value) {
        $routes[] = [
            'method' => implode('|', $value->methods()),
            'uri' => $value->uri(),
            'name' => $value->getName() ?: 'N/A',
            'action' => str_replace('App\\Http\\Controllers\\', '', $value->getActionName()),
        ];
    }
    
    return response()->json(['routes' => $routes]);
});

