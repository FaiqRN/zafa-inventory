<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create a test role
     */
    protected function createRole(): Role
    {
        return Role::create([
            'nama_role' => 'admin',
            'deskripsi' => 'Administrator dengan akses penuh',
        ]);
    }

    /**
     * Create a test user
     */
    protected function createUser(array $attributes = []): User
    {
        $role = $this->createRole();
        
        return User::create(array_merge([
            'role_id' => $role->role_id,
            'username' => 'testuser',
            'firstname' => 'Test',
            'lastname' => 'User',
            'password' => 'password123',
            'email' => 'test@example.com',
            'jenis_kelamin' => 'L',
            'tempat_lahir' => 'Jakarta',
            'tanggal_lahir' => '1990-01-01',
            'alamat' => 'Test Address',
            'telp' => '081234567890',
        ], $attributes));
    }

    /**
     * Test successful login with valid username and password
     */
    public function test_successful_login_with_valid_credentials(): void
    {
        // Create active user
        $user = $this->createUser();

        // Attempt login
        $response = $this->post(route('login'), [
            'username' => 'testuser',
            'password' => 'password123',
        ]);

        // Assert redirect to dashboard
        $response->assertRedirect(route('dashboard'));
        
        // Assert user is authenticated
        $this->assertAuthenticatedAs($user);
        
        // Assert session has success message
        $response->assertSessionHas('message', 'Selamat datang kembali!');
        $response->assertSessionHas('class', 'success');
    }

    /**
     * Test successful login with remember me checked
     */
    public function test_successful_login_with_remember_me_checked(): void
    {
        // Create active user
        $user = $this->createUser();

        // Attempt login with remember me
        $response = $this->post(route('login'), [
            'username' => 'testuser',
            'password' => 'password123',
            'remember' => true,
        ]);

        // Assert redirect to dashboard
        $response->assertRedirect(route('dashboard'));
        
        // Assert user is authenticated
        $this->assertAuthenticatedAs($user);
        
        // Assert session has success message
        $response->assertSessionHas('message', 'Selamat datang kembali!');
    }

    /**
     * Test successful login with remember me unchecked
     */
    public function test_successful_login_without_remember_me(): void
    {
        // Create active user
        $user = $this->createUser();

        // Attempt login without remember me
        $response = $this->post(route('login'), [
            'username' => 'testuser',
            'password' => 'password123',
            'remember' => false,
        ]);

        // Assert redirect to dashboard
        $response->assertRedirect(route('dashboard'));
        
        // Assert user is authenticated
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Test session data is stored after successful login
     */
    public function test_session_data_stored_after_login(): void
    {
        // Create active user
        $user = $this->createUser();

        // Attempt login
        $this->post(route('login'), [
            'username' => 'testuser',
            'password' => 'password123',
        ]);

        // Assert session has authentication data
        $this->assertTrue(Auth::check());
        $this->assertEquals($user->user_id, Auth::user()->user_id);
        $this->assertEquals($user->user_id, session('user_id'));
        
        // Assert flash messages are in session
        $this->assertEquals('Selamat datang kembali!', session('message'));
        $this->assertEquals('success', session('class'));
    }

    /**
     * Test activity log is created after successful login
     */
    public function test_activity_log_created_after_login(): void
    {
        // Create active user
        $user = $this->createUser();

        // Mock log to capture activity
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) use ($user) {
                return str_contains($message, 'logged in successfully') &&
                       $context['user_id'] === $user->user_id &&
                       isset($context['ip']) &&
                       isset($context['user_agent']) &&
                       isset($context['remember']);
            });

        // Attempt login
        $response = $this->post(route('login'), [
            'username' => 'testuser',
            'password' => 'password123',
            'remember' => true,
        ]);

        // Assert redirect to dashboard
        $response->assertRedirect(route('dashboard'));
    }

    /**
     * Test failed login with invalid credentials
     */
    public function test_failed_login_with_invalid_credentials(): void
    {
        // Create active user
        $this->createUser();

        // Attempt login with wrong password
        $response = $this->post(route('login'), [
            'username' => 'testuser',
            'password' => 'wrongpassword',
        ]);

        // Assert redirect back
        $response->assertRedirect();
        
        // Assert error message
        $response->assertSessionHasErrors(['username' => 'Username atau password tidak valid.']);
        
        // Assert user is not authenticated
        $this->assertGuest();
    }

    /**
     * Test rate limiter clears on successful login
     */
    public function test_rate_limiter_clears_on_successful_login(): void
    {
        // Create active user
        $this->createUser();

        // Simulate some failed attempts
        $rateLimitKey = 'login.127.0.0.1.testuser';
        RateLimiter::hit($rateLimitKey, 60);
        RateLimiter::hit($rateLimitKey, 60);

        // Verify rate limiter has attempts
        $this->assertTrue(RateLimiter::attempts($rateLimitKey) > 0);

        // Attempt successful login
        $response = $this->post(route('login'), [
            'username' => 'testuser',
            'password' => 'password123',
        ]);

        // Assert redirect to dashboard
        $response->assertRedirect(route('dashboard'));
        
        // Assert rate limiter is cleared
        $this->assertEquals(0, RateLimiter::attempts($rateLimitKey));
    }

    /**
     * Test cache control headers are present on login response
     */
    public function test_cache_control_headers_on_login_response(): void
    {
        // Create active user
        $this->createUser();

        // Attempt login
        $response = $this->post(route('login'), [
            'username' => 'testuser',
            'password' => 'password123',
        ]);

        // Assert cache control headers (order may vary)
        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertStringContainsString('no-cache', $cacheControl);
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('max-age=0', $cacheControl);
        $this->assertStringContainsString('must-revalidate', $cacheControl);
        $response->assertHeader('Pragma', 'no-cache');
        $response->assertHeader('Expires', 'Fri, 01 Jan 1990 00:00:00 GMT');
    }

    /**
     * Test login form displays correctly
     */
    public function test_login_form_displays(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertViewIs('auth.login');
    }

    /**
     * Test authenticated user redirects to dashboard from login page
     */
    public function test_authenticated_user_redirects_to_dashboard(): void
    {
        // Create and authenticate user
        $user = $this->createUser();

        $this->actingAs($user);

        // Try to access login page
        $response = $this->get(route('login'));

        // Assert redirect to dashboard
        $response->assertRedirect(route('dashboard'));
    }

    protected function tearDown(): void
    {
        // Clear rate limiter
        RateLimiter::clear('login.127.0.0.1.testuser');
        
        parent::tearDown();
    }
}
