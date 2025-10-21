<?php

namespace App\Services;

use App\Exceptions\PdfExtractionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\PdfToText\Pdf;

class PdfExtractor
{
    private const MAX_CHARS = 15000;

    private const MIN_CHARS = 100;

    /**
     * Extract text from PDF file
     *
     * @throws PdfExtractionException
     */
    public function extract(UploadedFile $file): string
    {
        // Validate file first
        $this->validateFile($file);

        // Store file temporarily
        $tempPath = $this->storeTempFile($file);

        try {
            // Extract text using spatie/pdf-to-text
            $text = Pdf::getText($tempPath);

            // Clean up temp file immediately
            $this->deleteTempFile($tempPath);

            // Validate extracted text
            $this->validateExtractedText($text);

            // Clean and normalize text
            $cleanedText = $this->clean($text);

            // Truncate if too long
            return $this->truncate($cleanedText);

        } catch (\Exception $e) {
            // Clean up temp file on error
            $this->deleteTempFile($tempPath);

            // Determine specific error type
            throw $this->determineErrorType($e);
        }
    }

    /**
     * Validate file MIME type and magic bytes
     */
    private function validateFile(UploadedFile $file): void
    {
        // MIME type validation
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            throw PdfExtractionException::invalidPdf();
        }

        $mimeType = finfo_file($finfo, $file->getPathname());
        if ($mimeType !== 'application/pdf') {
            throw PdfExtractionException::invalidPdf();
        }

        // Magic-byte validation
        $handle = fopen($file->getPathname(), 'rb');
        if ($handle === false) {
            throw PdfExtractionException::invalidPdf();
        }

        $header = fread($handle, 5);
        fclose($handle);

        if ($header !== '%PDF-') {
            throw PdfExtractionException::invalidPdf();
        }
    }

    /**
     * Store file temporarily in storage/app/temp/
     */
    private function storeTempFile(UploadedFile $file): string
    {
        $filename = uniqid('pdf_', true).'.pdf';
        $path = 'temp/'.$filename;

        $content = file_get_contents($file->getPathname());
        if ($content === false) {
            throw PdfExtractionException::invalidPdf();
        }

        Storage::disk('local')->put($path, $content);

        return Storage::disk('local')->path($path);
    }

    /**
     * Delete temporary file
     */
    private function deleteTempFile(string $path): void
    {
        if (file_exists($path)) {
            unlink($path);
        }
    }

    /**
     * Validate extracted text meets minimum requirements
     */
    private function validateExtractedText(string $text): void
    {
        $textLength = strlen(trim($text));

        if ($textLength < self::MIN_CHARS) {
            // Determine if it's likely a scanned PDF or empty
            if ($textLength < 10) {
                throw PdfExtractionException::emptyPdf();
            } else {
                throw PdfExtractionException::scannedPdf();
            }
        }
    }

    /**
     * Clean and normalize extracted text
     */
    private function clean(string $text): string
    {
        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;

        // Remove control characters except newlines
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text) ?? $text;

        // Trim whitespace
        return trim($text);
    }

    /**
     * Truncate CV text to ~15,000 chars (token management)
     * Keep first 80%, last 20%
     */
    private function truncate(string $text, int $maxChars = self::MAX_CHARS): string
    {
        if (strlen($text) <= $maxChars) {
            return $text;
        }

        $firstPartLength = intval($maxChars * 0.8);
        $lastPartLength = intval($maxChars * 0.2);

        $firstPart = substr($text, 0, $firstPartLength);
        $lastPart = substr($text, -$lastPartLength);

        return $firstPart."\n\n[... content truncated ...]\n\n".$lastPart;
    }

    /**
     * Determine specific error type from exception
     */
    private function determineErrorType(\Exception $e): PdfExtractionException
    {
        $message = strtolower($e->getMessage());

        if (strpos($message, 'password') !== false || strpos($message, 'encrypted') !== false) {
            return PdfExtractionException::encryptedPdf();
        }

        if (strpos($message, 'corrupt') !== false || strpos($message, 'damaged') !== false) {
            return PdfExtractionException::corruptPdf();
        }

        if (strpos($message, 'empty') !== false || strpos($message, 'no text') !== false) {
            return PdfExtractionException::emptyPdf();
        }

        // Default to extraction failed
        return PdfExtractionException::extractionFailed($e->getMessage());
    }
}
