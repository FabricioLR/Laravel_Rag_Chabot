<?php

use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\IngestionController;
use Illuminate\Support\Facades\Route;

Route::get("/ingestion/indexedPosts", [IngestionController::class, "indexedPosts"]);

Route::post('/chat', [ChatController::class, "chat"]);
Route::get('/chat/categories', [ChatController::class, 'categories']);
Route::get('/chat/history/{sessionId}', [ChatController::class, 'history']);
Route::post('/chat/feedback', [ChatController::class, "feedback"]);
