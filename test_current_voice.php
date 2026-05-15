<?php
require_once 'parking/config.php';

echo "Current ELEVENLABS_VOICE_ID: " . ELEVENLABS_VOICE_ID . "\n";
echo "Current ELEVENLABS_FALLBACK_VOICE_ID: " . ELEVENLABS_FALLBACK_VOICE_ID . "\n";

// Test the current voice ID with ElevenLabs API
$testText = "Test message for verification";
$voiceId = ELEVENLABS_VOICE_ID;
$apiKey = ELEVENLABS_API_KEY;

$url = "https://api.elevenlabs.io/v1/text-to-speech/" . rawurlencode($voiceId) . "?output_format=" . rawurlencode(ELEVENLABS_OUTPUT_FORMAT);

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

echo "HTTP Code: " . $httpCode . "\n";
if ($httpCode === 200) {
    echo "SUCCESS: ElevenLabs TTS API call succeeded with Adam voice!\n";
    echo "Response length: " . strlen($response) . " bytes\n";
    echo "Fix verified: No HTTP 402 errors with new voice ID\n";
} else {
    echo "Error: " . $curlError . "\n";
    echo "Response: " . substr($response, 0, 500) . "\n";
}