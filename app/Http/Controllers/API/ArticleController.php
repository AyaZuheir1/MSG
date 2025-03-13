<?php

namespace App\Http\Controllers\API;

use App\Models\Article;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\SupabaseStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Exception;

class ArticleController extends Controller
{
    protected $supabaseStorage;

    public function __construct(SupabaseStorageService $supabaseStorage)
    {
        $this->supabaseStorage = $supabaseStorage;
    }
    /**
     * Display a listing of the resource.
     */

    public function index()
    {
        $articles = Article::paginate(6);

        return response()->json([
            'code' => 200,
            'articles' => $articles
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        if (!Gate::allows('manage-article')) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'summary' => 'nullable|string|max:500',
            'content' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'in:published,deleted',
        ]);

        DB::beginTransaction();
        try {
            $validated['admin_id'] = Auth::user()->admin->id;

            if (empty($validated['summary'])) {
                $validated['summary'] = Str::words(strip_tags($validated['content']), 20, '...');
            }

            $validated['published_at'] = now();

            $article = Article::create($validated);
            
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                // $imageName = time() . '_image.' . $image->getClientOriginalExtension();
                // $image->storeAs('public/article/images', $imageName);
                $supabasePath = 'article-images/' . "$article->id";
                $supabaseResult = $this->supabaseStorage->uploadFile($image, $supabasePath);
                if (!$supabaseResult) {
                    new Exception('Could not upload your image , try agian ' . $image);
                }
            }
            $article->update(['image' => $supabaseResult['file_url']]);

            DB::commit();

            return response()->json([
                'message' => 'Article published successfully!',
                'article' => $article,
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Something went wrong while publishing the article.',
                'details' => $e->getMessage(),
            ], 500);
        }
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
        if (!Gate::allows('manage-article')) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        $article = Article::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'summary' => 'sometimes|required|string|max:500',
            'content' => 'sometimes|required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        DB::beginTransaction();
        try {
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                // // Delete old image only if the new one is successfully uploaded
                // if ($article->image) {
                //     Storage::disk('public')->delete($article->image);
                // }

                // $imagePath = $image->store('articles', 'public');
                // $validated['image'] = $imagePath;

                $supabasePath = "article-images/{$article->id}";
                $supabaseResult = $this->supabaseStorage->uploadFile($image, $supabasePath);

                if (!$supabaseResult) {
                    throw new Exception('Failed to upload image to Supabase Storage.');
                }
                $validated['image'] = $supabaseResult['file_url'];
            }

            $article->update($validated);

            DB::commit();

            return response()->json([
                'message' => 'Article updated successfully!',
                'article' => $article->fresh(),
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();

            // Rollback image upload if any
            if (!empty($validated['image'])) {
                Storage::disk('public')->delete($validated['image']);
            }

            return response()->json([
                'error' => 'Something went wrong while updating the article.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
    public function destroy(string $id): JsonResponse
    {
        if (!Gate::allows('manage-article')) {
            abort(403, 'Unauthorized action.');
        }

        $article = Article::findOrFail($id);

        DB::beginTransaction();
        try {
            // Delete associated image if exists
            if ($article->image) {
                Storage::disk('public')->delete($article->image);
            }

            $article->delete();

            DB::commit();

            return response()->json([
                'message' => 'Article deleted successfully!',
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Something went wrong while deleting the article.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function Articles()
    {

        $articles = Article::orderBy('created_at', 'desc')->paginate(5);
        return response()->json([
            'code' => 200,
            'articles' => $articles,
        ]);
    }
    // public function restore($id): JsonResponse
    // {
    //     if (!Gate::allows('manage-article')) {
    //         return response()->json(['error' => 'Unauthorized action.'], 403);
    //     }

    //     DB::beginTransaction();
    //     try {
    //         // Find soft-deleted article
    //         $article = Article::onlyTrashed()->findOrFail($id);

    //         // Restore the article
    //         $article->restore();

    //         DB::commit();

    //         return response()->json([
    //             'message' => 'Article restored successfully!',
    //             'article' => $article,
    //         ], 200);
    //     } catch (Exception $e) {
    //         DB::rollBack();

    //         return response()->json([
    //             'error' => 'Something went wrong while restoring the article.',
    //             'details' => $e->getMessage(),
    //         ], 500);
    //     }
    // }
    // public function trashedArticle()
    // {
    //     return "ssss";
    //     if (!Gate::allows('manage-article')) {
    //         abort(403, 'Unauthorized action.');
    //     }

    //     $articles = Article::onlyTrashed()->get();

    //     if ($articles->isEmpty()) {
    //         return response()->json([
    //             'message' => 'No trashed articles found.',
    //             'articles' => [],
    //         ], 200);
    //     }

    //     return response()->json([
    //         'message' => 'Trashed articles retrieved successfully.',
    //         'articles' => $articles,
    //     ], 200);
    // }
    // public function forceDelete($id)
    // {
    //     if (!Gate::allows('manage-article')) {
    //         abort(403, 'Unauthorized action.');
    //     }
    //     try {
    //         // Retrieve the article, including soft-deleted ones
    //         $article = Article::withTrashed()->find($id);

    //         // If article is not found
    //         if (!$article) {
    //             return response()->json([
    //                 'message' => 'Article not found',
    //             ], 404);
    //         }

    //         DB::statement('SET FOREIGN_KEY_CHECKS=0;');
    //         $article->forceDelete();
    //         DB::statement('SET FOREIGN_KEY_CHECKS=1;');

    //         return response()->json([
    //             'message' => 'Article permanently deleted successfully',
    //         ], 200);
    //     } catch (\Exception $e) {
    //         // In case of any errors
    //         return response()->json([
    //             'message' => 'An error occurred while deleting the article.',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }
}
