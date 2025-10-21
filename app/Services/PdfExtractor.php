<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class PdfExtractor
{
    /**
     * Extract text from PDF using client-side PDF.js
     * This is a fallback when pdftotext is not available
     */
    public function extract(UploadedFile $file): string
    {
        // For now, return a placeholder that indicates PDF.js should be used
        // The actual extraction will happen on the client side
        return "PDF_EXTRACTION_REQUIRED";
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