<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RefreshRememberCookieTest extends TestCase
{
    use RefreshDatabase;

    public function test_remember_cookie_is_extended_while_the_user_keeps_using_the_app(): void
    {
        $user = $this->user();
        $this->actingAs($user);

        $cookieName = Auth::guard()->getRecallerName();
        $recaller = $this->recaller($user);

        $this->withCookie($cookieName, $recaller)
            ->withSession(['auth.remember_cookie_refreshed_at' => now()->subDays(2)->timestamp])
            ->get('/mpanel')
            ->assertCookie($cookieName, $recaller)
            ->assertCookieNotExpired($cookieName)
            ->assertSessionHas('auth.remember_cookie_refreshed_at');
    }

    public function test_remember_cookie_is_not_rewritten_within_the_refresh_interval(): void
    {
        $user = $this->user();
        $this->actingAs($user);

        $cookieName = Auth::guard()->getRecallerName();

        $this->withCookie($cookieName, $this->recaller($user))
            ->withSession(['auth.remember_cookie_refreshed_at' => now()->timestamp])
            ->get('/mpanel')
            ->assertCookieMissing($cookieName);
    }

    public function test_session_only_login_does_not_receive_a_remember_cookie(): void
    {
        $this->actingAs($this->user());

        $this->get('/mpanel')
            ->assertCookieMissing(Auth::guard()->getRecallerName());
    }

    public function test_remember_cookie_from_another_account_is_removed_instead_of_extended(): void
    {
        $this->actingAs($this->user());

        $cookieName = Auth::guard()->getRecallerName();

        $this->withCookie($cookieName, '999|other-token|hash')
            ->get('/mpanel')
            ->assertCookieExpired($cookieName);
    }

    private function user(): User
    {
        $role = Role::query()->create([
            'name' => 'Administrator',
            'permissions' => ['dashboard'],
        ]);

        return User::query()->forceCreate([
            'name' => 'Test User',
            'email' => 'remember-cookie@example.test',
            'password' => Hash::make('secret-password'),
            'remember_token' => 'remember-token',
            'role_id' => $role->id,
            'is_active' => true,
        ]);
    }

    private function recaller(User $user): string
    {
        return $user->getAuthIdentifier().'|'.$user->getRememberToken().'|hash';
    }
}
