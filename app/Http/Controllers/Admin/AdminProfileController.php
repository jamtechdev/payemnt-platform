<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAdminProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class AdminProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user();
        $metadata = is_array($user?->metadata) ? $user->metadata : [];

        return Inertia::render('Admin/AdminProfile', [
            'profile' => [
                'job_title' => $metadata['job_title'] ?? null,
                'phone' => $metadata['phone'] ?? null,
                'timezone' => $metadata['timezone'] ?? null,
            ],
            'apiTokens' => $user->hasRole('super_admin')
                ? $user->tokens()->orderByDesc('id')->get(['id', 'name', 'last_used_at', 'created_at'])->map(fn ($t) => [
                    'id' => $t->id,
                    'name' => $t->name,
                    'last_used_at' => $t->last_used_at?->toIso8601String(),
                    'created_at' => $t->created_at->toIso8601String(),
                ])
                : [],
        ]);
    }

    public function update(UpdateAdminProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $metadata = is_array($user->metadata) ? $user->metadata : [];
        $avatarPath = is_string($metadata['avatar_path'] ?? null) ? $metadata['avatar_path'] : null;

        if ($request->boolean('remove_avatar') && $avatarPath) {
            Storage::disk('public')->delete($avatarPath);
            $avatarPath = null;
        }

        if ($request->hasFile('avatar')) {
            if ($avatarPath) {
                Storage::disk('public')->delete($avatarPath);
            }
            $avatarPath = $request->file('avatar')->store("avatars/{$user->id}", 'public');
        }

        $user->update([
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'metadata' => [
                ...$metadata,
                'job_title' => $request->input('job_title'),
                'phone' => $request->input('phone'),
                'timezone' => $request->input('timezone'),
                'avatar_path' => $avatarPath,
            ],
        ]);

        return redirect()->route('admin.profile.index')->with('success', 'Profile updated.');
    }
}
