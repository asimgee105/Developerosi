<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test user registration auto-provisions a default workspace.
     */
    public function test_user_registration_auto_provisions_default_workspace(): void
    {
        $response = $this->postJson('/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $this->assertTrue(in_array($response->status(), [200, 201]), 'Response status was: ' . $response->status());

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'name' => 'John Doe',
        ]);

        $user = User::where('email', 'john@example.com')->first();
        $this->assertNotNull($user->active_organization_id);

        $this->assertDatabaseHas('organizations', [
            'id' => $user->active_organization_id,
            'name' => "John Doe's Workspace",
        ]);

        $this->assertDatabaseHas('organization_members', [
            'user_id' => $user->id,
            'organization_id' => $user->active_organization_id,
            'role' => 'owner',
        ]);
    }

    /**
     * Test active session limit warning is triggered when exceeding 2 concurrent sessions.
     */
    public function test_active_session_limit_blocks_third_login(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('Password123!'),
        ]);

        // Mock 2 active sessions in the database
        DB::table('sessions')->insert([
            [
                'id' => 'session_1',
                'user_id' => $user->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/100.0.0.0 Safari/537.36',
                'payload' => 'payload1',
                'last_activity' => time() - 3600, // 1 hour ago (Active)
            ],
            [
                'id' => 'session_2',
                'user_id' => $user->id,
                'ip_address' => '127.0.0.2',
                'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) Chrome/101.0.0.0 Safari/537.36',
                'payload' => 'payload2',
                'last_activity' => time() - 600, // 10 minutes ago (Active)
            ]
        ]);

        // Attempt 3rd login (without force parameter)
        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'Password123!',
        ]);

        $response->assertStatus(423); // Locked
        $response->assertJsonStructure([
            'message',
            'code',
            'sessions' => [
                '*' => ['id', 'ip_address', 'device', 'last_active']
            ]
        ]);
        
        $this->assertEquals('session_limit_exceeded', $response->json('code'));
    }

    /**
     * Test force login terminates the oldest active session.
     */
    public function test_force_login_terminates_oldest_session_and_succeeds(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('Password123!'),
        ]);

        // Mock 2 active sessions
        DB::table('sessions')->insert([
            [
                'id' => 'session_oldest',
                'user_id' => $user->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0) Firefox/99.0',
                'payload' => 'payload1',
                'last_activity' => time() - 3600, // 1 hour ago (Oldest active)
            ],
            [
                'id' => 'session_newer',
                'user_id' => $user->id,
                'ip_address' => '127.0.0.2',
                'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10) Chrome/101.0.0.0',
                'payload' => 'payload2',
                'last_activity' => time() - 600, // 10 minutes ago
            ]
        ]);

        // Login with force = true
        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'Password123!',
            'force' => true,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['user', 'message']);

        // Assert oldest session was deleted
        $this->assertDatabaseMissing('sessions', [
            'id' => 'session_oldest',
        ]);

        // Assert newer session is still active
        $this->assertDatabaseHas('sessions', [
            'id' => 'session_newer',
        ]);
    }

    /**
     * Test setup and verification of Two-Factor Authentication.
     */
    public function test_user_can_setup_and_verify_two_factor(): void
    {
        $user = User::factory()->create();

        // 1. Setup 2FA
        $response = $this->actingAs($user)
            ->postJson('/api/auth/two-factor/setup');

        $response->assertStatus(200);
        $response->assertJsonStructure(['secret', 'qr_code_url']);

        $user->refresh();
        $this->assertNotNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_confirmed_at);

        // 2. Verify invalid code fails
        $verifyFail = $this->actingAs($user)
            ->postJson('/api/auth/two-factor/verify', [
                'code' => '000000',
            ]);

        $verifyFail->assertStatus(422);

        // 3. Verify valid code succeeds
        $secret = decrypt($user->two_factor_secret);
        $otp = app(\PragmaRX\Google2FA\Google2FA::class)->getCurrentOtp($secret);

        $verifySuccess = $this->actingAs($user)
            ->postJson('/api/auth/two-factor/verify', [
                'code' => $otp,
            ]);

        $verifySuccess->assertStatus(200);
        $verifySuccess->assertJsonStructure(['message', 'recovery_codes']);

        $user->refresh();
        $this->assertNotNull($user->two_factor_confirmed_at);
        $this->assertNotNull($user->two_factor_recovery_codes);
    }

    /**
     * Test user can disable Two-Factor Authentication.
     */
    public function test_user_can_disable_two_factor(): void
    {
        $user = User::factory()->create([
            'two_factor_secret' => encrypt('secret_key'),
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => encrypt(json_encode(['code1', 'code2'])),
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/auth/two-factor/disable');

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Two-factor authentication disabled successfully.']);

        $user->refresh();
        $this->assertNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_confirmed_at);
        $this->assertNull($user->two_factor_recovery_codes);
    }
}
