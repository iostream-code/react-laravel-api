<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\PostController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::apiResource('/post', PostController::class);

// Chatbot (riwayat di Redis/cache; otak via n8n bila dikonfigurasi)
Route::post('/chat', [ChatController::class, 'kirim']);
Route::get('/chat/{sessionId}', [ChatController::class, 'riwayat']);
Route::delete('/chat/{sessionId}', [ChatController::class, 'hapus']);
