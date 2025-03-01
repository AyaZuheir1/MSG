<?php 
return [
    'credentials' => env('FIREBASE_CREDENTIALS',storage_path("app/medsg-85fd8-firebase-adminsdk-6dvwn-789bbc02c8.json")),
    'project_id' => env('FIREBASE_PROJECT_ID'),
    'database_url' => env('FIREBASE_DATABASE_URL',"https://medsg-85fd8-default-rtdb.asia-southeast1.firebasedatabase.app"),
//     'database' => [
//     'url' => env('FIREBASE_DATABASE_URL', 'https://medsg-85fd8-default-rtdb.firebaseio.com'),
// ],
];
?>