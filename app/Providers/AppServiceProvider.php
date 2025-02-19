<?php

namespace App\Providers;

use App\Models\Article;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->register(\App\Providers\FirebaseServiceProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void

    {
        // Gate::define('publish-article', function (User $user) {
        //     return $user->role === 'admin' ;
        // });
        Gate::define('manage-article', function ($user) {
            return $user == 'admin';
        });
        // Gate::define('delete-article', function (User $user, Article $article) {
        //     return $user->role === 'admin' && $article->admin_id === $user->id;
        // });
    }
}
