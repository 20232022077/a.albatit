<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AccessControlSeeder::class);
    }

    public function test_guest_can_view_login_page(): void
    {
        $this->get('/login')->assertOk()->assertSee('تسجيل الدخول');
    }

    public function test_authenticated_user_is_redirected_away_from_login(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)->get('/login')->assertRedirect(route('dashboard'));
    }

    public function test_successful_login_regenerates_session_and_redirects_to_dashboard(): void
    {
        $user = User::factory()->create(['is_active' => true, 'password' => 'CorrectPassword123']);

        $this->get('/login');
        $oldSessionId = session()->getId();

        $response = $this->post('/login', ['email' => $user->email, 'password' => 'CorrectPassword123']);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertNotEquals($oldSessionId, session()->getId());
    }

    public function test_login_fails_with_generic_message_not_revealing_which_field_is_wrong(): void
    {
        $user = User::factory()->create(['is_active' => true, 'password' => 'CorrectPassword123']);

        $response = $this->post('/login', ['email' => $user->email, 'password' => 'WrongPassword']);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_deactivated_user_cannot_log_in_even_with_correct_password(): void
    {
        $user = User::factory()->create(['is_active' => false, 'password' => 'CorrectPassword123']);

        $this->post('/login', ['email' => $user->email, 'password' => 'CorrectPassword123']);

        $this->assertGuest();
    }

    public function test_login_is_rate_limited_after_repeated_failures(): void
    {
        $user = User::factory()->create(['is_active' => true, 'password' => 'CorrectPassword123']);
        RateLimiter::clear(mb_strtolower($user->email).'|127.0.0.1');

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'wrong']);
        }

        // The 6th attempt should be throttled even with the correct password.
        $response = $this->post('/login', ['email' => $user->email, 'password' => 'CorrectPassword123']);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_logout_invalidates_the_session(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->actingAs($user);

        $this->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
