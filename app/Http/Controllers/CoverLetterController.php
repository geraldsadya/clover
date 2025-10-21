<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateCoverLetterRequest;
use App\Services\CoverLetterGenerator;
use App\Services\OpenAIClient;
use App\Services\PdfExtractor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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
        $requestIdHeader = $request->header('X-Request-ID');
        $requestId = is_string($requestIdHeader) ? $requestIdHeader : 'unknown';
        $startTime = microtime(true);

        // Log request start (NO PII)
        Log::info('Cover letter generation started', [
            'request_id' => $requestId,
            'cv_file_size' => is_array($request->file('cv')) ? 0 : ($request->file('cv')?->getSize() ?? 0),
            'job_description_length' => strlen($request->input('job_description', '')),
        ]);

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
                $duration = (microtime(true) - $startTime) * 1000;
                
                Log::warning('CV processing failed - needs manual', [
                    'request_id' => $requestId,
                    'duration_ms' => round($duration, 2),
                    'outcome' => 'cv_processing_failed'
                ]);

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
            $wordCount = str_word_count($coverLetter);

            // Calculate estimated cost (for reference)
            $estimatedCost = $this->calculateEstimatedCost($requestId);

            // Log successful completion (NO PII)
            Log::info('Cover letter generation completed', [
                'request_id' => $requestId,
                'duration_ms' => round($duration, 2),
                'word_count' => $wordCount,
                'cv_text_length' => strlen($cvText),
                'estimated_cost_usd' => $estimatedCost,
                'outcome' => 'success'
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'cover_letter' => $coverLetter,
                    'word_count' => $wordCount,
                    'request_id' => $requestId,
                ],
                'meta' => [
                    'processing_time_ms' => round($duration, 2),
                    'estimated_cost_usd' => $estimatedCost,
                ],
            ]);

        } catch (\Exception $e) {
            $duration = (microtime(true) - $startTime) * 1000;

            // Calculate estimated cost even for failed requests
            $estimatedCost = $this->calculateEstimatedCost($requestId);

            // Log error (NO PII)
            Log::error('Cover letter generation failed', [
                'request_id' => $requestId,
                'duration_ms' => round($duration, 2),
                'error_message' => $e->getMessage(),
                'estimated_cost_usd' => $estimatedCost,
                'outcome' => 'error'
            ]);

            return response()->json([
                'success' => false,
                'error' => [
                    'message' => $e->getMessage(),
                    'code' => 'PROCESSING_FAILED',
                    'request_id' => $requestId,
                ],
                'meta' => [
                    'processing_time_ms' => round($duration, 2),
                    'estimated_cost_usd' => $estimatedCost,
                ],
            ], 422);
        }
    }

    /**
     * Health check endpoint for monitoring
     */
    public function healthz(): JsonResponse
    {
        $checks = [];
        $overallStatus = 'ok';
        
        // Check database connectivity
        try {
            DB::connection()->getPdo();
            $checks['database'] = 'ok';
        } catch (\Exception $e) {
            $checks['database'] = 'error';
            $overallStatus = 'degraded';
        }
        
        // Check OpenAI API connectivity
        try {
            $openaiKey = env('OPENAI_API_KEY');
            $openaiBase = env('OPENAI_BASE');
            
            if (empty($openaiKey) || empty($openaiBase)) {
                $checks['openai'] = 'not_configured';
                $overallStatus = 'degraded';
            } else {
                // Test OpenAI API with a simple request
                $client = new \GuzzleHttp\Client(['timeout' => 5]);
                $response = $client->get($openaiBase . '/models', [
                    'headers' => [
                        'api-key' => $openaiKey,
                    ]
                ]);
                
                if ($response->getStatusCode() === 200) {
                    $checks['openai'] = 'ok';
                } else {
                    $checks['openai'] = 'error';
                    $overallStatus = 'degraded';
                }
            }
        } catch (\Exception $e) {
            $checks['openai'] = 'error';
            $overallStatus = 'degraded';
        }
        
        // Check storage accessibility
        try {
            $testFile = 'health-check-test-' . uniqid();
            Storage::put($testFile, 'test');
            Storage::delete($testFile);
            $checks['storage'] = 'ok';
        } catch (\Exception $e) {
            $checks['storage'] = 'error';
            $overallStatus = 'degraded';
        }
        
        // Check pdftotext binary
        $checks['pdftotext'] = $this->checkPdftotext();
        if ($checks['pdftotext'] === 'not_available') {
            $overallStatus = 'degraded';
        }
        
        return response()->json([
            'status' => $overallStatus,
            'app' => 'cover-letter-generator',
            'version' => '1.0.0',
            'checks' => $checks,
            'timestamp' => now()->toIso8601String(),
            'uptime' => $this->getUptime(),
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

    /**
     * Get application uptime
     */
    private function getUptime(): string
    {
        try {
            $uptime = shell_exec('uptime');
            return trim($uptime ?: 'unknown');
        } catch (\Exception $e) {
            return 'unknown';
        }
    }

    /**
     * Calculate estimated cost based on token usage logs
     */
    private function calculateEstimatedCost(string $requestId): float
    {
        // This is a simplified cost calculation for reference
        // In production, you'd want to track actual token usage per request
        
        // GPT-4o-mini pricing (as of 2024):
        // Input: $0.00015 per 1K tokens
        // Output: $0.0006 per 1K tokens
        
        // Estimate based on typical usage patterns
        $estimatedInputTokens = 2000; // CV + job description
        $estimatedOutputTokens = 500; // Cover letter + facts
        
        $inputCost = ($estimatedInputTokens / 1000) * 0.00015;
        $outputCost = ($estimatedOutputTokens / 1000) * 0.0006;
        
        return round($inputCost + $outputCost, 6);
    }
}
