<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Occupation;
use App\Models\Relationship;
use App\Models\TaskType;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AppResourceController extends Controller
{
    public function taskTypes(Request $request): Response
    {
        $items = TaskType::query()
            ->with('partner:id,name')
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->string('search').'%'))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/AppResources/TaskTypeList', [
            'items'   => $items,
            'filters' => $request->only(['search']),
        ]);
    }

    public function occupations(Request $request): Response
    {
        $items = Occupation::query()
            ->with('partner:id,name')
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->string('search').'%'))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/AppResources/OccupationList', [
            'items'   => $items,
            'filters' => $request->only(['search']),
        ]);
    }

    public function relationships(Request $request): Response
    {
        $items = Relationship::query()
            ->with('partner:id,name')
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->string('search').'%'))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/AppResources/RelationshipList', [
            'items'   => $items,
            'filters' => $request->only(['search']),
        ]);
    }
}
