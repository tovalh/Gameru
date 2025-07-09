<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Gemini\Laravel\Facades\Gemini;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Throwable;

class ChatController extends Controller
{
    /**
     * Muestra la interfaz de chat para un juego específico.
     */
    public function show(Request $request, Game $game)
    {
        $conversation = $game->conversations()->firstOrCreate([
            'user_id' => $request->user()->id,
        ]);

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
            'prompt' => 'required|string|max:1000',
        ]);

        $conversation = $game->conversations()->firstWhere('user_id', $request->user()->id);
        $rulebook = $game->rulebooks()->where('status', 'ready')->first();

        if (!$rulebook || empty($rulebook->full_text)) {
            throw ValidationException::withMessages([
                'prompt' => 'El manual de este juego no está disponible o no ha sido procesado.',
            ]);
        }

        try {
            // 1. Iniciar o continuar el chat
            $chat = Gemini::chat(model: 'gemini-2.0-flash')->startChat();

            // 2. Configurar el contexto del sistema
            $systemContext = "Eres un experto mundial en reglas de juegos de mesa llamado Rulebook AI. Tu única fuente de verdad es el siguiente texto del manual del juego '{$game->name}'. Responde a la pregunta del usuario basándote exclusivamente en este texto. Si la respuesta no se encuentra en el texto, responde amablemente que no tienes esa información en el manual. Sé conciso y claro.\n\nMANUAL DEL JUEGO:\n{$rulebook->full_text}";

            // 3. Enviar contexto del sistema
            $chat->sendMessage($systemContext);

            // 4. Reproducir el historial de la conversación
            $this->replayConversationHistory($chat, $conversation);

            // 5. Enviar la pregunta actual
            $response = $chat->sendMessage($validated['prompt']);
            $aiResponse = $response->text();

            // 6. Guardar la pregunta y la respuesta en la base de datos
            $conversation->messages()->create([
                'role' => 'user',
                'content' => $validated['prompt'],
            ]);

            $aiMessage = $conversation->messages()->create([
                'role' => 'ai',
                'content' => $aiResponse,
            ]);

            // 7. Devolver la respuesta de la IA al frontend
            return response()->json($aiMessage);

        } catch (Throwable $e) {
            Log::error("GEMINI_API_ERROR: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'game_id' => $game->id,
                'user_id' => $request->user()->id,
                'prompt' => $validated['prompt'],
            ]);

            return response()->json([
                'error' => 'Hubo un problema al comunicarse con la IA. El equipo técnico ha sido notificado.'
            ], 500);
        }
    }

    /**
     * Reproduce el historial de la conversación en el chat.
     */
    private function replayConversationHistory($chat, $conversation): void
    {
        $previousMessages = $conversation->messages()->orderBy('created_at')->get();

        foreach ($previousMessages as $message) {
            if ($message->role === 'user') {
                $chat->sendMessage($message->content);
            }
            // Las respuestas de la IA se generan automáticamente, no necesitamos reproducirlas
        }
    }

    /**
     * Versión alternativa que mantiene el contexto en una sola llamada.
     */
    public function askSimple(Request $request, Game $game)
    {
        $validated = $request->validate([
            'prompt' => 'required|string|max:1000',
        ]);

        $conversation = $game->conversations()->firstWhere('user_id', $request->user()->id);
        $rulebook = $game->rulebooks()->where('status', 'ready')->first();

        if (!$rulebook || empty($rulebook->full_text)) {
            throw ValidationException::withMessages([
                'prompt' => 'El manual de este juego no está disponible o no ha sido procesado.',
            ]);
        }

        try {
            // 1. Construir el prompt completo con todo el contexto
            $fullPrompt = $this->buildFullPrompt($conversation, $game, $rulebook, $validated['prompt']);

            // 2. Hacer la consulta a Gemini
            $result = Gemini::generativeModel(model: 'gemini-2.0-flash')->generateContent($fullPrompt);
            $aiResponse = $result->text();

            // 3. Guardar la pregunta y la respuesta
            $conversation->messages()->create([
                'role' => 'user',
                'content' => $validated['prompt'],
            ]);

            $aiMessage = $conversation->messages()->create([
                'role' => 'ai',
                'content' => $aiResponse,
            ]);

            return response()->json($aiMessage);

        } catch (Throwable $e) {
            Log::error("GEMINI_API_ERROR: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'game_id' => $game->id,
                'user_id' => $request->user()->id,
                'prompt' => $validated['prompt'],
            ]);

            return response()->json([
                'error' => 'Hubo un problema al comunicarse con la IA. El equipo técnico ha sido notificado.'
            ], 500);
        }
    }

    /**
     * Construye el prompt completo con contexto e historial.
     */
    private function buildFullPrompt($conversation, $game, $rulebook, $currentPrompt): string
    {
        // Contexto del sistema
        $systemContext = "Eres un experto mundial en reglas de juegos de mesa llamado Rulebook AI. Tu única fuente de verdad es el siguiente texto del manual del juego '{$game->name}'. Responde a la pregunta del usuario basándote exclusivamente en este texto. Si la respuesta no se encuentra en el texto, responde amablemente que no tienes esa información en el manual. Sé conciso y claro.\n\nMANUAL DEL JUEGO:\n{$rulebook->full_text}\n\n";

        // Historial de la conversación
        $history = "HISTORIAL DE LA CONVERSACIÓN:\n";
        $previousMessages = $conversation->messages()->orderBy('created_at')->get();

        foreach ($previousMessages as $message) {
            $role = $message->role === 'user' ? 'USUARIO' : 'ASISTENTE';
            $history .= "{$role}: {$message->content}\n";
        }

        // Pregunta actual
        $history .= "USUARIO: {$currentPrompt}\nASISTENTE: ";

        return $systemContext . $history;
    }
}
