<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConnectArticle;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ConnectArticleController extends Controller
{
    public function index(Request $request): Response
    {
        $query = ConnectArticle::query()
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = $request->string('search')->toString();
                $q->where(function ($sub) use ($term) {
                    $sub->where('title', 'like', "%{$term}%")
                        ->orWhere('article_code', 'like', "%{$term}%")
                        ->orWhere('category_code', 'like', "%{$term}%")
                        ->orWhere('partner_code', 'like', "%{$term}%");
                });
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->toString()))
            ->latest();

        $articles = $query->paginate(15)->withQueryString();

        return Inertia::render('Admin/ConnectArticles/ConnectArticleList', [
            'articles' => $articles,
            'filters'  => $request->only(['search', 'status']),
        ]);
    }

    public function show(ConnectArticle $connectArticle): Response
    {
        return Inertia::render('Admin/ConnectArticles/ConnectArticleDetail', [
            'article' => $connectArticle,
        ]);
    }
}
