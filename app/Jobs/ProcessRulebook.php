<?php

namespace App\Jobs;

use App\Models\Rulebook;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\PdfToText\Pdf;

class ProcessRulebook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Rulebook $rulebook
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // 1. Asegurarnos de que el archivo PDF existe antes de intentar procesarlo.
        if (!Storage::disk('public')->exists($this->rulebook->file_path)) {
            $this->rulebook->update(['status' => 'failed']);
            Log::error("PDF not found for rulebook ID: {$this->rulebook->id}");
            return;
        }

        try {
            // 2. Usar el paquete para extraer el texto del PDF.
            // Le pasamos la ruta completa al archivo guardado.
            $text = (new Pdf())
                ->setPdf(Storage::disk('public')->path($this->rulebook->file_path))
                ->text();

            // 3. Actualizar el registro en la base de datos con el texto extraído
            // y cambiar el estado a 'ready'.
            $this->rulebook->update([
                'full_text' => $text,
                'status' => 'ready',
            ]);

        } catch (\Exception $e) {
            // 4. Si algo falla durante la extracción, lo registramos y marcamos como 'failed'.
            $this->rulebook->update(['status' => 'failed']);
            Log::error("Failed to process PDF for rulebook ID: {$this->rulebook->id} - {$e->getMessage()}");
        }
    }
}
