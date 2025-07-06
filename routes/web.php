<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/upload', function () {
    return Inertia::render('admin/upload'); // Renderiza nuestro nuevo componente
})->middleware(['auth', 'verified'])->name('upload');

Route::middleware('auth')->group(function () {
    // ... (rutas de perfil, etc.)
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
