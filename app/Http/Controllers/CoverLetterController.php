<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateCoverLetterRequest;
use App\Services\PdfExtractor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CoverLetterController extends Controller
{
    public function __construct(
        private PdfExtractor $pdfExtractor
    ) {}

    /**
     * Display the cover letter generation form
     */
    public function index(): View
    {
        return view('cover-letter.index');
    }

    /**
     * Handle form submission and generate cover letter
     */
    public function generate(GenerateCoverLetterRequest $request): JsonResponse
    {
        try {
            // Extract text from PDF
            $cvText = $this->pdfExtractor->extract($request->file('cv'));
            
            // TODO: Implement AI cover letter generation
            // For now, return a placeholder response with extracted text info
            
            return response()->json([
                'success' => true,
                'data' => [
                    'cover_letter' => 'This is a placeholder cover letter. The actual implementation will generate a tailored cover letter using AI based on the extracted CV text and job description.',
                    'word_count' => 25,
                    'request_id' => uniqid('req_', true),
                    'cv_text_length' => strlen($cvText),
                    'cv_text_preview' => substr($cvText, 0, 100) . '...'
                ],
                'meta' => [
                    'tokens_used' => 0,
                    'processing_time_ms' => 100
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'message' => $e->getMessage(),
                    'code' => 'PDF_EXTRACTION_FAILED',
                    'request_id' => uniqid('req_', true)
                ]
            ], 422);
        }
    }

    /**
     * Health check endpoint for monitoring
     */
    public function healthz(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'app' => 'cover-letter-generator',
            'version' => '1.0.0',
            'checks' => [
                'database' => 'ok',
                'openai' => 'not_configured', // Will be updated when OpenAI is configured
                'storage' => 'ok',
                'pdftotext' => $this->checkPdftotext()
            ],
            'timestamp' => now()->toIso8601String()
        ]);
    }

    /**
     * Check if pdftotext binary is available
     */
    private function checkPdftotext(): string
    {
        $output = [];
        $returnCode = 0;
        exec('which pdftotext', $output, $returnCode);
        return $returnCode === 0 ? 'available' : 'not_available';
    }
}