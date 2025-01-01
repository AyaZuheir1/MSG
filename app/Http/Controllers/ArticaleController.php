<?php

namespace App\Http\Controllers;

use App\Models\Articale;
use App\Http\Requests\StoreArticaleRequest;
use App\Http\Requests\UpdateArticaleRequest;
use App\Models\Article;
use Illuminate\Support\Facades\Auth;

class ArticaleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreArticaleRequest $request)
    {
        $request->validated();
        $articale = Article::create([
            'admin_id' => Auth::id(),
            'title' => $request->title,
            'content' => $request->content,
        ]);
        return [
            'code' => 200,
            'message' => 'OK',
            'articale' => $articale,
        ];
    }

    /**
     * Display the specified resource.
     */
    public function show(Articale $articale)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateArticaleRequest $request, Articale $articale)
    {
        $request->validated();
        $articale = Article::create([
            'admin_id' => Auth::id(),
            'title' => $request->title,
            'content' => $request->content,
        ]);
        return [
            'code' => 200,
            'message' => 'OK',
            'articale' => $articale,
        ];
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Articale $articale)
    {
        Article::softDeletes($articale->id);
        return [
            'code' => 200,
            'message' => 'success delete',
        ];
    }
}
