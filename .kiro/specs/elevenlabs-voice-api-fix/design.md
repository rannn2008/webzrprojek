# ElevenLabs Voice API Fix Bugfix Design

## Overview

Voice AI Steven dari ElevenLabs tidak berfungsi karena error HTTP 402 "payment_required" yang terjadi ketika free account mencoba menggunakan library voice (Voice ID: 9zOaLLJKBwYOwr8bOPDj). Masalah ini muncul setelah simplifikasi voice AI system yang menghapus semua fallback mechanism. Solusi utama adalah mengganti voice ID Steven dengan voice ID yang kompatibel dengan free account, atau mengimplementasikan fallback mechanism yang robust untuk menangani error payment_required.

## Glossary

- **Bug_Condition (C)**: Kondisi yang memicu bug - ketika sistem mencoba menggunakan library voice Steven dengan free ElevenLabs account
- **Property (P)**: Perilaku yang diinginkan - voice AI harus berhasil menghasilkan audio tanpa error HTTP 402
- **Preservation**: Fungsionalitas voice AI yang harus tetap berjalan seperti sebelumnya (animasi orb, emoji, kualitas suara)
- **playElevenLabsTts()**: Fungsi dalam `global_ai_assistant.php` yang memanggil ElevenLabs TTS API melalui `api_elevenlabs_tts.php`
- **Library Voice**: Voice yang tersedia di ElevenLabs library yang memerlukan paid subscription untuk API access
- **Generated Voice**: Voice yang dibuat user sendiri di ElevenLabs yang dapat digunakan dengan free account
- **Free Account Limitation**: Pembatasan ElevenLabs yang tidak mengizinkan free user menggunakan library voices via API

## Bug Details

### Bug Condition

Bug terjadi ketika sistem parking mencoba menggunakan ElevenLabs TTS API dengan Voice ID Steven (9zOaLLJKBwYOwr8bOPDj) yang merupakan library voice, sementara API key yang digunakan adalah free account yang tidak memiliki akses ke library voices via API.

**Formal Specification:**
```
FUNCTION isBugCondition(input)
  INPUT: input of type TtsRequest
  OUTPUT: boolean
  
  RETURN input.voice_id == "9zOaLLJKBwYOwr8bOPDj"
         AND account_type == "free"
         AND voice_type == "library_voice"
         AND api_access_required == true
END FUNCTION
```

### Examples

- **Contoh 1**: User mengklik AI orb untuk test suara → sistem memanggil `playElevenLabsTts("Halo User, asisten AI SpotFinder siap membantu Anda di halaman mana pun!", "9zOaLLJKBwYOwr8bOPDj")` → API mengembalikan HTTP 402 → tidak ada suara yang keluar
- **Contoh 2**: Kendaraan masuk parking → sistem memanggil `speakText("Selamat datang John. Silahkan parkir.")` → `playElevenLabsTts()` dipanggil dengan Steven voice → HTTP 402 error → notifikasi welcome muncul tapi tanpa suara
- **Contoh 3**: Admin menerima notifikasi kendaraan masuk → sistem memanggil `speakText("Sistem: Kendaraan John telah masuk.")` → HTTP 402 error → admin tidak mendengar notifikasi suara
- **Edge case**: Sistem mencoba multiple concurrent requests → HTTP 429 rate limit error juga muncul bersamaan dengan HTTP 402

## Expected Behavior

### Preservation Requirements

**Unchanged Behaviors:**
- Animasi AI orb speaking harus tetap muncul ketika voice sedang diputar
- Emoji animations harus tetap trigger berdasarkan tipe pesan (welcome, thanks, money, alert)
- Notifikasi visual (overlay welcome/goodbye) harus tetap berfungsi dengan timing yang sama
- Voice settings (stability: 0.55, similarity_boost: 0.85, use_speaker_boost: true) harus tetap digunakan
- Chat notification sound dan unread badge functionality harus tetap berfungsi
- Status text "Sistem Berbicara..." harus tetap muncul dan menghilang dengan timing yang benar

**Scope:**
Semua aspek visual dan interaksi UI yang tidak terkait langsung dengan audio output harus tetap berfungsi persis seperti sebelumnya. Ini termasuk:
- Mouse click interactions pada AI orb
- Visual feedback dan animations
- Notification overlays dan timing
- Chat badge notifications
- Global event checking dan status updates

## Hypothesized Root Cause

Berdasarkan analisis debug log dan error messages, root cause yang paling mungkin adalah:

1. **Library Voice Restriction**: Voice ID Steven (9zOaLLJKBwYOwr8bOPDj) adalah library voice yang memerlukan paid subscription untuk API access
   - Error message: "Free users cannot use library voices via the API. Please upgrade your subscription to use this voice."
   - Ini adalah pembatasan baru atau perubahan policy dari ElevenLabs

2. **Account Type Mismatch**: API key yang digunakan (sk_efa5fd674e50eccfeba1bcb3a508c7112d2eaa754cd7e7c5) adalah free account
   - Sebelumnya mungkin voice Steven tersedia untuk free accounts
   - Atau account sebelumnya adalah paid account yang sudah expired

3. **API Policy Change**: ElevenLabs mungkin mengubah policy tentang library voice access
   - Library voices sekarang restricted untuk paid accounts only
   - Free accounts hanya bisa menggunakan generated voices (voices yang dibuat sendiri)

4. **Missing Fallback Mechanism**: Setelah simplifikasi, sistem tidak memiliki fallback ketika primary voice gagal
   - Sebelumnya ada multiple fallback options (Gemini TTS, browser Speech Synthesis)
   - Sekarang hanya mengandalkan satu voice ID tanpa error handling

## Correctness Properties

Property 1: Bug Condition - ElevenLabs Voice API Success

_For any_ TTS request where the system attempts to generate voice audio, the fixed system SHALL successfully generate audio output without HTTP 402 payment_required errors, ensuring voice AI Steven functionality is restored.

**Validates: Requirements 2.1, 2.2, 2.3, 2.4**

Property 2: Preservation - Voice AI Functionality Preservation

_For any_ voice AI interaction that worked correctly before the bug occurred, the fixed system SHALL produce exactly the same visual and auditory behavior, preserving AI orb animations, emoji effects, notification overlays, and voice quality.

**Validates: Requirements 3.1, 3.2, 3.3, 3.4**

## Fix Implementation

### Changes Required

Berdasarkan root cause analysis, ada beberapa pendekatan solusi yang dapat diimplementasikan:

**File**: `parking/config.php`

**Primary Solution - Voice ID Replacement**:
1. **Replace Steven Voice ID**: Ganti `ELEVENLABS_VOICE_ID` dari library voice ke generated voice atau free-compatible voice
   - Research voice IDs yang kompatibel dengan free account
   - Test voice quality untuk memastikan kualitas suara tetap baik
   - Update constant definition di config.php

2. **Add Fallback Voice Configuration**: Tambahkan fallback voice ID untuk error handling
   - Define `ELEVENLABS_FALLBACK_VOICE_ID` dengan voice yang pasti kompatibel
   - Implementasikan fallback logic di `api_elevenlabs_tts.php`

**File**: `parking/api_elevenlabs_tts.php`

**Enhanced Error Handling**:
3. **Implement Smart Fallback Logic**: Tambahkan logic untuk handle HTTP 402 errors
   - Detect payment_required error specifically
   - Automatically retry dengan fallback voice ID
   - Log fallback attempts untuk monitoring

4. **Add Voice Compatibility Check**: Implementasikan pre-check untuk voice compatibility
   - Check account type dan voice permissions sebelum API call
   - Prevent unnecessary API calls yang pasti gagal

5. **Improve Error Logging**: Enhance logging untuk better debugging
   - Log account type dan voice type information
   - Add structured error reporting
   - Include suggested solutions dalam error messages

**Alternative Solution - Account Upgrade**:
6. **API Key Upgrade**: Jika memungkinkan, upgrade ElevenLabs account ke paid plan
   - Verify current account status
   - Upgrade subscription untuk unlock library voices
   - Update API key jika diperlukan

**Fallback Solution - Hybrid Approach**:
7. **Implement Progressive Fallback**: Kombinasi multiple approaches
   - Primary: Try Steven voice dengan current API key
   - Secondary: Try alternative free-compatible voice
   - Tertiary: Implement browser Speech Synthesis sebagai last resort
   - Log semua attempts untuk analysis

## Testing Strategy

### Validation Approach

Testing strategy menggunakan two-phase approach: pertama surface counterexamples yang mendemonstrasikan bug pada unfixed code, kemudian verify bahwa fix bekerja dengan benar dan preserve existing behavior.

### Exploratory Bug Condition Checking

**Goal**: Surface counterexamples yang mendemonstrasikan bug SEBELUM implementing fix. Confirm atau refute root cause analysis. Jika refute, perlu re-hypothesize.

**Test Plan**: Write tests yang simulate ElevenLabs TTS API calls dengan Steven voice ID dan assert bahwa HTTP 402 errors terjadi. Run tests pada UNFIXED code untuk observe failures dan understand root cause.

**Test Cases**:
1. **Steven Voice API Test**: Simulate API call dengan Voice ID 9zOaLLJKBwYOwr8bOPDj (akan fail pada unfixed code)
2. **AI Orb Click Test**: Simulate user click pada AI orb dan verify HTTP 402 error occurs (akan fail pada unfixed code)
3. **Welcome Notification Test**: Simulate kendaraan masuk dan verify voice tidak keluar karena HTTP 402 (akan fail pada unfixed code)
4. **Multiple Concurrent Test**: Simulate multiple voice requests dan verify HTTP 402 + HTTP 429 errors (may fail pada unfixed code)

**Expected Counterexamples**:
- HTTP 402 "payment_required" errors ketika menggunakan Steven voice ID
- Possible causes: library voice restriction, free account limitation, API policy change

### Fix Checking

**Goal**: Verify bahwa untuk semua inputs dimana bug condition holds, fixed function menghasilkan expected behavior.

**Pseudocode:**
```
FOR ALL input WHERE isBugCondition(input) DO
  result := api_elevenlabs_tts_fixed(input)
  ASSERT expectedBehavior(result)
END FOR
```

### Preservation Checking

**Goal**: Verify bahwa untuk semua inputs dimana bug condition TIDAK hold, fixed function menghasilkan result yang sama dengan original function.

**Pseudocode:**
```
FOR ALL input WHERE NOT isBugCondition(input) DO
  ASSERT api_elevenlabs_tts_original(input) = api_elevenlabs_tts_fixed(input)
END FOR
```

**Testing Approach**: Property-based testing direkomendasikan untuk preservation checking karena:
- Generates many test cases automatically across input domain
- Catches edge cases yang manual unit tests mungkin miss
- Provides strong guarantees bahwa behavior unchanged untuk semua non-buggy inputs

**Test Plan**: Observe behavior pada UNFIXED code pertama untuk successful voice interactions, kemudian write property-based tests capturing behavior tersebut.

**Test Cases**:
1. **Voice Animation Preservation**: Verify AI orb speaking animation tetap berfungsi setelah fix
2. **Emoji Animation Preservation**: Verify emoji effects tetap trigger dengan timing yang benar
3. **Notification Overlay Preservation**: Verify welcome/goodbye overlays tetap muncul dengan durasi yang sama
4. **Voice Settings Preservation**: Verify voice quality settings tetap digunakan dengan benar

### Unit Tests

- Test ElevenLabs API calls dengan different voice IDs untuk identify compatible voices
- Test error handling untuk HTTP 402, HTTP 429, dan other API errors
- Test fallback logic ketika primary voice gagal
- Test voice quality dan clarity dengan alternative voice IDs

### Property-Based Tests

- Generate random TTS requests dan verify successful audio generation tanpa HTTP 402 errors
- Generate random voice configurations dan verify preservation of visual animations
- Test across many scenarios untuk ensure robust error handling dan fallback mechanisms

### Integration Tests

- Test full voice AI flow dari user interaction sampai audio output
- Test voice notifications dalam context of parking system events (masuk/keluar kendaraan)
- Test concurrent voice requests untuk verify rate limiting dan error handling
- Test voice AI functionality across different pages dan user contexts