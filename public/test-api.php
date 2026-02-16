<?php
/**
 * API Test File - Test collector API endpoints
 * Access: http://localhost:8080/test-api.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') === false || str_starts_with(trim($line), '#')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value, "\"'");
    }
}

require_once __DIR__ . '/../src/helpers.php';

session_start();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Collector API Test</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .section { background: white; padding: 20px; margin: 20px 0; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .result { background: #f8f9fa; padding: 15px; border-left: 4px solid #007bff; margin: 10px 0; }
        .error { border-left-color: #dc3545; }
        .success { border-left-color: #28a745; }
        pre { background: #f4f4f4; padding: 10px; overflow-x: auto; }
        button { background: #007bff; color: white; border: none; padding: 10px 20px; cursor: pointer; border-radius: 4px; margin: 5px; }
        button:hover { background: #0056b3; }
        .info { color: #666; font-size: 14px; }
    </style>
</head>
<body>
    <h1>🧪 Collector API Test</h1>
    
    <div class="section">
        <h2>Session Information</h2>
        <div class="result">
            <strong>Logged in:</strong> <?= isset($_SESSION['user_id']) ? 'Yes' : 'No' ?><br>
            <strong>User ID:</strong> <?= $_SESSION['user_id'] ?? 'Not set' ?><br>
            <strong>User Type:</strong> <?= $_SESSION['user_type'] ?? 'Not set' ?><br>
            <strong>User Name:</strong> <?= $_SESSION['user_name'] ?? 'Not set' ?><br>
        </div>
        <?php if (!isset($_SESSION['user_id'])): ?>
        <p class="info">⚠️ You need to <a href="/login">login</a> as a collector first!</p>
        <?php endif; ?>
    </div>

    <div class="section">
        <h2>API Endpoint Tests</h2>
        
        <button onclick="testEndpoint('/api/collector/metrics?collector_id=2', 'metrics-result')">
            Test Metrics API
        </button>
        
        <button onclick="testEndpoint('/api/collector/feedback?collector_id=2', 'feedback-result')">
            Test Feedback API
        </button>
        
        <button onclick="testEndpoint('/api/collector/waste-collection?collector_id=2', 'waste-result')">
            Test Waste Collection API
        </button>
        
        <div id="results"></div>
    </div>

    <div class="section">
        <h2>Database Quick Check</h2>
        <?php
        try {
            $db = new \Core\Database();
            
            // Check collectors
            $collectors = $db->fetchAll("SELECT id, name, email FROM users WHERE type = 'collector' LIMIT 3");
            echo "<div class='result success'>";
            echo "<strong>Collectors found:</strong> " . count($collectors) . "<br>";
            foreach ($collectors as $c) {
                echo "- ID: {$c['id']}, Name: {$c['name']}, Email: {$c['email']}<br>";
            }
            echo "</div>";
            
            // Check ratings
            $ratings = $db->fetchAll("SELECT COUNT(*) as count FROM collector_ratings");
            echo "<div class='result success'>";
            echo "<strong>Total Ratings:</strong> " . $ratings[0]['count'];
            echo "</div>";
            
            // Check waste records
            $waste = $db->fetchAll("SELECT COUNT(*) as count FROM pickup_request_wastes WHERE weight IS NOT NULL");
            echo "<div class='result success'>";
            echo "<strong>Waste Records with Weight:</strong> " . $waste[0]['count'];
            echo "</div>";
            
        } catch (Exception $e) {
            echo "<div class='result error'><strong>Database Error:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
        }
        ?>
    </div>

    <script>
        async function testEndpoint(url, resultId) {
            const resultsDiv = document.getElementById('results');
            const resultDiv = document.createElement('div');
            resultDiv.id = resultId;
            resultDiv.className = 'result';
            resultDiv.innerHTML = `<strong>Testing ${url}...</strong><br>Please wait...`;
            resultsDiv.appendChild(resultDiv);
            
            try {
                const response = await fetch(url, {
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                
                const contentType = response.headers.get('content-type');
                let data;
                
                if (contentType && contentType.includes('application/json')) {
                    data = await response.json();
                } else {
                    data = await response.text();
                }
                
                resultDiv.className = response.ok ? 'result success' : 'result error';
                resultDiv.innerHTML = `
                    <strong>${url}</strong><br>
                    <strong>Status:</strong> ${response.status} ${response.statusText}<br>
                    <strong>Content-Type:</strong> ${contentType}<br>
                    <strong>Response:</strong>
                    <pre>${JSON.stringify(data, null, 2)}</pre>
                `;
            } catch (error) {
                resultDiv.className = 'result error';
                resultDiv.innerHTML = `
                    <strong>${url}</strong><br>
                    <strong>Error:</strong> ${error.message}
                `;
            }
        }
    </script>
</body>
</html>
