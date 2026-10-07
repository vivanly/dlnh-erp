<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $avatarPath = UploadedFile::fake()->image('profile.png', 64, 64)
            ->store('profile-avatars/'.$user->getKey(), 'local');
        $user->avatar_path = $avatarPath;
        $user->save();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
        Storage::disk('local')->assertMissing($avatarPath);
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }

    public function test_user_can_upload_and_view_their_own_avatar(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post('/profile/avatar', [
                'avatar' => UploadedFile::fake()->image('profile.png', 64, 64),
            ]);

        $response->assertRedirect('/profile');

        $path = $user->fresh()->avatar_path;
        $this->assertNotEmpty($path);
        Storage::disk('local')->assertExists($path);

        $avatarResponse = $this->get('/profile/avatar');
        $avatarResponse->assertOk();
        $this->assertStringContainsString('private', $avatarResponse->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', $avatarResponse->headers->get('Cache-Control'));
    }

    public function test_user_can_replace_and_remove_their_avatar(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post('/profile/avatar', [
            'avatar' => UploadedFile::fake()->image('first.png', 64, 64),
        ])->assertRedirect('/profile');
        $oldPath = $user->fresh()->avatar_path;

        $this->post('/profile/avatar', [
            'avatar' => UploadedFile::fake()->image('second.jpg', 64, 64),
        ])->assertRedirect('/profile');
        $newPath = $user->fresh()->avatar_path;

        $this->assertNotSame($oldPath, $newPath);
        Storage::disk('local')->assertMissing($oldPath);
        Storage::disk('local')->assertExists($newPath);

        $this->delete('/profile/avatar')->assertRedirect('/profile');
        $this->assertNull($user->fresh()->avatar_path);
        Storage::disk('local')->assertMissing($newPath);
    }

    public function test_avatar_upload_rejects_non_image_files_and_files_over_two_megabytes(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->from('/profile')->post('/profile/avatar', [
            'avatar' => UploadedFile::fake()->create('avatar.svg', 10, 'image/svg+xml'),
        ])->assertSessionHasErrors('avatar');

        $this->from('/profile')->post('/profile/avatar', [
            'avatar' => UploadedFile::fake()->image('large.png', 64, 64)->size(2049),
        ])->assertSessionHasErrors('avatar');

        $this->assertNull($user->fresh()->avatar_path);
        $this->assertSame([], Storage::disk('local')->allFiles('profile-avatars'));
    }

    public function test_avatar_endpoints_require_authentication(): void
    {
        $this->get('/profile/avatar')->assertRedirect('/login');
        $this->post('/profile/avatar')->assertRedirect('/login');
        $this->delete('/profile/avatar')->assertRedirect('/login');
    }
}
