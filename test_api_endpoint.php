<?php
// Test the actual API endpoint that the frontend uses
$apiUrl = 'http://localhost/parking/api_elevenlabs_tts.php';
$testData = json_encode([
    'text' => 'Test message for API endpoint verification',
    'voice_id' => '' // Use default voice ID from config
]);

$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($testData)
    ],
    CURLOPT_POSTFIELDS => $testData,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "API Endpoint Test Results:\n";
echo "HTTP Code: " . $httpCode . "\n";

if ($curlError) {
    echo "Connection Error: " . $curlError . "\n";
    echo "Note: This is expected if web server is not running\n";
} else if ($httpCode === 200) {
    echo "SUCCESS: API endpoint returned audio successfully\n";
    echo "Response length: " . strlen($response) . " bytes\n";
} else {
    echo "API Error Response: " . substr($response, 0, 500) . "\n";
}