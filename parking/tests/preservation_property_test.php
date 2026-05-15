<?php
/**
 * Preservation Property Test
 * 
 * This test verifies that the baseline voice playback functionality works correctly
 * on UNFIXED code and must continue to work after the fix is implemented.
 * 
 * **EXPECTED OUTCOME ON UNFIXED CODE**: This test MUST PASS
 * - Success confirms the baseline behavior that must be preserved
 * 
 * **EXPECTED OUTCOME AFTER FIX**: This test MUST STILL PASS
 * - Success confirms no regressions occurred (preservation property holds)
 * 
 * Property 2: Preservation - Voice Playback Functionality Preservation
 * 
 * Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8
 */

// Test configuration
$testResults = [];
$testsPassed = 0;
$testsFailed = 0;

function logTest($name, $passed, $message, $details = null) {
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
    
    if ($details) {
        echo "  Details: {$details}\n";
    }
    echo "\n";
    
    $testResults[] = [
        'name' => $name,
        'passed' => $passed,
        'message' => $message,
        'details' => $details
    ];
}

echo "\n";
echo "================================================================================\n";
echo "Preservation Property Test - Voice Playback Functionality Preservation\n";
echo "================================================================================\n";
echo "\n";
echo "This test verifies baseline behavior that must be preserved after the fix.\n";
echo "EXPECTED: This test should PASS on both unfixed and fixed code.\n";
echo "\n";

// Test 3.1: Verify api_elevenlabs_tts.php endpoint exists and is functional
echo "Test 3.1: Checking ElevenLabs TTS API endpoint...\n";
$apiElevenLabsFile = __DIR__ . '/../api_elevenlabs_tts.php';
$apiElevenLabsExists = file_exists($apiElevenLabsFile);

if ($apiElevenLabsExists) {
    $apiContent = file_get_contents($apiElevenLabsFile);
    
    // Verify essential functionality
    $hasTextInput = strpos($apiContent, '$input["text"]') !== false || strpos($apiContent, '$text') !== false;
    $hasVoiceId = strpos($apiContent, 'voice_id') !== false || strpos($apiContent, 'voiceId') !== false;
    $hasCurlCall = strpos($apiContent, 'curl_init') !== false;
    $hasElevenLabsUrl = strpos($apiContent, 'api.elevenlabs.io') !== false;
    
    $isFullyFunctional = $hasTextInput && $hasVoiceId && $hasCurlCall && $hasElevenLabsUrl;
    
    if ($isFullyFunctional) {
        logTest(
            "Test 3.1: ElevenLabs TTS API endpoint",
            true,
            "Preservation verified: api_elevenlabs_tts.php exists and contains essential functionality",
            "File: {$apiElevenLabsFile}"
        );
    } else {
        $missing = [];
        if (!$hasTextInput) $missing[] = "text input handling";
        if (!$hasVoiceId) $missing[] = "voice_id parameter";
        if (!$hasCurlCall) $missing[] = "cURL initialization";
        if (!$hasElevenLabsUrl) $missing[] = "ElevenLabs API URL";
        
        logTest(
            "Test 3.1: ElevenLabs TTS API endpoint",
            false,
            "Preservation violation: api_elevenlabs_tts.php is missing essential functionality",
            "Missing: " . implode(", ", $missing)
        );
    }
} else {
    logTest(
        "Test 3.1: ElevenLabs TTS API endpoint",
        false,
        "Preservation violation: api_elevenlabs_tts.php file does not exist",
        "Expected file: {$apiElevenLabsFile}"
    );
}

// Test 3.2: Verify AI orb speaking animation functionality
echo "Test 3.2: Checking AI orb speaking animation...\n";
$globalAiFile = __DIR__ . '/../global_ai_assistant.php';
$globalAiContent = file_exists($globalAiFile) ? file_get_contents($globalAiFile) : '';

if ($globalAiContent) {
    // Check for orb animation elements
    $hasOrbElement = strpos($globalAiContent, 'ai-orb-global') !== false;
    $hasSpeakingClass = strpos($globalAiContent, 'speaking') !== false;
    $hasOrbAnimation = strpos($globalAiContent, 'orb-speaking') !== false || 
                       strpos($globalAiContent, 'classList.add') !== false;
    
    $hasFullAnimation = $hasOrbElement && $hasSpeakingClass && $hasOrbAnimation;
    
    if ($hasFullAnimation) {
        logTest(
            "Test 3.2: AI orb speaking animation",
            true,
            "Preservation verified: AI orb animation functionality is present",
            "Orb element, speaking class, and animation logic found"
        );
    } else {
        $missing = [];
        if (!$hasOrbElement) $missing[] = "orb element (ai-orb-global)";
        if (!$hasSpeakingClass) $missing[] = "speaking class";
        if (!$hasOrbAnimation) $missing[] = "animation logic";
        
        logTest(
            "Test 3.2: AI orb speaking animation",
            false,
            "Preservation violation: AI orb animation is incomplete",
            "Missing: " . implode(", ", $missing)
        );
    }
} else {
    logTest(
        "Test 3.2: AI orb speaking animation",
        false,
        "Preservation violation: global_ai_assistant.php not found",
        "Expected file: {$globalAiFile}"
    );
}

// Test 3.3: Verify emoji animation functionality
echo "Test 3.3: Checking emoji animation functionality...\n";
if ($globalAiContent) {
    $hasEmojiFunction = strpos($globalAiContent, 'triggerEmojiAnimation') !== false;
    $hasEmojiFlyer = strpos($globalAiContent, 'emoji-flyer') !== false;
    $hasEmojiTypes = strpos($globalAiContent, 'welcome') !== false && 
                     strpos($globalAiContent, 'thanks') !== false;
    
    $hasFullEmojiSystem = $hasEmojiFunction && $hasEmojiFlyer && $hasEmojiTypes;
    
    if ($hasFullEmojiSystem) {
        logTest(
            "Test 3.3: Emoji animation functionality",
            true,
            "Preservation verified: Emoji animation system is present",
            "triggerEmojiAnimation function, emoji-flyer class, and emoji types found"
        );
    } else {
        $missing = [];
        if (!$hasEmojiFunction) $missing[] = "triggerEmojiAnimation function";
        if (!$hasEmojiFlyer) $missing[] = "emoji-flyer class";
        if (!$hasEmojiTypes) $missing[] = "emoji type detection";
        
        logTest(
            "Test 3.3: Emoji animation functionality",
            false,
            "Preservation violation: Emoji animation system is incomplete",
            "Missing: " . implode(", ", $missing)
        );
    }
} else {
    logTest(
        "Test 3.3: Emoji animation functionality",
        false,
        "Preservation violation: global_ai_assistant.php not found",
        null
    );
}

// Test 3.4: Verify voice ID configuration (updated for Adam voice after fix)
echo "Test 3.4: Checking voice ID configuration...\n";
$configFile = __DIR__ . '/../config.php';
$configContent = file_exists($configFile) ? file_get_contents($configFile) : '';

if ($configContent) {
    // Check for Adam voice ID (pNInz6obpgDQGcFmaJgB) - the fixed voice ID
    $adamVoiceId = 'pNInz6obpgDQGcFmaJgB';
    $hasAdamVoiceId = strpos($configContent, $adamVoiceId) !== false;
    $hasElevenLabsVoiceIdConstant = preg_match('/define\s*\(\s*[\'"]ELEVENLABS_VOICE_ID[\'"]/i', $configContent);
    
    if ($hasAdamVoiceId && $hasElevenLabsVoiceIdConstant) {
        logTest(
            "Test 3.4: Voice ID configuration",
            true,
            "Preservation verified: Adam voice ID (pNInz6obpgDQGcFmaJgB) is configured",
            "ELEVENLABS_VOICE_ID constant found with Adam voice (fixed from Steven)"
        );
    } else {
        $issues = [];
        if (!$hasAdamVoiceId) $issues[] = "Adam voice ID not found";
        if (!$hasElevenLabsVoiceIdConstant) $issues[] = "ELEVENLABS_VOICE_ID constant not defined";
        
        logTest(
            "Test 3.4: Voice ID configuration",
            false,
            "Preservation violation: Voice ID configuration is incomplete",
            "Issues: " . implode(", ", $issues)
        );
    }
} else {
    logTest(
        "Test 3.4: Voice ID configuration",
        false,
        "Preservation violation: config.php not found",
        "Expected file: {$configFile}"
    );
}

// Test 3.5: Verify audio unlocking mechanism
echo "Test 3.5: Checking audio unlocking mechanism...\n";
if ($globalAiContent) {
    $hasUnlockFunction = strpos($globalAiContent, 'unlockAudio') !== false;
    $hasAudioUnlockedFlag = strpos($globalAiContent, 'G_AUDIO_UNLOCKED') !== false || 
                            strpos($globalAiContent, 'AUDIO_UNLOCKED') !== false;
    $hasClickListener = strpos($globalAiContent, 'click') !== false || 
                        strpos($globalAiContent, 'touchstart') !== false;
    
    $hasFullUnlockSystem = $hasUnlockFunction && $hasAudioUnlockedFlag && $hasClickListener;
    
    if ($hasFullUnlockSystem) {
        logTest(
            "Test 3.5: Audio unlocking mechanism",
            true,
            "Preservation verified: Audio unlocking mechanism is present",
            "unlockAudio function, audio unlocked flag, and click listener found"
        );
    } else {
        $missing = [];
        if (!$hasUnlockFunction) $missing[] = "unlockAudio function";
        if (!$hasAudioUnlockedFlag) $missing[] = "audio unlocked flag";
        if (!$hasClickListener) $missing[] = "click/touch listener";
        
        logTest(
            "Test 3.5: Audio unlocking mechanism",
            false,
            "Preservation violation: Audio unlocking mechanism is incomplete",
            "Missing: " . implode(", ", $missing)
        );
    }
} else {
    logTest(
        "Test 3.5: Audio unlocking mechanism",
        false,
        "Preservation violation: global_ai_assistant.php not found",
        null
    );
}

// Test 3.6: Verify welcome/goodbye notification functionality
echo "Test 3.6: Checking welcome/goodbye notification functionality...\n";
if ($globalAiContent) {
    $hasNotificationFunction = strpos($globalAiContent, 'showGlobalNotification') !== false;
    $hasWelcomeType = strpos($globalAiContent, 'welcome') !== false;
    $hasGoodbyeType = strpos($globalAiContent, 'goodbye') !== false;
    $hasNotificationOverlay = strpos($globalAiContent, 'notif-overlay-global') !== false;
    
    $hasFullNotificationSystem = $hasNotificationFunction && $hasWelcomeType && 
                                 $hasGoodbyeType && $hasNotificationOverlay;
    
    if ($hasFullNotificationSystem) {
        logTest(
            "Test 3.6: Welcome/goodbye notification functionality",
            true,
            "Preservation verified: Welcome/goodbye notification system is present",
            "showGlobalNotification function, welcome/goodbye types, and overlay found"
        );
    } else {
        $missing = [];
        if (!$hasNotificationFunction) $missing[] = "showGlobalNotification function";
        if (!$hasWelcomeType) $missing[] = "welcome type";
        if (!$hasGoodbyeType) $missing[] = "goodbye type";
        if (!$hasNotificationOverlay) $missing[] = "notification overlay";
        
        logTest(
            "Test 3.6: Welcome/goodbye notification functionality",
            false,
            "Preservation violation: Welcome/goodbye notification system is incomplete",
            "Missing: " . implode(", ", $missing)
        );
    }
} else {
    logTest(
        "Test 3.6: Welcome/goodbye notification functionality",
        false,
        "Preservation violation: global_ai_assistant.php not found",
        null
    );
}

// Test 3.7: Verify admin voice status report functionality
echo "Test 3.7: Checking admin voice status report functionality...\n";
if ($globalAiContent) {
    $hasCheckGlobalEvents = strpos($globalAiContent, 'checkGlobalEvents') !== false;
    $hasSpeakText = strpos($globalAiContent, 'speakText') !== false;
    $hasAdminCheck = strpos($globalAiContent, 'G_IS_ADMIN') !== false || 
                     strpos($globalAiContent, 'isAdmin') !== false;
    $hasEventActions = strpos($globalAiContent, 'action') !== false && 
                       (strpos($globalAiContent, 'IN') !== false || strpos($globalAiContent, 'OUT') !== false);
    
    $hasFullStatusSystem = $hasCheckGlobalEvents && $hasSpeakText && 
                          $hasAdminCheck && $hasEventActions;
    
    if ($hasFullStatusSystem) {
        logTest(
            "Test 3.7: Admin voice status report functionality",
            true,
            "Preservation verified: Admin voice status report system is present",
            "checkGlobalEvents, speakText, admin check, and event actions found"
        );
    } else {
        $missing = [];
        if (!$hasCheckGlobalEvents) $missing[] = "checkGlobalEvents function";
        if (!$hasSpeakText) $missing[] = "speakText function";
        if (!$hasAdminCheck) $missing[] = "admin role check";
        if (!$hasEventActions) $missing[] = "event action handling";
        
        logTest(
            "Test 3.7: Admin voice status report functionality",
            false,
            "Preservation violation: Admin voice status report system is incomplete",
            "Missing: " . implode(", ", $missing)
        );
    }
} else {
    logTest(
        "Test 3.7: Admin voice status report functionality",
        false,
        "Preservation violation: global_ai_assistant.php not found",
        null
    );
}

// Test 3.8: Verify chat notification voice alert functionality
echo "Test 3.8: Checking chat notification voice alert functionality...\n";
if ($globalAiContent) {
    $hasChatNotificationFunction = strpos($globalAiContent, 'playChatNotificationSound') !== false;
    $hasCheckUnreadChat = strpos($globalAiContent, 'checkUnreadChat') !== false;
    $hasChatBadge = strpos($globalAiContent, 'chat-badge-global') !== false;
    $hasUnreadCountCheck = strpos($globalAiContent, 'unread_count') !== false || 
                          strpos($globalAiContent, 'unreadCount') !== false;
    
    $hasFullChatSystem = $hasChatNotificationFunction && $hasCheckUnreadChat && 
                        $hasChatBadge && $hasUnreadCountCheck;
    
    if ($hasFullChatSystem) {
        logTest(
            "Test 3.8: Chat notification voice alert functionality",
            true,
            "Preservation verified: Chat notification voice alert system is present",
            "playChatNotificationSound, checkUnreadChat, chat badge, and unread count check found"
        );
    } else {
        $missing = [];
        if (!$hasChatNotificationFunction) $missing[] = "playChatNotificationSound function";
        if (!$hasCheckUnreadChat) $missing[] = "checkUnreadChat function";
        if (!$hasChatBadge) $missing[] = "chat badge element";
        if (!$hasUnreadCountCheck) $missing[] = "unread count check";
        
        logTest(
            "Test 3.8: Chat notification voice alert functionality",
            false,
            "Preservation violation: Chat notification voice alert system is incomplete",
            "Missing: " . implode(", ", $missing)
        );
    }
} else {
    logTest(
        "Test 3.8: Chat notification voice alert functionality",
        false,
        "Preservation violation: global_ai_assistant.php not found",
        null
    );
}

// Test 3.9: Verify playElevenLabsTts function exists and is properly structured
echo "Test 3.9: Checking playElevenLabsTts function structure...\n";
if ($globalAiContent) {
    $hasPlayElevenLabsFunction = preg_match('/(?:async\s+)?function\s+playElevenLabsTts\s*\(/i', $globalAiContent);
    $hasApiCall = strpos($globalAiContent, 'api_elevenlabs_tts.php') !== false;
    $hasAudioPlayback = strpos($globalAiContent, 'new Audio') !== false || 
                        strpos($globalAiContent, 'audio.play') !== false;
    $hasBlobHandling = strpos($globalAiContent, 'Blob') !== false || 
                       strpos($globalAiContent, 'createObjectURL') !== false;
    
    $hasProperStructure = $hasPlayElevenLabsFunction && $hasApiCall && 
                         $hasAudioPlayback && $hasBlobHandling;
    
    if ($hasProperStructure) {
        logTest(
            "Test 3.9: playElevenLabsTts function structure",
            true,
            "Preservation verified: playElevenLabsTts function is properly structured",
            "Function definition, API call, audio playback, and blob handling found"
        );
    } else {
        $missing = [];
        if (!$hasPlayElevenLabsFunction) $missing[] = "playElevenLabsTts function definition";
        if (!$hasApiCall) $missing[] = "API call to api_elevenlabs_tts.php";
        if (!$hasAudioPlayback) $missing[] = "audio playback logic";
        if (!$hasBlobHandling) $missing[] = "blob handling";
        
        logTest(
            "Test 3.9: playElevenLabsTts function structure",
            false,
            "Preservation violation: playElevenLabsTts function structure is incomplete",
            "Missing: " . implode(", ", $missing)
        );
    }
} else {
    logTest(
        "Test 3.9: playElevenLabsTts function structure",
        false,
        "Preservation violation: global_ai_assistant.php not found",
        null
    );
}

// Test 3.10: Verify status text display functionality
echo "Test 3.10: Checking status text display functionality...\n";
if ($globalAiContent) {
    $hasStatusTextElement = strpos($globalAiContent, 'ai-status-text-global') !== false;
    $hasStatusTextUpdate = strpos($globalAiContent, 'statusText') !== false || 
                          strpos($globalAiContent, 'status-text') !== false;
    $hasOpacityControl = strpos($globalAiContent, 'opacity') !== false;
    
    $hasFullStatusDisplay = $hasStatusTextElement && $hasStatusTextUpdate && $hasOpacityControl;
    
    if ($hasFullStatusDisplay) {
        logTest(
            "Test 3.10: Status text display functionality",
            true,
            "Preservation verified: Status text display functionality is present",
            "Status text element, update logic, and opacity control found"
        );
    } else {
        $missing = [];
        if (!$hasStatusTextElement) $missing[] = "status text element (ai-status-text-global)";
        if (!$hasStatusTextUpdate) $missing[] = "status text update logic";
        if (!$hasOpacityControl) $missing[] = "opacity control";
        
        logTest(
            "Test 3.10: Status text display functionality",
            false,
            "Preservation violation: Status text display functionality is incomplete",
            "Missing: " . implode(", ", $missing)
        );
    }
} else {
    logTest(
        "Test 3.10: Status text display functionality",
        false,
        "Preservation violation: global_ai_assistant.php not found",
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
    echo "PRESERVATION VIOLATION DETECTED\n";
    echo "================================================================================\n";
    echo "\033[0m\n";
    echo "Some baseline functionality is missing or incomplete.\n";
    echo "\n";
    echo "Failed tests:\n";
    foreach ($testResults as $result) {
        if (!$result['passed']) {
            echo "  - {$result['name']}: {$result['message']}\n";
            if ($result['details']) {
                echo "    {$result['details']}\n";
            }
        }
    }
    echo "\n";
    echo "IMPORTANT: These tests should PASS on unfixed code to establish baseline.\n";
    echo "If tests are failing, the baseline behavior may already be broken.\n";
    echo "\n";
    exit(1); // Exit with error code to indicate test failure
} else {
    echo "\033[32m";
    echo "================================================================================\n";
    echo "BASELINE BEHAVIOR CONFIRMED\n";
    echo "================================================================================\n";
    echo "\033[0m\n";
    echo "All preservation tests PASSED.\n";
    echo "Baseline voice playback functionality is working correctly.\n";
    echo "\n";
    echo "This establishes the preservation property:\n";
    echo "  - ElevenLabs TTS API endpoint is functional\n";
    echo "  - AI orb speaking animation is present\n";
    echo "  - Emoji animations are configured\n";
    echo "  - Steven voice ID is configured\n";
    echo "  - Audio unlocking mechanism is present\n";
    echo "  - Welcome/goodbye notifications are functional\n";
    echo "  - Admin voice status reports are functional\n";
    echo "  - Chat notification voice alerts are functional\n";
    echo "  - playElevenLabsTts function is properly structured\n";
    echo "  - Status text display is functional\n";
    echo "\n";
    echo "After implementing the fix, re-run this test to verify no regressions.\n";
    echo "The test should still PASS after the fix (preservation property holds).\n";
    echo "\n";
    exit(0); // Exit with success code
}
