<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\BookmarkController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Autenticación
Route::post('/login', [UserController::class, 'login']);
Route::post('/register', [UserController::class, 'create']); // Si tienes registro

// Rutas protegidas (requieren token)
Route::middleware('auth:sanctum')->group(function () {
    // Usuarios
    Route::get('/user', [UserController::class, 'show']); // Perfil actual
    Route::put('/user', [UserController::class, 'update']);
    Route::post('/logout', [UserController::class, 'logout']);
    // Materiales
    Route::apiResource('materials', MaterialController::class);
    //Bookmarks
    Route::post('materials/{material}/bookmark', [BookmarkController::class, 'store']);
    Route::delete('materials/{material}/bookmark', [BookmarkController::class, 'destroy']);
    Route::get('users/me/bookmarks', [BookmarkController::class, 'index']); // Listar favoritos
});
