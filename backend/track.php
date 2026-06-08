<?php
// track.php
// Set headers to deliver a 1x1 transparent GIF pixel
header('Content-Type: image/gif');
header('Cache-Control: no-cache, must-revalidate');

// The 1x1 transparent GIF binary (Flushed immediately to keep client load instant)
echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');

// Fast execution: Parse data from the request
$campaignId = isset($_GET['cid']) ? filter_var($_GET['cid'], FILTER_SANITIZE_STRING) : 'unknown';
$ipAddress  = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$userAgent  = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

// Quick & dirty regex parsing to mimic an analytics parser
$browser = 'Other';
if (preg_match('/Edg/i', $userAgent)) {
  $browser = 'Edge';
} elseif (preg_match('/Opera|OPR/i', $userAgent)) {
  $browser = 'Opera';
} elseif (preg_match('/Chrome/i', $userAgent)) {
  $browser = 'Chrome';
} elseif (preg_match('/Safari/i', $userAgent)) {
  $browser = 'Safari';
} elseif (preg_match('/Firefox/i', $userAgent)) {
  $browser = 'Firefox';
}

$platform = 'Other';
if (preg_match('/Windows|Win32/i', $userAgent)) { $platform = 'Windows'; }
elseif (preg_match('/Macintosh|Mac OS X/i', $userAgent)) { $platform = 'MacOS'; }
elseif (preg_match('/Linux/i', $userAgent)) { $platform = 'Linux'; }
elseif (preg_match('/Android/i', $userAgent)) { $platform = 'Android'; }
elseif (preg_match('/iPhone|iPad/i', $userAgent)) { $platform = 'iOS'; }

// ------------------------------------------------------------------
// 🛠️ STEP 1: Forward the Payload via POST API to the Backend Service
// ------------------------------------------------------------------
$apiPayload = json_encode([
  'campaign_id' => $campaignId,
  'ip_address'  => $ipAddress,
  'user_agent'  => $userAgent,
  'browser'     => $browser,
  'platform'    => $platform
]);

// Inside the Docker bridge network, we can hit your backend Nginx service directly on port 80
$chApi = curl_init('http://backend:80/api/analytics');
curl_setopt($chApi, CURLOPT_RETURNTRANSFER, true);
curl_setopt($chApi, CURLOPT_POST, true);
curl_setopt($chApi, CURLOPT_POSTFIELDS, $apiPayload);
curl_setopt($chApi, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($chApi, CURLOPT_TIMEOUT, 1); // Aggressive 1-second timeout so it never stalls
curl_exec($chApi);
curl_close($chApi);

// ------------------------------------------------------------------
// 📡 STEP 2: Notify WebSocket server of the new impression event
// ------------------------------------------------------------------
$wsPayload = json_encode([
  'event'       => 'impression_received',
  'campaign_id' => $campaignId,
  'browser'     => $browser
]);

$chWs = curl_init('http://ws_server:8085/broadcast');
curl_setopt($chWs, CURLOPT_RETURNTRANSFER, true);
curl_setopt($chWs, CURLOPT_POST, true);
curl_setopt($chWs, CURLOPT_POSTFIELDS, $wsPayload);
curl_setopt($chWs, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($chWs, CURLOPT_TIMEOUT, 1); // Low timeout so it never blocks pixel delivery
curl_exec($chWs);
curl_close($chWs);