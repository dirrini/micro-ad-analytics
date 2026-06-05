<?php
// track.php
// Set headers to deliver a 1x1 transparent GIF pixel
header('Content-Type: image/gif');
header('Cache-Control: no-cache, must-revalidate');

// The 1x1 transparent GIF binary
echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');

// Fast execution: Parse data from the request
$campaignId = isset($_GET['cid']) ? filter_var($_GET['cid'], FILTER_SANITIZE_STRING) : 'unknown';
$ipAddress  = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$userAgent  = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

// Quick & dirty regex parsing to mimic an analytics parser
$browser = 'Other';
// 1. Check for Edge first (using 'Edg')
if (preg_match('/Edg/i', $userAgent)) {
  $browser = 'Edge';
}
// 2. Check for Opera (using 'Opera' or 'OPR')
elseif (preg_match('/Opera|OPR/i', $userAgent)) {
  $browser = 'Opera';
}
// 3. Check for Chrome (only after discarding Edge/Opera)
elseif (preg_match('/Chrome/i', $userAgent)) {
  $browser = 'Chrome';
}
// 4. Check for Safari (only after discarding Chrome)
elseif (preg_match('/Safari/i', $userAgent)) {
  $browser = 'Safari';
}
// 5. Check for Firefox
elseif (preg_match('/Firefox/i', $userAgent)) {
  $browser = 'Firefox';
}

$platform = 'Other';
if (preg_match('/Windows|Win32/i', $userAgent)) { $platform = 'Windows'; }
elseif (preg_match('/Macintosh|Mac OS X/i', $userAgent)) { $platform = 'MacOS'; }
elseif (preg_match('/Linux/i', $userAgent)) { $platform = 'Linux'; }
elseif (preg_match('/Android/i', $userAgent)) { $platform = 'Android'; }
elseif (preg_match('/iPhone|iPad/i', $userAgent)) { $platform = 'iOS'; }

// Insert into Database using PDO (Best practice)
try {
  $dsn = sprintf(
    'mysql:host=%s;dbname=%s;charset=utf8mb4',
    getenv('DB_HOST') ?: 'db',
    getenv('DB_NAME')
  );

  $pdo = new PDO(
    $dsn,
    getenv('DB_USER'),
    getenv('DB_PASSWORD'),
    [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
  );

  $stmt = $pdo->prepare("
    INSERT INTO ad_impressions (campaign_id, ip_address, user_agent, browser, platform) 
    VALUES (:campaign_id, :ip_address, :user_agent, :browser, :platform)
  ");

  $stmt->execute([
    'campaign_id' => $campaignId,
    'ip_address'  => $ipAddress,
    'user_agent'  => $userAgent,
    'browser'     => $browser,
    'platform'    => $platform
  ]);

  // Notify WebSocket server of the new impression event
  $wsPayload = json_encode([
    'event' => 'impression_received',
    'campaign_id' => $campaignId,
    'browser' => $browser
  ]);

  $ch = curl_init('http://ws_server:8085/broadcast');
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $wsPayload);
  curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
  curl_setopt($ch, CURLOPT_TIMEOUT, 1); // Low timeout so it never blocks pixel delivery
  curl_exec($ch);
  curl_close($ch);
} catch (\PDOException $e) {
    // In production Ad Tech, you'd log this to a file rather than breaking the pixel execution
    error_log($e->getMessage());
}