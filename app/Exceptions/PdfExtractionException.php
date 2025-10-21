<?php

namespace App\Exceptions;

use Exception;

class PdfExtractionException extends Exception
{
    public static function scannedPdf(): self
    {
        return new self('Could not extract text from PDF. Please use a text-based CV.');
    }

    public static function corruptPdf(): self
    {
        return new self('Could not read PDF. Try a different file.');
    }

    public static function encryptedPdf(): self
    {
        return new self('PDF is password-protected. Please upload an unlocked file.');
    }

    public static function emptyPdf(): self
    {
        return new self('CV appears empty or unreadable.');
    }

    public static function invalidPdf(): self
    {
        return new self('File must be a valid PDF.');
    }

    public static function extractionFailed(string $reason = 'Unknown error'): self
    {
        return new self("PDF extraction failed: {$reason}");
    }
}
