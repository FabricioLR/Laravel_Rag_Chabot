<?php

use App\Http\Controllers\Api\ChatController;
use Illuminate\Support\Facades\Route;

Route::post('/chat', [ChatController::class, "chat"]);
Route::get('/chat/categories', [ChatController::class, 'categories']);
Route::get('/chat/history/{sessionId}', [ChatController::class, 'history']);
