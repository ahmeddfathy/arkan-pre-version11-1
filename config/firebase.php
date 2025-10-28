<?php

return [
    'credentials' => [
        'file' => storage_path('firebase-key.json'),
    ],
    'project_id' => 'track-employee-2d4fa',
    'database_url' => 'https://track-employee-2d4fa.firebaseio.com',
    'storage_bucket' => 'track-employee-2d4fa.appspot.com',
    'dynamic_links_domain' => null,
    'ssl_certificate' => env('FIREBASE_SSL_CERTIFICATE', storage_path('app/firebase/certs/cacert.pem')),
]; 