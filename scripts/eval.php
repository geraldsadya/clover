<?php

/**
 * Golden Test Set Evaluation Script
 * 
 * This script runs the golden test set to verify that the cover letter generator
 * produces high-quality, non-hallucinated results across different scenarios.
 * 
 * Usage: php scripts/eval.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Bootstrap Laravel application
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\CoverLetterGenerator;
use App\Services\PdfExtractor;
use App\Services\OpenAIClient;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class GoldenTestEvaluator
{
    private CoverLetterGenerator $generator;
    private PdfExtractor $pdfExtractor;
    private OpenAIClient $openaiClient;
    private array $testResults = [];
    private int $passedTests = 0;
    private int $totalTests = 0;
    private float $lastCallAt = 0.0;

    public function __construct()
    {
        $this->generator = new CoverLetterGenerator();
        $this->pdfExtractor = new PdfExtractor();
        $this->openaiClient = new OpenAIClient();
    }

    /**
     * Pace calls to respect Free tier 3 RPM limit (≥21s between calls)
     */
    private function paceCalls(): void
    {
        $now = microtime(true);
        $minGap = 21.0; // 3 RPM = 20s, use 21s to be safe
        $sleep = $minGap - ($now - $this->lastCallAt);
        
        if ($sleep > 0) {
            echo "⏳ Waiting " . round($sleep, 1) . " seconds to respect 3 RPM limit...\n";
            usleep((int)($sleep * 1_000_000));
        }
        
        $this->lastCallAt = microtime(true);
    }

    /**
     * Run all golden tests
     */
    public function runAllTests(): bool
    {
        echo "🧪 Starting Golden Test Set Evaluation\n";
        echo "=====================================\n\n";

        // Load expected results
        $expectedResults = $this->loadExpectedResults();
        
        // Run each test case with proper Free tier pacing
        foreach ($expectedResults as $index => $testCase) {
            if ($index > 0) {
                $this->paceCalls();
            }
            $this->runTestCase($testCase);
        }

        // Print summary
        $this->printSummary();

        return $this->passedTests === $this->totalTests;
    }

    /**
     * Load expected results from golden/expected.json
     */
    private function loadExpectedResults(): array
    {
        $expectedFile = __DIR__ . '/../golden/expected.json';
        
        if (!file_exists($expectedFile)) {
            throw new Exception("Expected results file not found: {$expectedFile}");
        }

        $content = file_get_contents($expectedFile);
        $expected = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON in expected results: " . json_last_error_msg());
        }

        return $expected;
    }

    /**
     * Run a single test case
     */
    private function runTestCase(array $testCase): void
    {
        $this->totalTests++;
        
        echo "🔍 Testing: {$testCase['name']}\n";
        echo "   CV: {$testCase['cv_file']}\n";
        echo "   Job: {$testCase['job_file']}\n";

        try {
            // Load CV text and job description with truncation
            $cvText = $this->loadCvText($testCase['cv_file']);
            $jobDescription = $this->loadJobDescription($testCase['job_file']);
            
            // Truncate inputs aggressively for Free tier (≤2k tokens input)
            $cvText = OpenAIClient::safeTruncate($cvText, 15000); // ~12-15k chars
            $jobDescription = OpenAIClient::safeTruncate($jobDescription, 5000); // ~3-5k chars

            // Use direct OpenAI call for golden test
            $messages = [
                [
                    'role' => 'system',
                    'content' => 'Return ONLY valid JSON with keys cover_letter, build_summary. Do not invent facts not in CV.'
                ],
                [
                    'role' => 'user',
                    'content' => OpenAIClient::buildPrompt($cvText, $jobDescription)
                ]
            ];

            $response = $this->openaiClient->chat($messages, 0.5);
            $json = json_decode($response, true);
            
            if (!is_array($json) || !isset($json['cover_letter'])) {
                $json = OpenAIClient::tryRepairJson($response);
            }
            
            $coverLetter = $json['cover_letter'] ?? '';
            $wordCount = str_word_count($coverLetter);
            
            $result = [
                'cover_letter' => $coverLetter,
                'word_count' => $wordCount
            ];

            // Validate results
            $validationResult = $this->validateResult($result, $testCase);

            if ($validationResult['passed']) {
                echo "   ✅ PASSED\n";
                $this->passedTests++;
            } else {
                echo "   ❌ FAILED\n";
                foreach ($validationResult['errors'] as $error) {
                    echo "      - {$error}\n";
                }
            }

            $this->testResults[] = [
                'name' => $testCase['name'],
                'passed' => $validationResult['passed'],
                'errors' => $validationResult['errors'],
                'word_count' => $result['word_count'] ?? 0,
                'company_mentioned' => $validationResult['company_mentioned'] ?? false,
                'banned_phrases_found' => $validationResult['banned_phrases_found'] ?? []
            ];

        } catch (Exception $e) {
            echo "   ❌ ERROR: {$e->getMessage()}\n";
            $this->testResults[] = [
                'name' => $testCase['name'],
                'passed' => false,
                'errors' => ["Exception: {$e->getMessage()}"],
                'word_count' => 0,
                'company_mentioned' => false,
                'banned_phrases_found' => []
            ];
        }

        echo "\n";
    }

    /**
     * Load CV text content (using text files for testing)
     */
    private function loadCvText(string $filename): string
    {
        // Try to load from text file first (for testing)
        $textPath = __DIR__ . "/../golden/cvs/" . str_replace('.pdf', '.txt', $filename);
        
        if (file_exists($textPath)) {
            return file_get_contents($textPath);
        }
        
        // Fallback to PDF extraction (requires pdftotext)
        $pdfPath = __DIR__ . "/../golden/cvs/{$filename}";
        
        if (!file_exists($pdfPath)) {
            throw new Exception("CV file not found: {$pdfPath}");
        }

        $cvFile = new UploadedFile(
            $pdfPath,
            $filename,
            'application/pdf',
            null,
            true
        );
        
        return $this->pdfExtractor->extract($cvFile);
    }

    /**
     * Load job description from text file
     */
    private function loadJobDescription(string $filename): string
    {
        $jobPath = __DIR__ . "/../golden/jobs/{$filename}";
        
        if (!file_exists($jobPath)) {
            throw new Exception("Job description file not found: {$jobPath}");
        }

        return file_get_contents($jobPath);
    }

    /**
     * Validate the generated cover letter against expected results
     */
    private function validateResult(array $result, array $expected): array
    {
        $errors = [];
        $passed = true;

        // Check word count
        $wordCount = $result['word_count'] ?? 0;
        if ($wordCount < $expected['word_count_min'] || $wordCount > $expected['word_count_max']) {
            $errors[] = "Word count {$wordCount} not in range [{$expected['word_count_min']}, {$expected['word_count_max']}]";
            $passed = false;
        }

        // Check company mention
        $coverLetter = $result['cover_letter'] ?? '';
        $companyMentioned = false;
        foreach ($expected['must_mention'] as $phrase) {
            if (stripos($coverLetter, $phrase) !== false) {
                $companyMentioned = true;
                break;
            }
        }

        if (!$companyMentioned) {
            $errors[] = "Company/role not mentioned. Expected one of: " . implode(', ', $expected['must_mention']);
            $passed = false;
        }

        // Check for banned phrases (anti-hallucination)
        $bannedPhrasesFound = [];
        foreach ($expected['banned_phrases'] as $phrase) {
            if (stripos($coverLetter, $phrase) !== false) {
                $bannedPhrasesFound[] = $phrase;
                $errors[] = "Banned phrase found: '{$phrase}'";
                $passed = false;
            }
        }

        return [
            'passed' => $passed,
            'errors' => $errors,
            'company_mentioned' => $companyMentioned,
            'banned_phrases_found' => $bannedPhrasesFound
        ];
    }

    /**
     * Print test summary
     */
    private function printSummary(): void
    {
        echo "📊 Test Summary\n";
        echo "==============\n";
        echo "Total Tests: {$this->totalTests}\n";
        echo "Passed: {$this->passedTests}\n";
        echo "Failed: " . ($this->totalTests - $this->passedTests) . "\n";
        echo "Success Rate: " . round(($this->passedTests / $this->totalTests) * 100, 1) . "%\n\n";

        if ($this->passedTests === $this->totalTests) {
            echo "🎉 All tests passed! The cover letter generator is working correctly.\n";
        } else {
            echo "⚠️  Some tests failed. Please review the errors above.\n";
        }

        // Detailed results
        echo "\n📋 Detailed Results:\n";
        foreach ($this->testResults as $result) {
            $status = $result['passed'] ? '✅' : '❌';
            echo "{$status} {$result['name']} - {$result['word_count']} words\n";
            if (!$result['passed']) {
                foreach ($result['errors'] as $error) {
                    echo "   - {$error}\n";
                }
            }
        }
    }
}

// Run the evaluation
try {
    $evaluator = new GoldenTestEvaluator();
    $allPassed = $evaluator->runAllTests();
    
    exit($allPassed ? 0 : 1);
} catch (Exception $e) {
    echo "💥 Fatal Error: {$e->getMessage()}\n";
    exit(1);
}
