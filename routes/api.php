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
    Route::prefix('bot')->group(function(){
        Route::apiResource('chat', AiChatBotController::class);
        Route::get(
        'payment/callback',
        [FlutterwaveController::class,'callback']
        );
        Route::post('payment', [FlutterwaveController::class, 'checkout']);
    });
}); 

Route::prefix('bot/botunregistered-chat')->group(function () {
    Route::apiResource('chat', UnRegisteredAiChatBotController::class);
});



require __DIR__.'/auth.php';