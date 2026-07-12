<?php

use App\Http\Controllers\Bot\AiChatBotController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Bot\UnRegisteredAiChatBotController;
use App\Http\Controllers\Bot\FlutterwaveController;

Route::middleware(['auth:sanctum', 'throttle:6,1'])->group(function(){
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::apiResource('bot/chat', AiChatBotController::class);
    Route::get(
    'bot/payment/callback',
    [FlutterwaveController::class,'callback']
    );
    Route::post('bot/payment', [FlutterwaveController::class, 'checkout']);

    Route::get('/payment/callback', [FlutterwaveController::class, 'callback']);
}); 

Route::apiResource('botunregistered-chat/chat', UnRegisteredAiChatBotController::class);


require __DIR__.'/auth.php';