<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\Validator;

class CoverLetterGenerator
{
    private const EXTRACTION_TEMPERATURE = 0.1;
    private const COMPOSITION_TEMPERATURE = 0.4;
    private const MAX_RETRIES = 1;
    private const MAX_COMPOSITION_RETRIES = 1;
    private const MIN_WORD_COUNT = 150;
    private const MAX_WORD_COUNT = 300;

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
     * 
     * @param array<string, mixed> $facts Extracted facts from Stage 1
     * @param string $jobDescription Raw job description (will be sanitized internally)
     * @return string Generated cover letter (150-300 words, 2-3 paragraphs)
     */
    public function generateCoverLetter(array $facts, string $jobDescription): string
    {
        $requestId = uniqid('compose_', true);
        $startTime = microtime(true);

        Log::info('Starting cover letter composition', [
            'request_id' => $requestId,
            'facts_keys' => array_keys($facts),
            'job_description_length' => strlen($jobDescription),
        ]);

        try {
            // Sanitize job description before AI processing
            $sanitizedJobDescription = $this->sanitizeJobDescription($jobDescription);
            
            // Extract company name and role from job description
            $companyAndRole = $this->extractCompanyAndRole($sanitizedJobDescription);
            
            // Generate cover letter with retry logic for word count
            $coverLetter = $this->performComposition($facts, $sanitizedJobDescription, $companyAndRole, $requestId);
            
            $duration = (microtime(true) - $startTime) * 1000;
            
            Log::info('Cover letter composition successful', [
                'request_id' => $requestId,
                'duration_ms' => round($duration, 2),
                'word_count' => str_word_count($coverLetter),
                'outcome' => 'success',
            ]);

            return $coverLetter;

        } catch (\Exception $e) {
            $duration = (microtime(true) - $startTime) * 1000;
            
            Log::error('Cover letter composition failed', [
                'request_id' => $requestId,
                'duration_ms' => round($duration, 2),
                'outcome' => 'failure',
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Sanitize job description input before AI processing
     * 
     * @param string $jobDescription Raw job description input
     * @return string Sanitized job description
     */
    public function sanitizeJobDescription(string $jobDescription): string
    {
        // Strip HTML tags but preserve spaces
        $sanitized = strip_tags($jobDescription);
        
        // Remove UTM tracking parameters (common patterns)
        $sanitized = preg_replace('/[?&]utm_[^&\s]*/', '', $sanitized) ?? $sanitized;
        $sanitized = preg_replace('/[?&](fbclid|gclid|msclkid)=[^&\s]*/', '', $sanitized) ?? $sanitized;
        
        // Clean up any double ampersands or question marks that might be left
        $sanitized = preg_replace('/[?&]+/', '&', $sanitized) ?? $sanitized;
        $sanitized = preg_replace('/^&/', '?', $sanitized) ?? $sanitized;
        
        // Normalize whitespace (replace multiple spaces/newlines with single space)
        $sanitized = preg_replace('/\s+/', ' ', $sanitized) ?? $sanitized;
        
        // Trim whitespace
        $sanitized = trim($sanitized);
        
        // Cap length to 10k chars (keep first 10k chars)
        if (strlen($sanitized) > 10000) {
            $sanitized = substr($sanitized, 0, 10000);
            // Ensure we don't cut off in the middle of a word
            $lastSpace = strrpos($sanitized, ' ');
            if ($lastSpace !== false && $lastSpace > 9500) {
                $sanitized = substr($sanitized, 0, $lastSpace);
            }
        }
        
        return $sanitized;
    }

    /**
     * Extract company name and role from job description
     * 
     * @param string $jobDescription Sanitized job description
     * @return array<string, string> Company name and role
     */
    private function extractCompanyAndRole(string $jobDescription): array
    {
        // Simple extraction - look for common patterns
        $company = 'the company';
        $role = 'the position';
        
        // Look for company name patterns
        if (preg_match('/at\s+([A-Z][a-zA-Z\s&]+?)(?:\s|$|,|\.)/', $jobDescription, $matches)) {
            $company = trim($matches[1]);
        } elseif (preg_match('/company[:\s]+([A-Z][a-zA-Z\s&]+?)(?:\s|$|,|\.)/i', $jobDescription, $matches)) {
            $company = trim($matches[1]);
        }
        
        // Look for role patterns - prioritize patterns that come before "position"
        if (preg_match('/^([A-Z][a-zA-Z\s]+?)\s+position/i', $jobDescription, $matches)) {
            $role = trim($matches[1]);
        } elseif (preg_match('/(?:position|role|job)[:\s]+([A-Z][a-zA-Z\s]+?)(?:\s|$|,|\.)/i', $jobDescription, $matches)) {
            $role = trim($matches[1]);
        } elseif (preg_match('/looking for\s+([a-zA-Z\s]+?)(?:\s|$|,|\.)/i', $jobDescription, $matches)) {
            $role = trim($matches[1]);
        }
        
        return ['company' => $company, 'role' => $role];
    }

    /**
     * Perform cover letter composition with retry logic for word count
     * 
     * @param array<string, mixed> $facts
     * @param string $sanitizedJobDescription
     * @param array<string, string> $companyAndRole
     * @param string $requestId
     * @return string Generated cover letter
     */
    private function performComposition(array $facts, string $sanitizedJobDescription, array $companyAndRole, string $requestId): string
    {
        $attempt = 0;
        $lastError = null;

        for ($attempt = 0; $attempt <= self::MAX_COMPOSITION_RETRIES; $attempt++) {
            try {
                $response = $this->callOpenAIForComposition($facts, $sanitizedJobDescription, $companyAndRole, $attempt);
                $coverLetter = $this->parseAndValidateComposition($response, $facts);
                
                Log::info('Composition attempt successful', [
                    'request_id' => $requestId,
                    'attempt' => $attempt + 1,
                    'word_count' => str_word_count($coverLetter),
                ]);

                return $coverLetter;

            } catch (\Exception $e) {
                $lastError = $e;

                Log::warning('Composition attempt failed', [
                    'request_id' => $requestId,
                    'attempt' => $attempt + 1,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // If all retries failed, throw exception
        Log::error('All composition attempts failed', [
            'request_id' => $requestId,
            'final_error' => $lastError->getMessage(),
        ]);

        throw new \Exception('Cover letter composition failed after all retries: ' . $lastError->getMessage());
    }

    /**
     * Call OpenAI API for cover letter composition
     * @param array<string, mixed> $facts
     * @param string $sanitizedJobDescription
     * @param array<string, string> $companyAndRole
     * @param int $attempt
     */
    private function callOpenAIForComposition(array $facts, string $sanitizedJobDescription, array $companyAndRole, int $attempt): string
    {
        $prompt = $this->buildCompositionPrompt($facts, $sanitizedJobDescription, $companyAndRole, $attempt);
        
        $response = OpenAI::chat()->create([
            'model' => 'gpt-4-turbo',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a professional cover letter writer. Write compelling, personalized cover letters that are grounded in the provided facts. Do not invent information that is not explicitly provided.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => self::COMPOSITION_TEMPERATURE,
            'max_tokens' => 1000,
        ]);

        $content = $response->choices[0]->message->content;
        
        if ($content === null) {
            throw new \Exception('OpenAI returned null content');
        }
        
        return $content;
    }

    /**
     * Build the composition prompt based on attempt number
     * @param array<string, mixed> $facts
     * @param string $sanitizedJobDescription
     * @param array<string, string> $companyAndRole
     * @param int $attempt
     */
    private function buildCompositionPrompt(array $facts, string $sanitizedJobDescription, array $companyAndRole, int $attempt): string
    {
        $basePrompt = "Write a professional cover letter for {$companyAndRole['role']} at {$companyAndRole['company']}.\n\n";
        
        $basePrompt .= "REQUIREMENTS:\n";
        $basePrompt .= "- 2-3 paragraphs, 150-300 words total\n";
        $basePrompt .= "- Include company name and role\n";
        $basePrompt .= "- Use ONLY the facts provided below\n";
        $basePrompt .= "- Do not invent skills, experience, or qualifications not mentioned\n";
        $basePrompt .= "- Write in first person\n";
        $basePrompt .= "- Be professional and compelling\n\n";
        
        if ($attempt > 0) {
            $basePrompt .= "IMPORTANT: This is a retry attempt. Ensure the word count is between 150-300 words and the content is grounded in the provided facts only.\n\n";
        }
        
        $basePrompt .= "CANDIDATE FACTS:\n";
        $basePrompt .= json_encode($facts, JSON_PRETTY_PRINT) . "\n\n";
        
        $basePrompt .= "JOB DESCRIPTION:\n";
        $basePrompt .= $sanitizedJobDescription . "\n\n";
        
        $basePrompt .= "Write the cover letter now:";

        return $basePrompt;
    }

    /**
     * Parse and validate the composition response
     * @param string $response
     * @param array<string, mixed> $facts
     */
    private function parseAndValidateComposition(string $response, array $facts): string
    {
        // Clean the response
        $coverLetter = trim($response);
        
        // Remove any markdown formatting
        if (str_starts_with($coverLetter, '```')) {
            $lines = explode("\n", $coverLetter);
            $coverLetter = implode("\n", array_slice($lines, 1, -1));
        }
        
        $coverLetter = trim($coverLetter);
        
        // Validate word count
        $wordCount = str_word_count($coverLetter);
        if ($wordCount < self::MIN_WORD_COUNT || $wordCount > self::MAX_WORD_COUNT) {
            throw new \Exception("Word count {$wordCount} is outside required range of " . self::MIN_WORD_COUNT . "-" . self::MAX_WORD_COUNT);
        }
        
        // Validate groundedness - check for hallucinated content
        $this->validateGroundedness($coverLetter, $facts);
        
        return $coverLetter;
    }

    /**
     * Validate that the cover letter is grounded in provided facts only
     * @param string $coverLetter
     * @param array<string, mixed> $facts
     */
    private function validateGroundedness(string $coverLetter, array $facts): void
    {
        // Extract skills from facts
        $providedSkills = $facts['skills'] ?? [];
        $factsString = json_encode($facts);
        
        if ($factsString === false) {
            throw new \Exception('Failed to encode facts as JSON');
        }
        
        // Check for common skills that might be hallucinated
        $commonSkills = ['Docker', 'Kubernetes', 'AWS', 'Azure', 'React', 'Vue', 'Angular', 'Python', 'Java', 'C++', 'Machine Learning', 'AI'];
        
        foreach ($commonSkills as $skill) {
            // If skill is mentioned in cover letter but not in facts
            if (stripos($coverLetter, $skill) !== false && stripos($factsString, $skill) === false) {
                throw new \Exception("Hallucinated skill detected: {$skill} is mentioned but not in provided facts");
            }
        }
        
        // Check for banned phrases that indicate hallucination
        $bannedPhrases = [
            'not mentioned',
            'not specified',
            'not provided',
            'unknown',
            'n/a',
            'not found',
        ];
        
        foreach ($bannedPhrases as $phrase) {
            if (stripos($coverLetter, $phrase) !== false) {
                throw new \Exception("Banned phrase detected in cover letter: {$phrase}");
            }
        }
    }
}
