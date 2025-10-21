<?php

/**
 * Production Golden Test Set Evaluation Script
 *
 * This script runs the golden test set against a production deployment
 * to verify that the cover letter generator works correctly in production.
 *
 * Usage: php scripts/eval-production.php --url=https://your-app.railway.app
 */

require_once __DIR__.'/../vendor/autoload.php';

// Parse command line arguments
$options = getopt('', ['url:', 'help']);
$productionUrl = $options['url'] ?? null;

if (isset($options['help']) || !$productionUrl) {
    echo "Usage: php scripts/eval-production.php --url=https://your-app.railway.app\n";
    echo "Options:\n";
    echo "  --url=URL    Production URL to test against\n";
    echo "  --help       Show this help message\n";
    exit(1);
}

// Validate URL
if (!filter_var($productionUrl, FILTER_VALIDATE_URL)) {
    echo "Error: Invalid URL provided\n";
    exit(1);
}

echo "=== PRODUCTION GOLDEN TEST SET EVALUATION ===\n";
echo "Testing against: $productionUrl\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

// Load golden test cases
$goldenCases = json_decode(file_get_contents(__DIR__.'/../golden/expected.json'), true);

if (!$goldenCases) {
    echo "Error: Could not load golden test cases\n";
    exit(1);
}

$results = [];
$totalTests = count($goldenCases);
$passedTests = 0;

foreach ($goldenCases as $index => $case) {
    $testNumber = $index + 1;
    echo "Test $testNumber/$totalTests: {$case['job_title']}\n";
    
    try {
        // Create test PDF content
        $pdfContent = '%PDF-1.4
1 0 obj
<<
/Type /Catalog
/Pages 2 0 R
>>
endobj
2 0 obj
<<
/Type /Pages
/Kids [3 0 R]
/Count 1
>>
endobj
3 0 obj
<<
/Type /Page
/Parent 2 0 R
/MediaBox [0 0 612 792]
/Contents 4 0 R
>>
endobj
4 0 obj
<<
/Length 44
>>
stream
BT
/F1 12 Tf
72 720 Td
(' . $case['cv_content'] . ') Tj
ET
endstream
endobj
xref
0 5
0000000000 65535 f 
0000000009 00000 n 
0000000058 00000 n 
0000000115 00000 n 
0000000204 00000 n 
trailer
<<
/Size 5
/Root 1 0 R
>>
startxref
297
%%EOF';

        // Create multipart form data
        $boundary = '----WebKitFormBoundary' . uniqid();
        $data = '';
        
        // Add CV file
        $data .= "--$boundary\r\n";
        $data .= "Content-Disposition: form-data; name=\"cv\"; filename=\"test.pdf\"\r\n";
        $data .= "Content-Type: application/pdf\r\n\r\n";
        $data .= $pdfContent . "\r\n";
        
        // Add job description
        $data .= "--$boundary\r\n";
        $data .= "Content-Disposition: form-data; name=\"job_description\"\r\n\r\n";
        $data .= $case['job_description'] . "\r\n";
        
        $data .= "--$boundary--\r\n";

        // Make HTTP request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $productionUrl . '/generate');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: multipart/form-data; boundary=$boundary",
            "Accept: application/json"
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("cURL error: $error");
        }

        if ($httpCode !== 200) {
            throw new Exception("HTTP $httpCode: $response");
        }

        $result = json_decode($response, true);
        
        if (!$result || !$result['success']) {
            throw new Exception("API error: " . ($result['error']['message'] ?? 'Unknown error'));
        }

        $coverLetter = $result['data']['cover_letter'];
        $wordCount = str_word_count($coverLetter);

        // Validate results
        $errors = [];
        
        // Check word count
        if ($wordCount < 150 || $wordCount > 300) {
            $errors[] = "Word count $wordCount not in range 150-300";
        }
        
        // Check company/role mention
        $companyMentioned = stripos($coverLetter, $case['expected_company']) !== false;
        $roleMentioned = stripos($coverLetter, $case['expected_role']) !== false;
        
        if (!$companyMentioned) {
            $errors[] = "Company '{$case['expected_company']}' not mentioned";
        }
        
        if (!$roleMentioned) {
            $errors[] = "Role '{$case['expected_role']}' not mentioned";
        }
        
        // Check for banned phrases
        foreach ($case['banned_phrases'] as $phrase) {
            if (stripos($coverLetter, $phrase) !== false) {
                $errors[] = "Banned phrase found: '$phrase'";
            }
        }

        if (empty($errors)) {
            echo "✅ PASSED\n";
            $passedTests++;
            $results[] = [
                'test' => $testNumber,
                'job_title' => $case['job_title'],
                'status' => 'PASSED',
                'word_count' => $wordCount,
                'company_mentioned' => $companyMentioned,
                'role_mentioned' => $roleMentioned
            ];
        } else {
            echo "❌ FAILED: " . implode(', ', $errors) . "\n";
            $results[] = [
                'test' => $testNumber,
                'job_title' => $case['job_title'],
                'status' => 'FAILED',
                'errors' => $errors,
                'word_count' => $wordCount,
                'company_mentioned' => $companyMentioned,
                'role_mentioned' => $roleMentioned
            ];
        }

    } catch (Exception $e) {
        echo "❌ ERROR: " . $e->getMessage() . "\n";
        $results[] = [
            'test' => $testNumber,
            'job_title' => $case['job_title'],
            'status' => 'ERROR',
            'error' => $e->getMessage()
        ];
    }
    
    echo "\n";
    
    // Add delay between requests to be respectful
    if ($testNumber < $totalTests) {
        sleep(2);
    }
}

// Summary
echo "=== PRODUCTION TEST SUMMARY ===\n";
echo "Total tests: $totalTests\n";
echo "Passed: $passedTests\n";
echo "Failed: " . ($totalTests - $passedTests) . "\n";
echo "Success rate: " . round(($passedTests / $totalTests) * 100, 1) . "%\n\n";

// Test health endpoint
echo "=== HEALTH CHECK ===\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $productionUrl . '/healthz');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

$healthResponse = curl_exec($ch);
$healthCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($healthCode === 200) {
    $healthData = json_decode($healthResponse, true);
    echo "✅ Health endpoint working\n";
    echo "Status: " . $healthData['status'] . "\n";
    echo "Database: " . $healthData['checks']['database'] . "\n";
    echo "Storage: " . $healthData['checks']['storage'] . "\n";
    echo "OpenAI: " . $healthData['checks']['openai'] . "\n";
    echo "pdftotext: " . $healthData['checks']['pdftotext'] . "\n";
} else {
    echo "❌ Health endpoint failed (HTTP $healthCode)\n";
}

echo "\n=== PRODUCTION TEST COMPLETE ===\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n";

// Exit with appropriate code
exit($passedTests === $totalTests ? 0 : 1);
