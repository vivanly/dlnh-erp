<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_change_and_persist_the_interface_language(): void
    {
        $user = User::factory()->create(['locale' => 'vi']);

        $this->actingAs($user)
            ->get('/profile?lang=en')
            ->assertOk()
            ->assertSee('<html lang="en">', false)
            ->assertSee('Personal information')
            ->assertSessionHas('locale', 'en');

        $this->assertSame('en', $user->fresh()->locale);

        $this->get('/profile')
            ->assertOk()
            ->assertSee('<html lang="en">', false);

        $this->get('/profile?lang=vi')
            ->assertOk()
            ->assertSee('<html lang="vi">', false)
            ->assertSee('Hồ sơ cá nhân');

        $this->assertSame('vi', $user->fresh()->locale);
    }

    public function test_guest_can_select_language_on_the_login_page(): void
    {
        $this->get('/login?lang=en')
            ->assertOk()
            ->assertSee('<html lang="en">', false)
            ->assertSee('Employee ID')
            ->assertSee('Password')
            ->assertSee('Log in');

        $this->get('/login?lang=vi')
            ->assertOk()
            ->assertSee('<html lang="vi">', false)
            ->assertSee('Mã nhân viên');
    }

    public function test_language_selected_before_login_is_saved_to_the_user_account(): void
    {
        $user = User::factory()->create([
            'employee_code' => 'EMP-LOCALE',
            'locale' => 'vi',
        ]);

        $this->get('/login?lang=en');

        $this->post('/login', [
            'employee_code' => $user->employee_code,
            'password' => 'password',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
        $this->assertSame('en', $user->fresh()->locale);

        $this->get('/profile')
            ->assertOk()
            ->assertSee('<html lang="en">', false);
    }
}
