<?php
require_once __DIR__ . "/config.php";

header("Access-Control-Allow-Origin: same-origin");

function failElevenTts($message, $code = 400)
{
    http_response_code($code);
    header("Content-Type: application/json");
    echo json_encode(["success" => false, "message" => $message]);
    exit;
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

$outputFormat = preg_replace("/[^A-Za-z0-9_]/", "", ELEVENLABS_OUTPUT_FORMAT);
$url = "https://api.elevenlabs.io/v1/text-to-speech/" . rawurlencode($voiceId) .
       "?output_format=" . rawurlencode($outputFormat);

$payload = [
    "text" => $text,
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
    @file_put_contents(__DIR__ . "/debug_elevenlabs_tts.log", date("c") . " " . $message . PHP_EOL, FILE_APPEND);
    failElevenTts($message, 502);
}

header("Content-Type: " . $contentType);
header("Cache-Control: no-store");
echo $audio;
