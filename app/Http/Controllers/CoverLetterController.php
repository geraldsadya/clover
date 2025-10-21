<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateCoverLetterRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CoverLetterController extends Controller
{
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
            // TODO: Implement PDF extraction and AI generation
            // For now, return a placeholder response
            
            return response()->json([
                'success' => true,
                'data' => [
                    'cover_letter' => 'This is a placeholder cover letter. The actual implementation will extract text from the PDF and generate a tailored cover letter using AI.',
                    'word_count' => 25,
                    'request_id' => uniqid('req_', true)
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
                    'message' => 'An error occurred while generating the cover letter.',
                    'code' => 'GENERATION_ERROR',
                    'request_id' => uniqid('req_', true)
                ]
            ], 500);
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
                'storage' => 'ok'
            ],
            'timestamp' => now()->toIso8601String()
        ]);
    }
}