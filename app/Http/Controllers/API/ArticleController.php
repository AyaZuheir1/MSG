<?php

namespace App\Http\Controllers\API;

use App\Models\Article;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ArticleController extends Controller
{
    public function __construct()
    {
        // $this->middleware('auth:sanctum');
    }
    /**
     * Display a listing of the resource.
     */

    public function index()
    {
        $articles = Article::select('id', 'title', 'summary', 'image')->get();
        return response()->json($articles);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'summary' => 'nullable|string|max:500',
            'content' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'published_at' => 'nullable|date_format:Y-m-d H:i:s'
        ]);

        if (auth::user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated['admin_id'] = auth::user()->admin->id;

        if (empty($validated['summary'])) {
            $validated['summary'] = Str::words($validated['content'], 20, '...');
        }

        if (empty($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        if ($request->hasFile('image')) {
            $imageName = $request->file('image')->store('images/articles', 'public');
            $validated['image'] = $imageName;
        } else {
            $validated['image'] = null;
        }

        $article = Article::create($validated);

        return response()->json([
            'message' => 'Article added successfully!',
            'article' => $article
        ]);
    }


    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $article = Article::findOrFail($id);

        if (!$article) {
            return response()->json(['message' => 'Article not found'], 404);
        }

        return response()->json([
            'status' => 'success',
            'article' => $article
        ]);
    }
    public function search(Request $request)
    {
        $keyword = $request->query('keyword');

        if (!$keyword) {
            return response()->json(['message' => 'Search keyword is required'], 400);
        }

        $articles = Article::where('title', 'LIKE', "%{$keyword}%")
            ->orWhere('content', 'LIKE', "%{$keyword}%")
            ->get();

        if ($articles->isEmpty()) {
            return response()->json(['message' => 'No articles found'], 404);
        }

        return response()->json([
            'status' => 'success',
            'articles' => $articles
        ]);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Article $article)
    {
        if (auth::user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'summary' => 'sometimes|required|string|max:500',
            'content' => 'sometimes|required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);


        if ($request->hasFile('image')) {
            if ($article->image) {
                Storage::disk('public')->delete($article->image);
            }
            $imageName = $request->file('image')->store('images/articles', 'public');
            $validated['image'] = $imageName;
        }


        $article->update($validated);

        return response()->json([
            'message' => 'Article updated successfully!',
            'article' => $article->fresh(),
        ]);
    }

    public function destroy(string $id)
    {
        $article = Article::findOrFail($id);

        if (auth::user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($article->image) {
            Storage::disk('public')->delete($article->image);
        }

        $article->delete();

        return response()->json([
            'message' => 'Article deleted successfully!',
        ]);
    }
    public function Articles()
    {
        $articles = Article::orderBy('created_at', 'desc')->paginate(5);
        return $articles;
    }
}
