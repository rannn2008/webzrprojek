<?php
require_once __DIR__ . "/config.php";

header("Access-Control-Allow-Origin: same-origin");

function failElevenTts($message, $code = 400, $suggestedAction = null)
{
    http_response_code($code);
    header("Content-Type: application/json");
    $response = ["success" => false, "message" => $message];
    if ($suggestedAction) {
        $response["suggested_action"] = $suggestedAction;
    }
    echo json_encode($response);
    exit;
}

function logElevenLabsError($message, $details = [])
{
    $logEntry = [
        'timestamp' => date('c'),
        'message' => $message,
        'details' => $details
    ];
    @file_put_contents(__DIR__ . "/debug_elevenlabs_tts.log", json_encode($logEntry) . PHP_EOL, FILE_APPEND);
}

function checkVoiceCompatibility($voiceId)
{
    // Adam voice (pNInz6obpgDQGcFmaJgB) is compatible with free accounts
    $freeCompatibleVoices = ['pNInz6obpgDQGcFmaJgB'];
    return in_array($voiceId, $freeCompatibleVoices);
}

function getElevenLabsUnavailableUntil()
{
    $cacheFile = __DIR__ . "/elevenlabs_tts_status.json";
    if (!is_file($cacheFile)) return 0;

    $status = json_decode((string)@file_get_contents($cacheFile), true);
    return (int)($status["unavailable_until"] ?? 0);
}

function markElevenLabsUnavailable($seconds = 1800)
{
    $cacheFile = __DIR__ . "/elevenlabs_tts_status.json";
    $status = [
        "unavailable_until" => time() + $seconds,
        "updated_at" => date("c")
    ];
    @file_put_contents($cacheFile, json_encode($status));
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    failElevenTts("Method not allowed", 405);
}

if (!function_exists("curl_init")) {
    failElevenTts("Ekstensi PHP cURL belum aktif", 503);
}

$apiKey = defined("ELEVENLABS_API_KEY") ? trim(ELEVENLABS_API_KEY) : "";
if ($apiKey === "") {
    failElevenTts("ElevenLabs API key belum diatur", 503);
}

if (getElevenLabsUnavailableUntil() > time()) {
    failElevenTts(
        "ElevenLabs TTS sedang dinonaktifkan sementara karena akun/API ditolak. Menggunakan browser voice fallback.",
        200,
        "Browser voice fallback should be used immediately"
    );
}

$raw = file_get_contents("php://input");
$input = json_decode($raw, true);
$text = trim((string)($input["text"] ?? ""));
if ($text === "") {
    failElevenTts("Text kosong");
}

$text = function_exists("mb_substr") ? mb_substr($text, 0, 700, "UTF-8") : substr($text, 0, 700);
$voiceId = preg_replace("/[^A-Za-z0-9_-]/", "", (string)($input["voice_id"] ?? ELEVENLABS_VOICE_ID));
if ($voiceId === "") {
    $voiceId = ELEVENLABS_VOICE_ID;
}

// Check voice compatibility
if (!checkVoiceCompatibility($voiceId)) {
    logElevenLabsError("Voice compatibility warning", [
        'voice_id' => $voiceId,
        'action' => 'using_fallback'
    ]);
    // Use Adam voice as fallback for compatibility
    $voiceId = 'pNInz6obpgDQGcFmaJgB';
}

$outputFormat = preg_replace("/[^A-Za-z0-9_]/", "", ELEVENLABS_OUTPUT_FORMAT);
$url = "https://api.elevenlabs.io/v1/text-to-speech/" . rawurlencode($voiceId) .
       "?output_format=" . rawurlencode($outputFormat);

$payload = [
    "text" => $text,
    "model_id" => ELEVENLABS_TTS_MODEL,
    "voice_settings" => [
        "stability" => ELEVENLABS_VOICE_STABILITY,
        "similarity_boost" => ELEVENLABS_VOICE_SIMILARITY_BOOST,
        "use_speaker_boost" => ELEVENLABS_USE_SPEAKER_BOOST
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
    CURLOPT_CONNECTTIMEOUT => 4,
    CURLOPT_TIMEOUT => 12
]);

$audio = curl_exec($ch);
$httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE) ?: "audio/mpeg";
$curlError = curl_error($ch);
curl_close($ch);

if ($audio === false || $httpCode < 200 || $httpCode >= 300) {
    $detail = trim((string)$audio);
    $message = "ElevenLabs TTS gagal: " . ($curlError ?: "HTTP " . $httpCode);
    if ($detail !== "") {
        $message .= " - " . substr($detail, 0, 500);
    }
    
    $errorDetails = [
        'http_code' => $httpCode,
        'curl_error' => $curlError,
        'voice_id' => $voiceId,
        'response_detail' => substr($detail, 0, 200)
    ];
    
    $suggestedAction = null;
    if (in_array($httpCode, [401, 402, 429], true)) {
        $suggestedAction = "ElevenLabs account is unavailable; browser voice fallback should be used";
        markElevenLabsUnavailable();
        logElevenLabsError("ElevenLabs account unavailable", $errorDetails);
    } else {
        logElevenLabsError($message, $errorDetails);
    }

    // Return HTTP 200 with a JSON failure payload so fetch() does not create
    // noisy red 502 errors in DevTools. The frontend will switch to browser TTS.
    failElevenTts($message, 200, $suggestedAction);
}

header("Content-Type: " . $contentType);
header("Cache-Control: no-store");
echo $audio;
