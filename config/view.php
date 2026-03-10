<?php

return [

    /*
    |--------------------------------------------------------------------------
    | View Storage Paths
    |--------------------------------------------------------------------------
    |
    | BLB uses a core/licensee directory separation under resources/.
    | The licensee view path (if it exists) is registered first so that
    | licensee component overrides take precedence over core components.
    |
    | The licensee directory name is read from VITE_THEME_DIR in .env
    | (default: 'custom').
    |
    */

    'paths' => array_filter([
        resource_path(env('VITE_THEME_DIR', 'custom').'/views'),
        resource_path('core/views'),
    ], fn (string $path): bool => is_dir($path)),

    /*
    |--------------------------------------------------------------------------
    | Compiled View Path
    |--------------------------------------------------------------------------
    |
    | This option determines where all the compiled Blade templates will be
    | stored for your application. Typically, this is within the storage
    | directory. However, as usual, you are free to change this value.
    |
    */

    'compiled' => env(
        'VIEW_COMPILED_PATH',
        realpath(storage_path('framework/views'))
    ),

];
