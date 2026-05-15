<?php
// c:/xampp/htdocs/parking/config.php

if (!function_exists('date_default_timezone_get') || date_default_timezone_get() !== 'Asia/Jakarta') {
    date_default_timezone_set('Asia/Jakarta');
}

$host = 'localhost';
$user = 'root';
$pass = '';
$db_name = 'parking_system';

$conn = new mysqli($host, $user, $pass);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->query("CREATE DATABASE IF NOT EXISTS $db_name");
$conn->select_db($db_name);

// Settings table (auto-create)
$conn->query("CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value VARCHAR(255) NOT NULL
)");

// Default settings
$conn->query("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES 
    ('parking_rate', '3000'),
    ('min_fee', '3000'),
    ('grace_period', '15'),
    ('billing_interval_minutes', '10'),
    ('booking_fee', '5000'),
    ('booking_duration_minutes', '15')
");

// Helper: get setting from DB
function getSetting($conn, $key, $default = '')
{
    $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row['setting_value'];
    }
    return $default;
}

function getParkingTariffConfig($conn)
{
    return [
        'parking_rate' => (int)getSetting($conn, 'parking_rate', '3000'),
        'min_fee' => (int)getSetting($conn, 'min_fee', '3000'),
        'grace_period' => max(0, (int)getSetting($conn, 'grace_period', '15')),
        'billing_interval_minutes' => max(1, (int)getSetting($conn, 'billing_interval_minutes', '10'))
    ];
}

function calculateParkingFee($totalMinutes, $rate, $minFee, $gracePeriod, $billingIntervalMinutes)
{
    $minutes = max(0.0, (float)$totalMinutes);
    $grace = max(0.0, (float)$gracePeriod);
    $interval = max(1.0, (float)$billingIntervalMinutes);

    // Completely free only if parking duration is precisely 0, or inside a non-zero grace period
    if ($minutes <= 0 || ($grace > 0 && $minutes <= $grace)) {
        return 0;
    }

    $billableUnits = (int)ceil(($minutes - $grace) / $interval);
    if ($billableUnits < 1) $billableUnits = 1;

    return max($billableUnits * (int)$rate, (int)$minFee);
}

// Keep constant for backward compatibility, but read from DB
$db_rate = getSetting($conn, 'parking_rate', '3000');
define('PARKING_RATE_PER_HOUR', intval($db_rate));

// ElevenLabs TTS configuration for a clearer custom male voice.
// Prefer setting ELEVENLABS_API_KEY in your system environment.
if (!defined('ELEVENLABS_API_KEY')) {
    define('ELEVENLABS_API_KEY', 'sk_efa5fd674e50eccfeba1bcb3a508c7112d2eaa754cd7e7c5');
}
if (!defined('ELEVENLABS_VOICE_ID')) {
    define('ELEVENLABS_VOICE_ID', 'pNInz6obpgDQGcFmaJgB');
}
if (!defined('ELEVENLABS_TTS_MODEL')) {
    define('ELEVENLABS_TTS_MODEL', 'eleven_multilingual_v2');
}
if (!defined('ELEVENLABS_OUTPUT_FORMAT')) {
    define('ELEVENLABS_OUTPUT_FORMAT', 'mp3_44100_128');
}
if (!defined('ELEVENLABS_VOICE_STABILITY')) {
    define('ELEVENLABS_VOICE_STABILITY', 0.55);
}
if (!defined('ELEVENLABS_VOICE_SIMILARITY_BOOST')) {
    define('ELEVENLABS_VOICE_SIMILARITY_BOOST', 0.85);
}
if (!defined('ELEVENLABS_USE_SPEAKER_BOOST')) {
    define('ELEVENLABS_USE_SPEAKER_BOOST', true);
}
?>
