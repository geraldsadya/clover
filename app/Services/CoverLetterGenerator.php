<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\Validator;

class CoverLetterGenerator
{
    private const EXTRACTION_TEMPERATURE = 0.1;
    private const MAX_RETRIES = 1;

    /**
     * Extract structured facts from CV text as JSON (Stage 1)
     * 
     * @param string $cvText The extracted CV text
     * @return array<string, mixed> Structured facts as array
     * @throws \Exception On extraction failure
     */
    public function extractFacts(string $cvText): array
    {
        $requestId = uniqid('extract_', true);
        $startTime = microtime(true);

        Log::info('Starting facts extraction', [
            'request_id' => $requestId,
            'cv_text_length' => strlen($cvText),
        ]);

        try {
            $facts = $this->performExtraction($cvText, $requestId);
            
            $duration = (microtime(true) - $startTime) * 1000;
            
            Log::info('Facts extraction successful', [
                'request_id' => $requestId,
                'duration_ms' => round($duration, 2),
                'outcome' => 'success',
            ]);

            return $facts;

        } catch (\Exception $e) {
            $duration = (microtime(true) - $startTime) * 1000;
            
            Log::error('Facts extraction failed', [
                'request_id' => $requestId,
                'duration_ms' => round($duration, 2),
                'error' => $e->getMessage(),
                'outcome' => 'failure',
            ]);

            throw $e;
        }
    }

    /**
     * Perform the actual extraction with retry logic
     * @return array<string, mixed>
     */
    private function performExtraction(string $cvText, string $requestId): array
    {
        $attempt = 0;
        $lastError = null;

        for ($attempt = 0; $attempt <= self::MAX_RETRIES; $attempt++) {
            try {
                $response = $this->callOpenAI($cvText, $attempt);
                $facts = $this->parseAndValidateResponse($response);
                
                Log::info('Extraction attempt successful', [
                    'request_id' => $requestId,
                    'attempt' => $attempt + 1,
                ]);

                return $facts;

            } catch (\Exception $e) {
                $lastError = $e;

                Log::warning('Extraction attempt failed', [
                    'request_id' => $requestId,
                    'attempt' => $attempt + 1,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // If all retries failed, return needs-manual path
        Log::error('All extraction attempts failed, returning needs-manual', [
            'request_id' => $requestId,
            'final_error' => $lastError->getMessage(),
        ]);

        return ['status' => 'needs-manual', 'reason' => 'extraction_failed'];
    }

    /**
     * Call OpenAI API for facts extraction
     */
    private function callOpenAI(string $cvText, int $attempt): string
    {
        $prompt = $this->buildExtractionPrompt($cvText, $attempt);
        
        $response = OpenAI::chat()->create([
            'model' => 'gpt-4-turbo',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a professional CV analyzer. Extract structured facts from CV text and return ONLY valid JSON. Do not invent information that is not explicitly stated.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => self::EXTRACTION_TEMPERATURE,
            'max_tokens' => 2000,
        ]);

        $content = $response->choices[0]->message->content;
        
        if ($content === null) {
            throw new \Exception('OpenAI returned null content');
        }
        
        return $content;
    }

    /**
     * Build the extraction prompt based on attempt number
     */
    private function buildExtractionPrompt(string $cvText, int $attempt): string
    {
        $basePrompt = "Extract the following information from this CV text and return ONLY valid JSON with no additional text:\n\n";
        
        $schema = [
            'name' => 'Full name (string)',
            'skills' => 'Array of technical skills mentioned',
            'experience' => 'Array of work experience entries with company, role, duration',
            'education' => 'Array of education entries with institution, degree, year',
            'certifications' => 'Array of certifications mentioned',
            'years_of_experience' => 'Total years of professional experience (integer)'
        ];

        $schemaText = "Required JSON schema:\n";
        foreach ($schema as $field => $description) {
            $schemaText .= "- {$field}: {$description}\n";
        }

        if ($attempt > 0) {
            $basePrompt .= "IMPORTANT: This is a retry attempt. Ensure the JSON is perfectly valid and follows the exact schema. ";
        }

        $basePrompt .= $schemaText . "\nCV Text:\n" . $cvText;

        return $basePrompt;
    }

    /**
     * Parse and validate the OpenAI response
     * @return array<string, mixed>
     */
    private function parseAndValidateResponse(string $response): array
    {
        // Clean the response (remove any markdown formatting)
        $cleanResponse = trim($response);
        if (str_starts_with($cleanResponse, '```json')) {
            $cleanResponse = substr($cleanResponse, 7);
        }
        if (str_ends_with($cleanResponse, '```')) {
            $cleanResponse = substr($cleanResponse, 0, -3);
        }
        $cleanResponse = trim($cleanResponse);

        // Parse JSON
        $facts = json_decode($cleanResponse, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON response: ' . json_last_error_msg());
        }

        // Validate schema
        $this->validateFactsSchema($facts);

        // Check for banned phrases (anti-hallucination)
        $this->checkForBannedPhrases($facts);

        return $facts;
    }

    /**
     * Validate the facts schema
     * @param array<string, mixed> $facts
     */
    private function validateFactsSchema(array $facts): void
    {
        $validator = Validator::make($facts, [
            'name' => 'required|string|max:255',
            'skills' => 'present|array',
            'experience' => 'present|array',
            'education' => 'present|array',
            'certifications' => 'present|array',
            'years_of_experience' => 'required|integer|min:0|max:50',
        ]);

        if ($validator->fails()) {
            throw new \Exception('Invalid facts schema: ' . implode(', ', $validator->errors()->all()));
        }
    }

    /**
     * Check for banned phrases to prevent hallucination
     * @param array<string, mixed> $facts
     */
    private function checkForBannedPhrases(array $facts): void
    {
        $bannedPhrases = [
            'not specified',
            'not mentioned',
            'not provided',
            'not available',
            'unknown',
            'n/a',
            'not found',
        ];

        $factsString = json_encode($facts);
        
        if ($factsString === false) {
            throw new \Exception('Failed to encode facts as JSON');
        }
        
        foreach ($bannedPhrases as $phrase) {
            if (stripos($factsString, $phrase) !== false) {
                throw new \Exception("Banned phrase detected: {$phrase}");
            }
        }
    }

    /**
     * Generate cover letter from facts and job description (Stage 2)
     * This will be implemented in Ticket E
     * @param array<string, mixed> $facts
     */
    public function generateCoverLetter(array $facts, string $jobDescription): string
    {
        // Placeholder - will be implemented in Ticket E
        return 'Cover letter generation will be implemented in Ticket E';
    }

    /**
     * Sanitize job description input
     * This will be implemented in Ticket D
     */
    public function sanitizeJobDescription(string $jobDescription): string
    {
        // Placeholder - will be implemented in Ticket D
        return $jobDescription;
    }
}
