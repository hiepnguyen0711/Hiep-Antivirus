<?php
/**
 * Test Scanner System
 * File test để kiểm tra hệ thống quét malware
 * Author: Hiệp Nguyễn
 */

echo "<!DOCTYPE html>
<html lang='vi'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Test Security Scanner</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css' rel='stylesheet'>
</head>
<body class='bg-light'>
    <div class='container mt-5'>
        <div class='row justify-content-center'>
            <div class='col-md-8'>
                <div class='card shadow'>
                    <div class='card-header bg-primary text-white'>
                        <h4><i class='fas fa-shield-alt'></i> Test Security Scanner System</h4>
                    </div>
                    <div class='card-body'>";

// Test 1: Kiểm tra Client API
echo "<h5><i class='fas fa-server'></i> Test 1: Client API Health Check</h5>";

$clientUrl = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/security_scan_client.php';
$apiKey = 'hiep-security-client-2025-change-this-key';

$testUrl = $clientUrl . '?endpoint=health&api_key=' . urlencode($apiKey);

echo "<p><strong>Testing URL:</strong> <code>$testUrl</code></p>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $testUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "<div class='alert alert-danger'><i class='fas fa-times'></i> <strong>Error:</strong> $error</div>";
} elseif ($httpCode === 200) {
    $data = json_decode($response, true);
    if ($data) {
        echo "<div class='alert alert-success'><i class='fas fa-check'></i> <strong>Success!</strong> Client API is working</div>";
        echo "<pre class='bg-light p-3 rounded'>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";
    } else {
        echo "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle'></i> <strong>Warning:</strong> Invalid JSON response</div>";
        echo "<pre class='bg-light p-3 rounded'>$response</pre>";
    }
} else {
    echo "<div class='alert alert-danger'><i class='fas fa-times'></i> <strong>HTTP Error:</strong> $httpCode</div>";
    echo "<pre class='bg-light p-3 rounded'>$response</pre>";
}

echo "<hr>";

// Test 2: Kiểm tra Status API
echo "<h5><i class='fas fa-info-circle'></i> Test 2: Client Status Check</h5>";

$statusUrl = $clientUrl . '?endpoint=status&api_key=' . urlencode($apiKey);
echo "<p><strong>Testing URL:</strong> <code>$statusUrl</code></p>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $statusUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "<div class='alert alert-danger'><i class='fas fa-times'></i> <strong>Error:</strong> $error</div>";
} elseif ($httpCode === 200) {
    $data = json_decode($response, true);
    if ($data) {
        echo "<div class='alert alert-success'><i class='fas fa-check'></i> <strong>Success!</strong> Status API is working</div>";
        echo "<pre class='bg-light p-3 rounded'>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";
    } else {
        echo "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle'></i> <strong>Warning:</strong> Invalid JSON response</div>";
    }
} else {
    echo "<div class='alert alert-danger'><i class='fas fa-times'></i> <strong>HTTP Error:</strong> $httpCode</div>";
}

echo "<hr>";

// Test 3: Kiểm tra Server Dashboard
echo "<h5><i class='fas fa-tachometer-alt'></i> Test 3: Server Dashboard Check</h5>";

$serverUrl = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/security_scan_server.php';
echo "<p><strong>Dashboard URL:</strong> <a href='$serverUrl' target='_blank' class='btn btn-primary btn-sm'><i class='fas fa-external-link-alt'></i> Open Dashboard</a></p>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $serverUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "<div class='alert alert-danger'><i class='fas fa-times'></i> <strong>Error:</strong> $error</div>";
} elseif ($httpCode === 200) {
    if (strpos($response, 'Hiệp Security Center') !== false) {
        echo "<div class='alert alert-success'><i class='fas fa-check'></i> <strong>Success!</strong> Server Dashboard is accessible</div>";
    } else {
        echo "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle'></i> <strong>Warning:</strong> Dashboard loaded but content may be incorrect</div>";
    }
} else {
    echo "<div class='alert alert-danger'><i class='fas fa-times'></i> <strong>HTTP Error:</strong> $httpCode</div>";
}

echo "<hr>";

// Test 4: Kiểm tra File Permissions
echo "<h5><i class='fas fa-folder-open'></i> Test 4: File Permissions Check</h5>";

$checkDirs = ['logs', 'quarantine', 'config', 'data'];
$allGood = true;

foreach ($checkDirs as $dir) {
    if (!file_exists($dir)) {
        echo "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle'></i> Directory <code>$dir</code> does not exist. Creating...</div>";
        if (mkdir($dir, 0755, true)) {
            echo "<div class='alert alert-success'><i class='fas fa-check'></i> Directory <code>$dir</code> created successfully</div>";
        } else {
            echo "<div class='alert alert-danger'><i class='fas fa-times'></i> Failed to create directory <code>$dir</code></div>";
            $allGood = false;
        }
    } else {
        if (is_writable($dir)) {
            echo "<div class='alert alert-success'><i class='fas fa-check'></i> Directory <code>$dir</code> is writable</div>";
        } else {
            echo "<div class='alert alert-danger'><i class='fas fa-times'></i> Directory <code>$dir</code> is not writable</div>";
            $allGood = false;
        }
    }
}

if ($allGood) {
    echo "<div class='alert alert-success'><i class='fas fa-check-circle'></i> <strong>All directory permissions are correct!</strong></div>";
}

echo "<hr>";

// Test 5: Kiểm tra PHP Extensions
echo "<h5><i class='fas fa-code'></i> Test 5: PHP Extensions Check</h5>";

$requiredExtensions = ['curl', 'json', 'openssl', 'mbstring'];
$missingExtensions = [];

foreach ($requiredExtensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<div class='alert alert-success'><i class='fas fa-check'></i> Extension <code>$ext</code> is loaded</div>";
    } else {
        echo "<div class='alert alert-danger'><i class='fas fa-times'></i> Extension <code>$ext</code> is missing</div>";
        $missingExtensions[] = $ext;
    }
}

if (empty($missingExtensions)) {
    echo "<div class='alert alert-success'><i class='fas fa-check-circle'></i> <strong>All required PHP extensions are available!</strong></div>";
} else {
    echo "<div class='alert alert-danger'><i class='fas fa-exclamation-circle'></i> <strong>Missing extensions:</strong> " . implode(', ', $missingExtensions) . "</div>";
}

echo "<hr>";

// Test Summary
echo "<h5><i class='fas fa-clipboard-check'></i> Test Summary</h5>";

echo "<div class='row'>
    <div class='col-md-6'>
        <div class='card border-primary'>
            <div class='card-header bg-primary text-white'>
                <h6><i class='fas fa-info-circle'></i> System Information</h6>
            </div>
            <div class='card-body'>
                <p><strong>PHP Version:</strong> " . PHP_VERSION . "</p>
                <p><strong>Server:</strong> " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</p>
                <p><strong>Document Root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "</p>
                <p><strong>Current Directory:</strong> " . getcwd() . "</p>
                <p><strong>Memory Limit:</strong> " . ini_get('memory_limit') . "</p>
                <p><strong>Max Execution Time:</strong> " . ini_get('max_execution_time') . "s</p>
            </div>
        </div>
    </div>
    <div class='col-md-6'>
        <div class='card border-success'>
            <div class='card-header bg-success text-white'>
                <h6><i class='fas fa-tasks'></i> Next Steps</h6>
            </div>
            <div class='card-body'>
                <ol>
                    <li>Thay đổi API key mặc định</li>
                    <li>Cấu hình email SMTP</li>
                    <li>Thêm clients vào dashboard</li>
                    <li>Thiết lập cron jobs</li>
                    <li>Test quét malware thực tế</li>
                </ol>
                <a href='$serverUrl' class='btn btn-success btn-sm mt-2'>
                    <i class='fas fa-arrow-right'></i> Go to Dashboard
                </a>
            </div>
        </div>
    </div>
</div>";

echo "
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'></script>
</body>
</html>";
?>
