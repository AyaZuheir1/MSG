<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging;

class FirebaseServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('firebase.messaging', function ($app) {
            $firebase = (new Factory)
                ->withServiceAccount(config('firebase.credentials'))
                ->withProjectId(config('firebase.project_id','medsg-85fd8'));

            return $firebase->createMessaging();
        });
    }
    // public function register()
    // {
    //     $this->app->singleton('firebase', function ($app) {
    //         $serviceAccountPath = storage_path('app/medsg-85fd8-firebase-adminsdk-6dvwn-789bbc02c8.json');

    //         if (!file_exists($serviceAccountPath)) {
    //             throw new \Exception('Firebase Admin SDK JSON file not found: ' . $serviceAccountPath);
    //         }

    //         return (new Factory)
    //             ->withServiceAccount($serviceAccountPath);
    // });
    // $firebase = (new Factory)
    // ->withServiceAccount(config('firebase.credentials'))->withProjectId(config('firebase.project_id'));
    // return $firebase->createMessaging();
    // ->createMessaging();
        // $this->app->singleton('firebase.messaging', function ($app) {
            // $firebase = app('firebase');
        // });
    // }

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
