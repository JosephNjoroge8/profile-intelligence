<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Profile Intelligence API Routes
|--------------------------------------------------------------------------
| All routes are prefixed with /api (set in bootstrap/app.php)
| Full paths: POST /api/profiles, GET /api/profiles, etc.
*/

Route::prefix('profiles')->group(function () {
    Route::post('/',       [ProfileController::class, 'store']);
    Route::get('/',        [ProfileController::class, 'index']);
    Route::get('/{id}',    [ProfileController::class, 'show']);
    Route::delete('/{id}', [ProfileController::class, 'destroy']);
});
