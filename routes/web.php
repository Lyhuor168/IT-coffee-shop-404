<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// SPA fallback: return the main view for any client-side route (e.g. /dashboard)
Route::view('/{any}', 'welcome')->where('any', '.*');
