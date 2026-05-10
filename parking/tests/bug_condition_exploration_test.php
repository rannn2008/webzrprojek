<?php
/**
 * Bug Condition Exploration Test
 * 
 * This test verifies the bug condition: multiple voice service fallback mechanisms exist.
 * 
 * **EXPECTED OUTCOME ON UNFIXED CODE**: This test MUST FAIL
 * - Failure confirms the bug exists (multiple fallback mechanisms are present)
 * 
 * **EXPECTED OUTCOME AFTER FIX**: This test MUST PASS
 * - Success confirms the bug is fixed (only ElevenLabs Steven voice is used)
 * 
 * Property 1: Bug Condition - Multiple Voice Service Fallback Detection
 * 
 * Validates: Requirements 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7, 1.8
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
echo "Bug Condition Exploration Test - Multiple Voice Service Fallback Detection\n";
echo "================================================================================\n";
echo "\n";
echo "This test verifies that the bug condition exists (multiple fallback mechanisms).\n";
echo "EXPECTED: This test should FAIL on unfixed code to prove the bug exists.\n";
echo "\n";

// Test 1.1: Verify playAiVoiceTts() attempts multiple voice services
echo "Test 1.1: Checking playAiVoiceTts() for multiple fallback attempts...\n";
$globalAiFile = __DIR__ . '/../global_ai_assistant.php';
$globalAiContent = file_get_contents($globalAiFile);

// Check for multiple playElevenLabsTts calls with different voice IDs
$stevenVoicePattern = '/playElevenLabsTts\s*\([^,]+,\s*[\'"]9zOaLLJKBwYOwr8bOPDj[\'"]\s*\)/';
$adamVoicePattern = '/playElevenLabsTts\s*\([^,]+,\s*[\'"]pNInz6obpgDQGcFmaJgB[\'"]\s*\)/';
$geminiCallPattern = '/playGeminiTts\s*\(/';

$hasStevenCall = preg_match($stevenVoicePattern, $globalAiContent);
$hasAdamCall = preg_match($adamVoicePattern, $globalAiContent);
$hasGeminiCall = preg_match($geminiCallPattern, $globalAiContent);

$multipleAttempts = ($hasStevenCall && $hasAdamCall) || ($hasStevenCall && $hasGeminiCall);

if ($multipleAttempts) {
    $counterexamples = [];
    if ($hasAdamCall) $counterexamples[] = "ElevenLabs Adam voice (pNInz6obpgDQGcFmaJgB)";
    if ($hasGeminiCall) $counterexamples[] = "Gemini TTS";
    
    logTest(
        "Test 1.1: playAiVoiceTts() multiple fallback attempts",
        false,
        "Bug detected: playAiVoiceTts() attempts multiple voice services",
        "Fallback services found: " . implode(", ", $counterexamples)
    );
} else {
    logTest(
        "Test 1.1: playAiVoiceTts() multiple fallback attempts",
        true,
        "Expected behavior: playAiVoiceTts() uses only ElevenLabs Steven voice",
        null
    );
}

// Test 1.2: Verify playGeminiTts() function does NOT exist
echo "Test 1.2: Checking for playGeminiTts() function existence...\n";
$playGeminiTtsPattern = '/function\s+playGeminiTts\s*\(/';
$hasPlayGeminiTtsFunction = preg_match($playGeminiTtsPattern, $globalAiContent);

if ($hasPlayGeminiTtsFunction) {
    logTest(
        "Test 1.2: playGeminiTts() function existence",
        false,
        "Bug detected: playGeminiTts() function exists in global_ai_assistant.php",
        "Function definition found in global_ai_assistant.php"
    );
} else {
    logTest(
        "Test 1.2: playGeminiTts() function existence",
        true,
        "Expected behavior: playGeminiTts() function does not exist",
        null
    );
}

// Test 1.3: Verify speakWithBrowserVoice() function does NOT exist
echo "Test 1.3: Checking for speakWithBrowserVoice() function existence...\n";
$speakWithBrowserVoicePattern = '/function\s+speakWithBrowserVoice\s*\(/';
$hasSpeakWithBrowserVoiceFunction = preg_match($speakWithBrowserVoicePattern, $globalAiContent);

if ($hasSpeakWithBrowserVoiceFunction) {
    logTest(
        "Test 1.3: speakWithBrowserVoice() function existence",
        false,
        "Bug detected: speakWithBrowserVoice() function exists in global_ai_assistant.php",
        "Function definition found in global_ai_assistant.php"
    );
} else {
    logTest(
        "Test 1.3: speakWithBrowserVoice() function existence",
        true,
        "Expected behavior: speakWithBrowserVoice() function does not exist",
        null
    );
}

// Test 1.4: Verify pickMaleVoice() function does NOT exist in global_ai_assistant.php
echo "Test 1.4: Checking for pickMaleVoice() function in global_ai_assistant.php...\n";
$pickMaleVoicePattern = '/function\s+pickMaleVoice\s*\(/';
$hasPickMaleVoiceFunction = preg_match($pickMaleVoicePattern, $globalAiContent);

if ($hasPickMaleVoiceFunction) {
    logTest(
        "Test 1.4: pickMaleVoice() function in global_ai_assistant.php",
        false,
        "Bug detected: pickMaleVoice() function exists in global_ai_assistant.php",
        "Function definition found in global_ai_assistant.php"
    );
} else {
    logTest(
        "Test 1.4: pickMaleVoice() function in global_ai_assistant.php",
        true,
        "Expected behavior: pickMaleVoice() function does not exist in global_ai_assistant.php",
        null
    );
}

// Test 1.5: Verify pickMaleVoice() function does NOT exist in index.php
echo "Test 1.5: Checking for pickMaleVoice() function in index.php...\n";
$indexFile = __DIR__ . '/../index.php';
$indexContent = file_get_contents($indexFile);
$hasPickMaleVoiceInIndex = preg_match($pickMaleVoicePattern, $indexContent);

if ($hasPickMaleVoiceInIndex) {
    logTest(
        "Test 1.5: pickMaleVoice() function in index.php",
        false,
        "Bug detected: pickMaleVoice() function exists in index.php",
        "Function definition found in index.php"
    );
} else {
    logTest(
        "Test 1.5: pickMaleVoice() function in index.php",
        true,
        "Expected behavior: pickMaleVoice() function does not exist in index.php",
        null
    );
}

// Test 1.6: Verify api_gemini_tts.php file does NOT exist
echo "Test 1.6: Checking for api_gemini_tts.php file existence...\n";
$apiGeminiTtsFile = __DIR__ . '/../api_gemini_tts.php';
$apiGeminiTtsExists = file_exists($apiGeminiTtsFile);

if ($apiGeminiTtsExists) {
    logTest(
        "Test 1.6: api_gemini_tts.php file existence",
        false,
        "Bug detected: api_gemini_tts.php file exists",
        "File found at: " . $apiGeminiTtsFile
    );
} else {
    logTest(
        "Test 1.6: api_gemini_tts.php file existence",
        true,
        "Expected behavior: api_gemini_tts.php file does not exist",
        null
    );
}

// Test 1.7: Verify Gemini TTS constants do NOT exist in config.php
echo "Test 1.7: Checking for Gemini TTS constants in config.php...\n";
$configFile = __DIR__ . '/../config.php';
$configContent = file_get_contents($configFile);

$geminiApiKeyPattern = '/define\s*\(\s*[\'"]GEMINI_API_KEY[\'"]/';
$geminiTtsModelPattern = '/define\s*\(\s*[\'"]GEMINI_TTS_MODEL[\'"]/';
$geminiTtsVoicePattern = '/define\s*\(\s*[\'"]GEMINI_TTS_VOICE[\'"]/';

$hasGeminiApiKey = preg_match($geminiApiKeyPattern, $configContent);
$hasGeminiTtsModel = preg_match($geminiTtsModelPattern, $configContent);
$hasGeminiTtsVoice = preg_match($geminiTtsVoicePattern, $configContent);

$hasGeminiConstants = $hasGeminiApiKey || $hasGeminiTtsModel || $hasGeminiTtsVoice;

if ($hasGeminiConstants) {
    $foundConstants = [];
    if ($hasGeminiApiKey) $foundConstants[] = "GEMINI_API_KEY";
    if ($hasGeminiTtsModel) $foundConstants[] = "GEMINI_TTS_MODEL";
    if ($hasGeminiTtsVoice) $foundConstants[] = "GEMINI_TTS_VOICE";
    
    logTest(
        "Test 1.7: Gemini TTS constants in config.php",
        false,
        "Bug detected: Gemini TTS constants exist in config.php",
        "Constants found: " . implode(", ", $foundConstants)
    );
} else {
    logTest(
        "Test 1.7: Gemini TTS constants in config.php",
        true,
        "Expected behavior: Gemini TTS constants do not exist in config.php",
        null
    );
}

// Test 1.8: Verify ELEVENLABS_FALLBACK_VOICE_ID constant does NOT exist in config.php
echo "Test 1.8: Checking for ELEVENLABS_FALLBACK_VOICE_ID constant in config.php...\n";
$elevenLabsFallbackPattern = '/define\s*\(\s*[\'"]ELEVENLABS_FALLBACK_VOICE_ID[\'"]/';
$hasElevenLabsFallback = preg_match($elevenLabsFallbackPattern, $configContent);

if ($hasElevenLabsFallback) {
    logTest(
        "Test 1.8: ELEVENLABS_FALLBACK_VOICE_ID constant in config.php",
        false,
        "Bug detected: ELEVENLABS_FALLBACK_VOICE_ID constant exists in config.php",
        "Constant found: ELEVENLABS_FALLBACK_VOICE_ID (Adam voice)"
    );
} else {
    logTest(
        "Test 1.8: ELEVENLABS_FALLBACK_VOICE_ID constant in config.php",
        true,
        "Expected behavior: ELEVENLABS_FALLBACK_VOICE_ID constant does not exist in config.php",
        null
    );
}

// Test 1.9: Verify index.php does NOT use browser Speech Synthesis
echo "Test 1.9: Checking for browser Speech Synthesis usage in index.php...\n";
$speechSynthesisPattern = '/SpeechSynthesisUtterance|speechSynthesis\.speak/';
$hasSpeechSynthesisInIndex = preg_match($speechSynthesisPattern, $indexContent);

if ($hasSpeechSynthesisInIndex) {
    logTest(
        "Test 1.9: Browser Speech Synthesis in index.php",
        false,
        "Bug detected: index.php uses browser Speech Synthesis",
        "SpeechSynthesisUtterance or speechSynthesis.speak found in index.php"
    );
} else {
    logTest(
        "Test 1.9: Browser Speech Synthesis in index.php",
        true,
        "Expected behavior: index.php does not use browser Speech Synthesis",
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
    echo "This confirms the bug exists: multiple voice service fallback mechanisms are present.\n";
    echo "\n";
    echo "Counterexamples found:\n";
    foreach ($testResults as $result) {
        if (!$result['passed'] && $result['counterexample']) {
            echo "  - {$result['name']}: {$result['counterexample']}\n";
        }
    }
    echo "\n";
    echo "Next steps:\n";
    echo "  1. Implement the fix (Tasks 3.1 - 3.9)\n";
    echo "  2. Re-run this test to verify the fix (Task 3.10)\n";
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
    echo "The system uses only ElevenLabs Steven voice without fallback mechanisms.\n";
    echo "\n";
    exit(0); // Exit with success code
}
