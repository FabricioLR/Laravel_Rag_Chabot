<?php

use App\Http\Controllers\Api\ChatController;
use Illuminate\Support\Facades\Route;

Route::post('/chat', [ChatController::class, "chat"]);
