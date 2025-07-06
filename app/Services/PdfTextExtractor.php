<?php

namespace App\Services;

use Spatie\PdfToText\Pdf;
use Illuminate\Support\Facades\Storage;
use Exception;

class PdfTextExtractor
{
    /**
     * Extract text from a PDF file.
     *
     * @param string $filePath Path to the PDF file
     * @return string The extracted text
     * @throws Exception If the file doesn't exist or extraction fails
     */
    public function extractTextFromFile(string $filePath): string
    {
        if (!file_exists($filePath)) {
            throw new Exception("PDF file not found: {$filePath}");
        }

        try {
            return Pdf::getText($filePath);
        } catch (Exception $e) {
            throw new Exception("Failed to extract text from PDF: " . $e->getMessage());
        }
    }

    /**
     * Extract text from a PDF stored in Laravel storage.
     *
     * @param string $storagePath Path in Laravel storage
     * @param string $disk Storage disk (default: 'local')
     * @return string The extracted text
     * @throws Exception If the file doesn't exist or extraction fails
     */
    public function extractTextFromStorage(string $storagePath, string $disk = 'local'): string
    {
        if (!Storage::disk($disk)->exists($storagePath)) {
            throw new Exception("PDF file not found in storage: {$storagePath}");
        }

        $fullPath = Storage::disk($disk)->path($storagePath);
        return $this->extractTextFromFile($fullPath);
    }

    /**
     * Extract text from an uploaded file.
     *
     * @param \Illuminate\Http\UploadedFile $uploadedFile
     * @return string The extracted text
     * @throws Exception If extraction fails
     */
    public function extractTextFromUploadedFile($uploadedFile): string
    {
        if (!$uploadedFile->isValid()) {
            throw new Exception("Invalid uploaded file");
        }

        $tempPath = $uploadedFile->getPathname();
        return $this->extractTextFromFile($tempPath);
    }

    /**
     * Clean and format extracted text.
     *
     * @param string $text Raw extracted text
     * @return string Cleaned text
     */
    public function cleanText(string $text): string
    {
        // Remove excessive whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        
        // Remove leading and trailing whitespace
        $text = trim($text);
        
        // Convert multiple line breaks to double line breaks
        $text = preg_replace('/\n\s*\n\s*\n/', "\n\n", $text);
        
        return $text;
    }

    /**
     * Extract and clean text from PDF in one step.
     *
     * @param string $filePath Path to the PDF file
     * @return string Clean extracted text
     * @throws Exception If extraction fails
     */
    public function extractAndCleanText(string $filePath): string
    {
        $rawText = $this->extractTextFromFile($filePath);
        return $this->cleanText($rawText);
    }
}
