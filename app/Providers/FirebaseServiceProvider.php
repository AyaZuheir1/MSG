<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Kreait\Firebase\Factory;

class FirebaseServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('firebase', function ($app) {
            $serviceAccountPath = storage_path('app/medsg-85fd8-firebase-adminsdk-6dvwn-789bbc02c8.json');

            if (!file_exists($serviceAccountPath)) {
                throw new \Exception('Firebase Admin SDK JSON file not found: ' . $serviceAccountPath);
            }

            return (new Factory)
                ->withServiceAccount($serviceAccountPath)
        ->withDatabaseUri(config('firebase.database_url'));
    });

        $this->app->singleton('firebase.messaging', function ($app) {
            $firebase = app('firebase');
            return $firebase->createMessaging();
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
