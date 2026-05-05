<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CurrencyController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/SuperAdmin/CurrencyList', [
            'currencies' => Currency::orderBy('code')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code'      => ['required', 'string', 'max:10', 'unique:currencies,code'],
            'name'      => ['required', 'string', 'max:100'],
            'symbol'    => ['nullable', 'string', 'max:10'],
            'is_active' => ['boolean'],
        ]);

        $validated['code'] = strtoupper($validated['code']);
        Currency::create($validated);

        return back()->with('success', 'Currency added.');
    }

    public function update(Request $request, Currency $currency): RedirectResponse
    {
        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:100'],
            'symbol'    => ['nullable', 'string', 'max:10'],
            'is_active' => ['boolean'],
        ]);

        $currency->update($validated);

        return back()->with('success', 'Currency updated.');
    }

    public function destroy(Currency $currency): RedirectResponse
    {
        $currency->delete();
        return back()->with('success', 'Currency removed.');
    }
}
