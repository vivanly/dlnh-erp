<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_list_shows_100_records_per_page_by_default(): void
    {
        $viewer = User::factory()->create(['role' => 'it']);
        User::factory()->count(100)->create();

        $response = $this->actingAs($viewer)->get(route('users.index'));

        $response->assertOk()
            ->assertSee('100 dòng')
            ->assertViewHas('users', fn ($users) => $users->perPage() === 100 && $users->count() === 100);
    }

    public function test_employee_list_supports_the_new_page_sizes_and_rejects_unsupported_sizes(): void
    {
        $viewer = User::factory()->create(['role' => 'it']);
        User::factory()->count(100)->create();

        $this->actingAs($viewer);
        foreach ([100, 500, 1000, 5000, 10000, 50000] as $size) {
            $response = $this->get(route('users.index', ['per_page' => $size]));
            $response->assertOk()
                ->assertViewHas('users', fn ($users) => $users->perPage() === $size && $users->count() === min($size, 101));
        }

        $response->assertSee('100 dòng')
            ->assertSee('500 dòng')
            ->assertSee('1000 dòng')
            ->assertSee('5000 dòng')
            ->assertSee('10000 dòng')
            ->assertSee('50000 dòng');

        $this->actingAs($viewer)
            ->get(route('users.index', ['per_page' => 50]))
            ->assertOk()
            ->assertViewHas('users', fn ($users) => $users->perPage() === 100);
    }

    public function test_non_it_users_cannot_view_employee_list(): void
    {
        $viewer = User::factory()->create(['role' => 'staff']);

        $this->actingAs($viewer)
            ->get(route('users.index'))
            ->assertForbidden();
    }
}
