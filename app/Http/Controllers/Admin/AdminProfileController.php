<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAdminProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class AdminProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user()->load('profile');

        return Inertia::render('Admin/AdminProfile', [
            'profile' => [
                'job_title' => $user->profile?->job_title,
                'phone' => $user->profile?->phone,
                'timezone' => $user->profile?->timezone,
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

        DB::transaction(function () use ($request, $user): void {
            $user->update([
                'name' => $request->string('name')->toString(),
                'email' => $request->string('email')->toString(),
            ]);

            $profile = $user->profile()->firstOrNew(['user_id' => $user->id]);
            if ($request->boolean('remove_avatar') && $profile->avatar_path) {
                Storage::disk('public')->delete($profile->avatar_path);
                $profile->avatar_path = null;
            }

            if ($request->hasFile('avatar')) {
                if ($profile->avatar_path) {
                    Storage::disk('public')->delete($profile->avatar_path);
                }
                $path = $request->file('avatar')->store("avatars/{$user->id}", 'public');
                $profile->avatar_path = $path;
            }

            $profile->fill([
                'job_title' => $request->input('job_title'),
                'phone' => $request->input('phone'),
                'timezone' => $request->input('timezone'),
            ]);
            $profile->save();
        });

        return redirect()->route('admin.profile.edit')->with('success', 'Profile updated.');
    }
}
