<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $user?->loadMissing('profile');
        $role = $user?->getRoleNames()->first();

        $avatarUrl = null;
        if ($user?->profile?->avatar_path) {
            $avatarUrl = Storage::disk('public')->url($user->profile->avatar_path);
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar_url' => $avatarUrl,
                ] : null,
                'role' => $role,
                'permissions' => $user ? $user->getPermissionsViaRoles()->pluck('name')->unique()->values()->all() : [],
                'modules' => $role ? config("admin_portal.modules.{$role}", []) : [],
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'new_api_token' => fn () => $request->session()->get('new_api_token'),
            ],
        ];
    }
}
