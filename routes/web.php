<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| This application is API-only. All routes are defined in routes/api.php.
| This file is kept minimal for any potential web-based needs.
|
*/

Route::get('/', function () {
    return response()->json([
        'name' => 'Training Event Management API',
        'version' => '1.0.0',
        'documentation' => '/api/v1',
    ]);
});
