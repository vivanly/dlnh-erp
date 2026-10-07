<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Display the authenticated user's private profile photo.
     */
    public function avatar(Request $request): BinaryFileResponse
    {
        $path = $request->user()->avatar_path;
        abort_unless($path && Storage::disk('local')->exists($path), Response::HTTP_NOT_FOUND);

        $response = response()->file(Storage::disk('local')->path($path));
        $response->setPrivate();
        $response->headers->set('Cache-Control', 'private, no-store');
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }

    /**
     * Store a profile photo for the authenticated user only.
     */
    public function updateAvatar(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'avatar' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
                'dimensions:max_width=2048,max_height=2048',
            ],
        ], [
            'avatar.required' => 'Vui lòng chọn ảnh đại diện.',
            'avatar.image' => 'Tệp tải lên phải là hình ảnh.',
            'avatar.mimes' => 'Ảnh đại diện chỉ hỗ trợ định dạng JPG, PNG hoặc WebP.',
            'avatar.max' => 'Ảnh đại diện không được vượt quá 2 MB.',
            'avatar.dimensions' => 'Kích thước ảnh tối đa là 2048 × 2048 pixel.',
        ]);

        $user = $request->user();
        $oldPath = $user->avatar_path;
        $newPath = $validated['avatar']->store('profile-avatars/'.$user->getKey(), 'local');

        if ($newPath === false) {
            throw new RuntimeException('Unable to store profile avatar.');
        }

        try {
            $user->avatar_path = $newPath;
            $user->save();
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($newPath);
            throw $exception;
        }

        $this->deleteOldAvatar($oldPath);

        return Redirect::route('profile.edit')->with('status', 'avatar-updated');
    }

    /**
     * Remove the authenticated user's profile photo.
     */
    public function destroyAvatar(Request $request): RedirectResponse
    {
        $user = $request->user();
        $oldPath = $user->avatar_path;
        $user->avatar_path = null;
        $user->save();

        $this->deleteOldAvatar($oldPath);

        return Redirect::route('profile.edit')->with('status', 'avatar-deleted');
    }

    private function deleteOldAvatar(?string $path): void
    {
        if ($path && Storage::disk('local')->exists($path) && !Storage::disk('local')->delete($path)) {
            Log::warning('Unable to delete replaced profile avatar.', ['path' => $path]);
        }
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();
        $avatarPath = $user->avatar_path;

        Auth::logout();

        $user->delete();
        $this->deleteOldAvatar($avatarPath);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
