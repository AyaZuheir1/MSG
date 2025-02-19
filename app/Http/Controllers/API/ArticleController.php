<?php

namespace App\Http\Controllers\API;

use App\Models\Article;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
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
        $articles = Article::paginate(3);
        return response()->json([
            'code' => 200,
            'articles' => $articles
        ]);
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
            'status' => 'in:published,deleted',
        ]);
        if(!(Auth::user()->role == 'admin')) {
            abort(403, 'You are not authorized.');
        }
    //     return (!(Gate::allows('manage-article')));
    //     if (!(Gate::allows('manage-article'))) {
    //     return "Sorry, you are not allowed to";
    // }
// if(!$user->role == 'admin') {
//     abort(403, 'You are not allowed to publish articles.');
// }
        $validated['admin_id'] = auth::user()->admin->id;

        if (empty($validated['summary'])) {
            $validated['summary'] = Str::words($validated['content'], 20, '...');
        }

        if ($request->hasFile('image')) {
            $imageName = $request->file('image')->store('images/articles', 'public');
            $validated['image'] = $imageName;
        } else {
            $validated['image'] = null;
        }
        $validated['published_at'] = now();
        $article = Article::create($validated);

        return response()->json([
            'code',
            201,
            'message' => 'Article Published successfully!',
            'article' => $article
        ], 201);
    }


    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $article = Article::findOrFail($id);

        return response()->json([
            'code' => 200,
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
    public function update(Request $request, $id)
    {
        $user = Request()->user();
        if(!($request->user()->role == 'admin')) {
            abort(403, 'You are not authorized.');
        }
        $article = Article::find($id);
        if (!$article) {
            return response()->json(['message' => 'Article Not Found'], 404);
        }
        // if (!(Gate::allows('manage-article', $article))) {
        //     abort(403,"not allowed");
        // }

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
        if(!(Auth::user()->role == 'admin')) {
            abort(403, 'You are not authorized.');
        }
        $article = Article::find($id);
        if (! Gate::allows('manage-article', $article)) {
            abort(403);
        }
        $deleted = Article::destroy($id);
        if ($deleted) {
            return response()->json([
                'code' => 200,
                'message' => 'Article deleted successfully!',
            ]);
        }
        return response()->json([
            'code' => 404,
            'message' => 'Article Not Found!',
        ], 404);
    }
    public function Articles()
    {
        $articles = Article::orderBy('created_at', 'desc')->paginate(5);
        return response()->json([
            'code' => 200,
            'articles' => $articles,
        ]);
    }
    public function restore($id)
    {
        if(!(Auth::user()->role == 'admin')) {
            abort(403, 'You are not authorized.');
        }
        $article = Article::onlyTrashed()->find($id);
        if (!$article) {
            return response()->json(['message' => 'Article not found'], 404);
        }
        $article->restore();

        return response()->json([
            'code' => 200,
            'message' => 'article restored successfully',
            'article' => $article,
        ]);
    }
    public function trashedArticle()
    {
        if(!(Auth::user()->role == 'admin')) {
            abort(403, 'You are not authorized.');
        }        $articles = Article::onlyTrashed()->get();
        return response()->json([
            'code' => 200,
            'articles' => $articles,
        ]);
    }
    public function forceDelete($id)
{
    if(!(Auth::user()->role == 'admin')) {
        abort(403, 'You are not authorized.');
    }    try {
        $article = Article::withTrashed()->find($id);
// return $article;
        if (!$article) {
            return response()->json([
                'code' => 404,
                'message' => 'Article not found',
            ], 404);
        }
        $article->delete();
// return $article;
        return response()->json([
            'code' => 202,
            'message' => 'Article permanently deleted successfully',
        ], 202);
    } catch (\Exception $e) {
        return response()->json([
            'code' => 500,
            'message' => 'An error occurred while deleting the article',
            'error' => $e->getMessage(),
        ], 500);
    }
}

}
