<?php

/**
 * Security Scanner Server - Central Dashboard
 * Đặt file này trên website trung tâm để quản lý tất cả clients
 * Author: Hiệp Nguyễn
 * Version: 1.0 Server Dashboard
 */

// ==================== CẤU HÌNH SERVER ====================
class SecurityServerConfig
{
    // Email cảnh báo
    const ADMIN_EMAIL = 'nguyenvanhiep0711@gmail.com';
    const EMAIL_FROM = 'security-server@yourdomain.com';
    const EMAIL_FROM_NAME = 'Hiệp Security Server';

    // SMTP Settings
    const SMTP_HOST = 'smtp.gmail.com';
    const SMTP_PORT = 587;
    const SMTP_USERNAME = 'nguyenvanhiep0711@gmail.com';
    const SMTP_PASSWORD = 'flnd neoz lhqw yzmd';
    const SMTP_SECURE = 'tls';

    // Server Settings
    const SERVER_NAME = 'Hiệp Security Center';
    const SERVER_VERSION = '1.0';
    const DEFAULT_API_KEY = 'hiep-security-client-2025-change-this-key';
    const MAX_CONCURRENT_SCANS = 10;
    const SCAN_TIMEOUT = 3000; // 50 phút
}

// ==================== CLIENT MANAGER ====================
class ClientManager
{
    private $clientsFile = './data/clients.json';

    public function __construct()
    {
        if (!file_exists('./data')) {
            mkdir('./data', 0755, true);
        }

        if (!file_exists($this->clientsFile)) {
            $this->saveClients([]);
        }
    }

    public function getClients()
    {
        if (!file_exists($this->clientsFile)) {
            return [];
        }

        $content = file_get_contents($this->clientsFile);
        return json_decode($content, true) ?: [];
    }

    public function getAllClients()
    {
        return $this->getClients();
    }

    public function saveClients($clients)
    {
        file_put_contents($this->clientsFile, json_encode($clients, JSON_PRETTY_PRINT));
    }

    public function addClient($name, $url, $apiKey)
    {
        $clients = $this->getClients();

        $client = [
            'id' => uniqid(),
            'name' => $name,
            'url' => $url,
            'api_key' => $apiKey,
            'status' => 'unknown',
            'last_scan' => null,
            'last_check' => null,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $clients[] = $client;
        $this->saveClients($clients);

        return $client;
    }

    public function updateClient($id, $data)
    {
        $clients = $this->getClients();

        foreach ($clients as &$client) {
            if ($client['id'] === $id) {
                $client = array_merge($client, $data);
                break;
            }
        }

        $this->saveClients($clients);
    }

    public function deleteClient($id)
    {
        $clients = $this->getClients();
        $clients = array_filter($clients, function ($client) use ($id) {
            return $client['id'] !== $id;
        });

        $this->saveClients(array_values($clients));
    }

    public function getClient($id)
    {
        $clients = $this->getClients();

        foreach ($clients as $client) {
            if ($client['id'] === $id) {
                return $client;
            }
        }

        return null;
    }
}

// ==================== SCANNER MANAGER ====================
class ScannerManager
{
    private $clientManager;

    public function __construct()
    {
        $this->clientManager = new ClientManager();
    }

    public function checkClientHealth($client)
    {
        // Xử lý URL - nếu chưa có security_scan_client.php thì thêm vào
        $url = rtrim($client['url'], '/');
        if (strpos($url, 'security_scan_client.php') === false) {
            $url .= '/security_scan_client.php';
        }
        $url .= '?endpoint=health&api_key=' . urlencode($client['api_key']);

        $response = $this->makeApiRequest($url, 'GET', [], null);

        if ($response['success']) {
            $this->clientManager->updateClient($client['id'], [
                'status' => 'online',
                'last_check' => date('Y-m-d H:i:s')
            ]);
            return true;
        } else {
            $this->clientManager->updateClient($client['id'], [
                'status' => 'offline',
                'last_check' => date('Y-m-d H:i:s'),
                'error' => $response['error'] ?? 'Unknown error'
            ]);
            return false;
        }
    }

    public function scanClient($client, $priorityFiles = [])
    {
        // Xử lý URL - nếu chưa có security_scan_client.php thì thêm vào
        $url = rtrim($client['url'], '/');
        if (strpos($url, 'security_scan_client.php') === false) {
            $url .= '/security_scan_client.php';
        }
        $url .= '?endpoint=scan&api_key=' . urlencode($client['api_key']);

        // Gửi priority files sang client
        $scanData = [
            'priority_files' => $priorityFiles
        ];

        // FIX: Sửa lỗi truyền tham số - data phải là scanData, apiKey phải là client api_key
        $response = $this->makeApiRequest($url, 'POST', $scanData, $client['api_key']);

        if ($response['success']) {
            $this->clientManager->updateClient($client['id'], [
                'status' => 'online',
                'last_scan' => date('Y-m-d H:i:s'),
                'last_check' => date('Y-m-d H:i:s')
            ]);

            return $response['data'];
        } else {
            $this->clientManager->updateClient($client['id'], [
                'status' => 'error',
                'last_check' => date('Y-m-d H:i:s'),
                'error' => $response['error'] ?? 'Unknown error'
            ]);

            return [
                'success' => false,
                'error' => $response['error'],
                'client_info' => [
                    'name' => $client['name'],
                    'domain' => $client['url']
                ]
            ];
        }
    }

    public function scanAllClients()
    {
        $clients = $this->clientManager->getClients();
        $results = [];

        foreach ($clients as $client) {
            $result = $this->scanClient($client);
            $results[] = [
                'client' => $client,
                'scan_result' => $result
            ];
        }

        return $results;
    }

    public function deployAdminSecurity($client)
    {
        // Xử lý URL
        $baseUrl = rtrim($client['url'], '/');
        if (strpos($baseUrl, 'security_scan_client.php') !== false) {
            $baseUrl = dirname($baseUrl);
        }

        $url = $baseUrl . '/security_scan_client.php?endpoint=deploy_admin_security&api_key=' . urlencode($client['api_key']);

        // Chuẩn bị dữ liệu bản vá bảo mật
        $securityFiles = $this->getAdminSecurityFiles();

        $deployData = [
            'action' => 'deploy_admin_security',
            'files' => $securityFiles,
            'verify_before' => true,
            'backup_existing' => true
        ];

        $response = $this->makeApiRequest($url, 'POST', $deployData, $client['api_key']);

        if ($response['success']) {
            $this->clientManager->updateClient($client['id'], [
                'admin_security_deployed' => date('Y-m-d H:i:s'),
                'admin_security_version' => '1.0'
            ]);

            return [
                'success' => true,
                'message' => 'Admin security patches deployed successfully',
                'details' => $response['data'] ?? []
            ];
        } else {
            return [
                'success' => false,
                'error' => $response['error'] ?? 'Failed to deploy admin security',
                'details' => $response['data'] ?? []
            ];
        }
    }

    private function getAdminSecurityFiles()
    {
        $files = [];

        // Đọc các file bảo mật từ thư mục admin
        $securityFiles = [
            'admin/.htaccess',
            'admin/security_patches.php',
            'admin/sources/.htaccess',
            'admin/filemanager/.htaccess',
            'admin/filemanager/security_config.php',
            'admin/ckeditor/.htaccess',
            'admin/lib/.htaccess'
        ];

        foreach ($securityFiles as $filePath) {
            if (file_exists($filePath)) {
                $files[basename($filePath)] = [
                    'path' => $filePath,
                    'content' => base64_encode(file_get_contents($filePath)),
                    'target_path' => $filePath
                ];
            }
        }

        return $files;
    }

    public function getClientStatus($client)
    {
        // Xử lý URL - nếu chưa có security_scan_client.php thì thêm vào
        $url = rtrim($client['url'], '/');
        if (strpos($url, 'security_scan_client.php') === false) {
            $url .= '/security_scan_client.php';
        }
        $url .= '?endpoint=status&api_key=' . urlencode($client['api_key']);

        // FIX: Sửa apiKey từ null thành client api_key
        $response = $this->makeApiRequest($url, 'GET', [], $client['api_key']);

        if ($response['success']) {
            return $response['data'];
        }

        return null;
    }

    private function makeApiRequest($url, $method = 'GET', $data = [], $apiKey = null)
    {
        $ch = curl_init();

        // Cấu hình cURL - tối ưu cho cả HTTP và HTTPS
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, SecurityServerConfig::SCAN_TIMEOUT);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        // Sử dụng User-Agent giống trình duyệt để tránh bị chặn
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

        // Chỉ set SSL option nếu là HTTPS
        if (strpos($url, 'https://') === 0) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
        }

        // Headers
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];

        if ($apiKey) {
            $headers[] = 'X-API-Key: ' . $apiKey;
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // Method và data
        if ($method === 'POST' && !empty($data)) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        // Debug logging
        if (!file_exists('./logs')) {
            mkdir('./logs', 0755, true);
        }

        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'url' => $url,
            'method' => $method,
            'data' => $data,
            'headers' => $headers
        ];

        // file_put_contents('./logs/api_requests.log', json_encode($logData) . "\n", FILE_APPEND);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        // Debug response
        $responseLog = [
            'timestamp' => date('Y-m-d H:i:s'),
            'url' => $url,
            'http_code' => $httpCode,
            'response' => $response,
            'error' => $error
        ];

        if ($error) {
            return [
                'success' => false,
                'error' => 'cURL Error: ' . $error,
                'http_code' => $httpCode
            ];
        }

        if ($httpCode !== 200) {
            return [
                'success' => false,
                'error' => 'HTTP Error: ' . $httpCode,
                'http_code' => $httpCode,
                'raw_response' => $response
            ];
        }

        $decodedResponse = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'error' => 'Invalid JSON response: ' . json_last_error_msg(),
                'raw_response' => $response
            ];
        }

        return [
            'success' => true,
            'data' => $decodedResponse,
            'http_code' => $httpCode
        ];
    }

    public function getFileContent($client, $filePath)
    {
        // Xử lý URL - nếu chưa có security_scan_client.php thì thêm vào
        $url = rtrim($client['url'], '/');
        if (strpos($url, 'security_scan_client.php') === false) {
            $url .= '/security_scan_client.php';
        }
        $url .= '?endpoint=get_file&api_key=' . urlencode($client['api_key']);

        // FIX: Sửa apiKey từ null thành client api_key
        $response = $this->makeApiRequest($url, 'POST', ['file_path' => $filePath], $client['api_key']);

        if ($response['success'] && isset($response['data']['content'])) {
            return [
                'success' => true,
                'content' => $response['data']['content'],
                'size' => $response['data']['size'] ?? strlen($response['data']['content']),
                'file_path' => $filePath
            ];
        } else {
            return [
                'success' => false,
                'error' => $response['error'] ?? 'Failed to get file content'
            ];
        }
    }

    public function saveFileContent($client, $filePath, $content)
    {
        // Xử lý URL - nếu chưa có security_scan_client.php thì thêm vào
        $url = rtrim($client['url'], '/');
        if (strpos($url, 'security_scan_client.php') === false) {
            $url .= '/security_scan_client.php';
        }
        $url .= '?endpoint=save_file&api_key=' . urlencode($client['api_key']);

        // FIX: Sửa apiKey từ null thành client api_key
        $response = $this->makeApiRequest($url, 'POST', [
            'file_path' => $filePath,
            'content' => $content
        ], $client['api_key']);

        if ($response['success']) {
            return [
                'success' => true,
                'message' => 'File saved successfully',
                'file_path' => $filePath,
                'size' => strlen($content)
            ];
        } else {
            return [
                'success' => false,
                'error' => $response['error'] ?? 'Failed to save file'
            ];
        }
    }

    public function deleteFileOnClient($client, $filePath)
    {
        // Xử lý URL - nếu chưa có security_scan_client.php thì thêm vào
        $url = rtrim($client['url'], '/');
        if (strpos($url, 'security_scan_client.php') === false) {
            $url .= '/security_scan_client.php';
        }
        $url .= '?endpoint=delete_file&api_key=' . urlencode($client['api_key']);

        // FIX: Sửa apiKey từ null thành client api_key
        $response = $this->makeApiRequest($url, 'POST', [
            'file_path' => $filePath
        ], $client['api_key']);

        if ($response['success'] && isset($response['data']['success']) && $response['data']['success']) {
            return [
                'success' => true,
                'message' => 'File deleted successfully',
                'file_path' => $filePath
            ];
        } else {
            $error = $response['error'] ?? 'Failed to delete file';
            if (isset($response['data']['error'])) {
                $error = $response['data']['error'];
            }

            return [
                'success' => false,
                'error' => $error,
                'file_path' => $filePath
            ];
        }
    }

    public function quarantineFileOnClient($client, $filePath)
    {
        // Xử lý URL - nếu chưa có security_scan_client.php thì thêm vào
        $url = rtrim($client['url'], '/');
        if (strpos($url, 'security_scan_client.php') === false) {
            $url .= '/security_scan_client.php';
        }
        $url .= '?endpoint=quarantine_file&api_key=' . urlencode($client['api_key']);

        // FIX: Sửa apiKey từ null thành client api_key
        $response = $this->makeApiRequest($url, 'POST', [
            'file_path' => $filePath
        ], $client['api_key']);

        if ($response['success'] && isset($response['data']['success']) && $response['data']['success']) {
            return [
                'success' => true,
                'message' => 'File quarantined successfully',
                'file_path' => $filePath
            ];
        } else {
            $error = $response['error'] ?? 'Failed to quarantine file';
            if (isset($response['data']['error'])) {
                $error = $response['data']['error'];
            }

            return [
                'success' => false,
                'error' => $error
            ];
        }
    }

    public function whitelistFileOnClient($client, $filePath, $reason = 'Manual whitelist')
    {
        // Xử lý URL - nếu chưa có security_scan_client.php thì thêm vào
        $url = rtrim($client['url'], '/');
        if (strpos($url, 'security_scan_client.php') === false) {
            $url .= '/security_scan_client.php';
        }
        $url .= '?endpoint=whitelist_file&api_key=' . urlencode($client['api_key']);

        $response = $this->makeApiRequest($url, 'POST', [
            'file_path' => $filePath,
            'reason' => $reason
        ], $client['api_key']);

        if ($response['success'] && isset($response['data']['success']) && $response['data']['success']) {
            return [
                'success' => true,
                'message' => 'File whitelisted successfully',
                'file_path' => $filePath
            ];
        } else {
            $error = $response['error'] ?? 'Failed to whitelist file';
            if (isset($response['data']['error'])) {
                $error = $response['data']['error'];
            }

            return [
                'success' => false,
                'error' => $error
            ];
        }
    }

    public function restoreFileFromQuarantine($client, $quarantinePath, $originalPath)
    {
        // Xử lý URL - nếu chưa có security_scan_client.php thì thêm vào
        $url = rtrim($client['url'], '/');
        if (strpos($url, 'security_scan_client.php') === false) {
            $url .= '/security_scan_client.php';
        }
        $url .= '?endpoint=restore_file&api_key=' . urlencode($client['api_key']);

        $response = $this->makeApiRequest($url, 'POST', [
            'quarantine_path' => $quarantinePath,
            'original_path' => $originalPath
        ], $client['api_key']);

        if ($response['success'] && isset($response['data']['success']) && $response['data']['success']) {
            return [
                'success' => true,
                'message' => 'File restored successfully',
                'original_path' => $originalPath
            ];
        } else {
            $error = $response['error'] ?? 'Failed to restore file';
            if (isset($response['data']['error'])) {
                $error = $response['data']['error'];
            }

            return [
                'success' => false,
                'error' => $error
            ];
        }
    }

    public function getScanHistory($client, $limit = 10)
    {
        // Xử lý URL - nếu chưa có security_scan_client.php thì thêm vào
        $url = rtrim($client['url'], '/');
        if (strpos($url, 'security_scan_client.php') === false) {
            $url .= '/security_scan_client.php';
        }
        $url .= '?endpoint=scan_history&api_key=' . urlencode($client['api_key']) . '&limit=' . $limit;

        // FIX: Sửa apiKey từ null thành client api_key
        $response = $this->makeApiRequest($url, 'GET', [], $client['api_key']);

        if ($response['success']) {
            return $response['data'];
        }

        return [];
    }
}

// ==================== EMAIL MANAGER ====================
class EmailManager
{
    public function sendDailyReport($scanResults)
    {
        $totalClients = count($scanResults);
        $criticalClients = 0;
        $warningClients = 0;
        $cleanClients = 0;
        $offlineClients = 0;

        $criticalDetails = [];
        $warningDetails = [];

        foreach ($scanResults as $result) {
            $client = $result['client'];
            $scanResult = $result['scan_result'];

            if (!$scanResult['success']) {
                $offlineClients++;
                continue;
            }

            $status = $scanResult['scan_results']['status'] ?? 'unknown';

            switch ($status) {
                case 'critical':
                    $criticalClients++;
                    $criticalDetails[] = [
                        'client' => $client,
                        'scan_result' => $scanResult
                    ];
                    break;
                case 'warning':
                    $warningClients++;
                    $warningDetails[] = [
                        'client' => $client,
                        'scan_result' => $scanResult
                    ];
                    break;
                case 'clean':
                    $cleanClients++;
                    break;
            }
        }

        // Tạo email
        $subject = "🔒 Báo Cáo Bảo Mật Hàng Ngày - " . date('d/m/Y');
        if ($criticalClients > 0) {
            $subject = "🚨 CẢNH BÁO: " . $criticalClients . " Website Có Threats Nghiêm Trọng - " . date('d/m/Y');
        }

        $htmlBody = $this->generateReportEmail($totalClients, $criticalClients, $warningClients, $cleanClients, $offlineClients, $criticalDetails, $warningDetails);

        return $this->sendEmail($subject, $htmlBody);
    }

    private function generateReportEmail($total, $critical, $warning, $clean, $offline, $criticalDetails, $warningDetails)
    {
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 8px 24px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 28px; }
        .header p { margin: 10px 0 0 0; opacity: 0.9; }
        .content { padding: 30px; }
        .summary { display: flex; justify-content: space-around; margin: 20px 0; }
        .stat { text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px; margin: 0 10px; }
        .stat-number { font-size: 24px; font-weight: bold; margin-bottom: 5px; }
        .stat-label { font-size: 12px; color: #666; text-transform: uppercase; }
        .critical { color: #ff4757; background: #ffe8e8; }
        .warning { color: #ffa502; background: #fff3e0; }
        .clean { color: #2ed573; background: #e8f5e8; }
        .offline { color: #747d8c; background: #f1f2f6; }
        .section { margin: 30px 0; }
        .section h2 { color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; }
        .client-item { background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #3498db; }
        .client-item.critical { border-left-color: #ff4757; background: #ffe8e8; }
        .client-item.warning { border-left-color: #ffa502; background: #fff3e0; }
        .client-name { font-weight: bold; margin-bottom: 5px; }
        .client-url { color: #666; font-size: 14px; margin-bottom: 10px; }
        .threat-summary { display: flex; gap: 20px; }
        .threat-count { font-size: 18px; font-weight: bold; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔒 Báo Cáo Bảo Mật Hàng Ngày</h1>
            <p>' . SecurityServerConfig::SERVER_NAME . ' - ' . date('d/m/Y H:i:s') . '</p>
        </div>
        
        <div class="content">
            <div class="summary">
                <div class="stat clean">
                    <div class="stat-number">' . $clean . '</div>
                    <div class="stat-label">An Toàn</div>
                </div>
                <div class="stat warning">
                    <div class="stat-number">' . $warning . '</div>
                    <div class="stat-label">Cảnh Báo</div>
                </div>
                <div class="stat critical">
                    <div class="stat-number">' . $critical . '</div>
                    <div class="stat-label">Nghiêm Trọng</div>
                </div>
                <div class="stat offline">
                    <div class="stat-number">' . $offline . '</div>
                    <div class="stat-label">Offline</div>
                </div>
            </div>';

        if ($critical > 0) {
            $html .= '<div class="section">
                <h2>🚨 Websites Có Threats Nghiêm Trọng</h2>';

            foreach ($criticalDetails as $detail) {
                $client = $detail['client'];
                $scanResult = $detail['scan_result'];
                $results = $scanResult['scan_results'];

                $html .= '<div class="client-item critical">
                    <div class="client-name">' . htmlspecialchars($client['name']) . '</div>
                    <div class="client-url">' . htmlspecialchars($client['url']) . '</div>
                    <div class="threat-summary">
                        <div><span class="threat-count">' . $results['critical_count'] . '</span> Critical Threats</div>
                        <div><span class="threat-count">' . $results['suspicious_count'] . '</span> Total Threats</div>
                        <div><span class="threat-count">' . $results['scanned_files'] . '</span> Files Scanned</div>
                    </div>
                </div>';
            }

            $html .= '</div>';
        }

        if ($warning > 0) {
            $html .= '<div class="section">
                <h2>⚠️ Websites Có Cảnh Báo</h2>';

            foreach ($warningDetails as $detail) {
                $client = $detail['client'];
                $scanResult = $detail['scan_result'];
                $results = $scanResult['scan_results'];

                $html .= '<div class="client-item warning">
                    <div class="client-name">' . htmlspecialchars($client['name']) . '</div>
                    <div class="client-url">' . htmlspecialchars($client['url']) . '</div>
                    <div class="threat-summary">
                        <div><span class="threat-count">' . $results['suspicious_count'] . '</span> Suspicious Files</div>
                        <div><span class="threat-count">' . $results['scanned_files'] . '</span> Files Scanned</div>
                    </div>
                </div>';
            }

            $html .= '</div>';
        }

        $html .= '</div>
        
        <div class="footer">
            <p><strong>' . SecurityServerConfig::SERVER_NAME . '</strong> - Automated Security Report</p>
            <p>Phát triển bởi <a href="https://www.facebook.com/G.N.S.L.7/">Hiệp Nguyễn</a></p>
        </div>
    </div>

    <!-- Code Editor Modal -->
    <div class="modal fade" id="codeEditorModal" tabindex="-1" aria-labelledby="codeEditorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="codeEditorModalLabel">
                        <i class="fas fa-code me-2"></i>Code Editor
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="closeCodeEditor()"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="code-editor-container">
                        <div class="editor-toolbar">
                            <div class="file-info">
                                <span class="file-path" id="currentFilePath"></span>
                                <span class="file-size" id="currentFileSize"></span>
                            </div>
                            <div class="editor-actions">
                                <button class="btn btn-sm btn-outline-secondary" onclick="formatCode()">
                                    <i class="fas fa-align-left me-1"></i>Format
                                </button>
                                <button class="btn btn-sm btn-outline-info" onclick="findInCode()">
                                    <i class="fas fa-search me-1"></i>Find
                                </button>
                                <button class="btn btn-sm btn-outline-dark" onclick="toggleFullscreen()" id="fullscreenBtn">
                                    <i class="fas fa-expand me-1"></i>Fullscreen
                                </button>
                                <button class="btn btn-sm btn-success" onclick="saveCode()">
                                    <i class="fas fa-save me-1"></i>Save
                                </button>
                            </div>
                        </div>
                        <div id="monacoEditor" style="height: 600px; width: 100%;"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="editor-status">
                        <span id="cursorPosition">Line 1, Column 1</span>
                        <span id="fileType">PHP</span>
                    </div>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="closeCodeEditor()">Close</button>
                    <button type="button" class="btn btn-primary" onclick="saveAndClose()">Save & Close</button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>';

        return $html;
    }

    private function sendEmail($subject, $htmlBody)
    {
        // Sử dụng PHPMailer nếu có
        if (file_exists('./smtp/class.phpmailer.php')) {
            require_once('./smtp/class.phpmailer.php');
            require_once('./smtp/class.smtp.php');

            $mail = new PHPMailer();

            try {
                $mail->isSMTP();
                $mail->Host = SecurityServerConfig::SMTP_HOST;
                $mail->SMTPAuth = true;
                $mail->Username = SecurityServerConfig::SMTP_USERNAME;
                $mail->Password = SecurityServerConfig::SMTP_PASSWORD;
                $mail->SMTPSecure = SecurityServerConfig::SMTP_SECURE;
                $mail->Port = SecurityServerConfig::SMTP_PORT;
                $mail->CharSet = 'UTF-8';

                $mail->setFrom(SecurityServerConfig::EMAIL_FROM, SecurityServerConfig::EMAIL_FROM_NAME);
                $mail->addAddress(SecurityServerConfig::ADMIN_EMAIL);
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body = $htmlBody;

                return $mail->send();
            } catch (Exception $e) {
                error_log("Email error: " . $e->getMessage());
                return false;
            }
        } else {
            // Fallback to mail() function
            $headers = "From: " . SecurityServerConfig::EMAIL_FROM . "\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

            return mail(SecurityServerConfig::ADMIN_EMAIL, $subject, $htmlBody, $headers);
        }
    }
}

// ==================== API HANDLERS ====================
if (isset($_GET['api'])) {
    header('Content-Type: application/json; charset=utf-8');

    $action = $_GET['api'];
    $clientManager = new ClientManager();
    $scannerManager = new ScannerManager();

    switch ($action) {
        case 'get_clients':
            echo json_encode($clientManager->getClients());
            break;

        case 'add_client':
            $name = $_POST['name'] ?? '';
            $url = $_POST['url'] ?? '';
            $apiKey = $_POST['api_key'] ?? SecurityServerConfig::DEFAULT_API_KEY;

            if (empty($name) || empty($url)) {
                echo json_encode(['success' => false, 'error' => 'Name and URL required']);
                break;
            }

            $client = $clientManager->addClient($name, $url, $apiKey);
            echo json_encode(['success' => true, 'client' => $client]);
            break;

        case 'update_client':
            $id = $_POST['id'] ?? '';
            $name = $_POST['name'] ?? '';
            $url = $_POST['url'] ?? '';
            $apiKey = $_POST['api_key'] ?? '';

            if (empty($id) || empty($name) || empty($url) || empty($apiKey)) {
                echo json_encode(['success' => false, 'error' => 'All fields required']);
                break;
            }

            $client = $clientManager->getClient($id);
            if (!$client) {
                echo json_encode(['success' => false, 'error' => 'Client not found']);
                break;
            }

            $clientManager->updateClient($id, [
                'name' => $name,
                'url' => $url,
                'api_key' => $apiKey,
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            echo json_encode(['success' => true]);
            break;

        case 'delete_client':
            $id = $_POST['id'] ?? '';
            if (empty($id)) {
                echo json_encode(['success' => false, 'error' => 'ID required']);
                break;
            }

            $clientManager->deleteClient($id);
            echo json_encode(['success' => true]);
            break;

        case 'check_client':
            $id = $_GET['id'] ?? '';
            $client = $clientManager->getClient($id);

            if (!$client) {
                echo json_encode(['success' => false, 'error' => 'Client not found']);
                break;
            }

            $isOnline = $scannerManager->checkClientHealth($client);
            echo json_encode(['success' => true, 'online' => $isOnline]);
            break;

        case 'scan_client':
            $id = $_GET['id'] ?? '';
            $client = $clientManager->getClient($id);

            if (!$client) {
                echo json_encode(['success' => false, 'error' => 'Client not found']);
                break;
            }

            // Get priority files from request
            $requestData = json_decode(file_get_contents('php://input'), true);
            $priorityFiles = $requestData['priority_files'] ?? [];

            $result = $scannerManager->scanClient($client, $priorityFiles);
            echo json_encode($result);
            break;

        case 'deploy_admin_security':
            $id = $_POST['id'] ?? '';
            $client = $clientManager->getClient($id);

            if (!$client) {
                echo json_encode(['success' => false, 'error' => 'Client not found']);
                break;
            }

            $result = $scannerManager->deployAdminSecurity($client);
            echo json_encode($result);
            break;

        case 'scan_all':
            $results = $scannerManager->scanAllClients();
            echo json_encode(['success' => true, 'results' => $results]);
            break;

        case 'send_report':
            $results = $scannerManager->scanAllClients();
            $emailManager = new EmailManager();
            $sent = $emailManager->sendDailyReport($results);

            echo json_encode(['success' => $sent, 'results' => $results]);
            break;

        case 'get_client_status':
            $id = $_GET['id'] ?? '';
            $client = $clientManager->getClient($id);

            if (!$client) {
                echo json_encode(['success' => false, 'error' => 'Client not found']);
                break;
            }

            $status = $scannerManager->getClientStatus($client);
            echo json_encode(['success' => true, 'data' => $status]);
            break;

        case 'get_client_scan_results':
            $id = $_GET['id'] ?? '';
            $client = $clientManager->getClient($id);

            if (!$client) {
                echo json_encode(['success' => false, 'error' => 'Client not found']);
                break;
            }

            $result = $scannerManager->scanClient($client);
            echo json_encode(['success' => true, 'data' => $result]);
            break;

        case 'delete_file':
            $clientId = $_POST['client_id'] ?? '';
            $filePath = $_POST['file_path'] ?? '';

            if (empty($clientId) || empty($filePath)) {
                echo json_encode(['success' => false, 'error' => 'Client ID and file path required']);
                break;
            }

            $client = $clientManager->getClient($clientId);
            if (!$client) {
                echo json_encode(['success' => false, 'error' => 'Client not found']);
                break;
            }

            $result = $scannerManager->deleteFileOnClient($client, $filePath);
            echo json_encode($result);
            break;

        case 'quarantine_file':
            $clientId = $_POST['client_id'] ?? '';
            $filePath = $_POST['file_path'] ?? '';

            if (empty($clientId) || empty($filePath)) {
                echo json_encode(['success' => false, 'error' => 'Client ID and file path required']);
                break;
            }

            $client = $clientManager->getClient($clientId);
            if (!$client) {
                echo json_encode(['success' => false, 'error' => 'Client not found']);
                break;
            }

            $result = $scannerManager->quarantineFileOnClient($client, $filePath);
            echo json_encode($result);
            break;

        case 'get_scan_history':
            $clientId = $_GET['client_id'] ?? '';
            $limit = $_GET['limit'] ?? 10;

            if (empty($clientId)) {
                echo json_encode(['success' => false, 'error' => 'Client ID required']);
                break;
            }

            $client = $clientManager->getClient($clientId);
            if (!$client) {
                echo json_encode(['success' => false, 'error' => 'Client not found']);
                break;
            }

            $history = $scannerManager->getScanHistory($client, $limit);
            echo json_encode(['success' => true, 'data' => $history]);
            break;

        case 'bulk_scan':
            $clientIds = $_POST['client_ids'] ?? [];

            if (empty($clientIds)) {
                echo json_encode(['success' => false, 'error' => 'No clients selected']);
                break;
            }

            $results = [];
            foreach ($clientIds as $clientId) {
                $client = $clientManager->getClient($clientId);
                if ($client) {
                    $result = $scannerManager->scanClient($client);
                    $results[] = [
                        'client_id' => $clientId,
                        'client_name' => $client['name'],
                        'result' => $result
                    ];
                }
            }

            echo json_encode(['success' => true, 'results' => $results]);
            break;

        case 'get_dashboard_stats':
            $clients = $clientManager->getClients();
            $stats = [
                'total_clients' => count($clients),
                'online_clients' => 0,
                'infected_clients' => 0,
                'last_scan_summary' => []
            ];

            foreach ($clients as $client) {
                $status = $scannerManager->getClientStatus($client);
                if ($status) {
                    $stats['online_clients']++;
                    if (
                        isset($status['last_scan']['status']) &&
                        in_array($status['last_scan']['status'], ['critical', 'infected'])
                    ) {
                        $stats['infected_clients']++;
                    }
                }
            }

            echo json_encode(['success' => true, 'data' => $stats]);
            break;

        case 'get_file_content':
            $clientId = $_GET['client_id'] ?? '';
            $filePath = $_GET['file_path'] ?? '';

            if (empty($clientId) || empty($filePath)) {
                echo json_encode(['success' => false, 'error' => 'Missing client_id or file_path']);
                break;
            }

            // Try to get client from database first
            $client = $clientManager->getClient($clientId);
            $allClients = $clientManager->getClients();
            
            // Enhanced client lookup logic for better matching
            if (!$client) {
                // Case 1: Handle numeric client IDs like "client_0", "client_1", "client_2"
                if (preg_match('/^client_(\d+)$/', $clientId, $matches)) {
                    $index = (int)$matches[1];
                    $clientArray = array_values($allClients); // Re-index array
                    if (isset($clientArray[$index])) {
                        $client = $clientArray[$index];
                    }
                }
                
                // Case 2: Handle temp client IDs like "temp_client_2_timestamp"
                if (!$client && strpos($clientId, 'temp_client_') === 0) {
                    if (preg_match('/temp_client_(\d+)/', $clientId, $matches)) {
                        $index = (int)$matches[1];
                        $clientArray = array_values($allClients); // Re-index array
                        if (isset($clientArray[$index])) {
                            $client = $clientArray[$index];
                        }
                    }
                }
                
                // Case 3: Smart matching by URL patterns
                if (!$client) {
                    foreach ($allClients as $c) {
                        if (strpos($clientId, 'xemay365') !== false && strpos($c['url'], 'xemay365') !== false) {
                            $client = $c;
                            break;
                        } else if (strpos($clientId, 'hiep') !== false && strpos($c['url'], 'hiep') !== false) {
                            $client = $c;
                            break;
                        } else if (strpos($clientId, 'local') !== false && strpos($c['url'], 'localhost') !== false) {
                            $client = $c;
                            break;
                        }
                    }
                }
            }
            
            if (!$client) {
                // Enhanced error with debug info
                $allClients = $clientManager->getClients();
                echo json_encode([
                    'success' => false, 
                    'error' => 'Client not found',
                    'debug' => [
                        'requested_client_id' => $clientId,
                        'available_clients' => array_map(function($c) {
                            return ['id' => $c['id'], 'name' => $c['name'], 'url' => $c['url']];
                        }, $allClients),
                        'is_temp_client' => strpos($clientId, 'temp_client_') === 0
                    ]
                ]);
                break;
            }

            $result = $scannerManager->getFileContent($client, $filePath);
            echo json_encode($result);
            break;

        case 'save_file_content':
            $clientId = $_GET['client_id'] ?? '';
            $requestData = json_decode(file_get_contents('php://input'), true);
            $filePath = $requestData['file_path'] ?? '';
            $content = $requestData['content'] ?? '';

            if (empty($clientId) || empty($filePath)) {
                echo json_encode(['success' => false, 'error' => 'Missing client_id or file_path']);
                break;
            }

            $client = $clientManager->getClient($clientId);
            if (!$client) {
                echo json_encode(['success' => false, 'error' => 'Client not found']);
                break;
            }

            $result = $scannerManager->saveFileContent($client, $filePath, $content);
            echo json_encode($result);
            break;

        case 'get_available_fixes':
            $clientId = $_GET['client_id'] ?? '';
            $client = $clientManager->getClient($clientId);

            if (!$client) {
                echo json_encode(['success' => false, 'error' => 'Client not found']);
                break;
            }

            $remediation = new SecurityRemediation($clientManager, $scannerManager);
            $fixes = $remediation->getAvailableFixes();
            echo json_encode(['success' => true, 'fixes' => $fixes]);
            break;

        case 'execute_remediation':
            // Ensure clean JSON output
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-cache, must-revalidate');

            // Enable error logging for debugging but don't display errors
            error_reporting(E_ALL);
            ini_set('display_errors', 0);
            ini_set('log_errors', 1);

            try {
                $clientId = $_GET['client_id'] ?? '';
                error_log("Execute remediation for client: " . $clientId);

                $input = file_get_contents('php://input');
                error_log("Request input: " . $input);

                $requestData = json_decode($input, true);
                error_log("Parsed request data: " . print_r($requestData, true));

                if (!$requestData) {
                    throw new Exception('Invalid JSON data provided');
                }

                $selectedFixes = $requestData['selected_fixes'] ?? [];
                error_log("Selected fixes: " . print_r($selectedFixes, true));

                $client = $clientManager->getClient($clientId);
                if (!$client) {
                    throw new Exception('Client not found: ' . $clientId);
                }
                error_log("Client found: " . print_r($client, true));

                if (empty($selectedFixes)) {
                    throw new Exception('No fixes selected');
                }

                error_log("Creating SecurityRemediation instance...");
                $remediation = new SecurityRemediation($clientManager, $scannerManager);
                error_log("Executing remediation...");
                $results = $remediation->executeRemediation($client, $selectedFixes);
                error_log("Remediation results: " . print_r($results, true));

                // Check if JSON_UNESCAPED_UNICODE is available
                $json_flags = 0;
                if (defined('JSON_UNESCAPED_UNICODE')) {
                    $json_flags = JSON_UNESCAPED_UNICODE;
                }

                echo json_encode(['success' => true, 'results' => $results], $json_flags);
            } catch (Exception $e) {
                // Check if JSON_UNESCAPED_UNICODE is available
                $json_flags = 0;
                if (defined('JSON_UNESCAPED_UNICODE')) {
                    $json_flags = JSON_UNESCAPED_UNICODE;
                }

                echo json_encode(['success' => false, 'error' => $e->getMessage()], $json_flags);
            }
            break;

        case 'run_daily_scan':
            // API endpoint để chạy daily scan (cho cron job)
            $apiKey = $_GET['cron_key'] ?? '';
            $expectedKey = 'hiep-security-cron-2025-' . date('Y-m-d'); // Key thay đổi mỗi ngày

            if ($apiKey !== $expectedKey) {
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Invalid cron key']);
                break;
            }

            $scheduler = new SecurityScheduler($clientManager, $scannerManager);
            $scheduler->runDailySecurityScan();
            echo json_encode(['success' => true, 'message' => 'Daily scan completed']);
            break;

        case 'test_email':
            // API endpoint để test email (chỉ cho admin)
            $adminKey = $_GET['admin_key'] ?? '';
            if ($adminKey !== 'hiep-admin-test-2025') {
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Invalid admin key']);
                break;
            }

            // Tạo test data
            $testThreats = array(
                array(
                    'client' => array(
                        'name' => 'Test Website',
                        'url' => 'https://test.example.com'
                    ),
                    'threats' => array(
                        array(
                            'file' => '/test/malware.php',
                            'threat_level' => 9,
                            'risk_score' => 85,
                            'size' => 1024,
                            'modified' => date('Y-m-d H:i:s'),
                            'threats' => array(
                                array(
                                    'pattern' => 'eval(',
                                    'description' => 'Eval function usage',
                                    'line' => 15
                                )
                            )
                        )
                    )
                )
            );

            $scheduler = new SecurityScheduler($clientManager, $scannerManager);
            $scheduler->testEmail($testThreats);
            echo json_encode(['success' => true, 'message' => 'Test email sent']);
            break;

        case 'get_scheduler_config':
            // API để lấy cấu hình scheduler
            $config = [
                'daily_scan_time' => SchedulerConfig::DAILY_SCAN_TIME,
                'weekly_scan_day' => SchedulerConfig::WEEKLY_SCAN_DAY,
                'monthly_report_day' => SchedulerConfig::MONTHLY_REPORT_DAY,
                'scan_timeout' => SchedulerConfig::SCAN_TIMEOUT,
                'max_concurrent_scans' => SchedulerConfig::MAX_CONCURRENT_SCANS,
                'email_on_critical' => SchedulerConfig::EMAIL_ON_CRITICAL,
                'email_daily_summary' => SchedulerConfig::EMAIL_DAILY_SUMMARY,
                'email_weekly_report' => SchedulerConfig::EMAIL_WEEKLY_REPORT,
                'auto_cleanup' => SchedulerConfig::AUTO_CLEANUP,
                'keep_logs_days' => SchedulerConfig::KEEP_LOGS_DAYS
            ];
            echo json_encode(['success' => true, 'config' => $config]);
            break;

        case 'get_email_config':
            // API để lấy cấu hình email
            $config = [
                'smtp_host' => EmailConfig::SMTP_HOST,
                'smtp_port' => EmailConfig::SMTP_PORT,
                'smtp_username' => EmailConfig::SMTP_USERNAME,
                'smtp_encryption' => EmailConfig::SMTP_ENCRYPTION,
                'report_email' => EmailConfig::REPORT_EMAIL,
                'from_email' => EmailConfig::FROM_EMAIL,
                'from_name' => EmailConfig::FROM_NAME,
                'additional_emails' => EmailConfig::ADDITIONAL_EMAILS,
                'send_daily_report' => EmailConfig::SEND_DAILY_REPORT,
                'send_critical_only' => EmailConfig::SEND_CRITICAL_ONLY,
                'send_weekly_summary' => EmailConfig::SEND_WEEKLY_SUMMARY
            ];
            echo json_encode(['success' => true, 'config' => $config]);
            break;

        case 'update_email_recipients':
            // API để cập nhật danh sách email nhận báo cáo
            $requestData = json_decode(file_get_contents('php://input'), true);
            $emails = $requestData['emails'] ?? [];

            // Validate emails
            $validEmails = [];
            foreach ($emails as $email) {
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $validEmails[] = $email;
                }
            }

            // Lưu vào file config (tạm thời lưu vào data/email_config.json)
            $configFile = './data/email_config.json';
            $emailConfig = [
                'additional_emails' => $validEmails,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            file_put_contents($configFile, json_encode($emailConfig, JSON_PRETTY_PRINT));
            echo json_encode(['success' => true, 'emails' => $validEmails]);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Unknown action']);
    }

    exit;
}

// ==================== EMAIL CONFIGURATION ====================
class EmailConfig
{
    const SMTP_HOST = 'smtp.gmail.com';
    const SMTP_PORT = 465;
    const SMTP_USERNAME = 'nguyenvanhiep0711@gmail.com';
    const SMTP_PASSWORD = 'flnd neoz lhqw yzmd';
    const SMTP_ENCRYPTION = 'ssl';

    const REPORT_EMAIL = 'nguyenvanhiep0711@gmail.com';
    const FROM_EMAIL = 'nguyenvanhiep0711@gmail.com';
    const FROM_NAME = 'Security Scanner System';

    // Danh sách email nhận báo cáo (có thể cấu hình nhiều email)
    const ADDITIONAL_EMAILS = [
        // 'admin@company.com',
        // 'security@company.com'
    ];

    // Cấu hình loại email
    const SEND_DAILY_REPORT = true;        // Gửi báo cáo hàng ngày
    const SEND_CRITICAL_ONLY = false;      // Chỉ gửi khi có critical threats
    const SEND_WEEKLY_SUMMARY = true;      // Gửi tóm tắt hàng tuần
}

// ==================== SCHEDULER CONFIGURATION ====================
class SchedulerConfig
{
    // Cấu hình thời gian quét
    const DAILY_SCAN_TIME = '02:00';       // Quét hàng ngày lúc 2:00 AM
    const WEEKLY_SCAN_DAY = 'sunday';      // Quét tổng hợp vào Chủ nhật
    const MONTHLY_REPORT_DAY = 1;          // Báo cáo tháng vào ngày 1

    // Cấu hình quét
    const SCAN_TIMEOUT = 300;              // Timeout cho mỗi client (giây)
    const MAX_CONCURRENT_SCANS = 5;        // Số client quét đồng thời
    const RETRY_FAILED_SCANS = 3;          // Số lần retry khi scan fail

    // Cấu hình email
    const EMAIL_ON_CRITICAL = true;        // Gửi email ngay khi có critical
    const EMAIL_DAILY_SUMMARY = true;      // Gửi tóm tắt hàng ngày
    const EMAIL_WEEKLY_REPORT = true;      // Gửi báo cáo hàng tuần

    // Cấu hình lưu trữ
    const KEEP_LOGS_DAYS = 30;             // Giữ logs trong 30 ngày
    const KEEP_SCAN_HISTORY_DAYS = 90;     // Giữ lịch sử scan trong 90 ngày
    const AUTO_CLEANUP = true;             // Tự động dọn dẹp files cũ
}

// ==================== SCHEDULER CLASS ====================
class SecurityScheduler
{
    private $clientManager;
    private $scannerManager;
    private $logFile = './data/logs/scheduler.log';

    public function __construct($clientManager, $scannerManager)
    {
        $this->clientManager = $clientManager;
        $this->scannerManager = $scannerManager;

        // Tạo thư mục logs nếu chưa có
        if (!is_dir('./data/logs')) {
            mkdir('./data/logs', 0755, true);
        }
    }

    /**
     * Chạy quét tự động hàng ngày
     */
    public function runDailySecurityScan()
    {
        $this->log("Bắt đầu quét bảo mật tự động hàng ngày");

        try {
            $clients = $this->clientManager->getAllClients();
            $criticalThreats = array();
            $scanResults = array();

            foreach ($clients as $client) {
                $this->log("Đang quét client: " . $client['name']);

                // Thực hiện quét
                $result = $this->scannerManager->scanClient($client);

                if ($result['success']) {
                    $scanData = $result['data'];
                    $scanResults[] = array(
                        'client' => $client,
                        'scan_data' => $scanData
                    );

                    // Kiểm tra critical threats được cập nhật hôm nay
                    $todayThreats = $this->filterTodayThreats($scanData['threats'] ?? array());

                    if (!empty($todayThreats)) {
                        $criticalThreats[] = array(
                            'client' => $client,
                            'threats' => $todayThreats
                        );
                    }
                } else {
                    $this->log("Lỗi quét client " . $client['name'] . ": " . ($result['error'] ?? 'Unknown error'));
                }
            }

            // Gửi email báo cáo nếu có critical threats
            if (!empty($criticalThreats)) {
                $this->sendCriticalThreatsEmail($criticalThreats);
                $this->log("Đã gửi email báo cáo " . count($criticalThreats) . " clients có threats nghiêm trọng");
            } else {
                $this->log("Không có threats nghiêm trọng mới, không gửi email");
            }

            // Lưu kết quả scan
            $this->saveScanResults($scanResults);

            $this->log("Hoàn thành quét tự động");

        } catch (Exception $e) {
            $this->log("Lỗi trong quá trình quét tự động: " . $e->getMessage());
        }
    }

    /**
     * Lọc threats được cập nhật hôm nay
     */
    private function filterTodayThreats($threats)
    {
        $today = date('Y-m-d');
        $todayThreats = array();

        foreach ($threats as $threat) {
            // Kiểm tra file modified hôm nay và threat level >= 8 (critical)
            if (isset($threat['threat_level']) && $threat['threat_level'] >= 8) {
                $fileModified = date('Y-m-d', strtotime($threat['modified'] ?? ''));
                if ($fileModified === $today) {
                    $todayThreats[] = $threat;
                }
            }
        }

        return $todayThreats;
    }

    /**
     * Ghi log
     */
    private function log($message)
    {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message\n";
        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }

    /**
     * Lưu kết quả scan
     */
    private function saveScanResults($scanResults)
    {
        $filename = './data/logs/daily_scan_' . date('Y-m-d') . '.json';
        file_put_contents($filename, json_encode($scanResults, JSON_PRETTY_PRINT));
    }

    /**
     * Gửi email báo cáo critical threats
     */
    private function sendCriticalThreatsEmail($criticalThreats)
    {
        try {
            $subject = "🚨 BÁO CÁO BẢO MẬT KHẨN CẤP - " . date('d/m/Y H:i');
            $htmlBody = $this->generateEmailTemplate($criticalThreats);

            // Thử sử dụng PHPMailer trước, fallback sang mail() function
            $emailSent = false;

            if ($this->setupPHPMailer()) {
                try {
                    $this->sendEmailWithPHPMailer($subject, $htmlBody);
                    $emailSent = true;
                } catch (Exception $e) {
                    $this->log("PHPMailer failed, trying mail() function: " . $e->getMessage());
                }
            }

            if (!$emailSent) {
                $this->sendEmailWithMailFunction($subject, $htmlBody);
            }

        } catch (Exception $e) {
            $this->log("Lỗi gửi email: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Setup PHPMailer - tự động tải nếu chưa có
     */
    private function setupPHPMailer()
    {
        // Kiểm tra xem PHPMailer đã có chưa
        if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            return true;
        }

        // Thử include PHPMailer từ các vị trí phổ biến
        $possiblePaths = [
            './vendor/autoload.php',
            '../vendor/autoload.php',
            './PHPMailer/src/PHPMailer.php',
            './phpmailer/PHPMailerAutoload.php'
        ];

        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                require_once $path;
                if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                    $this->log("PHPMailer loaded from: " . $path);
                    return true;
                }
            }
        }

        $this->log("PHPMailer not found, will use mail() function");
        return false;
    }

    /**
     * Gửi email bằng PHPMailer
     */
    private function sendEmailWithPHPMailer($subject, $htmlBody)
    {
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);

            // SMTP configuration
            $mail->isSMTP();
            $mail->Host = EmailConfig::SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = EmailConfig::SMTP_USERNAME;
            $mail->Password = EmailConfig::SMTP_PASSWORD;
            $mail->SMTPSecure = EmailConfig::SMTP_ENCRYPTION; // 'ssl'
            $mail->Port = EmailConfig::SMTP_PORT; // 465
            $mail->CharSet = 'UTF-8';

            // Enable verbose debug output (disable in production)
            // $mail->SMTPDebug = 2;

            // Recipients
            $mail->setFrom(EmailConfig::FROM_EMAIL, EmailConfig::FROM_NAME);
            $mail->addAddress(EmailConfig::REPORT_EMAIL);

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;

            $mail->send();
            $this->log("Email đã gửi thành công qua PHPMailer đến " . EmailConfig::REPORT_EMAIL);

        } catch (Exception $e) {
            $this->log("Lỗi PHPMailer: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Gửi email bằng mail() function (fallback method)
     */
    private function sendEmailWithMailFunction($subject, $htmlBody)
    {
        // Configure SMTP settings for mail() function
        ini_set('SMTP', EmailConfig::SMTP_HOST);
        ini_set('smtp_port', EmailConfig::SMTP_PORT);
        ini_set('sendmail_from', EmailConfig::FROM_EMAIL);

        $headers = array(
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . EmailConfig::FROM_NAME . ' <' . EmailConfig::FROM_EMAIL . '>',
            'Reply-To: ' . EmailConfig::FROM_EMAIL,
            'X-Mailer: PHP/' . phpversion(),
            'X-Priority: 1',
            'X-MSMail-Priority: High'
        );

        $result = mail(EmailConfig::REPORT_EMAIL, $subject, $htmlBody, implode("\r\n", $headers));

        if ($result) {
            $this->log("Email đã gửi thành công qua mail() function đến " . EmailConfig::REPORT_EMAIL);
        } else {
            $this->log("Lỗi gửi email qua mail() function - kiểm tra cấu hình SMTP");
            throw new Exception("Mail function failed");
        }
    }

    /**
     * Tạo template email HTML
     */
    private function generateEmailTemplate($criticalThreats)
    {
        $totalThreats = 0;
        $totalClients = count($criticalThreats);

        foreach ($criticalThreats as $clientData) {
            $totalThreats += count($clientData['threats']);
        }

        $html = '<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo Cáo Bảo Mật Khẩn Cấp</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px; background-color: #f4f4f4; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #dc3545, #c82333); color: white; padding: 20px; border-radius: 8px; text-align: center; margin-bottom: 30px; }
        .alert { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .threat-item { background: #f8f9fa; border-left: 4px solid #dc3545; padding: 15px; margin: 10px 0; border-radius: 0 5px 5px 0; }
        .critical { border-left-color: #dc3545; }
        .high { border-left-color: #fd7e14; }
        .medium { border-left-color: #ffc107; }
        .client-section { margin: 30px 0; padding: 20px; border: 1px solid #dee2e6; border-radius: 8px; }
        .stats { display: flex; justify-content: space-around; margin: 20px 0; }
        .stat-item { text-align: center; padding: 15px; background: #e9ecef; border-radius: 8px; }
        .footer { margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px; text-align: center; font-size: 12px; color: #6c757d; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚨 BÁO CÁO BẢO MẬT KHẨN CẤP</h1>
            <p>Phát hiện ' . $totalThreats . ' mối đe dọa nghiêm trọng trên ' . $totalClients . ' website</p>
            <p><strong>Thời gian quét:</strong> ' . date('d/m/Y H:i:s') . ' (UTC+7)</p>
        </div>

        <div class="alert">
            <strong>⚠️ CẢNH BÁO:</strong> Hệ thống đã phát hiện các file hack nghiêm trọng được cập nhật trong ngày hôm nay.
            Vui lòng kiểm tra và xử lý ngay lập tức để bảo vệ website.
        </div>

        <div class="stats">
            <div class="stat-item">
                <h3>' . $totalClients . '</h3>
                <p>Websites bị ảnh hưởng</p>
            </div>
            <div class="stat-item">
                <h3>' . $totalThreats . '</h3>
                <p>Mối đe dọa nghiêm trọng</p>
            </div>
            <div class="stat-item">
                <h3>' . date('H:i') . '</h3>
                <p>Thời gian phát hiện</p>
            </div>
        </div>';

        foreach ($criticalThreats as $clientData) {
            $client = $clientData['client'];
            $threats = $clientData['threats'];

            $html .= '<div class="client-section">
                <h2>🌐 ' . htmlspecialchars($client['name']) . '</h2>
                <p><strong>URL:</strong> <a href="' . htmlspecialchars($client['url']) . '">' . htmlspecialchars($client['url']) . '</a></p>
                <p><strong>Số mối đe dọa:</strong> ' . count($threats) . '</p>

                <h3>📋 Danh sách files bị hack:</h3>';

            foreach ($threats as $threat) {
                $severityClass = $threat['threat_level'] >= 9 ? 'critical' : ($threat['threat_level'] >= 7 ? 'high' : 'medium');
                $severityText = $threat['threat_level'] >= 9 ? 'CỰC KỲ NGUY HIỂM' : ($threat['threat_level'] >= 7 ? 'NGUY HIỂM CAO' : 'NGUY HIỂM TRUNG BÌNH');

                $html .= '<div class="threat-item ' . $severityClass . '">
                    <h4>📁 ' . htmlspecialchars($threat['file']) . '</h4>
                    <p><strong>Mức độ nguy hiểm:</strong> <span style="color: #dc3545; font-weight: bold;">' . $severityText . ' (' . $threat['threat_level'] . '/10)</span></p>
                    <p><strong>Điểm rủi ro:</strong> ' . ($threat['risk_score'] ?? 'N/A') . '</p>
                    <p><strong>Kích thước file:</strong> ' . number_format($threat['size'] ?? 0) . ' bytes</p>
                    <p><strong>Thời gian cập nhật:</strong> ' . ($threat['modified'] ?? 'N/A') . '</p>
                    <p><strong>Các mối đe dọa phát hiện:</strong></p>
                    <ul>';

                foreach ($threat['threats'] as $t) {
                    $html .= '<li><strong>' . htmlspecialchars($t['pattern']) . '</strong> - ' . htmlspecialchars($t['description']) . ' (Dòng: ' . $t['line'] . ')</li>';
                }

                $html .= '</ul></div>';
            }

            $html .= '</div>';
        }

        $html .= '<div class="footer">
            <p><strong>Security Scanner System</strong> - Hệ thống quét bảo mật tự động</p>
            <p>Email này được gửi tự động vào lúc 22:00 hàng ngày (UTC+7)</p>
            <p>Để biết thêm chi tiết, vui lòng truy cập dashboard quản lý bảo mật</p>
            <p><em>⚠️ Vui lòng không reply email này. Đây là email tự động từ hệ thống.</em></p>
        </div>
    </div>
</body>
</html>';

        return $html;
    }

    /**
     * Test email function (public method for testing)
     */
    public function testEmail($testThreats)
    {
        $this->sendCriticalThreatsEmail($testThreats);
    }
}

// ==================== SECURITY REMEDIATION CLASS ====================
class SecurityRemediation
{
    private $clientManager;
    private $scannerManager;
    private $backupDir = './data/backups/';

    public function __construct($clientManager, $scannerManager)
    {
        $this->clientManager = $clientManager;
        $this->scannerManager = $scannerManager;

        // Ensure backup directory exists
        if (!file_exists($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }

    /**
     * Get list of available security fixes
     */
    public function getAvailableFixes()
    {
        return [
            'enhanced_shell_detection' => [
                'title' => 'Nâng Cấp Phát Hiện Shell & Malware',
                'description' => 'Cải tiến hàm check_shell() với 55+ patterns phát hiện shell, webshell, backdoor và malware. Bao gồm phát hiện base64, hex encoding, obfuscated code và các kỹ thuật ẩn mã độc tinh vi.',
                'file' => 'admin/lib/function.php',
                'severity' => 'critical',
                'estimated_time' => '2-3 phút',
                'benefits' => 'Tăng khả năng phát hiện malware lên 95%, bảo vệ khỏi shell injection và code injection'
            ],
            'hiep_security_class' => [
                'title' => 'Thêm HiepSecurity Class Bảo Mật Nâng Cao',
                'description' => 'Tạo class bảo mật tổng hợp với input sanitization, XSS protection, SQL injection prevention, rate limiting và session security. Tương thích PHP 5.6+ và 7.x.',
                'file' => 'admin/lib/class.php',
                'severity' => 'critical',
                'estimated_time' => '3-4 phút',
                'benefits' => 'Bảo vệ toàn diện khỏi XSS, SQL injection, CSRF và brute force attacks'
            ],
            'php_compatibility_fixes' => [
                'title' => 'Sửa Lỗi Tương Thích PHP 7.x+',
                'description' => 'Thay thế deprecated curly braces syntax {$var} thành [$var], sửa lỗi each() function và các deprecated functions khác để tương thích PHP 7.x và 8.x.',
                'file' => 'admin/lib/class.php',
                'severity' => 'warning',
                'estimated_time' => '1-2 phút',
                'benefits' => 'Đảm bảo website hoạt động ổn định trên PHP phiên bản mới'
            ],
            'htaccess_csrf_protection' => [
                'title' => 'Bảo Mật .htaccess với CSRF Protection',
                'description' => 'Thêm security headers (X-XSS-Protection, X-Frame-Options, X-Content-Type-Options), CSRF protection cho admin, chống clickjacking và XSS attacks.',
                'file' => '.htaccess',
                'severity' => 'critical',
                'estimated_time' => '2-3 phút',
                'benefits' => 'Bảo vệ khỏi CSRF, XSS, clickjacking và các attacks qua browser'
            ],
            'admin_htaccess_balanced' => [
                'title' => 'Bảo Mật Admin Panel Cân Bằng',
                'description' => 'Áp dụng bảo mật vừa phải cho admin panel: chặn bot, rate limiting cơ bản, ẩn sensitive files nhưng không ảnh hưởng đến functionality.',
                'file' => 'admin/.htaccess',
                'severity' => 'warning',
                'estimated_time' => '1-2 phút',
                'benefits' => 'Tăng bảo mật admin mà không gây khó khăn cho việc quản trị'
            ],
            'file_upload_security' => [
                'title' => 'Bảo Mật Upload File Nâng Cao',
                'description' => 'Tạo class HiepFileUploadSecurity với validation MIME type, file extension, file size, malicious content detection và secure file handling.',
                'file' => 'admin/filemanager/security_config.php',
                'severity' => 'critical',
                'estimated_time' => '2-3 phút',
                'benefits' => 'Ngăn chặn upload shell, malware và các file độc hại qua file manager'
            ],
            'sources_htaccess_protection' => [
                'title' => 'Bảo Mật Thư Mục Sources',
                'description' => 'Tạo file .htaccess bảo vệ thư mục admin/sources/ khỏi truy cập trực tiếp. Chặn execution PHP, deny all access, chỉ cho phép include từ admin.',
                'file' => 'admin/sources/.htaccess',
                'severity' => 'critical',
                'estimated_time' => '1 phút',
                'benefits' => 'Ngăn chặn truy cập trực tiếp vào backend files, bảo vệ source code'
            ],
            'ckeditor_htaccess_protection' => [
                'title' => 'Bảo Mật CKEditor',
                'description' => 'Tạo file .htaccess bảo vệ thư mục admin/ckeditor/ khỏi các cuộc tấn công. Chặn PHP execution, block suspicious requests, chỉ cho phép assets.',
                'file' => 'admin/ckeditor/.htaccess',
                'severity' => 'warning',
                'estimated_time' => '1 phút',
                'benefits' => 'Bảo vệ CKEditor khỏi file injection và XSS attacks'
            ],
            'lib_htaccess_protection' => [
                'title' => 'Bảo Mật Thư Viện Core',
                'description' => 'Tạo file .htaccess bảo vệ thư mục admin/lib/ chứa các file core. Deny all direct access, chỉ cho phép include từ admin.',
                'file' => 'admin/lib/.htaccess',
                'severity' => 'critical',
                'estimated_time' => '1 phút',
                'benefits' => 'Bảo vệ core libraries khỏi truy cập trực tiếp và code exposure'
            ]
        ];
    }

    /**
     * Create backup of file before modification using new client endpoint
     */
    private function createBackup($client, $filePath)
    {
        // Xử lý URL - nếu chưa có security_scan_client.php thì thêm vào
        $url = rtrim($client['url'], '/');
        if (strpos($url, 'security_scan_client.php') === false) {
            $url .= '/security_scan_client.php';
        }
        $url .= '?endpoint=backup_file&api_key=' . urlencode($client['api_key']);

        // Gửi request backup đến client
        $response = $this->scannerManager->makeApiRequest($url, 'POST', [
            'file_path' => $filePath,
            'reason' => 'Remediation backup before applying security fixes'
        ], $client['api_key']);

        if ($response['success'] && isset($response['data']['success']) && $response['data']['success']) {
            // Backup thành công trên client
            return [
                'success' => true,
                'file_exists' => true,
                'backup_path' => $response['data']['backup_path'],
                'backup_name' => $response['data']['backup_name'],
                'timestamp' => $response['data']['timestamp'],
                'file_size' => $response['data']['file_size'],
                'message' => 'Backup created successfully on client'
            ];
        } else if ($response['success'] && isset($response['data']['file_exists']) && !$response['data']['file_exists']) {
            // File không tồn tại - cho phép tạo file mới
            return [
                'success' => true,
                'file_exists' => false,
                'backup_path' => null,
                'message' => 'File không tồn tại, sẽ tạo file mới'
            ];
        } else {
            // Lỗi backup
            $error = $response['error'] ?? 'Unknown backup error';
            if (isset($response['data']['error'])) {
                $error = $response['data']['error'];
            }

            return [
                'success' => false,
                'error' => 'Backup failed: ' . $error
            ];
        }

        // File tồn tại - tạo backup
        $remoteBackupPath = dirname($filePath) . '/' . $backupName;
        $backupResult = $this->scannerManager->saveFileContent($client, $remoteBackupPath, $result['content']);

        // Nếu không tạo được remote backup, vẫn tiếp tục (chỉ cảnh báo)
        $remoteBackupSuccess = $backupResult['success'];

        // Save backup locally for safety
        $localBackupPath = $this->backupDir . $client['id'] . '_' . $backupName;
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
        file_put_contents($localBackupPath, $result['content']);

        return [
            'success' => true,
            'file_exists' => true,
            'local_backup' => $localBackupPath,
            'remote_backup' => $remoteBackupSuccess ? $remoteBackupPath : null,
            'original_content' => $result['content'],
            'message' => $remoteBackupSuccess ? 'Backup thành công' : 'Backup local thành công, remote backup thất bại'
        ];
    }

    /**
     * Apply enhanced shell detection fix
     */
    private function applyEnhancedShellDetection($client, $originalContent)
    {
        // Check if enhanced shell detection already exists
        if (strpos($originalContent, 'Enhanced shell detection function') !== false) {
            return ['success' => false, 'error' => 'Enhanced shell detection already exists'];
        }

        // Find the original check_shell function
        $functionStart = strpos($originalContent, 'function check_shell(');
        if ($functionStart === false) {
            return ['success' => false, 'error' => 'Original check_shell function not found'];
        }

        // Find the complete function by counting braces
        $braceCount = 0;
        $functionEnd = $functionStart;
        $inFunction = false;

        for ($i = $functionStart; $i < strlen($originalContent); $i++) {
            if ($originalContent[$i] === '{') {
                $braceCount++;
                $inFunction = true;
            } elseif ($originalContent[$i] === '}') {
                $braceCount--;
                if ($inFunction && $braceCount === 0) {
                    $functionEnd = $i + 1;
                    break;
                }
            }
        }

        if ($braceCount !== 0) {
            return ['success' => false, 'error' => 'Could not find complete check_shell function'];
        }

        $originalFunction = substr($originalContent, $functionStart, $functionEnd - $functionStart);

        // Enhanced function with proper escaping
        $enhancedFunction = '/**
 * Enhanced shell detection function
 * Compatible with PHP 5.6+ and 7.x
 * Detects various types of malicious code patterns
 */
function check_shell($text)
{
    // Basic shell patterns
    $basic_patterns = array(
        "<?php", "<?=", "<%", "<script",
        "eval(", "exec(", "system(", "shell_exec(", "passthru(",
        "base64_decode(", "gzinflate(", "str_rot13(",
        "file_get_contents(", "file_put_contents(", "fopen(", "fwrite(",
        "readdir(", "scandir(", "opendir(",
        "ini_get(", "ini_set(", "ini_restore(",
        "phpinfo(", "show_source(", "highlight_file(",
        "$_GET", "$_POST", "$_REQUEST", "$_COOKIE", "$_SESSION",
        "$_F=__FILE__;", "$_SERVER", "GLOBALS",
        "<form", "<input", "<button", "<iframe"
    );

    // Advanced malware patterns
    $advanced_patterns = array(
        "chr(", "ord(", "hexdec(", "dechex(",
        "pack(", "unpack(",
        "create_function(", "call_user_func(",
        "preg_replace.*\/e", "assert(",
        "include_once", "require_once",
        "ob_start(", "ob_get_contents(",
        "error_reporting(0)", "@error_reporting",
        "set_time_limit(0)", "@set_time_limit",
        "ignore_user_abort", "register_shutdown_function"
    );

    // Webshell specific patterns
    $webshell_patterns = array(
        "c99", "r57", "wso", "b374k", "adminer",
        "shell_exec", "backdoor", "rootkit",
        "FilesMan", "Sec-Info", "Safe-Mode",
        "mysql_connect", "mysql_query",
        "base64_encode.*base64_decode",
        "gzdeflate.*gzinflate",
        "str_replace.*preg_replace"
    );

    $detection_count = 0;
    $detected_patterns = array();

    // Check basic patterns
    foreach ($basic_patterns as $pattern) {
        if (stripos($text, $pattern) !== false) {
            $detection_count++;
            $detected_patterns[] = $pattern;
        }
    }

    // Check advanced patterns
    foreach ($advanced_patterns as $pattern) {
        if (stripos($text, $pattern) !== false) {
            $detection_count++;
            $detected_patterns[] = $pattern;
        }
    }

    // Check webshell patterns with regex
    foreach ($webshell_patterns as $pattern) {
        if (preg_match("/" . preg_quote($pattern, "/") . "/i", $text)) {
            $detection_count++;
            $detected_patterns[] = $pattern;
        }
    }

    // Additional checks for obfuscated code
    if (preg_match("/[a-zA-Z0-9+\/]{50,}={0,2}/", $text)) {
        $detection_count++;
        $detected_patterns[] = "base64_like_string";
    }

    // Check for hex encoded strings
    if (preg_match("/\\\\x[0-9a-fA-F]{2}/", $text)) {
        $detection_count++;
        $detected_patterns[] = "hex_encoded";
    }

    // Log detection if patterns found
    if ($detection_count > 0 && function_exists("hiep_log_security")) {
        hiep_log_security("Shell detection: " . $detection_count . " patterns found: " . implode(", ", $detected_patterns), "WARNING");
    }

    // Return empty string if malicious content detected
    if ($detection_count > 0) {
        return "";
    } else {
        return $text;
    }
}';

        // Replace the original function with enhanced version
        $newContent = substr($originalContent, 0, $functionStart) .
                     $enhancedFunction .
                     substr($originalContent, $functionEnd);

        return ['success' => true, 'content' => $newContent];
    }

    /**
     * Apply HiepSecurity class fix
     */
    private function applyHiepSecurityClass($client, $originalContent)
    {
        // Check if HiepSecurity class already exists
        if (strpos($originalContent, 'class HiepSecurity') !== false) {
            return ['success' => false, 'error' => 'HiepSecurity class already exists'];
        }

        $hiepSecurityClass = '
/**
 * ==========================================
 * HIEP SECURITY - ENHANCED SECURITY CLASS
 * ==========================================
 * Enhanced security features for CMS
 * Compatible with PHP 5.6+ and 7.x
 * ==========================================
 */

/**
 * Enhanced Security Class for CMS
 * Compatible with PHP 5.6+ and 7.x
 */
class HiepSecurity
{
    private $log_file;
    private $max_login_attempts = 5;
    private $lockout_duration = 900; // 15 minutes

    public function __construct()
    {
        $this->log_file = dirname(__FILE__) . \'/../logs/security.log\';
        $this->ensureLogDirectory();
    }

    /**
     * Create log directory if not exists
     */
    private function ensureLogDirectory()
    {
        $log_dir = dirname($this->log_file);
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
    }

    /**
     * Enhanced SQL injection prevention
     */
    public function sanitizeInput($input, $type = \'string\')
    {
        if (is_array($input)) {
            foreach ($input as $key => $value) {
                $input[$key] = $this->sanitizeInput($value, $type);
            }
            return $input;
        }

        // Remove null bytes
        $input = str_replace(chr(0), \'\', $input);

        // Basic XSS prevention
        $input = htmlspecialchars($input, ENT_QUOTES, \'UTF-8\');

        switch ($type) {
            case \'int\':
                return (int)$input;
            case \'float\':
                return (float)$input;
            case \'email\':
                return filter_var($input, FILTER_SANITIZE_EMAIL);
            case \'url\':
                return filter_var($input, FILTER_SANITIZE_URL);
            case \'sql\':
                // Additional SQL injection prevention
                $dangerous_patterns = array(
                    \'/(\s|^)(union|select|insert|update|delete|drop|create|alter|exec|execute)(\s|$)/i\',
                    \'/(\s|^)(or|and)(\s|$)(\d+(\s|$)=(\s|$)\d+|\\\'\w*\\\'(\s|$)=(\s|$)\\\'\w*\\\')/i\',
                    \'/(\s|^)(\\\'|\")(\s|$)(or|and)(\s|$)(\d+|\\\'\w*\\\')(\s|$)(=|like)(\s|$)(\d+|\\\'\w*\\\')/i\'
                );

                foreach ($dangerous_patterns as $pattern) {
                    if (preg_match($pattern, $input)) {
                        $this->logSecurity(\'SQL injection attempt detected: \' . substr($input, 0, 100), \'CRITICAL\');
                        return \'\';
                    }
                }
                return addslashes($input);
            default:
                return trim($input);
        }
    }

    /**
     * Security logging
     */
    public function logSecurity($message, $level = \'INFO\')
    {
        $timestamp = date(\'Y-m-d H:i:s\');
        $ip = isset($_SERVER[\'REMOTE_ADDR\']) ? $_SERVER[\'REMOTE_ADDR\'] : \'unknown\';
        $user_agent = isset($_SERVER[\'HTTP_USER_AGENT\']) ? $_SERVER[\'HTTP_USER_AGENT\'] : \'unknown\';

        $log_entry = "[{$timestamp}] [{$level}] IP: {$ip} | {$message} | User-Agent: {$user_agent}\n";
        file_put_contents($this->log_file, $log_entry, FILE_APPEND | LOCK_EX);
    }
}';

        // Add the class at the end of the file
        $newContent = $originalContent . $hiepSecurityClass;

        return ['success' => true, 'content' => $newContent];
    }

    /**
     * Apply PHP compatibility fixes
     */
    private function applyPhpCompatibilityFixes($client, $originalContent)
    {
        // Fix curly braces syntax
        $patterns = [
            '/\$(\w+)\{\s*(\d+)\s*\}/' => '$${1}[${2}]',  // $var{0} -> $var[0]
        ];

        $newContent = $originalContent;
        $fixCount = 0;

        foreach ($patterns as $pattern => $replacement) {
            $newContent = preg_replace($pattern, $replacement, $newContent, -1, $count);
            $fixCount += $count;
        }

        if ($fixCount > 0) {
            return ['success' => true, 'content' => $newContent, 'fixes_applied' => $fixCount];
        } else {
            return ['success' => false, 'error' => 'No PHP compatibility issues found'];
        }
    }

    /**
     * Apply .htaccess CSRF protection
     */
    private function applyHtaccessCsrfProtection($client, $originalContent)
    {
        // Check if CSRF protection already exists
        if (strpos($originalContent, 'CSRF') !== false) {
            return ['success' => false, 'error' => 'CSRF protection already exists'];
        }

        $csrfProtection = '
# ==========================================
# HIEP SECURITY - CSRF PROTECTION
# ==========================================

# Chống CSRF cho admin access
RewriteCond %{REQUEST_METHOD} ^POST$ [NC]
RewriteCond %{REQUEST_URI} ^/.*admin/ [NC]
RewriteCond %{HTTP_REFERER} !^$ [NC]
RewriteCond %{HTTP_REFERER} !^https?://(www\.)?%{HTTP_HOST}/ [NC]
RewriteCond %{HTTP_REFERER} !^https?://(www\.)?localhost/ [NC]
RewriteCond %{HTTP_REFERER} !^https?://(www\.)?127\.0\.0\.1/ [NC]
RewriteRule ^(.*)$ - [F,L]

# Security headers
<IfModule mod_headers.c>
    Header always set X-XSS-Protection "1; mode=block"
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>

';

        // Add CSRF protection at the beginning after RewriteEngine On
        if (strpos($originalContent, 'RewriteEngine') !== false) {
            $newContent = str_replace('RewriteEngine on', 'RewriteEngine on' . $csrfProtection, $originalContent);
        } else {
            $newContent = "RewriteEngine on\n" . $csrfProtection . $originalContent;
        }

        return ['success' => true, 'content' => $newContent];
    }

    /**
     * Execute remediation for selected fixes
     */
    public function executeRemediation($client, $selectedFixes)
    {
        $results = [];
        $availableFixes = $this->getAvailableFixes();

        foreach ($selectedFixes as $fixId) {
            if (!isset($availableFixes[$fixId])) {
                $results[$fixId] = ['success' => false, 'error' => 'Unknown fix ID'];
                continue;
            }

            $fix = $availableFixes[$fixId];
            $filePath = $fix['file'];

            try {
                // Create backup (hoặc xác nhận file không tồn tại)
                $backupResult = $this->createBackup($client, $filePath);
                if (!$backupResult['success']) {
                    $results[$fixId] = ['success' => false, 'error' => 'Lỗi backup: ' . $backupResult['error']];
                    continue;
                }

                $fileExists = $backupResult['file_exists'];
                $originalContent = $backupResult['original_content'];

                // Apply fix based on type
                switch ($fixId) {
                    case 'enhanced_shell_detection':
                        $fixResult = $this->applyEnhancedShellDetection($client, $originalContent);
                        break;
                    case 'hiep_security_class':
                        $fixResult = $this->applyHiepSecurityClass($client, $originalContent);
                        break;
                    case 'php_compatibility_fixes':
                        $fixResult = $this->applyPhpCompatibilityFixes($client, $originalContent);
                        break;
                    case 'htaccess_csrf_protection':
                        $fixResult = $this->applyHtaccessCsrfProtection($client, $originalContent);
                        break;
                    case 'admin_htaccess_balanced':
                        $fixResult = $this->applyAdminHtaccessBalanced($client, $originalContent);
                        break;
                    case 'file_upload_security':
                        $fixResult = $this->applyFileUploadSecurity($client, $originalContent);
                        break;
                    case 'sources_htaccess_protection':
                        $fixResult = $this->applySourcesHtaccessProtection($client, $originalContent);
                        break;
                    case 'ckeditor_htaccess_protection':
                        $fixResult = $this->applyCkeditorHtaccessProtection($client, $originalContent);
                        break;
                    case 'lib_htaccess_protection':
                        $fixResult = $this->applyLibHtaccessProtection($client, $originalContent);
                        break;
                    default:
                        $fixResult = ['success' => false, 'error' => 'Phương thức khắc phục chưa được triển khai'];
                }

                if ($fixResult['success']) {
                    // Validate the fixed content before saving
                    if ($this->validateFixedContent($fixResult['content'], $filePath)) {
                        // Save the fixed content
                        $saveResult = $this->scannerManager->saveFileContent($client, $filePath, $fixResult['content']);
                        if ($saveResult['success']) {
                            $results[$fixId] = [
                                'success' => true,
                                'backup_path' => $backupResult['remote_backup'],
                                'fixes_applied' => $fixResult['fixes_applied'] ?? 1,
                                'file_status' => $fileExists ? 'Đã cập nhật file hiện có' : 'Đã tạo file mới',
                                'backup_message' => $backupResult['message']
                            ];
                        } else {
                            // Rollback on save failure (chỉ khi file đã tồn tại)
                            if ($fileExists) {
                                $this->rollbackFile($client, $filePath, $originalContent);
                                $results[$fixId] = ['success' => false, 'error' => 'Lưu file thất bại, đã khôi phục về trạng thái ban đầu'];
                            } else {
                                $results[$fixId] = ['success' => false, 'error' => 'Không thể tạo file mới: ' . ($saveResult['error'] ?? 'Lỗi không xác định')];
                            }
                        }
                    } else {
                        // Content validation failed, don't save
                        $results[$fixId] = ['success' => false, 'error' => 'Nội dung sau khi sửa không hợp lệ, không áp dụng thay đổi'];
                    }
                } else {
                    $results[$fixId] = $fixResult;
                }

            } catch (Exception $e) {
                // Rollback on any exception
                if (isset($backupResult) && $backupResult['success']) {
                    $this->rollbackFile($client, $filePath, $backupResult['original_content']);
                }
                $results[$fixId] = ['success' => false, 'error' => 'Exception: ' . $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Validate fixed content for basic syntax errors
     */
    private function validateFixedContent($content, $filePath)
    {
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);

        // For PHP files, check basic syntax
        if ($extension === 'php') {
            // Check for basic PHP syntax issues
            if (strpos($content, '<?php') === false && strpos($content, '<?=') === false) {
                return false; // No PHP opening tag
            }

            // Check for unmatched quotes (basic check)
            $singleQuotes = substr_count($content, "'") - substr_count($content, "\\'");
            $doubleQuotes = substr_count($content, '"') - substr_count($content, '\\"');

            if ($singleQuotes % 2 !== 0 || $doubleQuotes % 2 !== 0) {
                return false; // Unmatched quotes
            }
        }

        return true;
    }

    /**
     * Rollback file to original content
     */
    private function rollbackFile($client, $filePath, $originalContent)
    {
        try {
            $this->scannerManager->saveFileContent($client, $filePath, $originalContent);
        } catch (Exception $e) {
            // Log rollback failure but don't throw
            error_log("Rollback failed for $filePath: " . $e->getMessage());
        }
    }

    /**
     * Apply admin .htaccess balanced security
     */
    private function applyAdminHtaccessBalanced($client, $originalContent)
    {
        // Check if balanced protection already exists
        if (strpos($originalContent, 'HiepSecurity Balanced') !== false) {
            return ['success' => false, 'error' => 'Admin .htaccess balanced protection already exists'];
        }

        $balancedHtaccess = '# HiepSecurity Balanced Admin Protection
# Moderate security without breaking functionality

# Deny access to sensitive files
<FilesMatch "\.(log|sql|bak|backup|old|tmp)$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Basic bot protection
RewriteEngine On
RewriteCond %{HTTP_USER_AGENT} ^$ [OR]
RewriteCond %{HTTP_USER_AGENT} (bot|crawler|spider) [NC]
RewriteRule ^(.*)$ - [F,L]

# Rate limiting (basic)
RewriteCond %{REMOTE_ADDR} !^127\.0\.0\.1$
RewriteCond %{REQUEST_METHOD} POST
RewriteCond %{THE_REQUEST} \s[A-Z]{3,9}\s/.*\sHTTP/
RewriteRule ^(.*)$ - [E=RATE_LIMITED:1]

# Security headers
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options SAMEORIGIN
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>

# Original content below
';

        $newContent = $balancedHtaccess . "\n" . $originalContent;
        return ['success' => true, 'content' => $newContent, 'fixes_applied' => 1];
    }

    /**
     * Apply file upload security enhancement
     */
    private function applyFileUploadSecurity($client, $originalContent)
    {
        // Check if file upload security already exists
        if (strpos($originalContent, 'HiepFileUploadSecurity') !== false) {
            return ['success' => false, 'error' => 'File upload security already exists'];
        }

        // Check if original content starts with <?php
        $hasPhpTag = (strpos(trim($originalContent), '<?php') === 0);

        $securityConfig = ($hasPhpTag ? '' : '<?php' . "\n") . '/**
 * HiepFileUploadSecurity - Enhanced File Upload Security
 * Compatible with PHP 5.6+ and 7.x
 */

class HiepFileUploadSecurity
{
    private static $allowedTypes = [
        "image/jpeg", "image/png", "image/gif", "image/webp",
        "application/pdf", "text/plain", "application/zip"
    ];

    private static $allowedExtensions = [
        "jpg", "jpeg", "png", "gif", "webp", "pdf", "txt", "zip"
    ];

    private static $maxFileSize = 5242880; // 5MB

    public static function validateUpload($file)
    {
        if (!isset($file["tmp_name"]) || !is_uploaded_file($file["tmp_name"])) {
            return ["success" => false, "error" => "Invalid file upload"];
        }

        // Check file size
        if ($file["size"] > self::$maxFileSize) {
            return ["success" => false, "error" => "File too large"];
        }

        // Check extension
        $extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
        if (!in_array($extension, self::$allowedExtensions)) {
            return ["success" => false, "error" => "File type not allowed"];
        }

        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file["tmp_name"]);
        finfo_close($finfo);

        if (!in_array($mimeType, self::$allowedTypes)) {
            return ["success" => false, "error" => "MIME type not allowed"];
        }

        // Check for malicious content
        $content = file_get_contents($file["tmp_name"]);
        if (self::containsMaliciousCode($content)) {
            return ["success" => false, "error" => "Malicious content detected"];
        }

        return ["success" => true];
    }

    private static function containsMaliciousCode($content)
    {
        $patterns = [
            "/<\?php/i", "/eval\s*\(/i", "/exec\s*\(/i", "/system\s*\(/i",
            "/shell_exec\s*\(/i", "/base64_decode\s*\(/i", "/<script/i"
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }
}

// Usage example:
// $result = HiepFileUploadSecurity::validateUpload($_FILES["upload"]);
// if (!$result["success"]) { die($result["error"]); }

' . $originalContent;

        return ['success' => true, 'content' => $securityConfig, 'fixes_applied' => 1];
    }

    /**
     * Apply sources .htaccess protection
     */
    private function applySourcesHtaccessProtection($client, $originalContent)
    {
        // Check if sources protection already exists
        if (strpos($originalContent, 'HiepSecurity Sources Protection') !== false) {
            return ['success' => false, 'error' => 'Sources .htaccess protection already exists'];
        }

        $sourcesHtaccess = '# HiepSecurity Sources Protection
# Bảo vệ thư mục sources khỏi truy cập trực tiếp

# Deny all direct access
Order Deny,Allow
Deny from all

# Allow only from localhost and admin
Allow from 127.0.0.1
Allow from ::1

# Block all file types except PHP (for admin access only)
<FilesMatch "\.(php|phtml|php3|php4|php5|php7)$">
    Order Deny,Allow
    Deny from all
    # Only allow from admin directory
    Allow from 127.0.0.1
</FilesMatch>

# Security headers
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
</IfModule>

# Disable PHP execution for uploaded files
<FilesMatch "\.(jpg|jpeg|png|gif|bmp|txt|doc|docx|pdf|zip|rar)$">
    ForceType application/octet-stream
    Header set Content-Disposition attachment
</FilesMatch>';

        return ['success' => true, 'content' => $sourcesHtaccess, 'fixes_applied' => 1];
    }

    /**
     * Apply CKEditor .htaccess protection
     */
    private function applyCkeditorHtaccessProtection($client, $originalContent)
    {
        // Check if CKEditor protection already exists
        if (strpos($originalContent, 'HiepSecurity CKEditor Protection') !== false) {
            return ['success' => false, 'error' => 'CKEditor .htaccess protection already exists'];
        }

        $ckeditorHtaccess = '# HiepSecurity CKEditor Protection
# Bảo vệ CKEditor khỏi các cuộc tấn công

# Deny access to sensitive files
<FilesMatch "\.(log|sql|bak|backup|old|tmp|conf|config|ini|md)$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Block PHP execution in uploads
<FilesMatch "\.php$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Security headers
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options SAMEORIGIN
</IfModule>

# Block suspicious requests
<IfModule mod_rewrite.c>
    RewriteEngine On

    # Block file injection attempts
    RewriteCond %{QUERY_STRING} \.\./\.\. [NC,OR]
    RewriteCond %{QUERY_STRING} (eval\(|base64_decode) [NC]
    RewriteRule ^(.*)$ - [F,L]
</IfModule>

# Allow only specific file types for uploads
<FilesMatch "\.(js|css|png|jpg|jpeg|gif|ico|woff|woff2|ttf|eot|svg)$">
    Order allow,deny
    Allow from all
</FilesMatch>';

        return ['success' => true, 'content' => $ckeditorHtaccess, 'fixes_applied' => 1];
    }

    /**
     * Apply lib .htaccess protection
     */
    private function applyLibHtaccessProtection($client, $originalContent)
    {
        // Check if lib protection already exists
        if (strpos($originalContent, 'HiepSecurity Lib Protection') !== false) {
            return ['success' => false, 'error' => 'Lib .htaccess protection already exists'];
        }

        $libHtaccess = '# HiepSecurity Lib Protection
# Bảo vệ thư mục lib chứa các file core

# Deny all direct access to PHP files
<FilesMatch "\.php$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Deny access to sensitive files
<FilesMatch "\.(log|sql|bak|backup|old|tmp|conf|config|ini|json)$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Security headers
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
</IfModule>

# Block all access except from admin
Order Deny,Allow
Deny from all
Allow from 127.0.0.1
Allow from ::1';

        return ['success' => true, 'content' => $libHtaccess, 'fixes_applied' => 1];
    }
}

// ==================== WEB INTERFACE ====================
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Scanner Server - Multi-Website Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- jQuery MUST be loaded before Bootstrap -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/monaco-editor@0.45.0/min/vs/loader.js"></script>
    <style>
        :root {
            /* Medical/Hospital Theme Color Palette */
            --primary-blue: #2563eb;
            --light-blue: #eff6ff;
            --soft-blue: #dbeafe;
            --dark-blue: #1d4ed8;
            --accent-blue: #3b82f6;

            /* Severity Colors */
            --critical-red: #dc2626;
            --priority-color: #2563eb;
            --priority-light: #eff6ff;
            --priority-border: #dbeafe;
            --critical-bg: #fef2f2;
            --critical-border: #fecaca;

            --warning-yellow: #d97706;
            --warning-bg: #fffbeb;
            --warning-border: #fed7aa;

            --info-blue: #2563eb;
            --info-bg: #eff6ff;
            --info-border: #dbeafe;

            /* Medical White/Clean Colors */
            --bg-primary: #ffffff;
            --bg-secondary: #f8fafc;
            --bg-card: #ffffff;
            --border-light: #e5e7eb;
            --border-medium: #d1d5db;

            --text-primary: #1f2937;
            --text-secondary: #6b7280;
            --text-muted: #9ca3af;

            /* Medical-themed Shadows with Blue Tint */
            --shadow-sm: 0 1px 2px 0 rgba(37, 99, 235, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(37, 99, 235, 0.08);
            --shadow-lg: 0 10px 15px -3px rgba(37, 99, 235, 0.1);
            --shadow-xl: 0 20px 25px -5px rgba(37, 99, 235, 0.12);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 50%, #f1f5f9 100%);
            min-height: 100vh;
            color: var(--text-primary);
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 0;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 60 60"><defs><pattern id="hexagon" width="30" height="26" patternUnits="userSpaceOnUse"><polygon points="15,2 25,8 25,18 15,24 5,18 5,8" fill="none" stroke="rgba(255,255,255,0.08)" stroke-width="1"/></pattern></defs><rect width="100%" height="100%" fill="url(%23hexagon)"/></svg>');
            opacity: 0.3;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            text-align: center;
        }

        .hero-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .hero-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 2rem;
        }

        /* Main Container */
        .main-container {
            max-width: 1600px;
            margin: -60px auto 0;
            padding: 0 20px 40px;
            position: relative;
            z-index: 10;
        }

        /* Dashboard Grid Layout */
        .bento-grid {
            margin-bottom: 30px;
        }

        .bento-item {
            background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
            border-radius: 24px;
            padding: 32px;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.08);
            border: 1px solid #e2e8f0;
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            position: relative;
            overflow: hidden;
        }

        .bento-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .bento-item:hover::before {
            opacity: 1;
        }

        .bento-item:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.15);
            border-color: #cbd5e1;
        }

        .bento-item.span-2 {
            grid-column: span 2;
        }

        .bento-item.span-3 {
            grid-column: span 3;
        }

        /* Card Headers */
        .card-header-modern {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 28px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e2e8f0;
            position: relative;
        }

        .card-header-modern::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 60px;
            height: 2px;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            border-radius: 2px;
        }

        .card-title-modern {
            font-size: 1.4rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
            letter-spacing: -0.02em;
        }

        .card-icon {
            width: 52px;
            height: 52px;
            background: linear-gradient(145deg, #667eea 0%, #764ba2 100%);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.3rem;
            box-shadow: 0 8px 24px rgba(102, 126, 234, 0.3);
            transition: all 0.3s ease;
        }

        .card-icon:hover {
            transform: scale(1.1) rotate(5deg);
            box-shadow: 0 12px 32px rgba(102, 126, 234, 0.4);
        }

        /* Client Management */
        .client-management {
            background: var(--bg-card);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-xl);
        }

        .clients-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .client-card {
            background: var(--bg-secondary);
            border-radius: 16px;
            padding: 20px;
            border: 2px solid var(--border-light);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .client-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-blue), var(--accent-blue));
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .client-card:hover::before {
            transform: scaleX(1);
        }

        .client-card:hover {
            transform: translateY(-3px);
            border-color: var(--primary-blue);
            box-shadow: var(--shadow-lg);
        }

        .client-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
        }

        .client-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .client-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-online {
            background: #D1FAE5;
            color: #065F46;
        }

        .status-offline {
            background: #FEE2E2;
            color: #991B1B;
        }

        .client-url {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin-bottom: 15px;
            word-break: break-all;
        }

        /* Modern Buttons */
        .btn-modern {
            padding: 8px 16px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-modern::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .btn-modern:hover::before {
            left: 100%;
        }

        .btn-modern:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-modern:active {
            transform: translateY(0);
        }

        .btn-scan {
            background: linear-gradient(135deg, var(--primary-blue), var(--accent-blue));
            color: white;
        }

        .btn-health {
            background: linear-gradient(135deg, #10B981, #059669);
            color: white;
        }

        .btn-danger {
            background: linear-gradient(135deg, var(--critical-red), #B91C1C);
            color: white;
        }

        .btn-warning {
            background: linear-gradient(135deg, var(--warning-yellow), #D97706);
            color: white;
        }

        .btn-remediate {
            background: linear-gradient(135deg, #8B5CF6, #7C3AED);
            color: white;
        }

        .btn-remediate:hover {
            background: linear-gradient(135deg, #7C3AED, #6D28D9);
            transform: translateY(-1px);
        }

        .btn-info {
            background: linear-gradient(135deg, var(--info-blue), #2563EB);
            color: white;
        }

        .btn-secondary {
            background: linear-gradient(135deg, #6B7280, #4B5563);
            color: white;
        }

        /* Scan Results */
        .scan-results {
            background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
            border-radius: 24px;
            padding: 36px;
            margin-top: 32px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.12);
            border: 1px solid #e2e8f0;
            position: relative;
        }

        .scan-results::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            border-radius: 24px 24px 0 0;
        }

        .results-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 25px;
        }

        .results-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .results-filters {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .filter-select {
            padding: 8px 12px;
            border: 1px solid var(--border-medium);
            border-radius: 8px;
            background: white;
            font-size: 0.85rem;
            color: var(--text-primary);
        }

        /* Threat Cards */
        .threats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            position: relative;
        }

        /* Collapsible Threat Cards */
        .threats-container.collapsed .threat-card:nth-child(n+6) {
            display: none;
        }

        .show-all-threats-btn {
            grid-column: 1 / -1;
            justify-self: center;
            margin-top: 20px;
            padding: 12px 24px;
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .show-all-threats-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
        }

        .show-all-threats-btn i {
            margin-left: 8px;
            transition: transform 0.3s ease;
        }

        .show-all-threats-btn.expanded i {
            transform: rotate(180deg);
        }

        .threat-card {
            background: var(--bg-card);
            border-radius: 16px;
            padding: 20px;
            border: 2px solid var(--border-light);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .threat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            transition: all 0.3s ease;
        }

        .threat-card.critical::before {
            background: linear-gradient(90deg, var(--critical-red), #EF4444);
        }

        .threat-card.warning::before {
            background: linear-gradient(90deg, var(--warning-yellow), #F59E0B);
        }

        .threat-card.info::before {
            background: linear-gradient(90deg, var(--info-blue), #3B82F6);
        }

        .threat-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
        }

        .threat-card.critical {
            border-color: var(--critical-border);
            background: var(--critical-bg);
            position: relative;
            z-index: 1;
        }

        .threat-card.warning {
            border-color: var(--warning-border);
            background: var(--warning-bg);
        }

        .threat-card.info {
            border-color: var(--info-border);
            background: var(--info-bg);
        }

        .threat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
        }

        .threat-path {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-primary);
            flex: 1;
            margin-right: 15px;
            word-break: break-all;
        }

        .threat-actions {
            display: flex;
            gap: 8px;
        }

        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .action-btn:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-sm);
        }

        .action-delete {
            background: var(--critical-red);
            color: white;
        }

        .action-quarantine {
            background: var(--warning-yellow);
            color: white;
        }

        .action-view {
            background: var(--info-blue);
            color: white;
        }

        .threat-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
        }

        .meta-badge {
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .meta-severity {
            color: white;
        }

        .meta-severity.critical {
            background: var(--critical-red);
        }

        .meta-severity.warning {
            background: var(--warning-yellow);
            display: none;
        }

        .meta-severity.info {
            background: var(--info-blue);
            display: none;
        }

        .meta-age {
            background: var(--bg-secondary);
            color: var(--text-secondary);
        }

        .meta-age.new {
            background: #10B981;
            color: white;
        }

        .meta-age.recent {
            background: #3742fa;
            color: white;
        }

        .meta-size {
            background: var(--bg-secondary);
            color: var(--text-secondary);
        }

        .meta-time {
            background: #f3f4f6;
            color: #6b7280;
            border: 1px solid #e5e7eb;
        }

        .meta-time i {
            font-size: 0.6rem;
        }

        .threat-details {
            font-size: 0.85rem;
            color: var(--text-secondary);
            line-height: 1.4;
        }

        .threat-pattern {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.8rem;
            background: rgba(0, 0, 0, 0.05);
            padding: 2px 6px;
            border-radius: 4px;
            margin: 0 2px;
        }

        /* Statistics */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .stat-card {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            border: 1px solid var(--border-light);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-blue);
            display: block;
        }

        .stat-label {
            font-size: 0.8rem;
            color: var(--text-secondary);
            margin-top: 5px;
        }

        /* Loading States */
        .loading-card {
            background: var(--bg-card);
            border-radius: 16px;
            padding: 40px;
            text-align: center;
            border: 1px solid var(--border-light);
        }

        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 3px solid var(--border-light);
            border-top: 3px solid var(--primary-blue);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* Animation Keyframes */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes shimmer {
            0% {
                background-position: -1000px 0;
            }

            100% {
                background-position: 1000px 0;
            }
        }

        @keyframes pulse {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }
        }

        /* Fade in animation for items */
        .bento-item,
        .multi-client-results,
        .scan-results {
            animation: fadeInUp 0.6s ease-out;
        }

        .bento-item:nth-child(1) {
            animation-delay: 0.1s;
        }

        .bento-item:nth-child(2) {
            animation-delay: 0.2s;
        }

        .bento-item:nth-child(3) {
            animation-delay: 0.3s;
        }

        /* Loading shimmer effect */
        .loading-shimmer {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 1000px 100%;
            animation: shimmer 2s infinite;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-container {
                padding: 0 15px;
            }

            .bento-item {
                padding: 24px;
            }

            .multi-client-header,
            .scan-results {
                padding: 24px;
            }

            .clients-grid {
                grid-template-columns: 1fr;
            }

            .threats-container {
                grid-template-columns: 1fr;
            }

            .hero-title {
                font-size: 2rem;
            }

            .results-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
        }

        /* Priority Files Styles */
        .priority-files-input-container {
            position: relative;
            margin-bottom: 15px;
        }

        .priority-files-input {
            background: var(--priority-light);
            border: 2px solid var(--priority-border);
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 14px;
            transition: all 0.3s ease;
            transition: all 0.3s ease;
        }

        .priority-files-input:focus {
            border-color: var(--priority-color);
            box-shadow: 0 0 0 3px rgba(147, 51, 234, 0.1);
            outline: none;
        }

        .input-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid var(--priority-border);
            border-radius: 8px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .suggestion-item {
            padding: 10px 15px;
            cursor: pointer;
            border-bottom: 1px solid var(--priority-border);
            transition: background-color 0.2s ease;
        }

        .suggestion-item:hover {
            background-color: var(--priority-light);
        }

        .suggestion-item:last-child {
            border-bottom: none;
        }

        .priority-files-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            min-height: 40px;
            padding: 8px 0;
        }

        .priority-tag {
            background: var(--priority-color);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
            animation: tagSlideIn 0.3s ease;
        }

        .priority-tag .remove-tag {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            border-radius: 50%;
            width: 16px;
            height: 16px;
            font-size: 10px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.2s ease;
        }

        .priority-tag .remove-tag:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        @keyframes tagSlideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .priority-stats {
            display: flex;
            gap: 20px;
            padding: 20px;
            background: var(--priority-light);
            border-radius: 12px;
            border: 1px solid var(--priority-border);
        }

        .stat-item {
            text-align: center;
            flex: 1;
        }

        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: var(--priority-color);
            margin-bottom: 4px;
        }

        .stat-label {
            font-size: 12px;
            color: #6b7280;
            font-weight: 500;
        }

        .common-patterns {
            margin-top: 20px;
        }

        .pattern-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }

        .pattern-buttons .btn {
            border-radius: 20px;
            padding: 6px 14px;
            font-size: 12px;
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }

        .pattern-buttons .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .pattern-buttons .btn:hover::before {
            left: 100%;
        }

        .pattern-buttons .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .btn-icon {
            background: none;
            border: none;
            color: #6b7280;
            font-size: 16px;
            cursor: pointer;
            padding: 8px;
            border-radius: 6px;
            transition: all 0.2s ease;
        }

        .btn-icon:hover {
            background: rgba(107, 114, 128, 0.1);
            color: var(--primary-blue);
        }

        .card-actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        @media (max-width: 768px) {
            .priority-stats {
                flex-direction: column;
                gap: 15px;
            }

            .pattern-buttons {
                justify-content: center;
            }
        }

        /* Modern Card Style */
        .modern-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid #E5E7EB;
            margin-bottom: 24px;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .modern-card:hover {
            box-shadow: 0 8px 40px rgba(0, 0, 0, 0.12);
            transform: translateY(-2px);
        }

        .modern-card .card-header {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-bottom: 1px solid #e2e8f0;
            padding: 20px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .modern-card .card-title {
            font-size: 16px;
            font-weight: 600;
            color: #1e293b;
            margin: 0;
            display: flex;
            align-items: center;
        }

        .modern-card .card-body {
            padding: 24px;
        }

        .modern-card .form-group {
            margin-bottom: 20px;
        }

        .modern-card .form-label {
            font-weight: 500;
            color: #374151;
            margin-bottom: 8px;
            display: block;
        }

        /* Code Editor Modal Styles */
        .code-editor-container {
            background: #1e1e1e;
            border-radius: 8px;
            overflow: hidden;
        }

        .editor-toolbar {
            background: #2d2d30;
            border-bottom: 1px solid #3e3e42;
            padding: 10px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .file-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .file-path {
            color: #cccccc;
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px;
            font-weight: 500;
        }

        .file-size {
            color: #858585;
            font-size: 11px;
            background: #3e3e42;
            padding: 2px 6px;
            border-radius: 4px;
        }

        .editor-actions {
            display: flex;
            gap: 8px;
        }

        .editor-actions .btn {
            font-size: 12px;
            padding: 4px 8px;
            border-radius: 4px;
        }

        .editor-status {
            display: flex;
            gap: 20px;
            font-size: 12px;
            color: #6b7280;
        }

        #monacoEditor {
            border: none;
            outline: none;
            resize: both;
            overflow: hidden;
            min-width: 400px;
            min-height: 300px;
            max-width: 95%;
            max-height: 70vh;
        }

        /* Fullscreen Modal Styles */
        .fullscreen-modal {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
            max-width: none !important;
            max-height: none !important;
            margin: 0 !important;
            z-index: 9999 !important;
        }

        .fullscreen-modal .modal-dialog {
            width: 100vw !important;
            height: 100vh !important;
            max-width: none !important;
            max-height: none !important;
            margin: 0 !important;
        }

        .fullscreen-modal .modal-content {
            width: 100% !important;
            height: 100vh !important;
            border-radius: 0 !important;
            border: none !important;
        }

        .fullscreen-modal .modal-body {
            height: calc(100vh - 120px) !important;
        }

        .fullscreen-modal #monacoEditor {
            height: calc(100vh - 180px) !important;
            max-height: none !important;
        }

        .modal-xl {
            max-width: 90vw;
        }

        .modal-xl .modal-content {
            height: 80vh;
        }

        .modal-xl .modal-body {
            flex: 1;
            overflow: hidden;
        }

        /* Two Column Layout Styles */
        .threats-main-container {
            background: white;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        }

        .threats-header {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            padding: 16px 20px;
            border-bottom: 1px solid #e2e8f0;
            border-radius: 12px 12px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .threat-count-badge {
            background: var(--primary-blue);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        /* Override threats container for main column */
        .threats-main-container .threats-container {
            padding: 20px;
            display: grid;
            gap: 16px;
        }

        /* Original threat card styles */
        .threats-main-container .threat-card {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .threats-main-container .threat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--info-color);
        }

        .threats-main-container .threat-card.critical::before {
            background: var(--critical-red);
        }

        .threats-main-container .threat-card.warning::before {
            background: var(--warning-color);
        }

        .threats-main-container .threat-card.info::before {
            background: var(--info-color);
        }

        .threats-main-container .threat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
            border-bottom: none;
            padding: 0;
            background: none;
            border-radius: 0;
        }

        .threats-main-container .threat-file {
            color: #1f2937;
            font-size: 16px;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            cursor: help;
            /* flex: 1; */
            max-width: 230px;
        }

        .threats-main-container .threat-actions {
            display: flex;
            gap: 8px;
            flex-shrink: 0;
        }

        .threats-main-container .action-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .threats-main-container .action-view {
            background: var(--primary-blue);
            color: white;
        }

        .threats-main-container .action-view:hover {
            background: var(--dark-blue);
            transform: translateY(-1px);
        }

        .threats-main-container .action-delete {
            background: var(--critical-red);
            color: white;
        }

        .threats-main-container .action-delete:hover {
            background: #b91c1c;
            transform: translateY(-1px);
        }

        /* Recent Threats Sidebar Styles */
        .recent-threats-sidebar {
            background: white;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            height: fit-content;
            position: sticky;
            top: 20px;
        }

        .sidebar-header {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            padding: 16px 20px;
            border-bottom: 1px solid #fecaca;
            border-radius: 12px 12px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .sidebar-header h6 {
            color: #dc2626;
            font-weight: 600;
            margin: 0;
        }

        .sidebar-refresh {
            cursor: pointer;
            color: #dc2626;
            padding: 4px;
            border-radius: 4px;
            transition: all 0.2s ease;
        }

        .sidebar-refresh:hover {
            background: rgba(220, 38, 38, 0.1);
            transform: rotate(180deg);
        }

        .recent-threats-container {
            padding: 12px;
            max-height: 600px;
            overflow-y: auto;
        }

        .recent-threat-item {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 8px;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .recent-threat-item:hover {
            background: #fee2e2;
            border-color: #fca5a5;
            transform: translateX(2px);
        }

        .recent-threat-item:last-child {
            margin-bottom: 0;
        }

        .recent-threat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 6px;
        }

        .recent-threat-file {
            font-size: 12px;
            font-weight: 600;
            color: #dc2626;
            word-break: break-all;
            cursor: pointer;
            flex: 1;
            margin-right: 8px;
        }

        .recent-threat-file:hover {
            color: #991b1b;
            text-decoration: underline;
        }

        .recent-threat-delete {
            background: none;
            border: none;
            color: #6b7280;
            cursor: pointer;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10px;
            transition: all 0.2s ease;
            flex-shrink: 0;
        }

        .recent-threat-delete:hover {
            background: #dc2626;
            color: white;
        }

        .recent-threat-meta {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .meta-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .recent-threat-severity {
            font-size: 10px;
            padding: 2px 8px;
            border-radius: 12px;
            font-weight: 600;
        }

        .recent-threat-age {
            font-size: 9px;
            padding: 1px 6px;
            border-radius: 8px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .recent-threat-age.new {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .recent-threat-age.recent {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }

        .recent-threat-age.old {
            background: #f3f4f6;
            color: #6b7280;
            border: 1px solid #e5e7eb;
        }

        .recent-threat-time {
            font-size: 10px;
            color: #6b7280;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .time-relative {
            font-weight: 600;
            color: #4b5563;
        }

        .time-absolute {
            font-size: 9px;
            color: #9ca3af;
            font-weight: 400;
            margin-top: 1px;
        }

        .sidebar-footer {
            padding: 12px 20px;
            background: #f9fafb;
            border-top: 1px solid #e5e7eb;
            border-radius: 0 0 12px 12px;
        }

        /* Multi-Client Results Styles */
        .multi-client-results {
            background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.12);
            margin: 32px 0;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }

        .multi-client-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            color: white;
            padding: 32px;
            position: relative;
            overflow: hidden;
        }

        .multi-client-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(255, 255, 255, 0.1) 0%, transparent 100%);
            pointer-events: none;
        }

        .multi-client-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .multi-client-title h2 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }

        .multi-client-stats {
            display: flex;
            gap: 24px;
            justify-content: center;
        }

        .stat-item {
            text-align: center;
            padding: 12px 20px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }

        .stat-item:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .stat-number {
            display: block;
            font-size: 24px;
            font-weight: 700;
            line-height: 1;
        }

        .stat-label {
            font-size: 12px;
            opacity: 0.8;
            margin-top: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .multi-client-content {
            padding: 24px;
        }

        .client-pagination-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            padding: 12px 16px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .pagination-controls {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .page-number {
            display: inline-block;
            padding: 4px 8px;
            margin: 0 2px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 14px;
        }

        .page-number:hover {
            background: #e9ecef;
        }

        .page-number.current {
            background: var(--primary-blue);
            color: white;
            font-weight: 600;
        }

        .client-table {
            background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.08);
            border: 1px solid #e2e8f0;
        }

        .client-table table {
            margin: 0;
            width: 100%;
        }

        .client-table th {
            background: linear-gradient(145deg, #f1f5f9 0%, #e2e8f0 100%);
            font-weight: 700;
            font-size: 14px;
            padding: 18px 20px;
            border-bottom: 2px solid #cbd5e1;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: relative;
        }

        .client-table th::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 30px;
            height: 2px;
            background: linear-gradient(90deg, #667eea, #764ba2);
            opacity: 0.6;
        }

        .client-table td {
            padding: 16px 20px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
            max-width: 100vw;
        }

        .client-row {
            transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }

        .client-row:hover {
            background: linear-gradient(145deg, #f8fafc 0%, #f1f5f9 100%);
            transform: scale(1.005);
        }

        .client-info-cell {
            padding: 12px;
            border-radius: 8px;
            background: var(--bg-card);
        }

        .client-name {
            font-size: 14px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 2px;
        }

        .client-url {
            font-size: 11px;
            color: #6b7280;
            word-break: break-all;
        }

        .expand-btn {
            border: none !important;
            padding: 8px 12px !important;
            transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            border-radius: 12px !important;
            background: linear-gradient(145deg, #f8fafc 0%, #e2e8f0 100%) !important;
            color: #475569 !important;
        }

        .expand-btn:hover {
            background: linear-gradient(145deg, #667eea 0%, #764ba2 100%) !important;
            color: white !important;
            transform: scale(1.1);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }

        .client-details-row {
            background: #f8f9fa;
        }

        .client-threats-container {
            padding: 20px;
            border-left: 4px solid var(--primary-blue);
            max-width: 100vw;
        }

        .threats-header {
            margin-bottom: 16px;
            padding-bottom: 8px;
            border-bottom: 1px solid #dee2e6;
        }

        .threats-header h6 {
            margin: 0;
            color: #1f2937;
            font-weight: 600;
        }

        .threats-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .threat-item {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 16px;
            transition: all 0.2s ease;
        }

        .threat-item:hover {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border-color: #d1d5db;
        }

        .threat-item.critical {
            border-left: 4px solid #ef4444;
        }

        .threat-item.warning {
            border-left: 4px solid #f59e0b;
        }

        .threat-item.info {
            border-left: 4px solid #3b82f6;
        }

        .threat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .threat-file-info {
            flex: 1;
            font-size: 14px;
        }

        .threat-actions {
            display: flex;
            gap: 8px;
        }

        .threat-actions .btn {
            font-size: 12px;
            padding: 4px 8px;
        }

        .threat-meta {
            display: flex;
            gap: 8px;
            margin-bottom: 8px;
        }

        .threat-meta .badge {
            font-size: 10px;
            padding: 4px 8px;
        }

        .threat-details {
            font-size: 12px;
            color: #6b7280;
        }

        .back-to-multi-client {
            margin-left: 16px;
            font-size: 12px !important;
            padding: 8px 16px !important;
            background: linear-gradient(145deg, #f8fafc 0%, #e2e8f0 100%) !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 12px !important;
            transition: all 0.3s ease !important;
        }

        .back-to-multi-client:hover {
            background: linear-gradient(145deg, #667eea 0%, #764ba2 100%) !important;
            color: white !important;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3) !important;
        }

        /* Enhanced Badge Styles */
        .badge {
            font-weight: 600 !important;
            padding: 6px 12px !important;
            border-radius: 12px !important;
            font-size: 11px !important;
            letter-spacing: 0.5px !important;
            text-transform: uppercase !important;
            position: relative;
            overflow: hidden;
        }

        .badge::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s ease;
        }

        .badge:hover::before {
            left: 100%;
        }

        .bg-success {
            background: linear-gradient(145deg, #10b981 0%, #059669 100%) !important;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3) !important;
        }

        .bg-warning {
            background: linear-gradient(145deg, #f59e0b 0%, #d97706 100%) !important;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3) !important;
        }

        .bg-danger {
            background: linear-gradient(145deg, #ef4444 0%, #dc2626 100%) !important;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3) !important;
        }

        .bg-secondary {
            background: linear-gradient(145deg, #6b7280 0%, #4b5563 100%) !important;
            box-shadow: 0 4px 12px rgba(107, 114, 128, 0.3) !important;
        }

        .client-details {
            text-align: left;
        }

        .detail-row {
            padding: 8px 0;
            border-bottom: 1px solid #f3f4f6;
            display: flex;
            justify-content: space-between;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .multi-client-summary {
                grid-template-columns: repeat(2, 1fr);
            }

            .client-results-grid {
                grid-template-columns: 1fr;
                padding: 16px;
            }

            .summary-card {
                padding: 16px;
            }
        }

        @media (max-width: 480px) {
            .multi-client-summary {
                grid-template-columns: 1fr;
            }
        }

        /* Enhanced threat card with tooltip trigger */
        .threat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
        }

        .threat-file {
            color: #1f2937;
            font-size: 16px;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: help;
        }

        .threat-icon {
            color: #6b7280;
            font-size: 14px;
        }

        .threat-patterns {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 8px;
        }

        .threat-pattern {
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        @media (max-width: 768px) {
            .row {
                flex-direction: column;
            }

            .col-md-8,
            .col-md-4 {
                max-width: 100%;
                flex: 0 0 100%;
            }

            .recent-threats-sidebar {
                margin-top: 20px;
                position: static;
            }
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }

        /* Micro-interactions */
        .pulse-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #10B981;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.5;
            }
        }

        /* System Health */
        .health-item {
            padding: 12px 0;
            border-bottom: 1px solid var(--border-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .health-item:last-child {
            border-bottom: none;
        }

        .health-label {
            font-size: 0.85rem;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .health-value {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-direction: column;
            text-align: right;
        }

        .progress {
            width: 80px;
            height: 6px;
            background: var(--bg-secondary);
            border-radius: 3px;
            overflow: hidden;
        }

        .progress-bar {
            height: 100%;
            border-radius: 3px;
            transition: width 0.3s ease;
        }

        .bg-primary {
            background: var(--primary-blue) !important;
        }

        .bg-success {
            background: #10B981 !important;
        }

        .bg-warning {
            background: var(--warning-yellow) !important;
        }

        /* Recent Activity */
        .activity-item {
            padding: 12px 0;
            border-bottom: 1px solid var(--border-light);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--bg-secondary);
            font-size: 0.85rem;
            flex-shrink: 0;
        }

        .activity-content {
            flex: 1;
        }

        .activity-title {
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text-primary);
            margin-bottom: 2px;
        }

        .activity-time {
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        .text-success {
            color: #10B981 !important;
        }

        .text-warning {
            color: var(--warning-yellow) !important;
        }

        .text-primary {
            color: var(--primary-blue) !important;
        }

        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        /* ==================== HIEP FUTURISTIC MODAL STYLES ==================== */

        .hiep-futuristic-modal {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.98) 0%, rgba(248, 250, 252, 0.95) 100%);
            border: 1px solid rgba(37, 99, 235, 0.15);
            border-radius: 24px;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            box-shadow:
                0 25px 50px -12px rgba(37, 99, 235, 0.15),
                0 0 0 1px rgba(37, 99, 235, 0.08),
                inset 0 1px 0 rgba(255, 255, 255, 0.8);
            /* overflow: hidden; */
            position: relative;
            height: auto !important;
        }

        .hiep-modal-bg {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: -1;
            overflow: hidden;
        }

        .hiep-bg-particles {
            position: absolute;
            width: 100%;
            height: 100%;
            background:
                radial-gradient(circle at 20% 20%, rgba(37, 99, 235, 0.06) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(59, 130, 246, 0.04) 0%, transparent 50%),
                radial-gradient(circle at 40% 60%, rgba(5, 150, 105, 0.03) 0%, transparent 50%);
            animation: hiepParticleFloat 20s ease-in-out infinite;
        }

        .hiep-bg-grid {
            position: absolute;
            width: 100%;
            height: 100%;
            background-image:
                linear-gradient(rgba(37, 99, 235, 0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(37, 99, 235, 0.04) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: hiepGridMove 30s linear infinite;
        }

        @keyframes hiepParticleFloat {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            33% { transform: translate(30px, -30px) rotate(120deg); }
            66% { transform: translate(-20px, 20px) rotate(240deg); }
        }

        @keyframes hiepGridMove {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }

        /* Modal Header */
        .hiep-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 32px 40px 24px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.1);
            background: linear-gradient(90deg, rgba(59, 130, 246, 0.05) 0%, rgba(147, 51, 234, 0.05) 100%);
        }

        .hiep-header-content {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .hiep-header-icon {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #3b82f6 0%, #9333ea 100%);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: white;
            box-shadow: 0 8px 32px rgba(59, 130, 246, 0.3);
            animation: hiepIconPulse 3s ease-in-out infinite;
        }

        @keyframes hiepIconPulse {
            0%, 100% { transform: scale(1); box-shadow: 0 8px 32px rgba(59, 130, 246, 0.3); }
            50% { transform: scale(1.05); box-shadow: 0 12px 40px rgba(59, 130, 246, 0.4); }
        }

        .hiep-header-text h2,
        .hiep-modal-title {
            font-size: 28px;
            font-weight: 700;
            color: #1e293b !important;
            margin: 0;
            letter-spacing: -0.5px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .hiep-modal-subtitle {
            color: rgba(148, 163, 184, 0.8);
            font-size: 16px;
            margin: 4px 0 0 0;
            font-weight: 400;
        }

        .hiep-close-btn {
            width: 48px;
            height: 48px;
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 12px;
            color: #ef4444;
            font-size: 18px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .hiep-close-btn:hover {
            background: rgba(239, 68, 68, 0.2);
            border-color: rgba(239, 68, 68, 0.4);
            transform: scale(1.05);
        }

        /* Modal Body */
        .hiep-modal-body {
            padding: 40px;
            min-height: 400px;
        }

        /* Loading State */
        .hiep-loading-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 300px;
            gap: 32px;
        }

        .hiep-loading-spinner {
            position: relative;
            width: 120px;
            height: 120px;
        }

        .hiep-spinner-ring {
            position: absolute;
            width: 100%;
            height: 100%;
            border: 3px solid transparent;
            border-radius: 50%;
            animation: hiepSpinnerRotate 2s linear infinite;
        }

        .hiep-spinner-ring:nth-child(1) {
            border-top-color: #3b82f6;
            animation-duration: 2s;
        }

        .hiep-spinner-ring:nth-child(2) {
            border-right-color: #9333ea;
            animation-duration: 3s;
            animation-direction: reverse;
        }

        .hiep-spinner-ring:nth-child(3) {
            border-bottom-color: #10b981;
            animation-duration: 4s;
        }

        @keyframes hiepSpinnerRotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .hiep-loading-text {
            text-align: center;
        }

        .hiep-loading-text h3 {
            font-size: 24px;
            font-weight: 600;
            color: #ffffff;
            margin: 0 0 8px 0;
        }

        .hiep-loading-text p {
            font-size: 16px;
            color: rgba(148, 163, 184, 0.8);
            margin: 0;
        }

        /* Content State */
        .hiep-content-container {
            animation: hiepFadeInUp 0.6s ease-out;
        }

        @keyframes hiepFadeInUp {
            0% { opacity: 0; transform: translateY(30px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        .hiep-info-banner {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 24px;
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.1) 0%, rgba(217, 119, 6, 0.1) 100%);
            border: 1px solid rgba(245, 158, 11, 0.2);
            border-radius: 16px;
            margin-bottom: 32px;
        }

        .hiep-banner-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: white;
            flex-shrink: 0;
        }

        .hiep-banner-text h4 {
            font-size: 20px;
            font-weight: 600;
            color: #ffffff;
            margin: 0 0 4px 0;
        }

        .hiep-banner-text p {
            font-size: 14px;
            color: rgba(148, 163, 184, 0.8);
            margin: 0;
        }

        /* Bento Grid Layout */
        .hiep-bento-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }

        .hiep-fix-card {
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.8) 0%, rgba(51, 65, 85, 0.8) 100%);
            border: 1px solid rgba(148, 163, 184, 0.1);
            border-radius: 16px;
            padding: 24px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .hiep-fix-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #3b82f6 0%, #9333ea 50%, #10b981 100%);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .hiep-fix-card:hover {
            transform: translateY(-4px);
            border-color: rgba(59, 130, 246, 0.3);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }

        .hiep-fix-card:hover::before {
            transform: scaleX(1);
        }

        .hiep-fix-card.selected {
            border-color: rgba(59, 130, 246, 0.5);
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(147, 51, 234, 0.1) 100%);
        }

        .hiep-fix-card.selected::before {
            transform: scaleX(1);
        }

        .hiep-fix-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .hiep-fix-title {
            font-size: 18px;
            font-weight: 600;
            color: #ffffff;
            margin: 0;
        }

        .hiep-severity-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .hiep-severity-critical {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .hiep-severity-warning {
            background: rgba(245, 158, 11, 0.2);
            color: #f59e0b;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        .hiep-fix-description {
            color: rgba(148, 163, 184, 0.9);
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 16px;
        }

        .hiep-fix-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            color: rgba(148, 163, 184, 0.7);
        }

        .hiep-fix-checkbox {
            position: absolute;
            top: 16px;
            right: 16px;
            width: 20px;
            height: 20px;
            accent-color: #3b82f6;
        }

        /* Select All */
        .hiep-select-all {
            padding: 20px 24px;
            background: rgba(30, 41, 59, 0.5);
            border: 1px solid rgba(148, 163, 184, 0.1);
            border-radius: 12px;
            margin-bottom: 32px;
        }

        .hiep-checkbox-container {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .hiep-checkbox {
            width: 18px;
            height: 18px;
            accent-color: #3b82f6;
        }

        .hiep-checkbox-label {
            color: #ffffff;
            font-weight: 500;
            cursor: pointer;
        }

        /* Results State */
        .hiep-results-container {
            animation: hiepFadeInUp 0.6s ease-out;
        }

        .hiep-results-header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
        }

        .hiep-results-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: white;
        }

        .hiep-results-header h3 {
            font-size: 24px;
            font-weight: 600;
            color: #ffffff;
            margin: 0;
        }

        .hiep-results-content {
            background: rgba(30, 41, 59, 0.5);
            border: 1px solid rgba(148, 163, 184, 0.1);
            border-radius: 12px;
            padding: 24px;
        }

        /* Modal Footer */
        .hiep-modal-footer {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 16px;
            padding: 24px 40px 32px;
            border-top: 1px solid rgba(148, 163, 184, 0.1);
            background: linear-gradient(90deg, rgba(30, 41, 59, 0.5) 0%, rgba(51, 65, 85, 0.5) 100%);
        }

        /* Futuristic Buttons */
        .hiep-btn {
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 14px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            position: relative;
            overflow: hidden;
            text-decoration: none;
        }

        .hiep-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .hiep-btn:hover::before {
            left: 100%;
        }

        .hiep-btn-secondary {
            background: rgba(71, 85, 105, 0.8);
            color: #e2e8f0;
            border: 1px solid rgba(148, 163, 184, 0.3);
        }

        .hiep-btn-secondary:hover {
            background: rgba(71, 85, 105, 1);
            border-color: rgba(148, 163, 184, 0.5);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
        }

        .hiep-btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            border: 1px solid rgba(59, 130, 246, 0.5);
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
        }

        .hiep-btn-primary:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
        }

        .hiep-btn-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: 1px solid rgba(16, 185, 129, 0.5);
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }

        .hiep-btn-success:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .hiep-modal-header {
                padding: 24px 20px 16px;
            }

            .hiep-header-content {
                gap: 16px;
            }

            .hiep-header-icon {
                width: 48px;
                height: 48px;
                font-size: 20px;
            }

            .hiep-header-text h2 {
                font-size: 20px;
            }

            .hiep-modal-subtitle {
                font-size: 14px;
            }

            .hiep-modal-body {
                padding: 24px 20px;
            }

            .hiep-bento-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .hiep-fix-card {
                padding: 20px;
            }

            .hiep-modal-footer {
                padding: 16px 20px 24px;
                flex-direction: column;
                gap: 12px;
            }

            .hiep-btn {
                width: 100%;
                justify-content: center;
            }
        }

        /* Animation for modal entrance */
        .modal.fade .hiep-futuristic-modal {
            transform: scale(0.8) translateY(-50px);
            opacity: 0;
        }

        .modal.show .hiep-futuristic-modal {
            transform: scale(1) translateY(0);
            opacity: 1;
            transition: all 0.3s ease-out;
        }
    </style>
</head>

<body>
    <!-- Hero Section -->
    <div class="hero-section">
        <div class="hero-content">
            <h1 class="hero-title">
                <i class="fas fa-shield-alt"></i> Security Scanner Server
            </h1>
            <p class="hero-subtitle">
                Quản lý bảo mật tập trung cho nhiều website - Phát hiện và xử lý threats tự động
            </p>
        </div>
    </div>

    <!-- Main Container -->
    <div class="main-container">
        <!-- Priority Files Section -->
        <div class="modern-card mb-4">
            <div class="card-header">
                <h6 class="card-title">
                    <i class="fas fa-search-plus me-2"></i>Priority Files Scanner
                </h6>
                <div class="card-actions">
                    <button class="btn-icon" onclick="togglePriorityFiles()" id="priorityToggle">
                        <i class="fas fa-chevron-down"></i>
                    </button>
                </div>
            </div>
            <div class="card-body" id="priorityFilesContent" style="display: none;">
                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-file-code me-1"></i>Priority File Patterns
                            </label>
                            <div class="priority-files-input-container">
                                <input type="text"
                                    class="form-control priority-files-input"
                                    id="priorityFileInput"
                                    placeholder="Nhập tên file hoặc pattern (vd: *.php, shell.php, config.*)">
                                <div class="input-suggestions" id="patternSuggestions"></div>
                            </div>
                            <div class="priority-files-tags" id="priorityFilesTags"></div>
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                Các file này sẽ được ưu tiên quét trước. Hỗ trợ wildcard (*) và regex patterns.
                            </small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="priority-stats">
                            <div class="stat-item">
                                <div class="stat-value" id="priorityFilesCount">0</div>
                                <div class="stat-label">Priority Files</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value" id="priorityScore">0</div>
                                <div class="stat-label">Priority Score</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Common Patterns Quick Add -->
                <div class="common-patterns mt-3">
                    <label class="form-label">
                        <i class="fas fa-magic me-1"></i>Common Suspicious Patterns
                    </label>
                    <div class="pattern-buttons">
                        <button class="btn btn-outline-warning btn-sm" onclick="addCommonPattern('shell.php')">
                            <i class="fas fa-bug me-1"></i>shell.php
                        </button>
                        <button class="btn btn-outline-warning btn-sm" onclick="addCommonPattern('*.php.txt')">
                            <i class="fas fa-file-code me-1"></i>*.php.txt
                        </button>
                        <button class="btn btn-outline-warning btn-sm" onclick="addCommonPattern('config.php')">
                            <i class="fas fa-cog me-1"></i>config.php
                        </button>
                        <button class="btn btn-outline-warning btn-sm" onclick="addCommonPattern('upload*.php')">
                            <i class="fas fa-upload me-1"></i>upload*.php
                        </button>
                        <button class="btn btn-outline-warning btn-sm" onclick="addCommonPattern('admin*.php')">
                            <i class="fas fa-user-shield me-1"></i>admin*.php
                        </button>
                        <button class="btn btn-outline-danger btn-sm" onclick="addCommonPattern('eval*.php')">
                            <i class="fas fa-exclamation-triangle me-1"></i>eval*.php
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Client Management -->
        <div class="client-management">
            <div class="card-header-modern">
                <div class="card-icon">
                    <i class="fas fa-server"></i>
                </div>
                <h2 class="card-title-modern">Quản Lý Clients</h2>
                <div class="ms-auto">
                    <button class="btn btn-modern btn-scan" onclick="scanAllClients()">
                        <i class="fas fa-search"></i> Quét Tất Cả
                    </button>
                    <button class="btn btn-modern btn-secondary" data-bs-toggle="modal" data-bs-target="#addClientModal">
                        <i class="fas fa-plus"></i> Thêm Client
                    </button>
                </div>
            </div>

            <div class="clients-grid" id="clientsGrid">
                <!-- Clients will be loaded here -->
            </div>
        </div>
                   
                </div>
            </div>
        </div>
    </div>

    <!-- Dashboard Grid -->
    <div class="main-container mt-0">
        <div class="row bento-grid">
            <!-- Statistics Overview -->
            <div class="col-12 col-lg-4 mb-4">
                <div class="bento-item h-100">
                    <div class="card-header-modern">
                        <div class="card-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3 class="card-title-modern">Thống Kê Tổng Quan</h3>
                    </div>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <span class="stat-number" id="totalClients">0</span>
                            <div class="stat-label">Clients</div>
                        </div>
                        <div class="stat-card">
                            <span class="stat-number" id="totalThreats">0</span>
                            <div class="stat-label">Threats</div>
                        </div>
                        <div class="stat-card">
                            <span class="stat-number" id="criticalThreats">0</span>
                            <div class="stat-label">Critical</div>
                        </div>
                        <div class="stat-card">
                            <span class="stat-number" id="cleanClients">0</span>
                            <div class="stat-label">Clean</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="col-12 col-lg-4 mb-4">
                <div class="bento-item h-100">
                    <div class="card-header-modern">
                        <div class="card-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h3 class="card-title-modern">Hoạt Động Gần Đây</h3>
                    </div>
                    <div id="recentActivity">
                        <!-- Recent activity will be loaded here -->
                    </div>
                </div>
            </div>

            <!-- System Health -->
            <div class="col-12 col-lg-4 mb-4">
                <div class="bento-item h-100 ">
                    <div class="card-header-modern">
                        <div class="card-icon">
                            <i class="fas fa-heartbeat"></i>
                        </div>
                        <h3 class="card-title-modern">Trạng Thái Hệ Thống</h3>
                    </div>
                    <div id="systemHealth">
                        <!-- System health will be loaded here -->
                    </div>
                </div>
            </div>
        </div>


        <!-- Scan Results -->
        <div class="scan-results" id="scanResults" style="display: none;">
            <div class="results-header">
                <h2 class="results-title">
                    <i class="fas fa-bug"></i> Kết Quả Quét Bảo Mật
                </h2>
                <div class="results-filters">
                    <select class="filter-select" id="severityFilter">
                        <option value="all">Tất cả mức độ</option>
                        <option value="critical">Nguy hiểm (Đỏ)</option>
                        <option value="warning">Cảnh báo (Vàng)</option>
                        <option value="info">Thông tin (Xanh)</option>
                    </select>
                    <select class="filter-select" id="ageFilter">
                        <option value="all">Tất cả thời gian</option>
                        <option value="new">Mới (1 tuần)</option>
                        <option value="recent">Gần đây (5 tháng)</option>
                        <option value="old">Cũ (>5 tháng)</option>
                    </select>
                </div>
            </div>

            <!-- Two Column Layout -->
            <div class="row mt-4">
                <!-- Left Column - Main Threats List (Large) -->
                <div class="col-md-8">
                    <div class="threats-main-container">
                        <div class="threats-header">
                            <h5 class="mb-0">
                                <i class="fas fa-list-alt me-2"></i>Danh Sách File Đã Quét
                            </h5>
                            <div class="threat-count-badge" id="mainThreatCount">0 files</div>
                        </div>
                        <div class="threats-container" id="threatsContainer">
                            <!-- Main threats list will be populated by JavaScript -->
                        </div>
                    </div>
                </div>

                <!-- Right Column - Recent Threats Sidebar -->
                <div class="col-md-4">
                    <div class="recent-threats-sidebar">
                        <div class="sidebar-header">
                            <h6 class="mb-0">
                                <i class="fas fa-clock me-2"></i>File Nguy Hiểm Gần Đây
                            </h6>
                            <div class="sidebar-refresh" onclick="refreshRecentThreats()">
                                <i class="fas fa-sync-alt"></i>
                            </div>
                        </div>
                        <div class="recent-threats-container" id="recentThreatsContainer">
                            <!-- Recent threats will be populated by JavaScript -->
                        </div>
                        <div class="sidebar-footer">
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                Hiển thị files <strong>nguy hiểm</strong> được phát hiện trong <strong>5 tháng qua</strong>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

    <!-- Add Client Modal -->
    <div class="modal fade" id="addClientModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Thêm Client Mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addClientForm">
                        <div class="mb-3">
                            <label for="clientName" class="form-label">Tên Client</label>
                            <input type="text" class="form-control" id="clientName" required>
                        </div>
                        <div class="mb-3">
                            <label for="clientUrl" class="form-label">URL</label>
                            <input type="url" class="form-control" id="clientUrl" placeholder="https://example.com" required>
                        </div>
                        <div class="mb-3">
                            <label for="clientApiKey" class="form-label">API Key</label>
                            <input type="text" class="form-control" id="clientApiKey" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-primary" onclick="addClient()">Thêm Client</button>
                </div>
            </div>
        </div>
    </div>

    <!-- HIEP FUTURISTIC SECURITY REMEDIATION MODAL -->
    <div class="modal fade" id="remediationModal" tabindex="-1">
        <div class="modal-dialog modal-xl ">
            <div class="modal-content hiep-futuristic-modal ">
                <!-- Animated Background -->
                <div class="hiep-modal-bg">
                    <div class="hiep-bg-particles"></div>
                    <div class="hiep-bg-grid"></div>
                </div>

                <!-- Header -->
                <div class="hiep-modal-header">
                    <div class="hiep-header-content">
                        <div class="hiep-header-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div class="hiep-header-text">
                            <h2 class="hiep-modal-title text-dark" >Security Remediation Center</h2>
                            <p class="hiep-modal-subtitle">Advanced threat mitigation & system hardening</p>
                        </div>
                    </div>
                    <button type="button" class="hiep-close-btn" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <!-- Body -->
                <div class="hiep-modal-body">
                    <!-- Loading State -->
                    <div id="remediationLoading" class="hiep-loading-container" style="display: none;">
                        <div class="hiep-loading-spinner">
                            <div class="hiep-spinner-ring"></div>
                            <div class="hiep-spinner-ring"></div>
                            <div class="hiep-spinner-ring"></div>
                        </div>
                        <div class="hiep-loading-text">
                            <h3>Analyzing Security Vulnerabilities</h3>
                            <p>Scanning for available remediation options...</p>
                        </div>
                    </div>

                    <!-- Content State -->
                    <div id="remediationContent" class="hiep-content-container" style="display: none;">
                        <div class="hiep-info-banner">
                            <div class="hiep-banner-icon">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div class="hiep-banner-text">
                                <h4>Security Fixes Available</h4>
                                <p>Select the remediation methods you want to apply to strengthen your system security</p>
                            </div>
                        </div>

                        <!-- Bento Grid Layout for Fixes -->
                        <div id="fixesList" class="hiep-bento-grid ">
                            <!-- Available fixes will be loaded here -->
                        </div>

                        <!-- Select All Option -->
                        <div class="hiep-select-all">
                            <div class="hiep-checkbox-container">
                                <input class="hiep-checkbox" type="checkbox" id="selectAllFixes">
                                <label class="hiep-checkbox-label" for="selectAllFixes">
                                    <span class="hiep-checkbox-text">Select All Fixes</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Results State -->
                    <div id="remediationResults" class="hiep-results-container" style="display: none;">
                        <div class="hiep-results-header">
                            <div class="hiep-results-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h3>Remediation Results</h3>
                        </div>
                        <div id="resultsContent" class="hiep-results-content">
                            <!-- Results will be shown here -->
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="hiep-modal-footer">
                    <button type="button" class="hiep-btn hiep-btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancel
                    </button>
                    <button type="button" class="hiep-btn hiep-btn-primary" id="executeRemediationBtn" onclick="executeRemediation()" style="display: none;">
                        <i class="fas fa-rocket me-2"></i>Execute Remediation
                    </button>
                    <button type="button" class="hiep-btn hiep-btn-success" id="refreshAfterRemediationBtn" onclick="refreshAfterRemediation()" style="display: none;">
                        <i class="fas fa-sync me-2"></i>Rescan System
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let clients = [];
        let currentScanResults = [];
        let currentMultiClientResults = [];
        let currentClientId = null;
        let currentRemediationClientId = null;
        let availableFixes = {};

        // Initialize with sample data if no clients exist
        function initializeSampleData() {
            const sampleClients = [{
                    id: 'client_1',
                    name: 'Hiep Antivirus Local',
                    url: 'https://hiepcodeweb.com',
                    api_key: 'hiep-security-client-2025-change-this-key',
                    status: 'online',
                    last_scan: new Date().toISOString()
                },
                {
                    id: 'client_2',
                    name: 'Xemay365 Client',
                    url: 'https://xemay365.com.vn',
                    api_key: 'hiep-security-client-2025-change-this-key',
                    status: 'online',
                    last_scan: new Date().toISOString()
                },
                {
                    id: 'client_3',
                    name: 'Local Test Client',
                    url: window.location.origin,
                    api_key: 'hiep-security-client-2025-change-this-key',
                    status: 'online',
                    last_scan: new Date().toISOString()
                }
            ];

            if (clients.length === 0) {
                clients = sampleClients;
                renderClients();
                updateStats();
            }
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            loadClients();
            updateStats();
            loadRecentActivity();
            checkSystemHealth();

            // Initialize sample data after 1 second if no clients loaded
            setTimeout(() => {
                if (clients.length === 0) {
                    initializeSampleData();
                }
            }, 1000);
        });

        // Load clients
        function loadClients() {
            console.log('Loading clients...');
            fetch('?api=get_clients')
                .then(response => response.json())
                .then(data => {
                    console.log('Clients data received:', data);
                    // Ensure data is an array
                    if (Array.isArray(data)) {
                        clients = data;
                    } else if (data && data.success && Array.isArray(data.data)) {
                        clients = data.data;
                    } else {
                        clients = [];
                        console.error('Invalid clients data:', data);
                    }
                    console.log('Clients loaded:', clients);
                    renderClients();
                    updateStats();
                })
                .catch(error => {
                    console.error('Error loading clients:', error);
                    clients = [];
                    renderClients();
                });
        }

        // Render clients with modern design
        function renderClients() {
            const grid = document.getElementById('clientsGrid');

            // Ensure clients is an array
            if (!Array.isArray(clients)) {
                clients = [];
            }

            if (clients.length === 0) {
                grid.innerHTML = `
                    <div class="loading-card">
                        <i class="fas fa-server" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 20px;"></i>
                        <h5>Chưa có clients</h5>
                        <p class="text-muted">Thêm client đầu tiên để bắt đầu quét bảo mật</p>
                    </div>
                `;
                return;
            }

            grid.innerHTML = clients.map(client => `
                <div class="client-card fade-in-up">
                    <div class="client-header">
                        <div class="client-name">${client.name || 'Unnamed Client'}</div>
                        <div class="d-flex align-items-center gap-2">
                            <div class="pulse-dot"></div>
                            <span class="client-status status-online">Online</span>
                        </div>
                    </div>
                    <div class="client-url">${client.url || 'No URL'}</div>
                    <div class="d-flex gap-2 flex-wrap">
                        <button class="btn-modern btn-scan" onclick="scanClient('${client.id}')">
                            <i class="fas fa-search"></i> Quét
                        </button>
                        <button class="btn-modern btn-health" onclick="checkHealth('${client.id}')">
                            <i class="fas fa-heartbeat"></i> Health
                        </button>
                        <button class="btn-modern btn-info" onclick="viewClient('${client.id}')">
                            <i class="fas fa-eye"></i> Xem
                        </button>
                        
                        <button class="btn-modern btn-remediate" onclick="showRemediationModal('${client.id}')">
                            <i class="fas fa-tools"></i> Khắc phục
                        </button>
                        <button class="btn-modern btn-danger" onclick="deleteClient('${client.id}')">
                            <i class="fas fa-trash"></i> Xóa
                        </button>
                    </div>
                </div>
            `).join('');
        }

        // Modified scanClient function to use real API
        function scanClient(clientId) {
            currentClientId = clientId;
            const client = clients.find(c => c.id === clientId);

            if (!client) return;

            Swal.fire({
                title: 'Đang quét...',
                html: `Đang quét client: <strong>${client.name}</strong>`,
                allowOutsideClick: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            });

            // Call real API with priority files
            fetch(`?api=scan_client&id=${clientId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        priority_files: priorityFiles
                    })
                })
                .then(response => response.json())
                .then(data => {
                    Swal.close();

                    if (data.success) {
                        // Format the real data to match our display expectations
                        const formattedResults = formatScanResults(data);
                        currentScanResults = formattedResults;
                        displayScanResults(formattedResults);

                        Swal.fire({
                            icon: data.scan_results && data.scan_results.critical_count > 0 ? 'warning' : 'success',
                            title: 'Quét hoàn tất!',
                            html: `
                                <div class="text-start">
                                    <strong>Client:</strong> ${client.name}<br>
                                    <strong>Files quét:</strong> ${data.scan_results ? data.scan_results.scanned_files : 0}<br>
                                    <strong>Threats:</strong> ${data.scan_results ? data.scan_results.suspicious_count : 0}<br>
                                    <strong>Critical:</strong> ${data.scan_results ? data.scan_results.critical_count : 0}
                                </div>
                            `
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Lỗi quét!',
                            html: `
                                <div class="text-start">
                                    <strong>Client:</strong> ${client.name}<br>
                                    <strong>Lỗi:</strong> ${data.error || 'Unknown error'}<br>
                                    <strong>Trạng thái:</strong> Client có thể offline hoặc không thể kết nối
                                </div>
                            `
                        });
                    }
                })
                .catch(error => {
                    Swal.close();
                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi kết nối!',
                        text: 'Không thể kết nối với server để thực hiện quét.'
                    });
                    console.error('Scan error:', error);
                });
        }

        // Format scan results from API to match display format
        function formatScanResults(apiData) {
            if (!apiData || !apiData.success || !apiData.scan_results) {
                return {
                    success: false,
                    scanned_files: 0,
                    suspicious_count: 0,
                    critical_count: 0,
                    suspicious_files: []
                };
            }

            const scanResults = apiData.scan_results;
            const suspiciousFiles = [];

            // Process threats from API response
            if (scanResults.threats && scanResults.threats.all && Array.isArray(scanResults.threats.all)) {
                scanResults.threats.all.forEach(threat => {
                    // Determine severity based on time
                    const now = Date.now() / 1000;
                    const modifiedTime = threat.modified_time || threat.file_modified_time || now;
                    const timeDiff = now - modifiedTime;

                    let severity = 'info';
                    if (timeDiff <= 7 * 24 * 3600) { // 1 week
                        severity = 'critical';
                    } else if (timeDiff <= 5 * 30 * 24 * 3600) { // 5 months
                        severity = 'warning';
                    }

                    // Convert threat to display format
                    const suspiciousFile = {
                        path: threat.path || threat.file_path || 'Unknown',
                        severity: severity,
                        category: threat.category || 'suspicious',
                        metadata: {
                            modified_time: modifiedTime,
                            size: threat.file_size || 0
                        },
                        issues: []
                    };

                    // Convert issues
                    if (threat.issues && Array.isArray(threat.issues)) {
                        suspiciousFile.issues = threat.issues.map(issue => ({
                            pattern: issue.pattern || 'Unknown',
                            description: issue.description || 'Unknown issue',
                            line: issue.line || 1,
                            code_snippet: issue.context || issue.code_snippet || 'No code snippet'
                        }));
                    }

                    suspiciousFiles.push(suspiciousFile);
                });
            }

            return {
                success: true,
                scanned_files: scanResults.scanned_files || 0,
                suspicious_count: scanResults.suspicious_count || 0,
                critical_count: scanResults.critical_count || 0,
                suspicious_files: suspiciousFiles
            };
        }

        // Display scan results with two column design
        function displayScanResults(results) {
            const scanResultsDiv = document.getElementById('scanResults');
            const threatsContainer = document.getElementById('threatsContainer');
            const recentThreatsContainer = document.getElementById('recentThreatsContainer');
            const mainThreatCount = document.getElementById('mainThreatCount');

            if (!results || !results.suspicious_files || results.suspicious_files.length === 0) {
                scanResultsDiv.style.display = 'block';
                threatsContainer.innerHTML = `
                    <div class="loading-card">
                        <i class="fas fa-shield-check" style="font-size: 3rem; color: var(--primary-blue); margin-bottom: 20px;"></i>
                        <h5>Hệ thống an toàn!</h5>
                        <p class="text-muted">Không phát hiện threats nào</p>
                    </div>
                `;
                recentThreatsContainer.innerHTML = '<div class="text-center text-muted p-3"><small>Không có threats nào</small></div>';
                mainThreatCount.textContent = '0 files';
                return;
            }

            // Sort main threats by time (newest first) and severity
            const sortedFiles = results.suspicious_files.sort((a, b) => {
                const aTime = a.metadata?.modified_time || 0;
                const bTime = b.metadata?.modified_time || 0;

                // First sort by time (newest first)
                if (bTime !== aTime) return bTime - aTime;

                // Then by severity
                const severityOrder = {
                    critical: 3,
                    warning: 2,
                    info: 1
                };
                return (severityOrder[b.severity] || 0) - (severityOrder[a.severity] || 0);
            });

            // Filter recent threats (last 5 months) - Only critical, severe, and suspicious files  
            const now = Date.now() / 1000;
            const recentThreats = sortedFiles.filter(file => {
                const fileTime = file.metadata?.modified_time || 0;
                const age = now - fileTime;
                
                // Only show files in last 5 months
                if (age >= 5 * 30 * 24 * 3600) return false;
                
                // Only show files with critical, severe issues or suspicious extensions
                return file.category === 'critical' || file.category === 'webshell' || 
                       file.path.includes('.php.') || file.severity === 'critical';
            }).slice(0, 10); // Limit to 10 most recent

            scanResultsDiv.style.display = 'block';

            // Update main threat count
            mainThreatCount.textContent = `${sortedFiles.length} files`;

            // Populate main threats container
            const threatCards = sortedFiles.map(file => {
                const severity = getSeverityLevel(file);
                const ageInfo = getAgeInfo(file.metadata?.modified_time);
                const timeInfo = getTimeInfo(file.metadata?.modified_time);
                const fileSize = formatFileSize(file.metadata?.size || 0);

                // Issues data not needed since tooltip removed

                return `
                    <div class="threat-card ${severity} fade-in-up" 
                         data-file-path="${file.path}"


                         onmouseleave="hideThreatTooltip()">
                        <div class="threat-header">
                            <h4 class="threat-file">
                                <i class="fas fa-file-code threat-icon"></i>
                                ${file.path}
                            </h4>
                            <div class="threat-actions">
                                <button class="action-btn action-view" onclick="viewThreat('${file.path}')">
                                    <i class="fas fa-edit"></i> Sửa
                                </button>
                                <button class="action-btn action-delete" onclick="deleteFile('${file.path}')">
                                    <i class="fas fa-trash"></i> Xóa
                                </button>
                            </div>
                        </div>
                        
                        <div class="threat-meta">
                            <span class="meta-badge meta-severity ${severity}">${getSeverityLabel(severity)}</span>
                            <span class="meta-badge meta-age ${ageInfo.class}" title="${timeInfo.tooltip}">${ageInfo.label}</span>
                            <span class="meta-badge meta-size">${fileSize}</span>
                            <span class="meta-badge meta-time" title="${timeInfo.tooltip}">
                                <i class="fas fa-clock me-1"></i>${timeInfo.relative}
                            </span>
                        </div>
                        
                        <div class="threat-details">
                            <strong>${file.issues?.length || 0} vấn đề phát hiện:</strong>
                            <div class="threat-issues">
                                ${(file.issues || []).slice(0, 3).map(issue => {
                                    const getSeverityBadge = (severity) => {
                                        const badges = {
                                            'critical': '<span class="badge bg-danger">Critical</span>',
                                            'high': '<span class="badge bg-warning">High</span>',
                                            'medium': '<span class="badge bg-info">Medium</span>',
                                            'low': '<span class="badge bg-secondary">Low</span>',
                                            'warning': '<span class="badge bg-warning">Warning</span>'
                                        };
                                        return badges[severity] || '<span class="badge bg-secondary">Unknown</span>';
                                    };
                                    
                                    return `
                                        <div class="issue-item border rounded p-2 mb-2" style="background: #fef2f2; border-color: #fecaca !important;">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="flex-grow-1">
                                                    <span class="ms-2 fw-bold text-danger">${issue.description || issue.pattern}</span>
                                                    ${(issue.line_number || issue.line) ? `<span class="ms-2 badge bg-dark">Dòng ${issue.line_number || issue.line}</span>` : ''}
                                                    ${issue.pattern && issue.description && issue.pattern !== issue.description ? `<div class="mt-1"><small class="text-muted font-monospace" style="font-size: 9px;">Pattern: ${issue.pattern.length > 50 ? issue.pattern.substring(0, 50) + '...' : issue.pattern}</small></div>` : ''}
                                                </div>
                                            </div>
                                            ${issue.description ? `<div class="mt-1"><small class="text-muted">${issue.description}</small></div>` : ''}
                                            ${issue.context ? `<div class="mt-1"><code style="font-size: 11px; background: #f8f9fa; padding: 2px 4px; border-radius: 3px;">${issue.context.substring(0, 100)}...</code></div>` : ''}
                                        </div>
                                    `;
                                }).join('')}
                                ${(file.issues || []).length > 3 ? `
                                    <div class="mt-2 p-2 bg-light border rounded">
                                        <small class="text-muted">
                                            <i class="fas fa-info-circle me-1"></i>
                                            Và ${(file.issues || []).length - 3} issues khác. Click <strong>Sửa</strong> để xem toàn bộ.
                                        </small>
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                `;
            });

            // Set up collapsible functionality
            threatsContainer.innerHTML = threatCards.join('');

            // Add show all button if there are more than 5 cards
            if (threatCards.length > 5) {
                threatsContainer.classList.add('collapsed');
                const showAllBtn = document.createElement('button');
                showAllBtn.className = 'show-all-threats-btn';
                showAllBtn.innerHTML = 'Xem tất cả <i class="fas fa-chevron-down"></i>';
                showAllBtn.onclick = toggleAllThreats;
                threatsContainer.appendChild(showAllBtn);
            } else {
                threatsContainer.classList.remove('collapsed');
            }

            // Populate recent threats sidebar
            if (recentThreats.length > 0) {
                recentThreatsContainer.innerHTML = recentThreats.map(file => {
                    const severity = getSeverityLevel(file);
                    const timeInfo = getTimeInfo(file.metadata?.modified_time);
                    const ageInfo = getAgeInfo(file.metadata?.modified_time);

                    return `
                        <div class="recent-threat-item" data-file-path="${file.path}">
                            <div class="recent-threat-header">
                                <div class="recent-threat-file" onclick="viewThreat('${file.path}')" title="Click để mở editor">${file.path}</div>
                                <button class="recent-threat-delete" onclick="deleteFile('${file.path}')" title="Xóa file">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <div class="recent-threat-meta">
                                <div class="meta-row">
                                    <span class="recent-threat-severity ${severity}">${getSeverityLabel(severity)}</span>
                                    <span class="recent-threat-age ${ageInfo.class}">${ageInfo.label}</span>
                                </div>
                                <div class="meta-row mt-1">
                                    <div class="recent-threat-time" title="${timeInfo.tooltip}">
                                        <i class="fas fa-calendar-alt me-1"></i>
                                        <span class="time-relative">${timeInfo.relative}</span>
                                        <div class="time-absolute">${timeInfo.absolute}</div>
                                    </div>
                                </div>
                                ${file.issues && file.issues.length > 0 ? `
                                    <div class="meta-row mt-1">
                                        <small class="text-danger">
                                            <i class="fas fa-exclamation-triangle me-1"></i>
                                            ${file.issues.length} vấn đề phát hiện
                                        </small>
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                    `;
                }).join('');
            } else {
                recentThreatsContainer.innerHTML = `
                    <div class="text-center text-muted p-3">
                        <i class="fas fa-shield-alt text-success mb-2" style="font-size: 24px;"></i>
                        <div><small><strong>Không có file nguy hiểm gần đây</strong></small></div>
                        <div class="mt-1">
                            <small style="font-size: 9px; color: #9ca3af;">
                                Không phát hiện threats trong vòng 5 tháng qua
                            </small>
                        </div>
                    </div>
                `;
            }

            // Add filter event listeners
            setTimeout(() => {
                const severityFilter = document.getElementById('severityFilter');
                const ageFilter = document.getElementById('ageFilter');
                if (severityFilter) severityFilter.addEventListener('change', filterResults);
                if (ageFilter) ageFilter.addEventListener('change', filterResults);
            }, 100);
        }

        // Get severity level based on file characteristics
        function getSeverityLevel(file) {
            if (!file.metadata) return 'info';

            const now = Date.now() / 1000;
            const fileTime = file.metadata.modified_time || 0;
            const age = now - fileTime;

            // Critical: Files modified within last week with suspicious patterns
            if (age < 7 * 24 * 3600) { // 1 week
                return 'critical';
            }

            // Warning: Files modified within last 5 months
            if (age < 5 * 30 * 24 * 3600) { // 5 months
                return 'warning';
            }

            // Info: Older files
            return 'info';
        }

        // Get age info for display
        function getAgeInfo(timestamp) {
            if (!timestamp) return {
                class: 'old',
                label: 'Cũ'
            };

            const now = Date.now() / 1000;
            const age = now - timestamp;

            if (age < 30 * 24 * 3600) { // 1 month
                return {
                    class: 'new',
                    label: 'MỚI'
                };
            } else if (age < 5 * 30 * 24 * 3600) { // 5 months
                return {
                    class: 'recent',
                    label: 'GẦN ĐÂY'
                };
            } else {
                return {
                    class: 'old',
                    label: 'Cũ'
                };
            }
        }

        // Get severity label
        function getSeverityLabel(severity) {
            const labels = {
                critical: 'Nguy hiểm',
                warning: 'Cảnh báo',
                info: 'Thông tin'
            };
            return labels[severity] || 'Không xác định';
        }

        // Format file size
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        // Filter results
        function filterResults() {
            const severityFilter = document.getElementById('severityFilter').value;
            const ageFilter = document.getElementById('ageFilter').value;
            const cards = document.querySelectorAll('.threat-card');

            cards.forEach(card => {
                let showCard = true;

                // Filter by severity
                if (severityFilter !== 'all') {
                    showCard = showCard && card.classList.contains(severityFilter);
                }

                // Filter by age
                if (ageFilter !== 'all') {
                    const ageElement = card.querySelector('.meta-age');
                    if (ageElement) {
                        showCard = showCard && ageElement.classList.contains(ageFilter);
                    }
                }

                card.style.display = showCard ? 'block' : 'none';
            });
        }

        // Delete file
        function deleteFile(filePath) {
            if (!currentClientId) return;

            Swal.fire({
                title: 'Xác nhận xóa file',
                text: `Bạn có chắc muốn xóa file: ${filePath}?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Xóa',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`?api=delete_file`, {
                            method: 'POST',
                            dataType: 'json',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: `client_id=${currentClientId}&file_path=${encodeURIComponent(filePath)}`
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data.success) {
                                Swal.fire('Đã xóa!', 'File đã được xóa thành công.', 'success');
                                // Remove card from display
                                document.querySelectorAll('.threat-card').forEach(card => {
                                    if (card.dataset.filePath === filePath) {
                                        card.remove();
                                    }
                                });

                                document.querySelectorAll('.recent-threat-item').forEach(item => {
                                    if (item.dataset.filePath === filePath) {
                                        item.remove();
                                    }
                                });
                            } else {
                                // Better error handling - distinguish between different error types
                                let errorMessage = data.error || 'Không thể xóa file.';
                                let icon = 'error';
                                
                                if (errorMessage.includes('File not found') || errorMessage.includes('not found')) {
                                    errorMessage = 'File không tồn tại hoặc đã được xóa trước đó.';
                                    icon = 'info';
                                    
                                    // Remove card from display if file not found
                                    document.querySelectorAll('.threat-card').forEach(card => {
                                        if (card.querySelector('.threat-path').textContent === filePath) {
                                            card.remove();
                                        }
                                    });
                                }
                                
                                Swal.fire('Thông báo', errorMessage, icon);
                            }
                        })
                        .catch(error => {
                            Swal.fire('Lỗi!', 'Không thể kết nối tới server .'+error.message , 'error');
                        });
                }
            });
        }

        // Quarantine file
        function quarantineFile(filePath) {
            if (!currentClientId) return;

            Swal.fire({
                title: 'Cách ly file',
                text: `Cách ly file: ${filePath}?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f59e0b',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Cách ly',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`?api=quarantine_file`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: `client_id=${currentClientId}&file_path=${encodeURIComponent(filePath)}`
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire('Đã cách ly!', 'File đã được cách ly thành công.', 'success');
                            } else {
                                Swal.fire('Lỗi!', data.error || 'Không thể cách ly file.', 'error');
                            }
                        })
                        .catch(error => {
                            Swal.fire('Lỗi!', 'Không thể kết nối tới server.', 'error');
                        });
                }
            });
        }

        // View threat details - Open in Monaco Editor
        function viewThreat(filePath) {
            if (!currentClientId) {
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi!',
                    text: 'Không xác định được client ID.'
                });
                return;
            }

            openFileInEditor(currentClientId, filePath);
        }

        // Other functions (addClient, updateStats, etc.)
        function addClient() {
            const name = document.getElementById('clientName').value;
            const url = document.getElementById('clientUrl').value;
            const apiKey = document.getElementById('clientApiKey').value;

            if (!name || !url || !apiKey) {
                Swal.fire('Lỗi!', 'Vui lòng điền đầy đủ thông tin.', 'error');
                return;
            }

            fetch('?api=add_client', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `name=${encodeURIComponent(name)}&url=${encodeURIComponent(url)}&api_key=${encodeURIComponent(apiKey)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Thành công!', 'Client đã được thêm.', 'success');
                        loadClients();
                        bootstrap.Modal.getInstance(document.getElementById('addClientModal')).hide();
                        document.getElementById('addClientForm').reset();
                    } else {
                        Swal.fire('Lỗi!', data.error || 'Không thể thêm client.', 'error');
                    }
                })
                .catch(error => {
                    Swal.fire('Lỗi!', 'Không thể kết nối tới server.', 'error');
                });
        }

        function updateStats() {
            document.getElementById('totalClients').textContent = clients.length;

            // Mock statistics
            let totalThreats = 0;
            let criticalThreats = 0;
            let cleanClients = 0;

            if (currentScanResults && currentScanResults.suspicious_files) {
                totalThreats = currentScanResults.suspicious_count || 0;
                criticalThreats = currentScanResults.critical_count || 0;
            }

            // Calculate clean clients (assume most are clean)
            cleanClients = Math.max(0, clients.length - 1); // Assume 1 client has threats

            document.getElementById('totalThreats').textContent = totalThreats;
            document.getElementById('criticalThreats').textContent = criticalThreats;
            document.getElementById('cleanClients').textContent = cleanClients;
        }

        function loadRecentActivity() {
            const activityDiv = document.getElementById('recentActivity');
            activityDiv.innerHTML = `
                <div class="activity-item">
                    <div class="activity-icon">
                        <i class="fas fa-shield-alt text-success"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-title">Quét hoàn tất</div>
                        <div class="activity-time">2 phút trước</div>
                    </div>
                </div>
                <div class="activity-item">
                    <div class="activity-icon">
                        <i class="fas fa-exclamation-triangle text-warning"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-title">Phát hiện 3 threats</div>
                        <div class="activity-time">5 phút trước</div>
                    </div>
                </div>
                <div class="activity-item">
                    <div class="activity-icon">
                        <i class="fas fa-plus text-primary"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-title">Thêm client mới</div>
                        <div class="activity-time">1 giờ trước</div>
                    </div>
                </div>
            `;
        }

        function checkSystemHealth() {
            const healthDiv = document.getElementById('systemHealth');
            healthDiv.innerHTML = `
                <div class="health-item">
                    <div class="health-label">Server Status</div>
                    <div class="health-value">
                        <span class="badge bg-success">Online</span>
                    </div>
                </div>
                <div class="health-item">
                    <div class="health-label">Memory Usage</div>
                    <div class="health-value">
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-primary" style="width: 65%"></div>
                        </div>
                        <small class="text-muted">65%</small>
                    </div>
                </div>
                <div class="health-item">
                    <div class="health-label">Disk Space</div>
                    <div class="health-value">
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-warning" style="width: 82%"></div>
                        </div>
                        <small class="text-muted">82%</small>
                    </div>
                </div>
            `;
        }

        function scanAllClients() {
            if (!clients || clients.length === 0) {
                Swal.fire({
                    icon: 'info',
                    title: 'Không có clients',
                    text: 'Vui lòng thêm clients trước khi quét.'
                });
                return;
            }

            Swal.fire({
                title: 'Đang quét tất cả clients...',
                html: `Đang quét ${clients.length} clients...`,
                allowOutsideClick: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            });

            // Call real API
            fetch('?api=scan_all')
                .then(response => response.json())
                .then(data => {
                    Swal.close();

                    if (data.success && data.results) {
                        let totalScanned = 0;
                        let totalThreats = 0;
                        let totalCritical = 0;
                        let errorCount = 0;

                        data.results.forEach(result => {
                            if (result.scan_result && result.scan_result.success && result.scan_result.scan_results) {
                                totalScanned += result.scan_result.scan_results.scanned_files || 0;
                                totalThreats += result.scan_result.scan_results.suspicious_count || 0;
                                totalCritical += result.scan_result.scan_results.critical_count || 0;
                            } else {
                                errorCount++;
                            }
                        });



                        // Store results for multi-client display
                        currentMultiClientResults = data.results;

                        // Show multi-client results interface
                        displayMultiClientResults(data.results);

                        Swal.fire({
                            icon: totalCritical > 0 ? 'warning' : 'success',
                            title: 'Quét tất cả clients hoàn tất!',
                            html: `
                                <div class="text-start">
                                    <strong>Clients đã quét:</strong> ${clients.length}<br>
                                    <strong>Clients lỗi:</strong> ${errorCount}<br>
                                    <strong>Tổng files quét:</strong> ${totalScanned}<br>
                                    <strong>Tổng threats:</strong> ${totalThreats}<br>
                                    <strong>Tổng critical:</strong> ${totalCritical}
                                </div>
                            `
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Lỗi quét!',
                            text: data.error || 'Không thể quét tất cả clients.'
                        });
                    }
                })
                .catch(error => {
                    Swal.close();
                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi kết nối!',
                        text: 'Không thể kết nối với server để thực hiện quét tất cả.'
                    });
                    console.error('Scan all error:', error);
                });
        }

        function checkHealth(clientId) {
            const client = clients.find(c => c.id === clientId);

            if (!client) {
                Swal.fire('Lỗi!', 'Không tìm thấy client.', 'error');
                return;
            }

            Swal.fire({
                title: 'Đang kiểm tra...',
                html: `Đang kiểm tra sức khỏe client: <strong>${client.name}</strong>`,
                allowOutsideClick: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch(`?api=check_client&id=${clientId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const status = data.online ? 'Online' : 'Offline';
                        const icon = data.online ? 'success' : 'warning';
                        const color = data.online ? '#28a745' : '#ffc107';

                        Swal.fire({
                            icon: icon,
                            title: `Client ${status}`,
                            html: `
                                <div style="text-align: left;">
                                    <p><strong>Tên:</strong> ${client.name}</p>
                                    <p><strong>URL:</strong> ${client.url}</p>
                                    <p><strong>Trạng thái:</strong> <span style="color: ${color}; font-weight: bold;">${status}</span></p>
                                    <p><strong>Kiểm tra lúc:</strong> ${new Date().toLocaleString('vi-VN')}</p>
                                </div>
                            `,
                            confirmButtonText: 'Đóng'
                        });

                        // Refresh client list to update status
                        loadClients();
                    } else {
                        Swal.fire('Lỗi!', data.error || 'Không thể kiểm tra client.', 'error');
                    }
                })
                .catch(error => {
                    Swal.fire('Lỗi!', 'Không thể kết nối tới server.', 'error');
                    console.error('Check health error:', error);
                });
        }

        function viewClient(clientId) {
            const client = clients.find(c => c.id === clientId);

            if (!client) {
                Swal.fire('Lỗi!', 'Không tìm thấy client.', 'error');
                return;
            }

            const statusColor = client.status === 'online' ? '#28a745' :
                               client.status === 'offline' ? '#dc3545' : '#6c757d';

            const lastCheck = client.last_check ?
                new Date(client.last_check).toLocaleString('vi-VN') : 'Chưa kiểm tra';

            Swal.fire({
                title: 'Thông tin Client',
                html: `
                    <div style="text-align: left; max-width: 500px;">
                        <div style="margin-bottom: 15px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                            <h4 style="margin: 0 0 10px 0; color: #495057;">
                                <i class="fas fa-server"></i> ${client.name}
                            </h4>
                            <p style="margin: 5px 0;"><strong>ID:</strong> ${client.id}</p>
                            <p style="margin: 5px 0;"><strong>URL:</strong>
                                <a href="${client.url}" target="_blank" style="color: #007bff;">${client.url}</a>
                            </p>
                            <p style="margin: 5px 0;"><strong>API Key:</strong>
                                <code style="background: #e9ecef; padding: 2px 6px; border-radius: 4px; font-size: 12px;">
                                    ${client.api_key}
                                </code>
                            </p>
                        </div>

                        <div style="margin-bottom: 15px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                            <h5 style="margin: 0 0 10px 0; color: #495057;">
                                <i class="fas fa-info-circle"></i> Trạng thái
                            </h5>
                            <p style="margin: 5px 0;"><strong>Trạng thái:</strong>
                                <span style="color: ${statusColor}; font-weight: bold; text-transform: capitalize;">
                                    ${client.status || 'unknown'}
                                </span>
                            </p>
                            <p style="margin: 5px 0;"><strong>Lần kiểm tra cuối:</strong> ${lastCheck}</p>
                            <p style="margin: 5px 0;"><strong>Ngày thêm:</strong>
                                ${client.created_at ? new Date(client.created_at).toLocaleString('vi-VN') : 'N/A'}
                            </p>
                        </div>

                        <div style="margin-bottom: 15px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                            <h5 style="margin: 0 0 10px 0; color: #495057;">
                                <i class="fas fa-cogs"></i> Thao tác nhanh
                            </h5>
                            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                <button onclick="checkHealth('${client.id}')"
                                        style="padding: 8px 16px; background: #17a2b8; color: white; border: none; border-radius: 4px; cursor: pointer;">
                                    <i class="fas fa-heartbeat"></i> Kiểm tra
                                </button>
                                <button onclick="scanClient('${client.id}')"
                                        style="padding: 8px 16px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer;">
                                    <i class="fas fa-search"></i> Quét
                                </button>
                                <button onclick="window.open('${client.url}', '_blank')"
                                        style="padding: 8px 16px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">
                                    <i class="fas fa-external-link-alt"></i> Mở
                                </button>
                            </div>
                        </div>
                    </div>
                `,
                width: 600,
                showConfirmButton: true,
                confirmButtonText: 'Đóng',
                showCancelButton: true,
                cancelButtonText: 'Chỉnh sửa',
                cancelButtonColor: '#ffc107'
            }).then((result) => {
                if (result.dismiss === Swal.DismissReason.cancel) {
                    // Open edit modal
                    editClient(client);
                }
            });
        }

        function deleteClient(clientId) {
            const client = clients.find(c => c.id === clientId);

            if (!client) {
                Swal.fire('Lỗi!', 'Không tìm thấy client.', 'error');
                return;
            }

            Swal.fire({
                title: 'Xác nhận xóa',
                html: `
                    <div style="text-align: left;">
                        <p>Bạn có chắc chắn muốn xóa client này không?</p>
                        <div style="background: #f8d7da; padding: 15px; border-radius: 8px; margin: 15px 0;">
                            <h5 style="color: #721c24; margin: 0 0 10px 0;">
                                <i class="fas fa-exclamation-triangle"></i> Thông tin client sẽ bị xóa:
                            </h5>
                            <p style="margin: 5px 0;"><strong>Tên:</strong> ${client.name}</p>
                            <p style="margin: 5px 0;"><strong>URL:</strong> ${client.url}</p>
                            <p style="margin: 5px 0;"><strong>ID:</strong> ${client.id}</p>
                        </div>
                        <p style="color: #dc3545; font-weight: bold;">
                            <i class="fas fa-warning"></i> Hành động này không thể hoàn tác!
                        </p>
                    </div>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Xóa',
                cancelButtonText: 'Hủy',
                focusCancel: true
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading
                    Swal.fire({
                        title: 'Đang xóa...',
                        html: `Đang xóa client: <strong>${client.name}</strong>`,
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        willOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // Call delete API
                    const formData = new FormData();
                    formData.append('id', clientId);

                    fetch('?api=delete_client', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Đã xóa!',
                                text: `Client "${client.name}" đã được xóa thành công.`,
                                timer: 2000,
                                showConfirmButton: false
                            });

                            // Refresh client list
                            loadClients();
                        } else {
                            Swal.fire('Lỗi!', data.error || 'Không thể xóa client.', 'error');
                        }
                    })
                    .catch(error => {
                        Swal.fire('Lỗi!', 'Không thể kết nối tới server.', 'error');
                        console.error('Delete client error:', error);
                    });
                }
            });
        }

        function editClient(client) {
            Swal.fire({
                title: 'Chỉnh sửa Client',
                html: `
                    <div style="text-align: left;">
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Tên Client:</label>
                            <input type="text" id="editClientName" value="${client.name}"
                                   style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        </div>
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: bold;">URL:</label>
                            <input type="url" id="editClientUrl" value="${client.url}"
                                   style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        </div>
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: bold;">API Key:</label>
                            <input type="text" id="editClientApiKey" value="${client.api_key}"
                                   style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        </div>
                        <div style="background: #e7f3ff; padding: 10px; border-radius: 4px; font-size: 14px;">
                            <i class="fas fa-info-circle"></i>
                            Lưu ý: Thay đổi URL hoặc API Key có thể ảnh hưởng đến kết nối với client.
                        </div>
                    </div>
                `,
                width: 500,
                showCancelButton: true,
                confirmButtonText: 'Lưu thay đổi',
                cancelButtonText: 'Hủy',
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                preConfirm: () => {
                    const name = document.getElementById('editClientName').value.trim();
                    const url = document.getElementById('editClientUrl').value.trim();
                    const apiKey = document.getElementById('editClientApiKey').value.trim();

                    if (!name || !url || !apiKey) {
                        Swal.showValidationMessage('Vui lòng điền đầy đủ thông tin');
                        return false;
                    }

                    // Validate URL format
                    try {
                        new URL(url);
                    } catch (e) {
                        Swal.showValidationMessage('URL không hợp lệ');
                        return false;
                    }

                    return { name, url, apiKey };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const { name, url, apiKey } = result.value;

                    // Show loading
                    Swal.fire({
                        title: 'Đang cập nhật...',
                        html: 'Đang lưu thay đổi...',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        willOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // Call update API (we need to add this endpoint)
                    const formData = new FormData();
                    formData.append('id', client.id);
                    formData.append('name', name);
                    formData.append('url', url);
                    formData.append('api_key', apiKey);

                    fetch('?api=update_client', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Đã cập nhật!',
                                text: 'Thông tin client đã được cập nhật thành công.',
                                timer: 2000,
                                showConfirmButton: false
                            });

                            // Refresh client list
                            loadClients();
                        } else {
                            Swal.fire('Lỗi!', data.error || 'Không thể cập nhật client.', 'error');
                        }
                    })
                    .catch(error => {
                        Swal.fire('Lỗi!', 'Không thể kết nối tới server.', 'error');
                        console.error('Update client error:', error);
                    });
                }
            });
        }

        function deployAdminSecurity(clientId) {
            const client = clients.find(c => c.id === clientId);

            if (!client) {
                Swal.fire('Lỗi!', 'Không tìm thấy client.', 'error');
                return;
            }

            Swal.fire({
                title: 'Triển khai bảo mật Admin',
                html: `
                    <div style="text-align: left;">
                        <p>Bạn có muốn triển khai các bản vá bảo mật cho Admin CMS không?</p>
                        <div style="background: #e7f3ff; padding: 15px; border-radius: 8px; margin: 15px 0;">
                            <h5 style="color: #0c5460; margin: 0 0 10px 0;">
                                <i class="fas fa-shield-alt"></i> Các bản vá sẽ được triển khai:
                            </h5>
                            <ul style="margin: 0; padding-left: 20px; color: #0c5460;">
                                <li>File .htaccess bảo mật cho admin/</li>
                                <li>Bảo vệ thư mục sources/ khỏi truy cập trực tiếp</li>
                                <li>Bảo mật File Manager và CKEditor</li>
                                <li>CSRF Protection và validation upload</li>
                                <li>Rate limiting và access control</li>
                            </ul>
                        </div>
                        <div style="background: #fff3cd; padding: 10px; border-radius: 4px; margin: 10px 0;">
                            <p style="margin: 0; color: #856404;">
                                <i class="fas fa-info-circle"></i>
                                <strong>Lưu ý:</strong> Hệ thống sẽ tự động backup files hiện tại trước khi áp dụng bản vá.
                            </p>
                        </div>
                        <p><strong>Client:</strong> ${client.name}</p>
                        <p><strong>URL:</strong> ${client.url}</p>
                    </div>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Triển khai',
                cancelButtonText: 'Hủy',
                width: 600
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading
                    Swal.fire({
                        title: 'Đang triển khai...',
                        html: `
                            <div style="text-align: center;">
                                <p>Đang triển khai bản vá bảo mật cho: <strong>${client.name}</strong></p>
                                <div style="margin: 20px 0;">
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar progress-bar-striped progress-bar-animated"
                                             role="progressbar" style="width: 100%"></div>
                                    </div>
                                </div>
                                <p style="color: #6c757d; font-size: 14px;">
                                    Quá trình này có thể mất vài phút...
                                </p>
                            </div>
                        `,
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        willOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // Call deploy API
                    const formData = new FormData();
                    formData.append('id', clientId);

                    fetch('?api=deploy_admin_security', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Triển khai thành công!',
                                html: `
                                    <div style="text-align: left;">
                                        <p>Bản vá bảo mật đã được triển khai thành công cho <strong>${client.name}</strong></p>
                                        <div style="background: #d4edda; padding: 15px; border-radius: 8px; margin: 15px 0;">
                                            <h5 style="color: #155724; margin: 0 0 10px 0;">
                                                <i class="fas fa-check-circle"></i> Đã hoàn thành:
                                            </h5>
                                            <ul style="margin: 0; padding-left: 20px; color: #155724;">
                                                <li>Upload các file .htaccess bảo mật</li>
                                                <li>Cài đặt security patches PHP</li>
                                                <li>Cấu hình bảo vệ File Manager</li>
                                                <li>Backup files gốc</li>
                                            </ul>
                                        </div>
                                        <p style="color: #28a745; font-weight: bold;">
                                            <i class="fas fa-shield-check"></i>
                                            Admin CMS của bạn đã được bảo mật!
                                        </p>
                                    </div>
                                `,
                                confirmButtonText: 'Tuyệt vời!',
                                width: 600
                            });

                            // Refresh client list
                            loadClients();
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Triển khai thất bại!',
                                html: `
                                    <div style="text-align: left;">
                                        <p>Không thể triển khai bản vá bảo mật cho <strong>${client.name}</strong></p>
                                        <div style="background: #f8d7da; padding: 15px; border-radius: 8px; margin: 15px 0;">
                                            <h5 style="color: #721c24; margin: 0 0 10px 0;">
                                                <i class="fas fa-exclamation-triangle"></i> Lỗi:
                                            </h5>
                                            <p style="margin: 0; color: #721c24;">${data.error}</p>
                                        </div>
                                        <p style="color: #dc3545;">
                                            Vui lòng kiểm tra kết nối và thử lại sau.
                                        </p>
                                    </div>
                                `,
                                confirmButtonText: 'Đóng',
                                width: 600
                            });
                        }
                    })
                    .catch(error => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Lỗi kết nối!',
                            text: 'Không thể kết nối tới server để triển khai bản vá.',
                            confirmButtonText: 'Đóng'
                        });
                        console.error('Deploy admin security error:', error);
                    });
                }
            });
        }

        // Priority Files Functions
        let priorityFiles = JSON.parse(localStorage.getItem('priorityFiles')) || [];

        function togglePriorityFiles() {
            const content = document.getElementById('priorityFilesContent');
            const toggle = document.getElementById('priorityToggle');

            if (content.style.display === 'none') {
                content.style.display = 'block';
                toggle.innerHTML = '<i class="fas fa-chevron-up"></i>';
                toggle.classList.add('active');
            } else {
                content.style.display = 'none';
                toggle.innerHTML = '<i class="fas fa-chevron-down"></i>';
                toggle.classList.remove('active');
            }
        }

        function addPriorityFile(pattern) {
            if (!pattern || pattern.trim() === '') return;

            pattern = pattern.trim();

            // Check if pattern already exists
            if (priorityFiles.includes(pattern)) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Pattern đã tồn tại',
                    text: `Pattern "${pattern}" đã có trong danh sách.`
                });
                return;
            }

            priorityFiles.push(pattern);
            updatePriorityDisplay();
            savePriorityFiles();

            // Clear input
            document.getElementById('priorityFileInput').value = '';

            // Show success micro-interaction
            showPatternAdded(pattern);
        }

        function removePriorityFile(pattern) {
            priorityFiles = priorityFiles.filter(p => p !== pattern);
            updatePriorityDisplay();
            savePriorityFiles();
        }

        function updatePriorityDisplay() {
            const tagsContainer = document.getElementById('priorityFilesTags');
            const countElement = document.getElementById('priorityFilesCount');
            const scoreElement = document.getElementById('priorityScore');

            // Update count
            countElement.textContent = priorityFiles.length;

            // Calculate priority score
            let score = 0;
            priorityFiles.forEach(pattern => {
                if (pattern.includes('*')) score += 3;
                else if (pattern.includes('shell') || pattern.includes('eval')) score += 5;
                else score += 2;
            });
            scoreElement.textContent = score;

            // Update tags display
            tagsContainer.innerHTML = '';
            priorityFiles.forEach(pattern => {
                const tag = document.createElement('div');
                tag.className = 'priority-tag';
                tag.innerHTML = `
                    <span>${pattern}</span>
                    <button class="remove-tag" onclick="removePriorityFile('${pattern}')" title="Xóa pattern">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                tagsContainer.appendChild(tag);
            });
        }

        function addCommonPattern(pattern) {
            addPriorityFile(pattern);
        }

        function showPatternAdded(pattern) {
            // Create temporary success indicator
            const indicator = document.createElement('div');
            indicator.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: var(--priority-color);
                color: white;
                padding: 10px 20px;
                border-radius: 8px;
                font-size: 12px;
                z-index: 9999;
                animation: slideInRight 0.3s ease;
            `;
            indicator.innerHTML = `<i class="fas fa-check me-2"></i>Added: ${pattern}`;
            document.body.appendChild(indicator);

            setTimeout(() => {
                indicator.style.animation = 'slideOutRight 0.3s ease';
                setTimeout(() => {
                    document.body.removeChild(indicator);
                }, 300);
            }, 2000);
        }

        function savePriorityFiles() {
            localStorage.setItem('priorityFiles', JSON.stringify(priorityFiles));
        }

        function showPatternSuggestions(input) {
            const suggestions = [
                'shell.php', '*.php.txt', 'config.php', 'upload*.php', 'admin*.php',
                'eval*.php', 'backdoor*.php', 'wp-config.php', 'index.php.bak',
                '*.php~', 'test*.php', 'debug*.php', 'error*.log', 'access*.log'
            ];

            const suggestionsDiv = document.getElementById('patternSuggestions');
            const value = input.value.toLowerCase();

            if (value.length < 2) {
                suggestionsDiv.style.display = 'none';
                return;
            }

            const filtered = suggestions.filter(s =>
                s.toLowerCase().includes(value) && !priorityFiles.includes(s)
            );

            if (filtered.length === 0) {
                suggestionsDiv.style.display = 'none';
                return;
            }

            suggestionsDiv.innerHTML = filtered.map(s =>
                `<div class="suggestion-item" onclick="addPriorityFile('${s}')">${s}</div>`
            ).join('');

            suggestionsDiv.style.display = 'block';
        }

        // Initialize Priority Files
        document.addEventListener('DOMContentLoaded', function() {
            // Load saved priority files
            updatePriorityDisplay();

            // Setup input events
            const input = document.getElementById('priorityFileInput');
            input.addEventListener('input', function() {
                showPatternSuggestions(this);
            });

            input.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    addPriorityFile(this.value);
                }
            });

            // Hide suggestions when clicking outside
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.priority-files-input-container')) {
                    document.getElementById('patternSuggestions').style.display = 'none';
                }
            });
        });

        // Add CSS animations for micro-interactions
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideInRight {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            
            @keyframes slideOutRight {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(100%);
                    opacity: 0;
                }
            }
            
            .btn-icon.active {
                color: var(--priority-color);
                background: var(--priority-light);
            }
        `;
        document.head.appendChild(style);

        // Monaco Editor Setup
        let monacoEditor = null;
        let currentEditingFile = null;
        let currentEditingClientId = null;

        // Initialize Monaco Editor with DOM safety checks
        function initMonacoEditor() {
            const editorElement = document.getElementById('monacoEditor');
            if (!editorElement) {
                console.warn('Monaco Editor container not found, retrying...');
                setTimeout(initMonacoEditor, 100);
                return;
            }

            // Check if editor already exists
            if (monacoEditor) {
                console.log('Monaco Editor already initialized');
                return;
            }

            require.config({
                paths: {
                    vs: 'https://cdn.jsdelivr.net/npm/monaco-editor@0.45.0/min/vs'
                }
            });

            require(['vs/editor/editor.main'], function() {
                try {
                    // Double check element still exists
                    const editorContainer = document.getElementById('monacoEditor');
                    if (!editorContainer) {
                        console.error('Monaco Editor container disappeared during initialization');
                        return;
                    }

                    // Check if container has proper dimensions
                    if (editorContainer.offsetWidth === 0 || editorContainer.offsetHeight === 0) {
                        console.warn('Monaco Editor container has no dimensions, retrying...');
                        setTimeout(initMonacoEditor, 200);
                        return;
                    }

                    monacoEditor = monaco.editor.create(editorContainer, {
                        value: '',
                        language: 'php',
                        theme: 'vs-dark',
                        fontSize: 14,
                        fontFamily: 'JetBrains Mono, Monaco, "Courier New", monospace',
                        lineNumbers: 'on',
                        roundedSelection: false,
                        scrollBeyondLastLine: false,
                        readOnly: false,
                        automaticLayout: true,
                        minimap: {
                            enabled: true
                        },
                        folding: true,
                        wordWrap: 'on',
                        bracketMatching: 'always',
                        autoIndent: 'full',
                        formatOnPaste: true,
                        formatOnType: true
                    });

                    // Update cursor position with safety check
                    monacoEditor.onDidChangeCursorPosition(function(e) {
                        const cursorElement = document.getElementById('cursorPosition');
                        if (cursorElement) {
                            cursorElement.textContent = `Line ${e.position.lineNumber}, Column ${e.position.column}`;
                        }
                    });

                    // Add keyboard shortcuts
                    monacoEditor.addCommand(monaco.KeyMod.CtrlCmd | monaco.KeyCode.KeyS, function() {
                        saveCode();
                    });

                    monacoEditor.addCommand(monaco.KeyMod.CtrlCmd | monaco.KeyCode.KeyF, function() {
                        findInCode();
                    });

                    console.log('Monaco Editor initialized successfully');
                } catch (error) {
                    console.error('Error initializing Monaco Editor:', error);
                    monacoEditor = null;
                }
            });
        }

        // Open file in editor
        function openFileInEditor(clientId, filePath) {
            currentEditingClientId = clientId;
            currentEditingFile = filePath;

            // Check if DOM is ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() {
                    openFileInEditor(clientId, filePath);
                });
                return;
            }

            // Show loading
            Swal.fire({
                title: 'Đang tải file...',
                html: `Đang tải nội dung file: <strong>${filePath}</strong>`,
                allowOutsideClick: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            });

            // Fetch file content
            fetch(`?api=get_file_content&client_id=${clientId}&file_path=${encodeURIComponent(filePath)}`)
                .then(response => {
                    console.log('Response status:', response.status);
                    console.log('Response headers:', response.headers);
                    return response.json();
                })
                .then(data => {
                    console.log('Response data:', data);
                    Swal.close();

                    if (data && data.success) {
                        // Check if Bootstrap is loaded
                        if (typeof bootstrap === 'undefined') {
                            console.error('Bootstrap is not loaded');
                            Swal.fire({
                                icon: 'error',
                                title: 'Lỗi!',
                                text: 'Bootstrap chưa được load. Vui lòng tải lại trang.'
                            });
                            return;
                        }

                        // Check if modal element exists
                        let modalElement = document.getElementById('codeEditorModal');
                        if (!modalElement) {
                            console.error('Modal element not found, attempting to create...');
                            console.log('All elements with modal in ID:', document.querySelectorAll('[id*="modal"]'));
                            console.log('Body innerHTML length:', document.body.innerHTML.length);
                            console.log('Contains codeEditorModal:', document.body.innerHTML.includes('codeEditorModal'));

                            // Try to wait and check again
                            setTimeout(() => {
                                modalElement = document.getElementById('codeEditorModal');
                                if (!modalElement) {
                                    console.error('Modal element still not found after waiting');
                                    console.log('All divs:', document.querySelectorAll('div').length);

                                    // Create modal dynamically
                                    const modalHTML = `
                                        <div class="modal fade" id="codeEditorModal" tabindex="-1" aria-labelledby="codeEditorModalLabel" aria-hidden="true">
                                            <div class="modal-dialog modal-xl">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="codeEditorModalLabel">
                                                            <i class="fas fa-code me-2"></i>Code Editor
                                                        </h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="closeCodeEditor()"></button>
                                                    </div>
                                                    <div class="modal-body p-0">
                                                        <div class="code-editor-container">
                                                            <div class="editor-toolbar">
                                                                <div class="file-info">
                                                                    <span class="file-path" id="currentFilePath"></span>
                                                                    <span class="file-size" id="currentFileSize"></span>
                                                                </div>
                                                                <div class="editor-actions">
                                                                    <button class="btn btn-sm btn-outline-secondary" onclick="formatCode()">
                                                                        <i class="fas fa-align-left me-1"></i>Format
                                                                    </button>
                                                                    <button class="btn btn-sm btn-outline-info" onclick="findInCode()">
                                                                        <i class="fas fa-search me-1"></i>Find
                                                                    </button>
                                                                    <button class="btn btn-sm btn-outline-light text-light" onclick="toggleFullscreen()" id="fullscreenBtn">
                                                                        <i class="fas fa-expand me-1"></i>Fullscreen
                                                                    </button>
                                                                    <button class="btn btn-sm btn-success" onclick="saveCode()">
                                                                        <i class="fas fa-save me-1"></i>Save
                                                                    </button>
                                                                </div>
                                                            </div>
                                                            <div id="monacoEditor" style="height: 600px; width: 100%;"></div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <div class="editor-status">
                                                            <span id="cursorPosition">Line 1, Column 1</span>
                                                            <span id="fileType">PHP</span>
                                                        </div>
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="closeCodeEditor()">Close</button>
                                                        <button type="button" class="btn btn-primary" onclick="saveAndClose()">Save & Close</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    `;

                                    document.body.insertAdjacentHTML('beforeend', modalHTML);
                                    modalElement = document.getElementById('codeEditorModal');

                                    if (modalElement) {
                                        console.log('Modal created successfully');
                                        proceedWithModal(modalElement, data, filePath);
                                    } else {
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Lỗi!',
                                            text: 'Không thể tạo modal editor.',
                                            footer: '<small>Debug: Failed to create modal</small>'
                                        });
                                    }
                                } else {
                                    proceedWithModal(modalElement, data, filePath);
                                }
                            }, 500);
                            return;
                        }

                        proceedWithModal(modalElement, data, filePath);
                    } else {
                        console.error('API returned error:', data);
                        Swal.fire({
                            icon: 'error',
                            title: 'Lỗi tải file!',
                            text: data?.error || 'Không thể tải nội dung file.',
                            footer: `<small>Debug: ${JSON.stringify(data)}</small>`
                        });
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    Swal.close();
                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi kết nối!',
                        text: 'Không thể kết nối với server để tải file.',
                        footer: `<small>Debug: ${error.message}</small>`
                    });
                });
        }

        // Proceed with modal setup
        function proceedWithModal(modalElement, data, filePath) {
            // Update file info first
            const filePathElement = document.getElementById('currentFilePath');
            const fileSizeElement = document.getElementById('currentFileSize');
            const fileTypeElement = document.getElementById('fileType');

            if (filePathElement) filePathElement.textContent = filePath;
            if (fileSizeElement) fileSizeElement.textContent = `${data.size} bytes`;

            // Set file type
            const extension = filePath.split('.').pop().toLowerCase();
            const language = getLanguageFromExtension(extension);
            if (fileTypeElement) fileTypeElement.textContent = language.toUpperCase();

            // Set editor content
            if (monacoEditor) {
                try {
                    console.log('Setting Monaco editor content...');
                    monaco.editor.setModelLanguage(monacoEditor.getModel(), language);
                    monacoEditor.setValue(data.content);
                    console.log('Monaco editor content set successfully');
                } catch (err) {
                    console.error('Monaco editor error:', err);
                }
            } else {
                console.error('Monaco editor not initialized');
                // Try to initialize Monaco editor
                setTimeout(() => {
                    initMonacoEditor();
                    setTimeout(() => {
                        if (monacoEditor) {
                            try {
                                monaco.editor.setModelLanguage(monacoEditor.getModel(), language);
                                monacoEditor.setValue(data.content);
                                console.log('Monaco editor content set after re-initialization');
                            } catch (err) {
                                console.error('Monaco editor error after re-init:', err);
                                // Fallback: show content in textarea
                                const editorDiv = document.getElementById('monacoEditor');
                                if (editorDiv) {
                                    editorDiv.innerHTML = `<textarea style="width: 100%; height: 500px; font-family: monospace; font-size: 14px; padding: 10px; border: none; outline: none; background: #1e1e1e; color: #d4d4d4;">${data.content}</textarea>`;
                                }
                            }
                        }
                    }, 1000);
                }, 100);
            }

            // Show modal using jQuery as fallback
            try {
                const modal = new bootstrap.Modal(modalElement);
                modal.show();
            } catch (err) {
                console.error('Bootstrap Modal error:', err);
                // Fallback to jQuery if available
                if (typeof $ !== 'undefined') {
                    $(modalElement).modal('show');
                } else {
                    // Manual show
                    modalElement.style.display = 'block';
                    modalElement.classList.add('show');
                    document.body.classList.add('modal-open');

                    // Add backdrop
                    const backdrop = document.createElement('div');
                    backdrop.className = 'modal-backdrop fade show';
                    backdrop.id = 'editorBackdrop';
                    document.body.appendChild(backdrop);
                }
            }
        }

        // Get language from file extension
        function getLanguageFromExtension(extension) {
            const languageMap = {
                'php': 'php',
                'js': 'javascript',
                'html': 'html',
                'htm': 'html',
                'css': 'css',
                'json': 'json',
                'xml': 'xml',
                'sql': 'sql',
                'txt': 'plaintext',
                'log': 'plaintext',
                'conf': 'plaintext',
                'htaccess': 'apache'
            };
            return languageMap[extension] || 'plaintext';
        }

        // Format code
        function formatCode() {
            if (monacoEditor) {
                monacoEditor.trigger('', 'editor.action.formatDocument');
            }
        }

        // Find in code
        function findInCode() {
            if (monacoEditor) {
                monacoEditor.trigger('', 'actions.find');
            }
        }

        // Toggle fullscreen
        function toggleFullscreen() {
            const modal = document.getElementById('codeEditorModal');
            const btn = document.getElementById('fullscreenBtn');
            
            if (!modal || !btn) return;
            
            if (modal.classList.contains('fullscreen-modal')) {
                // Exit fullscreen
                modal.classList.remove('fullscreen-modal');
                btn.innerHTML = '<i class="fas fa-expand me-1"></i>Fullscreen';
                
                // Resize editor
                setTimeout(() => {
                    if (monacoEditor) {
                        monacoEditor.layout();
                    }
                }, 100);
            } else {
                // Enter fullscreen
                modal.classList.add('fullscreen-modal');
                btn.innerHTML = '<i class="fas fa-compress me-1"></i>Exit Fullscreen';
                
                // Resize editor
                setTimeout(() => {
                    if (monacoEditor) {
                        monacoEditor.layout();
                    }
                }, 100);
            }
        }

        // Save code
        function saveCode() {
            if (!monacoEditor || !currentEditingFile || !currentEditingClientId) {
                return;
            }

            const content = monacoEditor.getValue();

            Swal.fire({
                title: 'Đang lưu file...',
                html: `Đang lưu: <strong>${currentEditingFile}</strong>`,
                allowOutsideClick: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch(`?api=save_file_content&client_id=${currentEditingClientId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        file_path: currentEditingFile,
                        content: content
                    })
                })
                .then(response => response.json())
                .then(data => {
                    Swal.close();

                    if (data.success) {
                        // Update file size
                        document.getElementById('currentFileSize').textContent = `${data.size} bytes`;

                        Swal.fire({
                            icon: 'success',
                            title: 'Lưu thành công!',
                            text: `File ${currentEditingFile} đã được lưu.`,
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Lỗi lưu file!',
                            text: data.error || 'Không thể lưu file.'
                        });
                    }
                })
                .catch(error => {
                    Swal.close();
                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi kết nối!',
                        text: 'Không thể kết nối với server để lưu file.'
                    });
                    console.error('Save error:', error);
                });
        }

        // Save and close
        function saveAndClose() {
            saveCode();
            setTimeout(() => {
                closeCodeEditor();
            }, 1000);
        }

        // Close code editor modal
        function closeCodeEditor() {
            const modalElement = document.getElementById('codeEditorModal');
            if (!modalElement) return;

            try {
                const modal = bootstrap.Modal.getInstance(modalElement);
                if (modal) {
                    modal.hide();
                } else {
                    const newModal = new bootstrap.Modal(modalElement);
                    newModal.hide();
                }
            } catch (err) {
                console.error('Bootstrap Modal close error:', err);
                // Fallback to jQuery
                if (typeof $ !== 'undefined') {
                    $(modalElement).modal('hide');
                } else {
                    // Manual hide
                    modalElement.style.display = 'none';
                    modalElement.classList.remove('show');
                    document.body.classList.remove('modal-open');

                    // Remove backdrop
                    const backdrop = document.getElementById('editorBackdrop');
                    if (backdrop) {
                        backdrop.remove();
                    }
                }
            }
        }

        // Multi-Client Results Display
        function displayMultiClientResults(results) {
            // Hide single client results
            const singleResults = document.getElementById('scanResults');
            if (singleResults) {
                singleResults.style.display = 'none';
            }

            // Show multi-client results
            const multiResults = document.getElementById('multiClientResults');
            if (!multiResults) {
                createMultiClientResultsContainer();
            }

            const container = document.getElementById('multiClientResults');
            container.style.display = 'block';

            // Calculate summary stats
            let totalClients = results.length;
            let successfulScans = 0;
            let totalThreats = 0;
            let totalCritical = 0;
            let totalFiles = 0;

            results.forEach(result => {
                if (result.scan_result && result.scan_result.success) {
                    successfulScans++;
                    if (result.scan_result.scan_results) {
                        totalFiles += result.scan_result.scan_results.scanned_files || 0;
                        totalThreats += result.scan_result.scan_results.suspicious_count || 0;
                        totalCritical += result.scan_result.scan_results.critical_count || 0;
                    }
                }
            });

            // Render multi-client interface with table layout
            container.innerHTML = `
                 <div class="multi-client-header">
                     <div class="multi-client-title">
                         <h2><i class="fas fa-network-wired me-2"></i>Kết Quả Quét Đa Client</h2>
                         <button class="btn btn-outline-secondary btn-sm" onclick="hideMultiClientResults()">
                             <i class="fas fa-times"></i> Đóng
                         </button>
                     </div>
                     
                     <div class="multi-client-stats">
                         <div class="stat-item">
                             <span class="stat-number">${totalClients}</span>
                             <span class="stat-label">Clients</span>
                         </div>
                         <div class="stat-item">
                             <span class="stat-number">${totalFiles.toLocaleString()}</span>
                             <span class="stat-label">Files</span>
                         </div>
                         <div class="stat-item">
                             <span class="stat-number">${totalThreats}</span>
                             <span class="stat-label">Threats</span>
                         </div>
                         <div class="stat-item">
                             <span class="stat-number">${totalCritical}</span>
                             <span class="stat-label">Critical</span>
                         </div>
                     </div>
                 </div>
                 
                 <div class="multi-client-content col-12">
                     <div class="client-pagination-header">
                         <div class="pagination-info">
                             <span>Hiển thị <span id="paginationStart">1</span>-<span id="paginationEnd">10</span> của ${totalClients} clients</span>
                         </div>
                         <div class="pagination-controls">
                             <button class="btn btn-sm btn-outline-primary" onclick="changePage(-1)" id="prevPage">
                                 <i class="fas fa-chevron-left"></i> Trước
                             </button>
                             <span id="pageNumbers"></span>
                             <button class="btn btn-sm btn-outline-primary" onclick="changePage(1)" id="nextPage">
                                 Sau <i class="fas fa-chevron-right"></i>
                             </button>
                         </div>
                     </div>
                     
                     <div class="client-table">
                         ${renderClientTable(results)}
                     </div>
                 </div>
             `;

            // Add interactions
            addClientCardInteractions();
        }

        function createMultiClientResultsContainer() {
            const container = document.createElement('div');
            container.id = 'multiClientResults';
            container.className = 'multi-client-results';
            container.style.display = 'none';

            // Insert after bento-grid, not inside it
            const bentoGrid = document.querySelector('.bento-grid');
            if (bentoGrid && bentoGrid.parentNode) {
                bentoGrid.parentNode.insertBefore(container, bentoGrid.nextSibling);
            } else {
                document.querySelector('.container-fluid').appendChild(container);
            }
        }

        // Pagination variables
        let currentPage = 1;
        let itemsPerPage = 10;
        let allClientResults = [];

        function renderClientTable(results) {
            allClientResults = results;
            const startIndex = (currentPage - 1) * itemsPerPage;
            const endIndex = startIndex + itemsPerPage;
            const paginatedResults = results.slice(startIndex, endIndex);

            return `
                 <table class="table table-hover">
                     <thead>
                         <tr>
                             <th width="5%"><i class="fas fa-expand-arrows-alt"></i></th>
                             <th width="25%">Client</th>
                             <th width="15%">Status</th>
                             <th width="15%">Files</th>
                             <th width="15%">Threats</th>
                             <th width="15%">Critical</th>
                             <th width="10%">Actions</th>
                         </tr>
                     </thead>
                     <tbody>
                         ${paginatedResults.map((result, index) => {
                             const realIndex = startIndex + index;
                             return renderClientRow(result, realIndex);
                         }).join('')}
                     </tbody>
                 </table>
             `;
        }

        function renderClientRow(result, index) {

            const client = result.client_info || result.client || {};

            // Handle different possible data structures
            let scanData = {};
            let success = false;

            if (result.scan_result) {
                if (result.scan_result.scan_results) {
                    scanData = result.scan_result.scan_results;
                } else {
                    scanData = result.scan_result;
                }
                success = result.scan_result.success || false;
            } else if (result.scan_results) {
                scanData = result.scan_results;
                success = result.success || false;
            } else {
                scanData = result;
                success = result.success || false;
            }

            const threats = scanData.suspicious_count || scanData.threats || 0;
            const critical = scanData.critical_count || scanData.critical || 0;
            const files = scanData.scanned_files || scanData.files || 0;



            const statusClass = success ? (critical > 0 ? 'danger' : threats > 0 ? 'warning' : 'success') : 'secondary';
            const statusText = success ? (critical > 0 ? 'Critical' : threats > 0 ? 'Warning' : 'Clean') : 'Error';
            const statusIcon = success ? (critical > 0 ? 'skull-crossbones' : threats > 0 ? 'exclamation-triangle' : 'check-circle') : 'times-circle';

            return `
                 <tr class="client-row" data-client-index="${index}">
                     <td>
                         <button class="btn btn-sm btn-outline-secondary expand-btn" onclick="toggleClientDetails(${index})" title="Expand details">
                             <i class="fas fa-chevron-right" id="expand-icon-${index}"></i>
                         </button>
                     </td>
                     <td>
                         <div class="client-info-cell">
                             <div class="client-name">${client.name || 'Unknown Client'}</div>
                             <div class="client-url">${client.domain || client.url || 'N/A'}</div>
                         </div>
                     </td>
                     <td>
                         <span class="badge bg-${statusClass}">
                             <i class="fas fa-${statusIcon} me-1"></i>${statusText}
                         </span>
                     </td>
                     <td><strong>${files.toLocaleString()}</strong></td>
                     <td><strong class="${threats > 0 ? 'text-warning' : ''}">${threats}</strong></td>
                     <td><strong class="${critical > 0 ? 'text-danger' : ''}">${critical}</strong></td>
                     <td>
                                               <button class="btn btn-sm btn-outline-danger" onclick="viewClientThreats(${index})" title="View threats">
                          <i class="fas fa-bug"></i>
                      </button>
                     </td>
                 </tr>
                 <tr class="client-details-row" id="client-details-${index}" style="display: none;">
                     <td colspan="7">
                         <div class="client-threats-container" id="threats-container-${index}">
                             <!-- Threats will be loaded here -->
                         </div>
                     </td>
                 </tr>
             `;
        }

        function toggleClientDetails(index) {
            const detailsRow = document.getElementById(`client-details-${index}`);
            const expandIcon = document.getElementById(`expand-icon-${index}`);
            const threatsContainer = document.getElementById(`threats-container-${index}`);

            if (detailsRow.style.display === 'none') {
                // Expand
                detailsRow.style.display = 'table-row';
                expandIcon.className = 'fas fa-chevron-down';

                // Load client threats
                loadClientThreats(index, threatsContainer);
            } else {
                // Collapse
                detailsRow.style.display = 'none';
                expandIcon.className = 'fas fa-chevron-right';
            }
        }

        function loadClientThreats(index, container) {
            const result = allClientResults[index];

            if (!result) {
                container.innerHTML = '<div class="p-3 text-muted">Không tìm thấy thông tin client.</div>';
                return;
            }

            // Extract threats from real scan data (same logic as viewClientThreats)
            let threats = [];

            const scanResults = result.scan_result?.scan_results || result.scan_result || {};

            // Check if threats data exists in the response
            if (scanResults.threats) {

                // Combine all threat categories
                const allCategories = ['critical', 'webshells', 'warnings'];

                allCategories.forEach(category => {
                    if (scanResults.threats[category] && Array.isArray(scanResults.threats[category])) {
                        scanResults.threats[category].forEach(threat => {
                            // Only add if not already exists
                            if (!threats.find(f => f.path === threat.path)) {
                                threats.push({
                                    path: threat.path,
                                    issues: threat.issues || [],
                                    metadata: {
                                        size: threat.file_size || 0,
                                        modified_time: threat.modified_time || 0,
                                        md5: threat.md5 || '',
                                    },
                                    category: threat.category || category,
                                    is_priority: threat.is_priority || false
                                });
                            }
                        });
                    }
                });
            }

            // Fallback: try suspicious_files directly
            if (threats.length === 0) {
                if (scanResults.suspicious_files && Array.isArray(scanResults.suspicious_files)) {
                    threats = scanResults.suspicious_files;
                } else if (result.suspicious_files && Array.isArray(result.suspicious_files)) {
                    threats = result.suspicious_files;
                }
            }



            if (!threats || threats.length === 0) {
                container.innerHTML = `
                     <div class="p-3 text-muted">
                         <div>Không có threats nào được phát hiện.</div>
                         <small class="text-secondary">Suspicious Count: ${scanResults.suspicious_count || 0}</small>
                     </div>
                 `;
                return;
            }

            const client = result.client_info || result.client || {};

            container.innerHTML = `
                 <div class="threats-header">
                     <h6><i class="fas fa-bug me-2"></i>Threats trong ${client.name || 'Client'} (${threats.length} files)</h6>
                 </div>
                 <div class="threats-list">
                     ${threats.map(threat => renderThreatItem(threat, index)).join('')}
                 </div>
             `;
        }

        function renderThreatItem(threat, clientIndex) {
            const getSeverityBadge = (severity) => {
                const badges = {
                    'critical': '<span class="badge bg-danger">Critical</span>',
                    'high': '<span class="badge bg-warning">High</span>',
                    'medium': '<span class="badge bg-info">Medium</span>',
                    'low': '<span class="badge bg-secondary">Low</span>',
                    'warning': '<span class="badge bg-warning">Warning</span>'
                };
                return badges[severity] || '<span class="badge bg-secondary">Unknown</span>';
            };

            const getCategoryBadge = (category) => {
                const badges = {
                    'critical': '<span class="badge bg-danger">Critical</span>',
                    'webshell': '<span class="badge bg-dark">Webshell</span>',
                    'warnings': '<span class="badge bg-warning">Warning</span>',
                    'filemanager': '<span class="badge bg-info">File Manager</span>'
                };
                return badges[category] || '<span class="badge bg-secondary">' + (category || 'Unknown') + '</span>';
            };

            const formatFileSize = (bytes) => {
                if (!bytes || bytes === 0) return '0 Bytes';
                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            };

            const formatDate = (timestamp) => {
                if (!timestamp) return 'Unknown';
                return new Date(timestamp * 1000).toLocaleString('vi-VN');
            };

            const fileSize = formatFileSize(threat.metadata?.size || threat.file_size || 0);
            const modifiedDate = formatDate(threat.metadata?.modified_time || threat.modified_time);

            return `
                 <div class="threat-item border rounded p-3 mb-3" style="background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%); border-left: 4px solid ${threat.category === 'critical' ? '#ef4444' : threat.category === 'webshell' ? '#1f2937' : '#f59e0b'} !important;">
                     <div class="d-flex justify-content-between align-items-start">
                         <div class="flex-grow-1">
                             <div class="threat-header mb-2">
                                 <h6 class="mb-1">
                                     <i class="fas fa-file-code text-primary me-2"></i>
                                     <code style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-size: 13px;">${threat.path}</code>
                                 </h6>
                                 <div class="threat-badges">
                                     ${getCategoryBadge(threat.category)}
                                     ${threat.is_priority ? '<span class="badge bg-danger ms-1">Priority</span>' : ''}
                                 </div>
                             </div>
                             
                             <div class="threat-meta mb-2">
                                 <div class="row">
                                     <div class="col-md-6">
                                         <small class="text-muted">
                                             <i class="fas fa-hdd me-1"></i>
                                             ${fileSize}
                                         </small>
                                     </div>
                                     <div class="col-md-6">
                                         <small class="text-muted">
                                             <i class="fas fa-clock me-1"></i>
                                             ${modifiedDate}
                                         </small>
                                     </div>
                                 </div>
                             </div>
                             
                             ${threat.issues && threat.issues.length > 0 ? `
                                 <div class="threat-issues">
                                     <small class="text-danger">
                                         <i class="fas fa-exclamation-triangle me-1"></i>
                                         <strong>${threat.issues.length} issues:</strong>
                                     </small>
                                     <div class="mt-1">
                                         ${threat.issues.slice(0, 3).map(issue => `
                                             <div class="issue-item border rounded p-2 mb-2" style="background: #fef2f2; border-color: #fecaca !important;">
                                                 <div class="d-flex justify-content-between align-items-start">
                                                     <div class="flex-grow-1">
                                                         ${getSeverityBadge(issue.severity)}
                                                         <span class="ms-2 fw-bold text-danger">${issue.description || issue.pattern}</span>
                                                         ${(issue.line_number || issue.line) ? `<span class="ms-2 badge bg-dark">Dòng ${issue.line_number || issue.line}</span>` : ''}
                                                         ${issue.pattern && issue.description && issue.pattern !== issue.description ? `<div class="mt-1"><small class="text-muted font-monospace" style="font-size: 9px;">Pattern: ${issue.pattern.length > 50 ? issue.pattern.substring(0, 50) + '...' : issue.pattern}</small></div>` : ''}
                                                     </div>
                                                 </div>
                                                 ${issue.description ? `<div class="mt-1"><small class="text-muted">${issue.description}</small></div>` : ''}
                                                 ${issue.context ? `<div class="mt-1"><code style="font-size: 11px; background: #f8f9fa; padding: 2px 4px; border-radius: 3px;">${issue.context.substring(0, 100)}...</code></div>` : ''}
                                             </div>
                                         `).join('')}
                                         ${threat.issues.length > 3 ? `
                                             <div class="mt-2 p-2 bg-light border rounded">
                                                 <small class="text-muted">
                                                     <i class="fas fa-info-circle me-1"></i>
                                                     Và ${threat.issues.length - 3} issues khác. Click <strong>Edit</strong> để xem toàn bộ.
                                                 </small>
                                             </div>
                                         ` : ''}
                                     </div>
                                 </div>
                             ` : ''}
                         </div>
                         
                         <div class="threat-actions ms-3">
                             <div class="btn-group-vertical">
                                 <button class="btn btn-sm btn-outline-primary" onclick="viewThreatInClient(${clientIndex}, '${threat.path}')" title="Edit File">
                                     <i class="fas fa-edit"></i>
                                 </button>
                                 <button class="btn btn-sm btn-outline-danger" onclick="deleteThreatInClient(${clientIndex}, '${threat.path}')" title="Delete File">
                                     <i class="fas fa-trash"></i>
                                 </button>
                             </div>
                         </div>
                     </div>
                 </div>
             `;
        }

        function viewThreatInClient(clientIndex, filePath) {
            console.log('viewThreatInClient called:', { clientIndex, filePath, allClientResults });
            
            const result = allClientResults[clientIndex];
            if (!result) {
                console.error('Result not found for clientIndex:', clientIndex);
                console.log('Available results:', allClientResults);
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi Debug Info!',
                    html: `
                        <div class="text-start">
                            <p><strong>Client Index:</strong> ${clientIndex}</p>
                            <p><strong>Total Results:</strong> ${allClientResults?.length || 0}</p>
                            <p><strong>Available Indices:</strong> ${allClientResults?.map((r, i) => i).join(', ') || 'None'}</p>
                            <p><strong>File Path:</strong> ${filePath}</p>
                        </div>
                    `
                });
                return;
            }

            // Try to get client from multiple sources with enhanced fallback
            let client = result.client_info || result.client || {};
            
            // Enhanced client info extraction with better fallback logic
            let matchedClient = null;
            
            // First try to find by exact index in clients array
            if (clients[clientIndex]) {
                matchedClient = clients[clientIndex];
                console.log('Found client by index:', matchedClient);
            }
            
            // If no direct match, try to find by client info in result
            if (!matchedClient && (result.client_info || result.client)) {
                const clientInfo = result.client_info || result.client;
                matchedClient = clients.find(c => 
                    c.name === clientInfo.name ||
                    c.id === clientInfo.id ||
                    c.url === clientInfo.url ||
                    c.url === clientInfo.client_url
                );
                console.log('Found client by info match:', matchedClient);
            }
            
            // Fallback to first client if still no match
            if (!matchedClient && clients.length > 0) {
                matchedClient = clients[0];
                console.log('Using fallback first client:', matchedClient);
            }
            
            // Use matched client or create temp one
            if (matchedClient) {
                client = matchedClient;
            } else {
                // Create temporary client based on result info
                const clientInfo = result.client_info || result.client || {};
                client = {
                    id: `temp_client_${clientIndex}_${Date.now()}`,
                    name: clientInfo.name || `Client ${clientIndex + 1}`,
                    url: clientInfo.url || clientInfo.client_url || 'http://localhost',
                    api_key: clientInfo.api_key || 'default-key',
                    domain: clientInfo.domain || 'localhost'
                };
                console.log('Created temporary client:', client);
            }

            console.log('Final client for editor:', {
                clientIndex,
                filePath,
                client,
                resultKeys: Object.keys(result),
                clientKeys: Object.keys(client)
            });

                         // Validate client info before opening editor
             if (!client.url || client.url === '') {
                 Swal.fire({
                     icon: 'warning',
                     title: 'Thiếu thông tin client!',
                     html: `
                         <div class="text-start">
                             <p><strong>Không có URL của client để mở editor.</strong></p>
                             <p><small>Client: ${client.name}</small></p>
                             <p><small>File: ${filePath}</small></p>
                             <p><small class="text-muted">Hãy đảm bảo rằng thông tin client đã được cấu hình đúng.</small></p>
                         </div>
                     `
                 });
                 return;
             }

            // Debug client info before calling editor
            console.log('Opening editor with client:', {
                providedClientId: client.id,
                clientName: client.name,
                clientUrl: client.url,
                clientIndex: clientIndex
            });
            
            // Use the same logic as single client threat viewing
            openFileInEditor(client.id, filePath);
        }

        function deleteThreatInClient(clientIndex, filePath) {
            const result = allClientResults[clientIndex];
            if (!result) return;

            // Enhanced client resolution - same logic as viewThreatInClient
            let client = result.client_info || result.client || {};
            let matchedClient = null;
            
            // First try to find by exact index in clients array
            if (clients[clientIndex]) {
                matchedClient = clients[clientIndex];
            }
            
            // If no direct match, try to find by client info in result
            if (!matchedClient && (result.client_info || result.client)) {
                const clientInfo = result.client_info || result.client;
                matchedClient = clients.find(c => 
                    c.name === clientInfo.name ||
                    c.id === clientInfo.id ||
                    c.url === clientInfo.url ||
                    c.url === clientInfo.client_url
                );
            }
            
            // Use matched client or create temp one
            if (matchedClient) {
                client = matchedClient;
            } else {
                // Create temporary client based on result info
                const clientInfo = result.client_info || result.client || {};
                client = {
                    id: `temp_client_${clientIndex}_${Date.now()}`,
                    name: clientInfo.name || `Client ${clientIndex + 1}`,
                    url: clientInfo.url || clientInfo.client_url || 'http://localhost',
                    api_key: clientInfo.api_key || 'default-key',
                    domain: clientInfo.domain || 'localhost'
                };
            }

            // Store current client context
            const previousClientId = currentClientId;
            currentClientId = client.id;

            // Use the same logic as single client threat deletion  
            Swal.fire({
                title: 'Xác Nhận Xóa File',
                html: `Bạn có chắc muốn xóa file này?<br><strong>${filePath}</strong>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Xóa',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Call delete API with specific client
                    console.log('Deleting file with client:', {
                        clientId: client.id,
                        filePath: filePath,
                        clientName: client.name
                    });
                    
                    fetch(`?api=delete_file&client_id=${client.id}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                file_path: filePath
                            })
                        })
                        .then(response => {
                            // Check if response is ok first
                            if (!response.ok) {
                                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data.success) {
                                Swal.fire('Đã Xóa!', 'File đã được xóa thành công.', 'success');

                                // Remove from current view
                                const threatItem = document.querySelector(`[onclick*="${filePath}"]`);
                                if (threatItem && threatItem.closest('.threat-item')) {
                                    threatItem.closest('.threat-item').remove();
                                }
                                
                                // Refresh scan results to update counts
                                if (typeof refreshScanResults === 'function') {
                                    refreshScanResults();
                                }
                            } else {
                                // Better error handling - distinguish between different error types
                                let errorMessage = data.error || 'Không thể xóa file.';
                                let icon = 'error';
                                
                                if (errorMessage.includes('File not found') || errorMessage.includes('not found')) {
                                    errorMessage = 'File không tồn tại hoặc đã được xóa trước đó.';
                                    icon = 'info';
                                    
                                    // Remove from view if file not found
                                    const threatItem = document.querySelector(`[onclick*="${filePath}"]`);
                                    if (threatItem && threatItem.closest('.threat-item')) {
                                        threatItem.closest('.threat-item').remove();
                                    }
                                }
                                
                                Swal.fire('Thông báo', errorMessage, icon);
                            }
                        })
                        .catch(error => {
                            console.error('Delete error:', error);
                            
                            // Enhanced error handling - check if it's a connection issue after potential success
                            if (error.message.includes('Failed to fetch') || error.message.includes('NetworkError')) {
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'Lưu ý!',
                                    html: `
                                        <p>Có thể file đã được xóa thành công nhưng gặp lỗi kết nối.</p>
                                        <p><small>Vui lòng refresh trang để kiểm tra.</small></p>
                                    `,
                                    showCancelButton: true,
                                    confirmButtonText: 'Refresh trang',
                                    cancelButtonText: 'Đóng'
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        location.reload();
                                    }
                                });
                            } else {
                                Swal.fire('Lỗi!', `Có lỗi xảy ra: ${error.message}`, 'error');
                            }
                        });
                }
            });
        }

        function changePage(direction) {
            const totalPages = Math.ceil(allClientResults.length / itemsPerPage);

            if (direction === -1 && currentPage > 1) {
                currentPage--;
            } else if (direction === 1 && currentPage < totalPages) {
                currentPage++;
            }

            updatePagination();
        }

        function updatePagination() {
            const totalPages = Math.ceil(allClientResults.length / itemsPerPage);
            const startIndex = (currentPage - 1) * itemsPerPage;
            const endIndex = Math.min(startIndex + itemsPerPage, allClientResults.length);

            // Update table
            const clientTable = document.querySelector('.client-table');
            if (clientTable) {
                clientTable.innerHTML = renderClientTable(allClientResults);
            }

            // Update pagination info
            document.getElementById('paginationStart').textContent = startIndex + 1;
            document.getElementById('paginationEnd').textContent = endIndex;

            // Update buttons
            document.getElementById('prevPage').disabled = currentPage === 1;
            document.getElementById('nextPage').disabled = currentPage === totalPages;

            // Update page numbers
            const pageNumbers = document.getElementById('pageNumbers');
            if (pageNumbers) {
                let pagesHtml = '';
                for (let i = 1; i <= totalPages; i++) {
                    if (i === currentPage) {
                        pagesHtml += `<span class="page-number current">${i}</span>`;
                    } else {
                        pagesHtml += `<span class="page-number" onclick="goToPage(${i})">${i}</span>`;
                    }
                }
                pageNumbers.innerHTML = pagesHtml;
            }
        }

        function goToPage(page) {
            currentPage = page;
            updatePagination();
        }



        function addClientCardInteractions() {
            // Add hover effects and click handlers
            document.querySelectorAll('.client-card').forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-4px)';
                    this.style.boxShadow = '0 8px 32px rgba(0, 0, 0, 0.15)';
                });

                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = '0 4px 16px rgba(0, 0, 0, 0.1)';
                });
            });
        }

        function viewClientDetails(index) {
            const result = currentMultiClientResults[index];
            if (!result) return;

            // Show detailed view in modal or switch to single client view
            const client = result.client_info || {};
            const scanData = result.scan_result?.scan_results || {};

            Swal.fire({
                title: `Chi Tiết Client: ${client.name || 'Unknown'}`,
                html: `
                    <div class="client-details">
                        <div class="detail-row">
                            <strong>Domain:</strong> ${client.domain || 'N/A'}
                        </div>
                        <div class="detail-row">
                            <strong>Files Scanned:</strong> ${scanData.scanned_files || 0}
                        </div>
                        <div class="detail-row">
                            <strong>Suspicious Files:</strong> ${scanData.suspicious_count || 0}
                        </div>
                        <div class="detail-row">
                            <strong>Critical Threats:</strong> ${scanData.critical_count || 0}
                        </div>
                        <div class="detail-row">
                            <strong>Scan Time:</strong> ${client.scan_time || 'N/A'}s
                        </div>
                    </div>
                `,
                width: 600,
                showConfirmButton: false,
                showCloseButton: true
            });
        }

        function viewClientThreats(index) {
            const result = currentMultiClientResults[index];

            if (!result) {
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi',
                    text: 'Không tìm thấy thông tin client.'
                });
                return;
            }

            // Extract threats from real scan data 
            let suspiciousFiles = [];

            // Try to parse threats from different data structures
            const scanResults = result.scan_result?.scan_results || result.scan_result || {};

            // Check if threats data exists in the response
            if (scanResults.threats) {

                // Combine all threat categories
                const allCategories = ['critical', 'webshells', 'warnings', 'all'];

                allCategories.forEach(category => {
                    if (scanResults.threats[category] && Array.isArray(scanResults.threats[category])) {
                        scanResults.threats[category].forEach(threat => {
                            // Only add if not already exists (avoid duplicates from 'all' category)
                            if (!suspiciousFiles.find(f => f.path === threat.path)) {
                                suspiciousFiles.push({
                                    path: threat.path,
                                    issues: threat.issues || [],
                                    metadata: {
                                        size: threat.file_size || 0,
                                        modified_time: threat.modified_time || 0,
                                        md5: threat.md5 || '',
                                    },
                                    category: threat.category || category,
                                    is_priority: threat.is_priority || false
                                });
                            }
                        });
                    }
                });
            }

            // Fallback: try suspicious_files directly
            if (suspiciousFiles.length === 0) {
                if (scanResults.suspicious_files && Array.isArray(scanResults.suspicious_files)) {
                    suspiciousFiles = scanResults.suspicious_files;
                } else if (result.suspicious_files && Array.isArray(result.suspicious_files)) {
                    suspiciousFiles = result.suspicious_files;
                }
            }



            if (!suspiciousFiles || suspiciousFiles.length === 0) {
                const suspiciousCount = scanResults.suspicious_count || 0;

                Swal.fire({
                    icon: 'info',
                    title: 'Không có threats',
                    text: 'Client này không có file nguy hiểm nào.',
                    html: `
                         <div style="text-align: left; font-size: 12px; margin-top: 10px;">
                             <strong>Debug Info:</strong><br>
                             - Index: ${index}<br>
                             - Client: ${result.client_info?.name || 'Unknown'}<br>
                             - Suspicious Count: ${suspiciousCount}<br>
                             - Has Threats Object: ${scanResults.threats ? 'Yes' : 'No'}<br>
                             - Has Suspicious Files: ${scanResults.suspicious_files ? 'Yes' : 'No'}
                         </div>
                     `
                });
                return;
            }

            // Set current client for file operations
            const client = result.client_info || {};
            currentClientId = client.id || `client_${index}`;

            // Switch to single client view with this client's results
            currentScanResults = {
                suspicious_files: suspiciousFiles,
                scanned_files: result.scan_result?.scan_results?.scanned_files || result.scan_result?.scanned_files || 0,
                suspicious_count: result.scan_result?.scan_results?.suspicious_count || result.scan_result?.suspicious_count || suspiciousFiles.length,
                critical_count: result.scan_result?.scan_results?.critical_count || result.scan_result?.critical_count || 0
            };

            // Store client info for file operations
            if (!clients.find(c => c.id === currentClientId)) {
                clients.push({
                    id: currentClientId,
                    name: client.name || `Client ${index + 1}`,
                    url: client.url || client.domain || '',
                    api_key: client.api_key || '',
                    domain: client.domain || client.url || ''
                });
            }

            // Hide multi-client results
            hideMultiClientResults();

            // Show single client results with back button
            const scanResultsDiv = document.getElementById('scanResults');
            if (scanResultsDiv) {
                scanResultsDiv.style.display = 'block';

                // Add back button to return to multi-client view
                const resultsHeader = scanResultsDiv.querySelector('.results-header');
                if (resultsHeader) {
                    // Remove existing back button if any
                    const existingBackBtn = resultsHeader.querySelector('.back-to-multi-client');
                    if (existingBackBtn) {
                        existingBackBtn.remove();
                    }

                    // Add new back button
                    const backButton = document.createElement('button');
                    backButton.className = 'btn btn-outline-secondary btn-sm back-to-multi-client';
                    backButton.innerHTML = '<i class="fas fa-arrow-left me-1"></i>Quay lại tổng quan';
                    backButton.onclick = function() {
                        document.getElementById('scanResults').style.display = 'none';
                        document.getElementById('multiClientResults').style.display = 'block';
                    };

                    const resultsTitle = resultsHeader.querySelector('.results-title');
                    if (resultsTitle) {
                        resultsTitle.appendChild(backButton);
                    }
                }
            }

            // Display results
            displayScanResults(currentScanResults);
        }

        function hideMultiClientResults() {
            const container = document.getElementById('multiClientResults');
            if (container) {
                container.style.display = 'none';
            }
        }

        // Enhanced time display function
        function getTimeInfo(timestamp) {
            if (!timestamp) return {
                relative: 'Không xác định',
                absolute: 'Không xác định',
                tooltip: 'Thời gian không xác định'
            };

            const now = Date.now() / 1000;
            const diff = now - timestamp;
            const date = new Date(timestamp * 1000);
            
            // Format absolute date and time
            const absoluteDate = date.toLocaleDateString('vi-VN', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit'
            });
            
            const absoluteTime = date.toLocaleTimeString('vi-VN', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            
            const absoluteFull = `${absoluteDate} lúc ${absoluteTime}`;

            // Calculate relative time
            let relative;
            if (diff < 60) { // < 1 minute
                relative = 'Vừa xong';
            } else if (diff < 3600) { // < 1 hour
                const minutes = Math.floor(diff / 60);
                relative = `${minutes} phút trước`;
            } else if (diff < 86400) { // < 1 day
                const hours = Math.floor(diff / 3600);
                relative = `${hours} giờ trước`;
            } else if (diff < 604800) { // < 1 week
                const days = Math.floor(diff / 86400);
                relative = `${days} ngày trước`;
            } else if (diff < 2592000) { // < 1 month
                const weeks = Math.floor(diff / 604800);
                relative = `${weeks} tuần trước`;
            } else if (diff < 31536000) { // < 1 year
                const months = Math.floor(diff / 2592000);
                relative = `${months} tháng trước`;
            } else {
                const years = Math.floor(diff / 31536000);
                relative = `${years} năm trước`;
            }

            return {
                relative: relative,
                absolute: absoluteFull,
                tooltip: `Phát hiện: ${absoluteFull} (${relative})`
            };
        }

        // Legacy function for backward compatibility
        function getTimeAgo(timestamp) {
            return getTimeInfo(timestamp).relative;
        }

        // Get severity label
        function getSeverityLabel(severity) {
            const labels = {
                'critical': 'Nguy hiểm',
                'warning': 'Cảnh báo',
                'info': 'Thông tin'
            };
            return labels[severity] || 'Thông tin';
        }

        // Refresh recent threats
        function refreshRecentThreats() {
            const refreshBtn = document.querySelector('.sidebar-refresh i');
            if (refreshBtn) {
                refreshBtn.style.animation = 'spin 0.5s linear';
                setTimeout(() => {
                    refreshBtn.style.animation = '';
                }, 500);
            }

            // Re-populate recent threats from current results
            if (currentScanResults) {
                displayScanResults(currentScanResults);
            }
        }

        // Toggle show all threats
        function toggleAllThreats() {
            const threatsContainer = document.getElementById('threatsContainer');
            const showAllBtn = threatsContainer.querySelector('.show-all-threats-btn');

            if (threatsContainer.classList.contains('collapsed')) {
                // Expand
                threatsContainer.classList.remove('collapsed');
                showAllBtn.innerHTML = 'Thu gọn <i class="fas fa-chevron-up"></i>';
                showAllBtn.classList.add('expanded');
            } else {
                // Collapse
                threatsContainer.classList.add('collapsed');
                showAllBtn.innerHTML = 'Xem tất cả <i class="fas fa-chevron-down"></i>';
                showAllBtn.classList.remove('expanded');

                // Scroll to top of threats container
                threatsContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }

        // Initialize Monaco Editor when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            // Don't initialize immediately, wait for modal to be shown
            console.log('DOM ready, Monaco Editor will be initialized when needed');
        });

        // Initialize Monaco Editor when modal is shown
        $(document).on('shown.bs.modal', '#fileEditorModal', function() {
            console.log('File editor modal shown, initializing Monaco Editor...');
            setTimeout(initMonacoEditor, 100);
        });

        // Cleanup Monaco Editor when modal is hidden
        $(document).on('hidden.bs.modal', '#fileEditorModal', function() {
            if (monacoEditor) {
                console.log('Disposing Monaco Editor...');
                monacoEditor.dispose();
                monacoEditor = null;
            }
        });

        // ==================== SECURITY REMEDIATION FUNCTIONS ====================

        /**
         * Show remediation modal for a client
         */
        function showRemediationModal(clientId) {
            console.log('showRemediationModal called with clientId:', clientId);
            console.log('Available clients:', clients);

            currentRemediationClientId = clientId;
            const client = clients.find(c => c.id === clientId);

            if (!client) {
                console.error('Client not found for ID:', clientId);
                Swal.fire('Lỗi!', 'Không tìm thấy client.', 'error');
                return;
            }

            console.log('Found client:', client);

            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('remediationModal'));
            modal.show();

            // Reset modal state
            document.getElementById('remediationLoading').style.display = 'block';
            document.getElementById('remediationContent').style.display = 'none';
            document.getElementById('remediationResults').style.display = 'none';
            document.getElementById('executeRemediationBtn').style.display = 'none';
            document.getElementById('refreshAfterRemediationBtn').style.display = 'none';

            // Load available fixes
            loadAvailableFixes(clientId);
        }

        /**
         * Load available fixes for a client
         */
        function loadAvailableFixes(clientId) {
            fetch(`?api=get_available_fixes&client_id=${clientId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        availableFixes = data.fixes;
                        renderFixesList();

                        document.getElementById('remediationLoading').style.display = 'none';
                        document.getElementById('remediationContent').style.display = 'block';
                        document.getElementById('executeRemediationBtn').style.display = 'inline-block';
                    } else {
                        Swal.fire('Lỗi!', data.error || 'Không thể tải danh sách khắc phục.', 'error');
                        document.getElementById('remediationLoading').style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error('Error loading fixes:', error);
                    Swal.fire('Lỗi!', 'Lỗi kết nối khi tải danh sách khắc phục.', 'error');
                    document.getElementById('remediationLoading').style.display = 'none';
                });
        }

        /**
         * Render fixes list in futuristic bento grid layout
         */
        function renderFixesList() {
            const fixesList = document.getElementById('fixesList');
            fixesList.innerHTML = '';

            Object.entries(availableFixes).forEach(([fixId, fix]) => {
                const severityClass = fix.severity === 'critical' ? 'hiep-severity-critical' : 'hiep-severity-warning';

                const fixCard = document.createElement('div');
                fixCard.className = 'hiep-fix-card';
                fixCard.setAttribute('data-fix-id', fixId);

                fixCard.innerHTML = `
                    <input class="hiep-fix-checkbox fix-checkbox" type="checkbox" value="${fixId}" id="fix_${fixId}">

                    <div class="hiep-fix-header">
                        <h3 class="hiep-fix-title">${fix.title}</h3>
                        <span class="hiep-severity-badge ${severityClass}">${fix.severity}</span>
                    </div>

                    <div class="hiep-fix-description">
                        ${fix.description}
                    </div>

                    <div class="hiep-fix-meta">
                        <span><i class="fas fa-file me-1"></i>${fix.file}</span>
                        <span><i class="fas fa-clock me-1"></i>${fix.estimated_time}</span>
                    </div>
                `;

                // Add click handler for card selection
                fixCard.addEventListener('click', function(e) {
                    if (e.target.type !== 'checkbox') {
                        const checkbox = this.querySelector('.hiep-fix-checkbox');
                        checkbox.checked = !checkbox.checked;
                        this.classList.toggle('selected', checkbox.checked);
                        updateExecuteButtonState();
                    }
                });

                // Add change handler for checkbox
                const checkbox = fixCard.querySelector('.hiep-fix-checkbox');
                checkbox.addEventListener('change', function() {
                    fixCard.classList.toggle('selected', this.checked);
                    updateExecuteButtonState();
                });

                fixesList.appendChild(fixCard);
            });

            // Add select all functionality
            const selectAllCheckbox = document.getElementById('selectAllFixes');
            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', function() {
                    const checkboxes = document.querySelectorAll('.fix-checkbox');
                    const cards = document.querySelectorAll('.hiep-fix-card');

                    checkboxes.forEach((cb, index) => {
                        cb.checked = this.checked;
                        cards[index].classList.toggle('selected', this.checked);
                    });

                    updateExecuteButtonState();
                });
            }

            updateExecuteButtonState();
        }

        /**
         * Update execute button state based on selected fixes
         */
        function updateExecuteButtonState() {
            const selectedFixes = document.querySelectorAll('.fix-checkbox:checked');
            const executeBtn = document.getElementById('executeRemediationBtn');

            if (selectedFixes.length > 0) {
                executeBtn.style.display = 'inline-flex';
                executeBtn.innerHTML = `<i class="fas fa-rocket me-2"></i>Execute ${selectedFixes.length} Fix${selectedFixes.length > 1 ? 'es' : ''}`;
            } else {
                executeBtn.style.display = 'none';
            }
        }

        /**
         * Execute selected remediation fixes
         */
        function executeRemediation() {
            const selectedFixes = Array.from(document.querySelectorAll('.fix-checkbox:checked'))
                .map(cb => cb.value);

            if (selectedFixes.length === 0) {
                Swal.fire('Cảnh báo!', 'Vui lòng chọn ít nhất một mục để khắc phục.', 'warning');
                return;
            }

            // Confirm with user
            Swal.fire({
                title: 'Xác nhận khắc phục',
                html: `
                    <p>Bạn đã chọn <strong>${selectedFixes.length}</strong> mục để khắc phục.</p>
                    <p class="text-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Hệ thống sẽ tự động tạo backup trước khi thực hiện.
                    </p>
                    <p>Bạn có chắc chắn muốn tiếp tục?</p>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Thực hiện',
                cancelButtonText: 'Hủy',
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#dc3545'
            }).then((result) => {
                if (result.isConfirmed) {
                    performRemediation(selectedFixes);
                }
            });
        }

        /**
         * Perform the actual remediation
         */
        function performRemediation(selectedFixes) {
            // Show loading
            Swal.fire({
                title: 'Đang thực hiện khắc phục...',
                html: 'Vui lòng đợi trong khi hệ thống khắc phục các lỗ hỏng bảo mật.',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Send remediation request
            fetch(`?api=execute_remediation&client_id=${currentRemediationClientId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    selected_fixes: selectedFixes
                })
            })
            .then(response => {
                // Check if response is ok
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                // Get response text first to check if it's valid JSON
                return response.text();
            })
            .then(text => {
                Swal.close();

                try {
                    // Try to parse as JSON
                    const data = JSON.parse(text);

                    if (data.success) {
                        displayRemediationResults(data.results);
                    } else {
                        Swal.fire('Lỗi!', data.error || 'Không thể thực hiện khắc phục.', 'error');
                    }
                } catch (jsonError) {
                    // If JSON parsing fails, show the raw response
                    console.error('Invalid JSON response:', text);
                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi phản hồi từ server!',
                        html: `
                            <div class="text-start">
                                <p>Server trả về phản hồi không hợp lệ:</p>
                                <pre style="max-height: 200px; overflow-y: auto; background: #f8f9fa; padding: 10px; border-radius: 4px; font-size: 12px;">${text.substring(0, 1000)}${text.length > 1000 ? '...' : ''}</pre>
                            </div>
                        `,
                        width: '600px'
                    });
                }
            })
            .catch(error => {
                Swal.close();
                console.error('Error performing remediation:', error);
                Swal.fire('Lỗi!', 'Lỗi kết nối khi thực hiện khắc phục: ' + error.message, 'error');
            });
        }

        /**
         * Display remediation results
         */
        function displayRemediationResults(results) {
            const resultsContent = document.getElementById('resultsContent');
            resultsContent.innerHTML = '';

            let successCount = 0;
            let failureCount = 0;

            Object.entries(results).forEach(([fixId, result]) => {
                const fix = availableFixes[fixId];
                const isSuccess = result.success;

                if (isSuccess) successCount++;
                else failureCount++;

                const resultItem = document.createElement('div');
                resultItem.className = `alert alert-${isSuccess ? 'success' : 'danger'}`;
                resultItem.innerHTML = `
                    <div class="d-flex align-items-start">
                        <i class="fas fa-${isSuccess ? 'check-circle' : 'times-circle'} me-2 mt-1"></i>
                        <div class="flex-grow-1">
                            <h6 class="mb-1">${fix.title}</h6>
                            ${isSuccess ?
                                `<p class="mb-1">Khắc phục thành công!</p>
                                 ${result.backup_path ? `<small class="text-muted">Backup: ${result.backup_path}</small>` : ''}
                                 ${result.fixes_applied ? `<br><small class="text-muted">Đã áp dụng: ${result.fixes_applied} thay đổi</small>` : ''}` :
                                `<p class="mb-0 text-danger">Lỗi: ${result.error}</p>`
                            }
                        </div>
                    </div>
                `;
                resultsContent.appendChild(resultItem);
            });

            // Show summary
            const summary = document.createElement('div');
            summary.className = 'alert alert-info mt-3';
            summary.innerHTML = `
                <h6><i class="fas fa-chart-bar me-2"></i>Tổng kết:</h6>
                <p class="mb-0">
                    <span class="text-success"><i class="fas fa-check me-1"></i>Thành công: ${successCount}</span> |
                    <span class="text-danger"><i class="fas fa-times me-1"></i>Thất bại: ${failureCount}</span>
                </p>
            `;
            resultsContent.appendChild(summary);

            // Hide content and show results
            document.getElementById('remediationContent').style.display = 'none';
            document.getElementById('remediationResults').style.display = 'block';
            document.getElementById('executeRemediationBtn').style.display = 'none';
            document.getElementById('refreshAfterRemediationBtn').style.display = 'inline-block';

            // Show success message
            if (successCount > 0) {
                Swal.fire({
                    title: 'Hoàn thành!',
                    html: `Đã khắc phục thành công <strong>${successCount}</strong> lỗ hỏng bảo mật.`,
                    icon: 'success',
                    confirmButtonText: 'OK'
                });
            }
        }

        /**
         * Refresh scan after remediation
         */
        function refreshAfterRemediation() {
            const modal = bootstrap.Modal.getInstance(document.getElementById('remediationModal'));
            modal.hide();

            // Scan the client again
            scanClient(currentRemediationClientId);
        }
    </script>
</body>

</html>