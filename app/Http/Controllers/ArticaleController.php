<?php

namespace App\Http\Controllers;

use App\Models\Articale;
use App\Http\Requests\StoreArticaleRequest;
use App\Http\Requests\UpdateArticaleRequest;
use App\Models\Article;
use Illuminate\Support\Facades\Auth;

class ArticaleController extends Controller
{

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

    public function destroy(Articale $articale)
    {
        Article::softDeletes($articale->id);
        return [
            'code' => 200,
            'message' => 'success delete',
        ];
    }
}
