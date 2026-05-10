# Simplify Voice AI to ElevenLabs Steven - Bugfix Design

## Overview

This bugfix simplifies the voice AI system by removing unnecessary complexity. Currently, the system implements multiple fallback mechanisms (ElevenLabs with multiple voices, Gemini TTS, and browser Speech Synthesis), creating maintenance overhead and unnecessary code paths. The fix will streamline the implementation to use only ElevenLabs TTS with the Steven voice (voice ID: 9zOaLLJKBwYOwr8bOPDj), removing all other voice AI implementations and fallback logic.

## Glossary

- **Bug_Condition (C)**: The condition where the system uses multiple voice AI services with complex fallback logic instead of a single, simple implementation
- **Property (P)**: The desired behavior where the system uses only ElevenLabs TTS with Steven voice for all voice output
- **Preservation**: Existing voice playback functionality, animations, and user experience that must remain unchanged
- **playAiVoiceTts()**: The function in `global_ai_assistant.php` that orchestrates voice playback with multiple fallback attempts
- **playGeminiTts()**: The function in `global_ai_assistant.php` that calls Gemini TTS API (to be removed)
- **playElevenLabsTts()**: The function in `global_ai_assistant.php` that calls ElevenLabs TTS API (to be kept and simplified)
- **speakWithBrowserVoice()**: The function in `global_ai_assistant.php` that uses browser Speech Synthesis API (to be removed)
- **pickMaleVoice()**: The function in `global_ai_assistant.php` and `index.php` that selects male voices for browser Speech Synthesis (to be removed)
- **api_gemini_tts.php**: The PHP endpoint that provides Gemini TTS service (to be deleted)
- **api_elevenlabs_tts.php**: The PHP endpoint that provides ElevenLabs TTS service (to be preserved)

## Bug Details

### Bug Condition

The bug manifests when the voice AI system attempts to play voice output. The system implements unnecessary complexity with multiple fallback mechanisms: ElevenLabs Steven voice, ElevenLabs Adam voice, Gemini TTS, and browser Speech Synthesis. This creates maintenance overhead, increases code complexity, and provides no practical benefit since ElevenLabs Steven voice is reliable and sufficient.

**Formal Specification:**
```
FUNCTION isBugCondition(voicePlaybackAttempt)
  INPUT: voicePlaybackAttempt of type VoicePlaybackRequest
  OUTPUT: boolean
  
  RETURN (playAiVoiceTts attempts multiple voice services in sequence)
         OR (playGeminiTts function exists and is called)
         OR (speakWithBrowserVoice function exists and is called)
         OR (config.php contains Gemini TTS constants)
         OR (config.php contains ELEVENLABS_FALLBACK_VOICE_ID constant)
         OR (api_gemini_tts.php file exists)
         OR (users.php uses browser Speech Synthesis)
         OR (index.php uses browser Speech Synthesis)
END FUNCTION
```

### Examples

- **Example 1**: When `playAiVoiceTts()` is called in `global_ai_assistant.php`, it tries ElevenLabs Steven (9zOaLLJKBwYOwr8bOPDj), then ElevenLabs Adam (pNInz6obpgDQGcFmaJgB), then Gemini TTS, creating unnecessary API calls
- **Example 2**: When voice playback fails in `index.php` `speakStatus()` function, it falls back to browser Speech Synthesis with complex male voice selection logic
- **Example 3**: When voice playback fails in `index.php` `speakText()` function, it calls `speakWithBrowserVoice()` as a fallback
- **Example 4**: The `config.php` file defines GEMINI_API_KEY, GEMINI_TTS_MODEL, GEMINI_TTS_VOICE, and ELEVENLABS_FALLBACK_VOICE_ID constants that are no longer needed
- **Example 5**: The `api_gemini_tts.php` file exists in the codebase but is no longer needed
- **Example 6**: In `users.php`, when top-up is approved, the system uses browser Speech Synthesis with inline voice selection logic instead of ElevenLabs
- **Edge case**: If ElevenLabs API is temporarily unavailable, the system should simply not play voice rather than falling back to inferior alternatives

## Expected Behavior

### Preservation Requirements

**Unchanged Behaviors:**
- ElevenLabs TTS API calls through `api_elevenlabs_tts.php` must continue to work exactly as before
- AI orb speaking animation must continue to display when voice is playing
- Emoji animations triggered by voice output must continue to work
- Audio unlocking mechanism must continue to function
- Welcome/goodbye voice notifications must continue to play for users
- Admin voice status reports must continue to work
- Chat notification voice alerts must continue to function
- All visual UI elements related to voice (orb, status text, animations) must remain unchanged

**Scope:**
All voice playback functionality that does NOT involve the removed services (Gemini TTS, browser Speech Synthesis, ElevenLabs Adam voice) should be completely unaffected by this fix. This includes:
- ElevenLabs Steven voice API calls
- Voice playback success/failure handling
- Audio blob creation and playback
- Voice animation triggers
- All non-voice features of the application

## Hypothesized Root Cause

Based on the bug description, the root causes are:

1. **Over-Engineering**: The system was designed with multiple fallback mechanisms that are no longer necessary, as ElevenLabs Steven voice is reliable and sufficient for all use cases

2. **Legacy Code**: Gemini TTS and browser Speech Synthesis were likely implemented as fallbacks during development or testing, but were never removed after ElevenLabs proved reliable

3. **Configuration Bloat**: The `config.php` file contains constants for multiple voice services (Gemini, ElevenLabs fallback) that add complexity without providing value

4. **Code Duplication**: Multiple files (`global_ai_assistant.php`, `index.php`, `users.php`) implement their own voice playback logic with browser Speech Synthesis fallbacks, creating maintenance burden

## Correctness Properties

Property 1: Bug Condition - Single Voice Service

_For any_ voice playback request in the system, the fixed implementation SHALL use only ElevenLabs TTS with Steven voice (voice ID: 9zOaLLJKBwYOwr8bOPDj) without attempting any fallback services.

**Validates: Requirements 2.1, 2.2, 2.3**

Property 2: Preservation - Voice Playback Functionality

_For any_ voice playback request that successfully uses ElevenLabs Steven voice, the fixed implementation SHALL produce exactly the same audio output, animations, and user experience as the original implementation, preserving all existing voice playback functionality.

**Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8**

## Fix Implementation

### Changes Required

Assuming our root cause analysis is correct:

**File 1**: `parking/global_ai_assistant.php`

**Function**: `playAiVoiceTts()`

**Specific Changes**:
1. **Simplify playAiVoiceTts()**: Remove all fallback logic, keep only the single ElevenLabs Steven voice call
   - Remove the second `playElevenLabsTts()` call with Adam voice ID
   - Remove the `playGeminiTts()` call
   - Change function to directly call `playElevenLabsTts()` with Steven voice ID and return the result

2. **Remove playGeminiTts() function**: Delete the entire function definition (lines ~150-180)

3. **Remove speakWithBrowserVoice() function**: Delete the entire function definition (lines ~200-215)

4. **Remove pickMaleVoice() function**: Delete the entire function definition (lines ~135-150)

5. **Update speakText() function**: Remove fallback to `speakWithBrowserVoice()`
   - Keep the `playAiVoiceTts()` call
   - Remove the `.then()` fallback that calls `speakWithBrowserVoice()`
   - Simply call `stopSpeaking()` if playback fails

**File 2**: `parking/config.php`

**Specific Changes**:
1. **Remove Gemini TTS constants**: Delete the following constant definitions:
   - `GEMINI_API_KEY` (lines ~85-87)
   - `GEMINI_TTS_MODEL` (lines ~88-90)
   - `GEMINI_TTS_VOICE` (lines ~91-93)

2. **Remove ElevenLabs fallback constant**: Delete the `ELEVENLABS_FALLBACK_VOICE_ID` constant definition (lines ~100-102)

3. **Keep ElevenLabs Steven constants**: Preserve all other ElevenLabs constants:
   - `ELEVENLABS_API_KEY`
   - `ELEVENLABS_VOICE_ID` (Steven voice)
   - `ELEVENLABS_TTS_MODEL`
   - `ELEVENLABS_OUTPUT_FORMAT`

**File 3**: `parking/api_gemini_tts.php`

**Specific Changes**:
1. **Delete entire file**: Remove the file from the codebase as it is no longer needed

**File 4**: `parking/index.php`

**Function**: `speakStatus()`, `speakText()`, `pickMaleVoice()`

**Specific Changes**:
1. **Update speakStatus() function**: Remove browser Speech Synthesis fallback logic
   - Keep the check for `playAiVoiceTts` function existence
   - Remove the entire `SpeechSynthesisUtterance` fallback block (lines ~520-535)
   - If `playAiVoiceTts` is not available, simply show an alert and return

2. **Update speakText() function**: Remove browser Speech Synthesis fallback logic
   - Keep the check for `playAiVoiceTts` function existence
   - Remove the entire `SpeechSynthesisUtterance` fallback block (lines ~570-585)
   - If `playAiVoiceTts` is not available, simply return without speaking

3. **Remove pickMaleVoice() function**: Delete the entire function definition (lines ~555-570)

**File 5**: `parking/users.php`

**Specific Changes**:
1. **Remove inline voice script**: Delete the `$voiceScript` variable and its inline JavaScript that uses browser Speech Synthesis (lines ~30-50)
   - The global AI assistant will handle voice notifications through the unified event system
   - No need for page-specific voice implementation

## Testing Strategy

### Validation Approach

The testing strategy follows a two-phase approach: first, surface counterexamples that demonstrate the bug on unfixed code (multiple fallback attempts), then verify the fix works correctly (single ElevenLabs call) and preserves existing behavior (same voice output and animations).

### Exploratory Bug Condition Checking

**Goal**: Surface counterexamples that demonstrate the bug BEFORE implementing the fix. Confirm that the system currently uses multiple fallback mechanisms unnecessarily.

**Test Plan**: Inspect the code to verify that multiple voice services are attempted in sequence. Run voice playback tests on the UNFIXED code to observe the fallback behavior and confirm the root cause.

**Test Cases**:
1. **Multiple Fallback Test**: Call `playAiVoiceTts()` and observe that it attempts ElevenLabs Steven, then Adam, then Gemini TTS (will show multiple attempts on unfixed code)
2. **Gemini TTS Existence Test**: Verify that `playGeminiTts()` function exists in `global_ai_assistant.php` (will exist on unfixed code)
3. **Browser Fallback Test**: Call voice playback in `index.php` and observe browser Speech Synthesis fallback (will occur on unfixed code)
4. **Config Bloat Test**: Verify that `config.php` contains Gemini and fallback constants (will exist on unfixed code)
5. **File Existence Test**: Verify that `api_gemini_tts.php` file exists (will exist on unfixed code)

**Expected Counterexamples**:
- `playAiVoiceTts()` attempts multiple voice services in sequence
- Possible causes: over-engineering, legacy code, lack of cleanup after ElevenLabs proved reliable

### Fix Checking

**Goal**: Verify that for all inputs where the bug condition holds (voice playback requests), the fixed function produces the expected behavior (single ElevenLabs Steven call).

**Pseudocode:**
```
FOR ALL voicePlaybackRequest WHERE isBugCondition(voicePlaybackRequest) DO
  result := playAiVoiceTts_fixed(voicePlaybackRequest.text)
  ASSERT result uses only ElevenLabs Steven voice
  ASSERT result does NOT attempt Gemini TTS
  ASSERT result does NOT attempt browser Speech Synthesis
  ASSERT result does NOT attempt ElevenLabs Adam voice
END FOR
```

### Preservation Checking

**Goal**: Verify that for all inputs where voice playback succeeds with ElevenLabs Steven, the fixed function produces the same result as the original function (same audio, same animations, same user experience).

**Pseudocode:**
```
FOR ALL voicePlaybackRequest WHERE ElevenLabsStevenSucceeds(voicePlaybackRequest) DO
  originalResult := playAiVoiceTts_original(voicePlaybackRequest.text)
  fixedResult := playAiVoiceTts_fixed(voicePlaybackRequest.text)
  ASSERT originalResult.audioOutput = fixedResult.audioOutput
  ASSERT originalResult.animations = fixedResult.animations
  ASSERT originalResult.userExperience = fixedResult.userExperience
END FOR
```

**Testing Approach**: Property-based testing is recommended for preservation checking because:
- It generates many test cases automatically across different voice text inputs
- It catches edge cases that manual unit tests might miss (empty strings, special characters, long text)
- It provides strong guarantees that behavior is unchanged for all successful voice playback scenarios

**Test Plan**: Observe behavior on UNFIXED code first for successful ElevenLabs Steven voice playback, then write property-based tests capturing that exact behavior (audio output, orb animation, emoji triggers).

**Test Cases**:
1. **Audio Output Preservation**: Verify that voice audio from ElevenLabs Steven sounds identical after fix
2. **Animation Preservation**: Verify that AI orb speaking animation continues to work correctly
3. **Emoji Preservation**: Verify that emoji animations triggered by voice type continue to work
4. **Status Text Preservation**: Verify that "Sistem Berbicara..." status text appears and disappears correctly
5. **Welcome/Goodbye Preservation**: Verify that welcome and goodbye notifications with voice continue to work
6. **Chat Notification Preservation**: Verify that "Ada pesan baru" voice notification continues to work

### Unit Tests

- Test that `playAiVoiceTts()` calls only `playElevenLabsTts()` with Steven voice ID
- Test that `playAiVoiceTts()` returns true when ElevenLabs succeeds
- Test that `playAiVoiceTts()` returns false when ElevenLabs fails (without attempting fallbacks)
- Test that `playGeminiTts()` function does not exist after fix
- Test that `speakWithBrowserVoice()` function does not exist after fix
- Test that `pickMaleVoice()` function does not exist after fix
- Test that `api_gemini_tts.php` file does not exist after fix
- Test that Gemini constants do not exist in `config.php` after fix
- Test that `ELEVENLABS_FALLBACK_VOICE_ID` constant does not exist in `config.php` after fix

### Property-Based Tests

- Generate random voice text inputs and verify that only ElevenLabs Steven is called (no fallbacks)
- Generate random voice text inputs and verify that successful playback produces same audio output as before
- Generate random voice text inputs and verify that animations trigger correctly
- Test across many scenarios that no Gemini TTS or browser Speech Synthesis calls occur

### Integration Tests

- Test full voice playback flow from user action to audio output
- Test welcome notification with voice in different UI contexts
- Test goodbye notification with voice in different UI contexts
- Test admin status report voice playback
- Test chat notification voice playback
- Test that all voice features work correctly with only ElevenLabs Steven voice
