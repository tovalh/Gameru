<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\GameController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\RulebookController;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    // Página para subir manuales
    Route::get('/upload', function () {
        return Inertia::render('admin/upload');
    })->name('upload');

    Route::post('/rulebooks', [RulebookController::class, 'store'])->name('rulebooks.store');

    // Página para listar los juegos
    Route::get('/games', [GameController::class, 'index'])->name('games.index');

    // Página de chat para un juego
    Route::get('/games/{game}', [ChatController::class, 'show'])->name('chat.show');

    // Endpoint para enviar preguntas al chat
    Route::post('/chat/{game}', [ChatController::class, 'ask'])->name('chat.ask');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
