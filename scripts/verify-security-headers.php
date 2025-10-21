<?php

/**
 * Security Headers Verification Script
 *
 * This script verifies that the production deployment has proper security headers
 * by checking against securityheaders.com API or by direct header inspection.
 *
 * Usage: php scripts/verify-security-headers.php --url=https://your-app.railway.app
 */

require_once __DIR__.'/../vendor/autoload.php';

// Parse command line arguments
$options = getopt('', ['url:', 'help']);
$productionUrl = $options['url'] ?? null;

if (isset($options['help']) || !$productionUrl) {
    echo "Usage: php scripts/verify-security-headers.php --url=https://your-app.railway.app\n";
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

echo "=== SECURITY HEADERS VERIFICATION ===\n";
echo "Testing: $productionUrl\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

// Required security headers
$requiredHeaders = [
    'X-Frame-Options' => 'DENY',
    'X-Content-Type-Options' => 'nosniff',
    'Referrer-Policy' => 'no-referrer',
    'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains; preload',
    'X-XSS-Protection' => '1; mode=block',
    'Cross-Origin-Embedder-Policy' => 'require-corp',
    'Cross-Origin-Opener-Policy' => 'same-origin',
    'Cross-Origin-Resource-Policy' => 'same-origin',
    'Permissions-Policy' => 'geolocation=(), microphone=(), camera=(), payment=(), usb=()',
];

// Optional headers (should be present)
$optionalHeaders = [
    'Content-Security-Policy' => null, // Will check for presence and nonce
    'X-Request-ID' => null, // Will check for presence
];

$results = [];
$allPassed = true;

// Test main page
echo "Testing main page...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $productionUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$headers = substr($response, 0, $headerSize);
curl_close($ch);

if ($httpCode !== 200) {
    echo "❌ Main page returned HTTP $httpCode\n";
    exit(1);
}

// Parse headers
$headerLines = explode("\r\n", $headers);
$parsedHeaders = [];
foreach ($headerLines as $line) {
    if (strpos($line, ':') !== false) {
        list($name, $value) = explode(':', $line, 2);
        $parsedHeaders[trim($name)] = trim($value);
    }
}

echo "Found " . count($parsedHeaders) . " headers\n\n";

// Check required headers
echo "=== REQUIRED HEADERS ===\n";
foreach ($requiredHeaders as $header => $expectedValue) {
    if (isset($parsedHeaders[$header])) {
        $actualValue = $parsedHeaders[$header];
        if ($actualValue === $expectedValue) {
            echo "✅ $header: $actualValue\n";
            $results[$header] = 'PASS';
        } else {
            echo "❌ $header: Expected '$expectedValue', got '$actualValue'\n";
            $results[$header] = 'FAIL';
            $allPassed = false;
        }
    } else {
        echo "❌ $header: Missing\n";
        $results[$header] = 'MISSING';
        $allPassed = false;
    }
}

echo "\n=== OPTIONAL HEADERS ===\n";
foreach ($optionalHeaders as $header => $expectedValue) {
    if (isset($parsedHeaders[$header])) {
        $actualValue = $parsedHeaders[$header];
        if ($header === 'Content-Security-Policy') {
            if (strpos($actualValue, 'nonce-') !== false && strpos($actualValue, "default-src 'self'") !== false) {
                echo "✅ $header: Present with nonce and proper CSP\n";
                $results[$header] = 'PASS';
            } else {
                echo "⚠️  $header: Present but may not have proper CSP configuration\n";
                $results[$header] = 'WARN';
            }
        } else {
            echo "✅ $header: $actualValue\n";
            $results[$header] = 'PASS';
        }
    } else {
        echo "⚠️  $header: Missing (optional)\n";
        $results[$header] = 'MISSING';
    }
}

// Test health endpoint
echo "\n=== HEALTH ENDPOINT HEADERS ===\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $productionUrl . '/healthz');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

$healthResponse = curl_exec($ch);
$healthCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$healthHeaderSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$healthHeaders = substr($healthResponse, 0, $healthHeaderSize);
curl_close($ch);

if ($healthCode === 200) {
    echo "✅ Health endpoint accessible\n";
    
    // Check if health endpoint has same security headers
    $healthHeaderLines = explode("\r\n", $healthHeaders);
    $healthParsedHeaders = [];
    foreach ($healthHeaderLines as $line) {
        if (strpos($line, ':') !== false) {
            list($name, $value) = explode(':', $line, 2);
            $healthParsedHeaders[trim($name)] = trim($value);
        }
    }
    
    $healthSecurityHeaders = 0;
    foreach ($requiredHeaders as $header => $expectedValue) {
        if (isset($healthParsedHeaders[$header]) && $healthParsedHeaders[$header] === $expectedValue) {
            $healthSecurityHeaders++;
        }
    }
    
    echo "Security headers on health endpoint: $healthSecurityHeaders/" . count($requiredHeaders) . "\n";
} else {
    echo "❌ Health endpoint failed (HTTP $healthCode)\n";
    $allPassed = false;
}

// Summary
echo "\n=== SECURITY HEADERS SUMMARY ===\n";
$passed = 0;
$failed = 0;
$missing = 0;

foreach ($results as $header => $status) {
    if ($status === 'PASS') {
        $passed++;
    } elseif ($status === 'FAIL' || $status === 'MISSING') {
        $failed++;
    }
}

echo "Passed: $passed\n";
echo "Failed/Missing: $failed\n";
echo "Overall: " . ($allPassed ? "✅ PASS" : "❌ FAIL") . "\n\n";

// Security recommendations
echo "=== SECURITY RECOMMENDATIONS ===\n";
if (!$allPassed) {
    echo "❌ Security headers need attention:\n";
    foreach ($results as $header => $status) {
        if ($status === 'FAIL' || $status === 'MISSING') {
            echo "  - Fix $header\n";
        }
    }
} else {
    echo "✅ All security headers are properly configured!\n";
    echo "✅ Your application has production-grade security headers.\n";
    echo "✅ Ready for securityheaders.com verification.\n";
}

echo "\n=== VERIFICATION COMPLETE ===\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n";

// Exit with appropriate code
exit($allPassed ? 0 : 1);
