# Bugfix Requirements Document

## Introduction

The parking project currently implements a complex voice AI system with multiple fallback mechanisms including ElevenLabs TTS (with multiple voice IDs), Gemini TTS, and browser Speech Synthesis. This complexity is unnecessary and creates maintenance overhead. The system should be simplified to use only ElevenLabs TTS with the Steven voice (voice ID: 9zOaLLJKBwYOwr8bOPDj), removing all other voice AI implementations and fallback mechanisms.

## Bug Analysis

### Current Behavior (Defect)

1.1 WHEN the system attempts to play voice output THEN the system tries multiple voice AI services in sequence (ElevenLabs Steven, ElevenLabs Adam, Gemini TTS, browser Speech Synthesis)

1.2 WHEN voice playback is triggered THEN the system includes unnecessary fallback logic with `playGeminiTts()` function calls

1.3 WHEN voice playback is triggered THEN the system includes unnecessary fallback logic with `speakWithBrowserVoice()` function calls using browser Speech Synthesis API

1.4 WHEN the system is configured THEN config.php contains Gemini TTS configuration constants (GEMINI_API_KEY, GEMINI_TTS_MODEL, GEMINI_TTS_VOICE)

1.5 WHEN the system is configured THEN config.php contains ElevenLabs fallback voice ID constant (ELEVENLABS_FALLBACK_VOICE_ID) for Adam voice

1.6 WHEN the codebase exists THEN api_gemini_tts.php file exists and provides Gemini TTS endpoint

1.7 WHEN voice playback is triggered in users.php THEN the system uses browser Speech Synthesis with male voice selection logic

1.8 WHEN voice playback is triggered in index.php THEN the system uses browser Speech Synthesis with male voice selection logic

### Expected Behavior (Correct)

2.1 WHEN the system attempts to play voice output THEN the system SHALL use only ElevenLabs TTS with Steven voice (voice ID: 9zOaLLJKBwYOwr8bOPDj)

2.2 WHEN voice playback is triggered THEN the system SHALL NOT attempt to use Gemini TTS

2.3 WHEN voice playback is triggered THEN the system SHALL NOT attempt to use browser Speech Synthesis as a fallback

2.4 WHEN the system is configured THEN config.php SHALL NOT contain Gemini TTS configuration constants

2.5 WHEN the system is configured THEN config.php SHALL NOT contain ElevenLabs fallback voice ID constant

2.6 WHEN the codebase exists THEN api_gemini_tts.php file SHALL NOT exist

2.7 WHEN voice playback is triggered in users.php THEN the system SHALL use only ElevenLabs TTS with Steven voice

2.8 WHEN voice playback is triggered in index.php THEN the system SHALL use only ElevenLabs TTS with Steven voice

### Unchanged Behavior (Regression Prevention)

3.1 WHEN ElevenLabs TTS API is called THEN the system SHALL CONTINUE TO use the api_elevenlabs_tts.php endpoint

3.2 WHEN voice output is played THEN the system SHALL CONTINUE TO display the speaking animation on the AI orb

3.3 WHEN voice output is played THEN the system SHALL CONTINUE TO trigger emoji animations based on message type

3.4 WHEN ElevenLabs TTS is configured THEN the system SHALL CONTINUE TO use the Steven voice ID (9zOaLLJKBwYOwr8bOPDj) as the primary voice

3.5 WHEN the system handles voice playback THEN the system SHALL CONTINUE TO use the same audio unlocking mechanism

3.6 WHEN voice notifications are triggered THEN the system SHALL CONTINUE TO speak welcome/goodbye messages for users

3.7 WHEN admin views the dashboard THEN the system SHALL CONTINUE TO provide voice status reports

3.8 WHEN chat notifications arrive THEN the system SHALL CONTINUE TO play voice notifications for new messages
