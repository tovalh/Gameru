<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Inertia\Inertia;
use Inertia\Response;

class GameController extends Controller
{
    /**
     * Display a listing of the games.
     */
    public function index(): Response
    {
        $games = Game::whereHas('rulebooks', function ($query) {
            $query->where('status', 'ready');
        })->latest()->get();

        return Inertia::render('Games/Index', [
            'games' => $games,
        ]);
    }
}

