<?php

namespace App\Http\Controllers\Api;

use App\Models\Article;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Routing\Controllers\Middleware;


class ArticleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    //عرض المقالات 

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
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // شرط الصورة
            'published_at' => 'nullable|date_format:Y-m-d H:i:s'
        ]);
    
        // التحقق من أن المستخدم ليس Admin
        if (auth::user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
    
        $validated['admin_id'] = auth::user()->admin->id;
    
        // إنشاء ملخص تلقائي إذا لم يتم توفيره
        if (empty($validated['summary'])) {
            $validated['summary'] = Str::words($validated['content'], 20, '...');
        }
    
        if (empty($validated['published_at'])) {
            $validated['published_at'] = now();
        }
    
        // التعامل مع الصورة إذا كانت موجودة
        if ($request->hasFile('image')) {
            // حفظ الصورة في مجلد "images/articles" وإرجاع اسم الملف
            $imageName = $request->file('image')->store('images/articles', 'public');
            $validated['image'] = $imageName;
        } else {
            $validated['image'] = null;
        }
    
        // إنشاء المقالة
        $article = Article::create($validated);
    
        return response()->json([
            'message' => 'Article added successfully!',
            'article' => $article
        ]);
    }
    

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $article = Article::findOrFail($id);
        return response()->json($article);
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
}
