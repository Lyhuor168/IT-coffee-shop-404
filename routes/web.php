<?php

use Illuminate\Support\Facades\Route;
use App\Services\TelegramService;

Route::get('/test-telegram', function () {
    $sent = app(TelegramService::class)->sendMessage("🔔 Test from Laravel!");
    return $sent ? 'Sent!' : 'Failed!';
});

// Serve React app for all other routes
Route::get('/{any}', function () {
    return file_get_contents(public_path('index.html'));
})->where('any', '.*');
