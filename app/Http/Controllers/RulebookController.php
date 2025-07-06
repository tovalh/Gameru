<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessRulebook;
use App\Models\Rulebook;
use App\Models\Game;
use App\Services\PdfTextExtractor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Exception;

class RulebookController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // 1. Validar los datos que vienen del formulario.
        $validated = $request->validate([
            'game_name' => 'required|string|max:255',
            'rulebook_pdf' => 'required|file|mimes:pdf|max:10240', // PDF de máximo 10MB
        ]);

        // 2. Crear el juego en la base de datos.
        $game = Game::firstOrCreate(
            ['name' => $validated['game_name']],
        );

        // 3. Guardar el archivo PDF.
        $path = $request->file('rulebook_pdf')->store('rulebooks', 'public');

        // 4. Crear el registro del manual en la base de datos.
        $rulebook = $game->rulebooks()->create([
            'language' => 'es',
            'status' => 'processing',
            'file_path' => $path,
        ]);

        // 5. ¡NUEVO PASO! Despachar el Job para procesar el PDF en segundo plano.
        ProcessRulebook::dispatch($rulebook);

        // 6. Redirigir de vuelta a la página de subida con un mensaje de éxito.
        return Redirect::route('upload')->with('success', '¡Manual subido con éxito! Se está procesando.');
    }
    /**
     * Display the specified resource.
     */


    /**
     * Update the specified resource in storage.
     */
    /**
     * Remove the specified resource from storage.
     */

    /**
     * Process a specific rulebook PDF to extract text.
     */
    public function processRulebook(Rulebook $rulebook)
    {
        if (!$rulebook->file_path) {
            return response()->json(['error' => 'No PDF file associated with this rulebook'], 400);
        }

        try {
            $pdfExtractor = new PdfTextExtractor();
            $extractedText = $pdfExtractor->extractTextFromStorage($rulebook->file_path, 'public');
            $cleanText = $pdfExtractor->cleanText($extractedText);

            $rulebook->update([
                'full_text' => $cleanText,
                'status' => 'ready'
            ]);

            return response()->json([
                'message' => 'PDF processed successfully',
                'rulebook' => $rulebook->fresh()
            ]);

        } catch (Exception $e) {
            $rulebook->update(['status' => 'failed']);
            \Log::error('PDF processing failed for rulebook ' . $rulebook->id . ': ' . $e->getMessage());

            return response()->json([
                'error' => 'Failed to process PDF: ' . $e->getMessage()
            ], 500);
        }
    }
}
