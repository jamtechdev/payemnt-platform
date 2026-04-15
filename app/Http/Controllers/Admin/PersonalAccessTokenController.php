<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PersonalAccessTokenController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token_name' => ['required', 'string', 'max:255'],
        ]);

        $plain = $request->user()->createToken($validated['token_name'])->plainTextToken;

        return back()
            ->with('success', 'API token created. Copy it now; it will not be shown again.')
            ->with('new_api_token', $plain);
    }

    public function destroy(Request $request, int $token): RedirectResponse
    {
        $deleted = $request->user()->tokens()->where('id', $token)->delete();

        return $deleted
            ? back()->with('success', 'Token revoked.')
            : back()->with('error', 'Token not found.');
    }
}
