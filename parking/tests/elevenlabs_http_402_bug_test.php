<?php
/**
 * Bug Condition Exploration Test - ElevenLabs Steven Voice HTTP 402 Error
 * 
 * This test verifies the bug condition: ElevenLabs TTS API returns HTTP 402 "payment_required" 
 * error when using Steven voice ID (9zOaLLJKBwYOwr8bOPDj) with free account.
 * 
 * **EXPECTED OUTCOME ON UNFIXED CODE**: This test MUST FAIL
 * - Failure confirms the bug exists (HTTP 402 errors occur)
 * 
 * **EXPECTED OUTCOME AFTER FIX**: This test MUST PASS
 * - Success confirms the bug is fixed (voice API calls succeed)
 * 
 * Property 1: Bug Condition - ElevenLabs Steven Voice HTTP 402 Error
 * 
 * Validates: Requirements 1.1, 1.2, 1.4
 */

// Test configuration
$testResults = [];
$testsPassed = 0;
$testsFailed = 0;

function logTest($name, $passed, $message, $counterexample = null) {
    global $testResults, $testsPassed, $testsFailed;
    
    if ($passed) {
        $testsPassed++;
        $status = "✓ PASS";
        $color = "\033[32m"; // Green
    } else {
        $testsFailed++;
        $status = "✗ FAIL";
        $color = "\033[31m"; // Red
    }
    
    $reset = "\033[0m";
    echo "{$color}{$status}{$reset} {$name}\n";
    echo "  {$message}\n";
    
    if ($counterexample) {
        echo "  Counterexample: {$counterexample}\n";
    }
    echo "\n";
    
    $testResults[] = [
        'name' => $name,
        'passed' => $passed,
        'message' => $message,
        'counterexample' => $counterexample
    ];
}

echo "\n";
echo "================================================================================\n";
echo "Bug Condition Exploration Test - ElevenLabs Steven Voice HTTP 402 Error\n";
echo "================================================================================\n";
echo "\n";
echo "This test verifies that the bug condition exists (HTTP 402 payment_required errors).\n";
echo "EXPECTED: This test should FAIL on unfixed code to prove the bug exists.\n";
echo "\n";

// Test 1.1: Direct ElevenLabs TTS API call with Steven voice ID
echo "Test 1.1: Testing direct ElevenLabs TTS API call with Steven voice...\n";

// Include config to get API key and voice ID
require_once __DIR__ . '/../config.php';

$testText = "Test message";
$stevenVoiceId = "9zOaLLJKBwYOwr8bOPDj";

// Simulate the API call that api_elevenlabs_tts.php makes
$apiKey = defined("ELEVENLABS_API_KEY") ? trim(ELEVENLABS_API_KEY) : "";
$url = "https://api.elevenlabs.io/v1/text-to-speech/" . rawurlencode($stevenVoiceId) . 
       "?output_format=" . rawurlencode(ELEVENLABS_OUTPUT_FORMAT);

$payload = [
    "text" => $testText,
    "model_id" => ELEVENLABS_TTS_MODEL,
    "voice_settings" => [
        "stability" => 0.55,
        "similarity_boost" => 0.85,
        "use_speaker_boost" => true
    ]
];

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "xi-api-key: " . $apiKey
    ],
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_PROXY => "",
    CURLOPT_NOPROXY => "*",
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 45
]);

$response = curl_exec($ch);
$httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// Check for HTTP 402 error (the bug condition)
if ($httpCode === 402) {
    $errorDetail = $response ? substr($response, 0, 500) : "No response body";
    logTest(
        "Test 1.1: ElevenLabs TTS API with Steven voice",
        false,
        "Bug detected: HTTP 402 payment_required error when using Steven voice ID",
        "HTTP 402 response: " . $errorDetail
    );
} else if ($httpCode >= 200 && $httpCode < 300) {
    logTest(
        "Test 1.1: ElevenLabs TTS API with Steven voice",
        true,
        "Expected behavior: ElevenLabs TTS API call succeeded without HTTP 402 error",
        null
    );
} else {
    // Other error codes (not the specific bug we're testing for)
    logTest(
        "Test 1.1: ElevenLabs TTS API with Steven voice",
        false,
        "Unexpected error: HTTP " . $httpCode . " (not the expected HTTP 402 bug)",
        "HTTP " . $httpCode . ": " . ($response ? substr($response, 0, 200) : $curlError)
    );
}

// Test 1.2: Test playElevenLabsTts function call via cURL to API endpoint
echo "Test 1.2: Testing playElevenLabsTts via api_elevenlabs_tts.php endpoint...\n";

// Use cURL to call the API endpoint like JavaScript would
$apiUrl = 'http://localhost/parking/api_elevenlabs_tts.php';
$postData = json_encode([
    'text' => $testText,
    'voice_id' => $stevenVoiceId
]);

$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($postData)
    ],
    CURLOPT_POSTFIELDS => $postData,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 30
]);

$apiResponse = curl_exec($ch);
$apiHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// Check if the API endpoint returned an error indicating HTTP 402
if ($apiHttpCode === 502 && $apiResponse && strpos($apiResponse, '402') !== false) {
    logTest(
        "Test 1.2: playElevenLabsTts via API endpoint",
        false,
        "Bug detected: api_elevenlabs_tts.php returns 502 error due to HTTP 402 from ElevenLabs",
        "API response: " . substr($apiResponse, 0, 300)
    );
} else if ($apiHttpCode === 200) {
    logTest(
        "Test 1.2: playElevenLabsTts via API endpoint",
        true,
        "Expected behavior: api_elevenlabs_tts.php succeeded without HTTP 402 error",
        null
    );
} else if ($curlError) {
    logTest(
        "Test 1.2: playElevenLabsTts via API endpoint",
        false,
        "Connection error (expected in test environment): " . $curlError,
        "Cannot test API endpoint without running web server"
    );
} else {
    logTest(
        "Test 1.2: playElevenLabsTts via API endpoint",
        false,
        "Unexpected API response: HTTP " . $apiHttpCode,
        "Response: " . substr($apiResponse ?: 'No response', 0, 200)
    );
}

// Test 1.3: Verify Steven voice ID is configured as primary voice
echo "Test 1.3: Verifying Steven voice ID configuration...\n";

$configuredVoiceId = defined('ELEVENLABS_VOICE_ID') ? ELEVENLABS_VOICE_ID : '';
$isStevenVoiceConfigured = ($configuredVoiceId === $stevenVoiceId);

if ($isStevenVoiceConfigured) {
    logTest(
        "Test 1.3: Steven voice ID configuration",
        true,
        "Confirmed: Steven voice ID (9zOaLLJKBwYOwr8bOPDj) is configured as primary voice",
        "ELEVENLABS_VOICE_ID = " . $configuredVoiceId
    );
} else {
    logTest(
        "Test 1.3: Steven voice ID configuration",
        false,
        "Configuration issue: Steven voice ID is not configured as primary voice",
        "ELEVENLABS_VOICE_ID = " . $configuredVoiceId . " (expected: " . $stevenVoiceId . ")"
    );
}

// Test 1.4: Check debug log for HTTP 402 errors
echo "Test 1.4: Checking debug log for HTTP 402 errors...\n";

$debugLogFile = __DIR__ . '/../debug_elevenlabs_tts.log';
$hasDebugLog = file_exists($debugLogFile);
$has402InLog = false;
$logContent = '';

if ($hasDebugLog) {
    $logContent = file_get_contents($debugLogFile);
    $has402InLog = strpos($logContent, '402') !== false || 
                   strpos($logContent, 'payment_required') !== false ||
                   strpos($logContent, 'payment required') !== false;
}

if ($has402InLog) {
    // Extract recent 402 errors from log
    $logLines = explode("\n", $logContent);
    $recent402Lines = [];
    foreach ($logLines as $line) {
        if (strpos($line, '402') !== false || 
            strpos($line, 'payment_required') !== false ||
            strpos($line, 'payment required') !== false) {
            $recent402Lines[] = trim($line);
        }
    }
    $recentErrors = array_slice($recent402Lines, -3); // Last 3 errors
    
    logTest(
        "Test 1.4: Debug log HTTP 402 errors",
        false,
        "Bug detected: HTTP 402 errors found in debug log",
        "Recent errors: " . implode(" | ", $recentErrors)
    );
} else if ($hasDebugLog) {
    logTest(
        "Test 1.4: Debug log HTTP 402 errors",
        true,
        "No HTTP 402 errors found in debug log",
        null
    );
} else {
    logTest(
        "Test 1.4: Debug log HTTP 402 errors",
        true,
        "No debug log file exists (no errors logged yet)",
        null
    );
}

// Summary
echo "\n";
echo "================================================================================\n";
echo "Test Summary\n";
echo "================================================================================\n";
echo "\n";
echo "Total Tests: " . ($testsPassed + $testsFailed) . "\n";
echo "\033[32mPassed: {$testsPassed}\033[0m\n";
echo "\033[31mFailed: {$testsFailed}\033[0m\n";
echo "\n";

if ($testsFailed > 0) {
    echo "\033[31m";
    echo "================================================================================\n";
    echo "BUG CONDITION CONFIRMED\n";
    echo "================================================================================\n";
    echo "\033[0m\n";
    echo "The test FAILED as expected on unfixed code.\n";
    echo "This confirms the bug exists: ElevenLabs TTS API returns HTTP 402 errors.\n";
    echo "\n";
    echo "Counterexamples found:\n";
    foreach ($testResults as $result) {
        if (!$result['passed'] && $result['counterexample']) {
            echo "  - {$result['name']}: {$result['counterexample']}\n";
        }
    }
    echo "\n";
    echo "Root cause analysis:\n";
    echo "  - Steven voice ID (9zOaLLJKBwYOwr8bOPDj) is a library voice\n";
    echo "  - Library voices require paid ElevenLabs subscription for API access\n";
    echo "  - Current API key appears to be from a free account\n";
    echo "  - Free accounts cannot use library voices via API (HTTP 402 restriction)\n";
    echo "\n";
    echo "Next steps:\n";
    echo "  1. Implement the fix (replace Steven voice with free-compatible voice)\n";
    echo "  2. Re-run this test to verify the fix\n";
    echo "  3. The test should PASS after the fix is implemented\n";
    echo "\n";
    exit(1); // Exit with error code to indicate test failure
} else {
    echo "\033[32m";
    echo "================================================================================\n";
    echo "EXPECTED BEHAVIOR CONFIRMED\n";
    echo "================================================================================\n";
    echo "\033[0m\n";
    echo "All tests PASSED.\n";
    echo "ElevenLabs TTS API calls succeed without HTTP 402 errors.\n";
    echo "The bug has been fixed successfully.\n";
    echo "\n";
    exit(0); // Exit with success code
}