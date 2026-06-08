<?php
// backend/index.php
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight OPTIONS requests smoothly
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit();
}

$dsn = sprintf(
  'mysql:host=%s;dbname=%s;charset=utf8mb4',
  getenv('DB_HOST') ?: 'db',
  getenv('DB_NAME')
);
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASSWORD') ?: '';
$options = [
  PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
  $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database connection failed."]);
    exit();
}

// 3. Extract the request method and URI path
$method = $_SERVER['REQUEST_METHOD'];
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Simple route matching for /api/analytics
if ($request_uri === '/api/analytics' || $request_uri === '/index.php') {
    
  // ----------------------------------------------------
  // 📥 POST METHOD: Input data to the database
  // ----------------------------------------------------
  if ($method === 'POST') {
    // Read raw JSON string from the input stream
    $raw_input = file_get_contents('php://input');
    $data = json_decode($raw_input, true);
    // Validate core requirements from track.php payload
    if (empty($data['campaign_id'])) {
      http_response_code(400);
      echo json_encode(["status" => "error", "message" => "Invalid payload. campaign_id required."]);
      exit();
    }
    try {
      // Prepare statement matching your exact tracking table schema
      $stmt = $pdo->prepare("
        INSERT INTO ad_impressions (campaign_id, ip_address, user_agent, browser, platform, created_at) 
        VALUES (:campaign_id, :ip_address, :user_agent, :browser, :platform, NOW())
      ");
      // Execute with the parameters forwarded from the track.php script
      $stmt->execute([
        'campaign_id' => $data['campaign_id'],
        'ip_address'  => $data['ip_address']  ?? '0.0.0.0',
        'user_agent'  => $data['user_agent']  ?? 'unknown',
        'browser'     => $data['browser']     ?? 'Other',
        'platform'    => $data['platform']    ?? 'Other'
      ]);
      http_response_code(201); // 201 Created standard for REST insertions
      echo json_encode(["status" => "success", "message" => "Tracking metrics logged successfully."]);
    } catch (\PDOException $e) {
      // Log specific DB errors to container logs without exposing system architecture to consumers
      error_log("Database Error: " . $e->getMessage());
      http_response_code(500);
      echo json_encode(["status" => "error", "message" => "Failed to write tracking metrics to database."]);
    }
    exit();
  }

  // ----------------------------------------------------
  // 📤 GET METHOD: Obtain the analytics data
  // ----------------------------------------------------
  if ($method === 'GET') {
      try {
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

        http_response_code(200);
        echo json_encode([
          'status' => 'success',
          'data' => [
            'campaigns' => $campaigns,
            'browsers' => $browsers
          ]
        ]);
      } catch (\PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
      }
      exit();
  }
}

// Catch-all fallback for undefined paths or unsupported methods
http_response_code(404);
echo json_encode(["status" => "error", "message" => "Endpoint not found."]);