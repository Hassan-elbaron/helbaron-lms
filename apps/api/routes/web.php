<?php

use Illuminate\Support\Facades\Route;

// Scaffold: the app is API-first. The admin panel (Filament) mounts at /admin
// once installed. No web pages are defined at this stage.
Route::get('/', fn () => response()->json(['service' => 'helbaron-api', 'docs' => '/api/v1/health']));
