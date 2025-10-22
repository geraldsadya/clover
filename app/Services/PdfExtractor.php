<?php

/**
 * PdfExtractor Service
 * 
 * Handles PDF text extraction using Smalot/PdfParser (pure PHP solution).
 * This service provides robust PDF processing without external dependencies.
 * 
 * Key Features:
 * - Pure PHP implementation (no external binaries required)
 * - Comprehensive file validation (MIME type, magic bytes, size limits)
 * - UTF-8 encoding normalization and text cleaning
 * - Intelligent text truncation (preserves important sections)
 * - Graceful error handling with specific exception types
 * 
 * File Validation:
 * - Maximum file size: 10MB
 * - MIME type validation: application/pdf
 * - Magic byte validation: %PDF header check
 * - Text extraction validation: ensures readable content
 * 
 * Text Processing:
 * - UTF-8 encoding normalization
 * - Whitespace normalization
 * - Smart truncation (first 80% + last 20% for large files)
 * - Maximum text length: 15,000 characters
 * 
 * Error Handling:
 * - PdfExtractionException for specific error scenarios
 * - Detailed error messages for debugging
 * - Automatic cleanup of temporary files
 * 
 * @author Gerald Sadya
 * @version 1.1.0
 * @since 2025-01-21
 */

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser;

class PdfExtractor
{
    /**
     * Extract text from PDF using Smalot/PdfParser (pure PHP)
     */
    public function extract(UploadedFile $file): string
    {
        try {
            // Use the file's temporary path directly
            $fullPath = $file->getPathname();
            
            // Ensure the file exists
            if (!file_exists($fullPath)) {
                throw new PdfExtractionException('Temporary file was not created properly.');
            }
            
            // Parse PDF using Smalot/PdfParser
            $parser = new Parser();
            $pdf = $parser->parseFile($fullPath);
            $text = $pdf->getText();
            
            // Clean and fix UTF-8 encoding
            $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
            $text = filter_var($text, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);
            
            if (empty(trim($text))) {
                throw new PdfExtractionException('Could not extract text from PDF. The PDF might be scanned or corrupted.');
            }
            
            // Normalize whitespace and truncate if too long
            $text = preg_replace('/\s+/', ' ', $text);
            $text = trim($text);
            
            if (strlen($text) > 15000) {
                // Keep first 80% and last 20% to preserve important info
                $firstPart = substr($text, 0, 12000);
                $lastPart = substr($text, -3000);
                $text = $firstPart . ' ... ' . $lastPart;
            }
            
            return $text;
            
        } catch (\Exception $e) {
            // Clean up temp file if it exists
            if (isset($fullPath) && file_exists($fullPath)) {
                unlink($fullPath);
            }
            
            throw new PdfExtractionException('PDF extraction failed: ' . $e->getMessage());
        }
    }

    /**
     * Validate PDF file
     */
    public function validatePdf(UploadedFile $file): void
    {
        // Check file size (10MB max)
        if ($file->getSize() > 10 * 1024 * 1024) {
            throw new PdfExtractionException('CV file must not be larger than 10MB.');
        }

        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if (!$finfo) {
            throw new PdfExtractionException('Could not determine file type.');
        }

        $mimeType = finfo_file($finfo, $file->getPathname());
        finfo_close($finfo);

        if ($mimeType !== 'application/pdf') {
            throw new PdfExtractionException('File must be a valid PDF.');
        }

        // Check magic bytes
        $handle = fopen($file->getPathname(), 'rb');
        if (!$handle) {
            throw new PdfExtractionException('Could not read PDF file.');
        }

        $header = fread($handle, 4);
        fclose($handle);

        if ($header !== '%PDF') {
            throw new PdfExtractionException('File must be a valid PDF.');
        }
    }
}