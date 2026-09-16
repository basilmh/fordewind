<?php

return [
    'cars' => [
        'repository_url' => env('CARS_IMPORT_REPOSITORY', 'https://gitlab.fdw.ru/ext/test_task.git'),
        'source_path' => env('CARS_IMPORT_SOURCE_PATH', base_path('data/test_task')),
        'batch_size' => (int) env('CARS_IMPORT_BATCH_SIZE', 200),
        'duplicate_cache_ttl' => (int) env('CARS_IMPORT_DUPLICATE_CACHE_TTL', 86400),
        'images' => [
            'source_path' => env('CARS_IMPORT_IMAGES_PATH'),
            'public_path' => env('CARS_PUBLIC_IMAGES_PATH', public_path('images/cars')),
        ],
    ],
];
