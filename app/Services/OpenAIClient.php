<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class OpenAIClient
{
    private Client $http;

    private string $apiKey;

    public function __construct()
    {
        // Support both OpenAI and Azure OpenAI
        $baseUrl = env('OPENAI_BASE', 'https://api.openai.com');
        if (str_contains($baseUrl, 'openai.azure.com')) {
            // Azure OpenAI format: https://your-resource.openai.azure.com/openai/deployments/your-deployment
            $this->http = new Client([
                'base_uri' => $baseUrl.'/',
                'timeout' => 60,
            ]);
        } else {
            // Standard OpenAI format
            $this->http = new Client([
                'base_uri' => $baseUrl.'/v1/',
                'timeout' => 60,
            ]);
        }

        $this->apiKey = env('OPENAI_API_KEY');
    }

    /**
     * Make a chat completion request with exponential backoff retry
     */
    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     */
    public function chat(array $messages, float $temperature = 0.5, int $maxRetries = 6): string
    {
        $attempt = 0;
        $delayMs = 600; // start at 0.6s

        while (true) {
            try {
                // Azure OpenAI uses different endpoint format
                $baseUrl = env('OPENAI_BASE', 'https://api.openai.com');
                if (str_contains($baseUrl, 'cognitiveservices.azure.com') || str_contains($baseUrl, 'openai.azure.com')) {
                    // Azure format: https://resource.cognitiveservices.azure.com/openai/deployments/deployment/chat/completions?api-version=2024-12-01-preview
                    $endpoint = $baseUrl.'/chat/completions?api-version=2024-12-01-preview';
                    $headers = [
                        'api-key' => $this->apiKey,
                        'Content-Type' => 'application/json',
                    ];
                } else {
                    // Standard OpenAI format
                    $endpoint = 'chat/completions';
                    $headers = [
                        'Authorization' => 'Bearer '.$this->apiKey,
                        'Content-Type' => 'application/json',
                    ];
                }

                $response = $this->http->post($endpoint, [
                    'headers' => $headers,
                    'json' => [
                        'model' => env('MODEL', 'gpt-4o-mini'),
                        'messages' => $messages,
                        'temperature' => $temperature,
                        'max_tokens' => 300, // Keep output small for Free tier
                    ],
                    'timeout' => 60,
                ]);

                $data = json_decode((string) $response->getBody(), true);
                $content = $data['choices'][0]['message']['content'] ?? '';

                if (empty($content)) {
                    throw new \Exception('OpenAI returned empty content');
                }

                return $content;

            } catch (RequestException $e) {
                $code = $e->getResponse()?->getStatusCode();
                $retryable = in_array($code, [429, 500, 502, 503, 504], true);

                Log::warning('OpenAI API request failed', [
                    'attempt' => $attempt + 1,
                    'status_code' => $code,
                    'retryable' => $retryable,
                    'error' => $e->getMessage(),
                ]);

                if (! $retryable || $attempt >= $maxRetries) {
                    throw new \Exception("OpenAI API request failed after {$maxRetries} retries: ".$e->getMessage());
                }

                // Honor Retry-After header if present
                $retryAfter = (int) ($e->getResponse()?->getHeaderLine('Retry-After') ?? 0);
                if ($retryAfter > 0) {
                    sleep($retryAfter);
                } else {
                    // Exponential backoff + jitter
                    $sleepMs = $delayMs + random_int(0, 250);
                    usleep($sleepMs * 1000);
                    $delayMs = min($delayMs * 2, 8000);
                }
                $attempt++;
            }
        }
    }

    /**
     * Safe text truncation to avoid token limits (Free tier optimized)
     */
    public static function safeTruncate(string $text, int $maxChars): string
    {
        $cleaned = preg_replace('/\s+/', ' ', $text);

        return mb_substr($cleaned ?? '', 0, $maxChars);
    }

    /**
     * Build a comprehensive prompt for cover letter generation
     */
    public static function buildPrompt(string $cvText, string $jobDescription): string
    {
        return <<<PROMPT
Inputs:
- CV (raw text): {$cvText}
- Job description: {$jobDescription}

Task:
1) Write a tailored, specific, human-sounding cover letter (2–3 short paragraphs, ~180–260 words) mapping CV facts to JD needs. Do not invent facts not present in CV.
2) Write a one-paragraph "How I built this" summary (3–4 sentences) describing the stack and approach.

Return ONLY valid JSON:
{"cover_letter":"...","build_summary":"..."}
PROMPT;
    }

    /**
     * Try to repair malformed JSON response
     */
    /**
     * @return array{cover_letter: string, build_summary: string}
     */
    public static function tryRepairJson(string $response): array
    {
        // First try to clean the response
        $cleaned = trim($response);

        // Try direct decode first
        $json = json_decode($cleaned, true);
        if (is_array($json) && isset($json['cover_letter'], $json['build_summary'])) {
            return $json;
        }

        // Try to extract JSON using regex
        if (preg_match('/\{.*\}/s', $cleaned, $matches)) {
            $json = json_decode($matches[0], true);
            if (is_array($json) && isset($json['cover_letter'], $json['build_summary'])) {
                return $json;
            }
        }

        // Simple approach: extract fields using basic string operations
        $coverLetter = '';
        $buildSummary = '';

        // Find cover_letter field
        if (preg_match('/"cover_letter":\s*"([^"]*(?:\\.[^"]*)*)"/s', $cleaned, $matches)) {
            $coverLetter = $matches[1];
            // Unescape JSON strings
            $coverLetter = str_replace(['\\n', '\\"', '\\\\'], ["\n", '"', '\\'], $coverLetter);
        }

        // Find build_summary field
        if (preg_match('/"build_summary":\s*"([^"]*(?:\\.[^"]*)*)"/s', $cleaned, $matches)) {
            $buildSummary = $matches[1];
            // Unescape JSON strings
            $buildSummary = str_replace(['\\n', '\\"', '\\\\'], ["\n", '"', '\\'], $buildSummary);
        }

        // Return the extracted content
        return [
            'cover_letter' => $coverLetter,
            'build_summary' => $buildSummary,
        ];
    }
}
