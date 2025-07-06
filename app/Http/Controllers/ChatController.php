<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Gemini\Laravel\Facades\Gemini;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ChatController extends Controller
{
    /**
     * Muestra la interfaz de chat para un juego específico.
     */
    public function show(Request $request, Game $game)
    {
        // Busca o crea una conversación para el usuario y el juego actual
        $conversation = $game->conversations()->firstOrCreate([
            'user_id' => $request->user()->id,
        ]);

        // Carga los mensajes de la conversación
        $conversation->load('messages');

        return Inertia::render('Games/Chat', [
            'game' => $game,
            'conversation' => $conversation,
        ]);
    }

    /**
     * Recibe una pregunta, la envía a Gemini y devuelve la respuesta.
     */
    public function ask(Request $request, Game $game)
    {
        $validated = $request->validate([
            'prompt' => 'required|string|max:500',
        ]);

        // Guardamos la pregunta del usuario en la base de datos
        $conversation = $game->conversations()->firstWhere('user_id', $request->user()->id);
        $conversation->messages()->create([
            'role' => 'user',
            'content' => $validated['prompt'],
        ]);

        // Buscamos el manual del juego que ya está procesado
        $rulebook = $game->rulebooks()->where('status', 'ready')->first();

        if (!$rulebook || empty($rulebook->full_text)) {
            throw ValidationException::withMessages([
                'prompt' => 'El manual de este juego no está disponible o no ha sido procesado.',
            ]);
        }

        // Construimos el prompt para Gemini
        $systemPrompt = "Eres un experto mundial en reglas de juegos de mesa llamado Rulebook AI. Tu única fuente de verdad es el siguiente texto del manual del juego '{$game->name}'. Responde a la pregunta del usuario basándote exclusivamente en este texto. Si la respuesta no se encuentra en el texto, responde amablemente que no tienes esa información en el manual. Sé conciso y claro. El manual es:\n\n{$rulebook->full_text}";

        // Hacemos la llamada a la API de Gemini
        $result = Gemini::geminiPro()->generateContent([
            ['role' => 'user', 'parts' => [['text' => $systemPrompt]]],
            ['role' => 'model', 'parts' => [['text' => 'Entendido. Estoy listo para responder preguntas sobre el manual de ' . $game->name]]],
            ['role' => 'user', 'parts' => [['text' => $validated['prompt']]]],
        ]);

        $aiResponse = $result->text();

        // Guardamos la respuesta de la IA
        $message = $conversation->messages()->create([
            'role' => 'ai',
            'content' => $aiResponse,
        ]);

        // Devolvemos la respuesta para que el frontend la muestre
        return response()->json($message);
    }
}

