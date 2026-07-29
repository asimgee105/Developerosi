<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent; // Optional, we can parse manually for maximum safety

class AuthController extends Controller
{
    /**
     * Handle user login with custom device limits (Max 2).
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::validate($credentials)) {
            return response()->json([
                'message' => 'Invalid email or password.',
                'errors' => [
                    'email' => ['The provided credentials do not match our records.']
                ]
            ], 422);
        }

        $user = User::where('email', $credentials['email'])->first();

        // 1. Enforce 2-device login limit
        $lifetime = config('session.lifetime', 120) * 60;
        $activeSessions = DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('last_activity', '>=', time() - $lifetime)
            ->get();

        if ($activeSessions->count() >= 2) {
            if ($request->boolean('force')) {
                // Terminate the oldest session
                $oldestSession = DB::table('sessions')
                    ->where('user_id', $user->id)
                    ->orderBy('last_activity', 'asc')
                    ->first();

                if ($oldestSession) {
                    DB::table('sessions')->where('id', $oldestSession->id)->delete();
                }
            } else {
                // Map session user agents to clean browser/OS descriptions
                $sessionList = $activeSessions->map(function ($session) {
                    return [
                        'id' => $session->id,
                        'ip_address' => $session->ip_address,
                        'device' => $this->parseUserAgent($session->user_agent),
                        'last_active' => date('Y-m-d H:i:s', $session->last_activity),
                    ];
                });

                return response()->json([
                    'message' => 'Active device limit reached (Max 2). Please disconnect another device to login.',
                    'code' => 'session_limit_exceeded',
                    'sessions' => $sessionList,
                ], 423);
            }
        }

        // 2. Handle Fortify-compatible Two Factor check
        if ($user->two_factor_secret && $user->two_factor_confirmed_at) {
            // Save temporary state in session for Fortify challenge route
            $request->session()->put([
                'login.id' => $user->id,
                'login.remember' => $request->boolean('remember'),
            ]);

            return response()->json([
                'two_factor' => true,
                'message' => 'Two-factor authentication code required.'
            ]);
        }

        // 3. Regular login
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return response()->json([
            'user' => $user,
            'active_organization' => $user->activeOrganization,
            'message' => 'Login successful.'
        ]);
    }

    /**
     * Get active login sessions for the authenticated user.
     */
    public function getActiveSessions(Request $request): JsonResponse
    {
        $user = $request->user();
        $lifetime = config('session.lifetime', 120) * 60;
        
        $sessions = DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('last_activity', '>=', time() - $lifetime)
            ->get()
            ->map(function ($session) use ($request) {
                return [
                    'id' => $session->id,
                    'ip_address' => $session->ip_address,
                    'device' => $this->parseUserAgent($session->user_agent),
                    'is_current' => $session->id === $request->session()->getId(),
                    'last_active' => date('Y-m-d H:i:s', $session->last_activity),
                ];
            });

        return response()->json(['sessions' => $sessions]);
    }

    /**
     * Terminate a specific active session.
     */
    public function terminateSession(Request $request, string $sessionId): JsonResponse
    {
        $user = $request->user();

        // Ensure the session belongs to the user
        $deleted = DB::table('sessions')
            ->where('id', $sessionId)
            ->where('user_id', $user->id)
            ->delete();

        if ($deleted) {
            return response()->json([
                'message' => 'Device logged out successfully.'
            ]);
        }

        return response()->json([
            'message' => 'Session not found or unauthorized.'
        ], 404);
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Logged out successfully.'
        ]);
    }

    /**
     * Helper to parse user agent string to clean OS and Browser combination.
     */
    private function parseUserAgent(?string $userAgent): string
    {
        if (empty($userAgent)) {
            return 'Unknown Device';
        }

        $browser = 'Unknown Browser';
        $os = 'Unknown OS';

        // Quick OS Detection
        if (preg_match('/windows|win32/i', $userAgent)) {
            $os = 'Windows';
        } elseif (preg_match('/macintosh|mac os x/i', $userAgent)) {
            $os = 'macOS';
        } elseif (preg_match('/linux/i', $userAgent)) {
            $os = 'Linux';
        } elseif (preg_match('/iphone|ipad|ipod/i', $userAgent)) {
            $os = 'iOS';
        } elseif (preg_match('/android/i', $userAgent)) {
            $os = 'Android';
        }

        // Quick Browser Detection
        if (preg_match('/chrome/i', $userAgent)) {
            $browser = 'Chrome';
        } elseif (preg_match('/safari/i', $userAgent) && !preg_match('/chrome/i', $userAgent)) {
            $browser = 'Safari';
        } elseif (preg_match('/firefox/i', $userAgent)) {
            $browser = 'Firefox';
        } elseif (preg_match('/edge/i', $userAgent)) {
            $browser = 'Edge';
        } elseif (preg_match('/msie|trident/i', $userAgent)) {
            $browser = 'Internet Explorer';
        }

        return "$browser on $os";
    }

    /**
     * Set up Two Factor Authentication (generate secret & QR code).
     */
    public function setupTwoFactor(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!class_exists(\Laravel\Fortify\TwoFactorAuthenticationProvider::class)) {
            return response()->json(['message' => 'Fortify 2FA Provider is not installed.'], 500);
        }

        $provider = app(\Laravel\Fortify\TwoFactorAuthenticationProvider::class);

        $secret = $provider->generateSecretKey();
        $qrCodeUrl = $provider->qrCodeUrl($user->email, config('app.name'), $secret);

        // Generate Google Chart QR code image URL for instant visual loading
        $qrCodeImageUrl = 'https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl=' . urlencode($qrCodeUrl);

        // Save temporary secret to user (not yet confirmed)
        $user->forceFill([
            'two_factor_secret' => encrypt($secret),
            'two_factor_confirmed_at' => null,
        ])->save();

        return response()->json([
            'secret' => $secret,
            'qr_code_url' => $qrCodeImageUrl,
        ]);
    }

    /**
     * Confirm/Verify Two Factor code to finalize activation.
     */
    public function verifyTwoFactor(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $user = $request->user();
        
        if (!$user->two_factor_secret) {
            return response()->json(['message' => 'Two-factor setup has not been initiated.'], 400);
        }

        $secret = decrypt($user->two_factor_secret);
        $provider = app(\Laravel\Fortify\TwoFactorAuthenticationProvider::class);
        $isValid = $provider->verify($secret, $request->input('code'));

        if (!$isValid) {
            return response()->json([
                'message' => 'The provided code is invalid.',
                'errors' => [
                    'code' => ['Invalid two-factor code.']
                ]
            ], 422);
        }

        // Generate recovery codes
        $recoveryCodes = Collection::times(8, function () {
            return Str::random(10) . '-' . Str::random(10);
        })->all();

        $user->forceFill([
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes)),
        ])->save();

        return response()->json([
            'message' => 'Two-factor authentication enabled successfully.',
            'recovery_codes' => $recoveryCodes,
        ]);
    }

    /**
     * Disable Two Factor Authentication.
     */
    public function disableTwoFactor(Request $request): JsonResponse
    {
        $user = $request->user();

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return response()->json([
            'message' => 'Two-factor authentication disabled successfully.'
        ]);
    }
}
