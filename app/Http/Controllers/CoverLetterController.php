<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateCoverLetterRequest;
use App\Services\CoverLetterGenerator;
use App\Services\PdfExtractor;
use Illuminate\Http\JsonResponse;

class CoverLetterController extends Controller
{
    public function __construct(
        private PdfExtractor $pdfExtractor,
        private CoverLetterGenerator $coverLetterGenerator
    ) {}

    /**
     * Display the cover letter generation form
     */
    public function index(): \Illuminate\Contracts\View\View
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
            $cvFile = $request->file('cv');
            if ($cvFile === null || is_array($cvFile)) {
                throw new \Exception('CV file is required');
            }
            $cvText = $this->pdfExtractor->extract($cvFile);

            // Extract facts from CV text (Stage 1)
            $facts = $this->coverLetterGenerator->extractFacts($cvText);

            // Check if extraction failed and needs manual processing
            if (isset($facts['status']) && $facts['status'] === 'needs-manual') {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'message' => 'Unable to process CV automatically. Please ensure the PDF contains readable text.',
                        'code' => 'CV_PROCESSING_FAILED',
                        'request_id' => $requestId,
                    ],
                ], 422);
            }

            // Generate cover letter from facts and job description (Stage 2)
            $coverLetter = $this->coverLetterGenerator->generateCoverLetter($facts, $request->input('job_description'));

            $duration = (microtime(true) - $startTime) * 1000;

            return response()->json([
                'success' => true,
                'data' => [
                    'cover_letter' => $coverLetter,
                    'word_count' => str_word_count($coverLetter),
                    'request_id' => $requestId,
                    'facts' => $facts,
                    'cv_text_length' => strlen($cvText),
                ],
                'meta' => [
                    'tokens_used' => 0, // Will be updated when OpenAI integration is complete
                    'processing_time_ms' => round($duration, 2),
                ],
            ]);

        } catch (\Exception $e) {
            $duration = (microtime(true) - $startTime) * 1000;

            return response()->json([
                'success' => false,
                'error' => [
                    'message' => $e->getMessage(),
                    'code' => 'PROCESSING_FAILED',
                    'request_id' => $requestId,
                ],
                'meta' => [
                    'processing_time_ms' => round($duration, 2),
                ],
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
                'pdftotext' => $this->checkPdftotext(),
            ],
            'timestamp' => now()->toIso8601String(),
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
