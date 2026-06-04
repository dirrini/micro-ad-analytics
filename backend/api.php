<?php
// api.php
// Allow access from your local frontend origin
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS requests (browsers send this automatically before the real request)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  exit(0);
}
header('Content-Type: application/json');

try {

    $dsn = "mysql:host=db;dbname=impression_track;charset=utf8mb4";
    $user = "tracking_user";
    $password = "tracking_password";

    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

  // Fetch total counts per campaign
  $campaignQuery = $pdo->query("
    SELECT campaign_id, COUNT(*) as total 
    FROM ad_impressions 
    GROUP BY campaign_id
  ");
  $campaigns = $campaignQuery->fetchAll(PDO::FETCH_ASSOC);

  // Fetch browser distribution
  $browserQuery = $pdo->query("
    SELECT browser, COUNT(*) as total 
    FROM ad_impressions 
    GROUP BY browser
  ");
  $browsers = $browserQuery->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode([
    'status' => 'success',
    'data' => [
      'campaigns' => $campaigns,
      'browsers' => $browsers
    ]
  ]);

} catch (\PDOException $e) {
  echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}