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
            $serviceAccountPath = storage_path('app/medsg-85fd8-881fafdc81d6.json');

            if (!file_exists($serviceAccountPath)) {
                throw new \Exception('Firebase Admin SDK JSON file not found: ' . $serviceAccountPath);
            }

            return (new Factory)
                ->withServiceAccount($serviceAccountPath);
                // ->withDatabaseUri('https://your-database-name.firebaseio.com'); // Replace with your database URI if applicable
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
