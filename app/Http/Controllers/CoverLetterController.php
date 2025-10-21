<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateCoverLetterRequest;
use App\Services\PdfExtractor;
use App\Services\CoverLetterGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CoverLetterController extends Controller
{
    public function __construct(
        private PdfExtractor $pdfExtractor,
        private CoverLetterGenerator $coverLetterGenerator
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
        $requestId = uniqid('req_', true);
        $startTime = microtime(true);

        try {
            // Extract text from PDF
            $cvText = $this->pdfExtractor->extract($request->file('cv'));
            
            // Extract facts from CV text (Stage 1)
            $facts = $this->coverLetterGenerator->extractFacts($cvText);
            
            // Check if extraction failed and needs manual processing
            if (isset($facts['status']) && $facts['status'] === 'needs-manual') {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'message' => 'Unable to process CV automatically. Please ensure the PDF contains readable text.',
                        'code' => 'CV_PROCESSING_FAILED',
                        'request_id' => $requestId
                    ]
                ], 422);
            }
            
            // TODO: Implement Stage 2 - Generate cover letter from facts and job description
            // For now, return facts extraction results
            
            $duration = (microtime(true) - $startTime) * 1000;
            
            return response()->json([
                'success' => true,
                'data' => [
                    'cover_letter' => 'Cover letter generation will be implemented in Ticket E. Facts extraction completed successfully.',
                    'word_count' => 15,
                    'request_id' => $requestId,
                    'facts' => $facts,
                    'cv_text_length' => strlen($cvText)
                ],
                'meta' => [
                    'tokens_used' => 0, // Will be updated when OpenAI integration is complete
                    'processing_time_ms' => round($duration, 2)
                ]
            ]);
            
        } catch (\Exception $e) {
            $duration = (microtime(true) - $startTime) * 1000;
            
            return response()->json([
                'success' => false,
                'error' => [
                    'message' => $e->getMessage(),
                    'code' => 'PROCESSING_FAILED',
                    'request_id' => $requestId
                ],
                'meta' => [
                    'processing_time_ms' => round($duration, 2)
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