<?php
/**
 * Security Scanner Server - Central Dashboard
 * Đặt file này trên website trung tâm để quản lý tất cả clients
 * Author: Hiệp Nguyễn
 * Version: 1.0 Server Dashboard
 */

// ==================== CẤU HÌNH SERVER ====================
class SecurityServerConfig {
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
    const SCAN_TIMEOUT = 300; // 5 phút
}

// ==================== CLIENT MANAGER ====================
class ClientManager {
    private $clientsFile = './data/clients.json';
    
    public function __construct() {
        if (!file_exists('./data')) {
            mkdir('./data', 0755, true);
        }
        
        if (!file_exists($this->clientsFile)) {
            $this->saveClients([]);
        }
    }
    
    public function getClients() {
        if (!file_exists($this->clientsFile)) {
            return [];
        }
        
        $content = file_get_contents($this->clientsFile);
        return json_decode($content, true) ?: [];
    }
    
    public function saveClients($clients) {
        file_put_contents($this->clientsFile, json_encode($clients, JSON_PRETTY_PRINT));
    }
    
    public function addClient($name, $url, $apiKey) {
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
    
    public function updateClient($id, $data) {
        $clients = $this->getClients();
        
        foreach ($clients as &$client) {
            if ($client['id'] === $id) {
                $client = array_merge($client, $data);
                break;
            }
        }
        
        $this->saveClients($clients);
    }
    
    public function deleteClient($id) {
        $clients = $this->getClients();
        $clients = array_filter($clients, function($client) use ($id) {
            return $client['id'] !== $id;
        });
        
        $this->saveClients(array_values($clients));
    }
    
    public function getClient($id) {
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
class ScannerManager {
    private $clientManager;
    
    public function __construct() {
        $this->clientManager = new ClientManager();
    }
    
    public function checkClientHealth($client) {
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
    
    public function scanClient($client) {
        // Xử lý URL - nếu chưa có security_scan_client.php thì thêm vào
        $url = rtrim($client['url'], '/');
        if (strpos($url, 'security_scan_client.php') === false) {
            $url .= '/security_scan_client.php';
        }
        $url .= '?endpoint=scan&api_key=' . urlencode($client['api_key']);
        
        $response = $this->makeApiRequest($url, 'POST', [], null);
        
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
    
    public function scanAllClients() {
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
    
    public function getClientStatus($client) {
        // Xử lý URL - nếu chưa có security_scan_client.php thì thêm vào
        $url = rtrim($client['url'], '/');
        if (strpos($url, 'security_scan_client.php') === false) {
            $url .= '/security_scan_client.php';
        }
        $url .= '?endpoint=status&api_key=' . urlencode($client['api_key']);
        
        $response = $this->makeApiRequest($url, 'GET', [], null);
        
        if ($response['success']) {
            return $response['data'];
        }
        
        return null;
    }
    
    private function makeApiRequest($url, $method = 'GET', $data = [], $apiKey = null) {
        $ch = curl_init();
        
        // Cấu hình cURL
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, SecurityServerConfig::SCAN_TIMEOUT);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Hiep Security Server/1.0');
        
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
        
        file_put_contents('./logs/api_requests.log', json_encode($logData) . "\n", FILE_APPEND);
        
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
        
        file_put_contents('./logs/api_responses.log', json_encode($responseLog) . "\n", FILE_APPEND);
        
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
    
    public function deleteFileOnClient($client, $filePath) {
        // Xử lý URL - nếu chưa có security_scan_client.php thì thêm vào
        $url = rtrim($client['url'], '/');
        if (strpos($url, 'security_scan_client.php') === false) {
            $url .= '/security_scan_client.php';
        }
        $url .= '?endpoint=delete_file&api_key=' . urlencode($client['api_key']);
        
        $response = $this->makeApiRequest($url, 'POST', [
            'file_path' => $filePath
        ], null);
        
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

    public function quarantineFileOnClient($client, $filePath) {
        // Xử lý URL - nếu chưa có security_scan_client.php thì thêm vào
        $url = rtrim($client['url'], '/');
        if (strpos($url, 'security_scan_client.php') === false) {
            $url .= '/security_scan_client.php';
        }
        $url .= '?endpoint=quarantine_file&api_key=' . urlencode($client['api_key']);

        $response = $this->makeApiRequest($url, 'POST', [
            'file_path' => $filePath
        ], null);

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

    public function getScanHistory($client, $limit = 10) {
        // Xử lý URL - nếu chưa có security_scan_client.php thì thêm vào
        $url = rtrim($client['url'], '/');
        if (strpos($url, 'security_scan_client.php') === false) {
            $url .= '/security_scan_client.php';
        }
        $url .= '?endpoint=scan_history&api_key=' . urlencode($client['api_key']) . '&limit=' . $limit;

        $response = $this->makeApiRequest($url, 'GET', [], null);

        if ($response['success']) {
            return $response['data'];
        }

        return [];
    }
}

// ==================== EMAIL MANAGER ====================
class EmailManager {
    public function sendDailyReport($scanResults) {
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
    
    private function generateReportEmail($total, $critical, $warning, $clean, $offline, $criticalDetails, $warningDetails) {
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
</body>
</html>';
        
        return $html;
    }
    
    private function sendEmail($subject, $htmlBody) {
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
            
            $result = $scannerManager->scanClient($client);
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
                    if (isset($status['last_scan']['status']) &&
                        in_array($status['last_scan']['status'], ['critical', 'infected'])) {
                        $stats['infected_clients']++;
                    }
                }
            }

            echo json_encode(['success' => true, 'data' => $stats]);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Unknown action']);
    }
    
    exit;
}

// ==================== WEB INTERFACE ====================
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SecurityServerConfig::SERVER_NAME; ?> - Central Security Dashboard</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #4A90E2;
            --secondary-color: #667eea;
            --success-color: #2ed573;
            --warning-color: #ffa502;
            --danger-color: #ff4757;
            --info-color: #3742fa;
            --light-bg: #f8f9fa;
            --dark-bg: #2c3e50;
            --border-color: #e9ecef;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            margin: 0;
        }
        
        .main-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            margin: 20px auto;
            max-width: 1200px;
            min-height: calc(100vh - 40px);
        }
        
        .header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 30px;
            border-radius: 16px 16px 0 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
            background-size: 30px 30px;
            animation: backgroundMove 20s linear infinite;
        }
        
        @keyframes backgroundMove {
            0% { transform: translate(0, 0); }
            100% { transform: translate(30px, 30px); }
        }
        
        .header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0;
            position: relative;
            z-index: 2;
        }
        
        .header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
            position: relative;
            z-index: 2;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            padding: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid var(--border-color);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            font-family: 'JetBrains Mono', monospace;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .online { color: var(--success-color); }
        .offline { color: var(--danger-color); }
        .warning { color: var(--warning-color); }
        .scanning { color: var(--info-color); }
        
        .control-panel {
            padding: 0 30px 30px;
        }
        
        .btn-custom {
            border-radius: 8px;
            padding: 12px 24px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary-custom {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }
        
        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(74, 144, 226, 0.4);
            color: white;
        }
        
        .btn-success-custom {
            background: linear-gradient(135deg, var(--success-color), #26d65f);
            color: white;
        }
        
        .btn-success-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(46, 213, 115, 0.4);
            color: white;
        }
        
        .btn-warning-custom {
            background: linear-gradient(135deg, var(--warning-color), #ff9f43);
            color: white;
        }
        
        .btn-warning-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 165, 2, 0.4);
            color: white;
        }
        
        .clients-section {
            padding: 0 30px 30px;
        }
        
        .clients-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .table {
            margin: 0;
        }
        
        .table th {
            background: var(--light-bg);
            border: none;
            padding: 15px;
            font-weight: 600;
            color: var(--dark-bg);
        }
        
        .table td {
            padding: 15px;
            border: none;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-online {
            background: rgba(46, 213, 115, 0.1);
            color: var(--success-color);
            border: 1px solid var(--success-color);
        }
        
        .status-offline {
            background: rgba(255, 71, 87, 0.1);
            color: var(--danger-color);
            border: 1px solid var(--danger-color);
        }
        
        .status-unknown {
            background: rgba(108, 117, 125, 0.1);
            color: #6c757d;
            border: 1px solid #6c757d;
        }
        
        .btn-sm-custom {
            padding: 6px 12px;
            font-size: 0.8rem;
            border-radius: 6px;
        }
        
        .loading {
            display: none;
            text-align: center;
            padding: 20px;
            color: var(--info-color);
        }
        
        .loading.active {
            display: block;
        }
        
        .results-section {
            padding: 0 30px 30px;
            display: none;
        }
        
        .results-section.active {
            display: block;
        }
        
        .result-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border-left: 4px solid var(--primary-color);
        }
        
        .result-card.critical {
            border-left-color: var(--danger-color);
        }
        
        .result-card.warning {
            border-left-color: var(--warning-color);
        }
        
        .result-card.clean {
            border-left-color: var(--success-color);
        }
        
        .modal-content {
            border-radius: 12px;
            border: none;
        }
        
        .modal-header {
            background: var(--light-bg);
            border-radius: 12px 12px 0 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .form-control {
            border-radius: 8px;
            border: 1px solid var(--border-color);
            padding: 12px;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(74, 144, 226, 0.25);
        }
        
        @media (max-width: 768px) {
            .main-container {
                margin: 10px;
                border-radius: 12px;
                min-height: calc(100vh - 20px);
            }
            
            .header {
                padding: 20px;
                border-radius: 12px 12px 0 0;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
                padding: 20px;
                gap: 15px;
            }
            
            .control-panel,
            .clients-section {
                padding: 0 20px 20px;
            }
            
            .btn-custom {
                padding: 10px 16px;
                font-size: 0.9rem;
                margin-bottom: 10px;
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-shield-alt"></i> <?php echo SecurityServerConfig::SERVER_NAME; ?></h1>
            <p>Dashboard điều khiển trung tâm - Quản lý bảo mật toàn hệ thống</p>
        </div>
        
        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon online">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-number online" id="onlineCount">0</div>
                <div class="stat-label">Clients Online</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon offline">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-number offline" id="offlineCount">0</div>
                <div class="stat-label">Clients Offline</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-number warning" id="threatsCount">0</div>
                <div class="stat-label">Active Threats</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon critical">
                    <i class="fas fa-virus"></i>
                </div>
                <div class="stat-number critical" id="infectedCount">0</div>
                <div class="stat-label">Infected Sites</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon scanning">
                    <i class="fas fa-sync-alt"></i>
                </div>
                <div class="stat-number scanning" id="lastScanTime">Never</div>
                <div class="stat-label">Last Scan</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon info">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-number info" id="totalScans">0</div>
                <div class="stat-label">Total Scans</div>
            </div>
        </div>
        
        <!-- Control Panel -->
        <div class="control-panel">
            <div class="d-flex gap-3 flex-wrap">
                <button class="btn-custom btn-primary-custom" onclick="addClient()">
                    <i class="fas fa-plus"></i> Thêm Client
                </button>
                <button class="btn-custom btn-success-custom" onclick="scanAllClients()">
                    <i class="fas fa-search"></i> Quét Tất Cả
                </button>
                <button class="btn-custom btn-warning-custom" onclick="sendDailyReport()">
                    <i class="fas fa-envelope"></i> Gửi Báo Cáo
                </button>
                <button class="btn-custom btn-primary-custom" onclick="refreshClients()">
                    <i class="fas fa-sync-alt"></i> Làm Mới
                </button>
            </div>
        </div>
        
        <!-- Loading -->
        <div class="loading" id="loadingIndicator">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p>Đang xử lý...</p>
        </div>
        
        <!-- Clients Table -->
        <div class="clients-section">
            <h3><i class="fas fa-server"></i> Danh Sách Clients</h3>
            <div class="clients-table">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Tên Client</th>
                            <th>URL</th>
                            <th>Trạng Thái</th>
                            <th>Lần Quét Cuối</th>
                            <th>Thao Tác</th>
                        </tr>
                    </thead>
                    <tbody id="clientsTableBody">
                        <!-- Clients will be loaded here -->
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Results Section -->
        <div class="results-section" id="resultsSection">
            <h3><i class="fas fa-clipboard-list"></i> Kết Quả Quét</h3>
            <div id="resultsContainer">
                <!-- Results will be displayed here -->
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
                            <input type="text" class="form-control" id="clientApiKey" value="<?php echo SecurityServerConfig::DEFAULT_API_KEY; ?>" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-primary" onclick="saveClient()">Lưu Client</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Client Details Modal -->
    <div class="modal fade" id="clientDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Chi Tiết Client & Kết Quả Quét</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="clientDetailsContent">
                        <!-- Content will be loaded here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="button" class="btn btn-primary" onclick="refreshClientDetails()">Làm Mới</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        // Global variables
        let clients = [];
        let lastScanResults = [];
        
        // Load dashboard stats
        function loadDashboardStats() {
            fetch('?api=get_dashboard_stats')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const stats = data.data;
                        document.getElementById('onlineCount').textContent = stats.online_clients;
                        document.getElementById('offlineCount').textContent = stats.total_clients - stats.online_clients;
                        document.getElementById('infectedCount').textContent = stats.infected_clients;
                        document.getElementById('totalScans').textContent = stats.total_clients;

                        // Update last scan time
                        const lastScanElement = document.getElementById('lastScanTime');
                        const now = new Date();
                        lastScanElement.textContent = now.toLocaleTimeString('vi-VN');
                    }
                })
                .catch(error => {
                    console.error('Error loading dashboard stats:', error);
                });
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            loadClients();
            updateStats();
            loadDashboardStats();

            // Auto refresh every 30 seconds
            setInterval(function() {
                loadClients();
                updateStats();
                loadDashboardStats();
            }, 30000);
        });
        
        // Load clients from server
        function loadClients() {
            fetch('?api=get_clients')
                .then(response => response.json())
                .then(data => {
                    clients = data;
                    updateClientsTable();
                    updateStats();
                })
                .catch(error => {
                    console.error('Error loading clients:', error);
                });
        }
        
        // Update clients table
        function updateClientsTable() {
            const tbody = document.getElementById('clientsTableBody');
            tbody.innerHTML = '';
            
            if (clients.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center text-muted">
                            <i class="fas fa-server fa-2x mb-2"></i><br>
                            Chưa có client nào. Hãy thêm client đầu tiên!
                        </td>
                    </tr>
                `;
                return;
            }
            
            clients.forEach(client => {
                const row = document.createElement('tr');
                
                let statusBadge = '';
                switch(client.status) {
                    case 'online':
                        statusBadge = '<span class="status-badge status-online">Online</span>';
                        break;
                    case 'offline':
                        statusBadge = '<span class="status-badge status-offline">Offline</span>';
                        break;
                    default:
                        statusBadge = '<span class="status-badge status-unknown">Unknown</span>';
                }
                
                row.innerHTML = `
                    <td>
                        <strong>${client.name}</strong><br>
                        <small class="text-muted">ID: ${client.id}</small>
                    </td>
                    <td>
                        <a href="${client.url}" target="_blank" class="text-decoration-none">
                            ${client.url}
                        </a>
                    </td>
                    <td>${statusBadge}</td>
                    <td>
                        ${client.last_scan ? new Date(client.last_scan).toLocaleString('vi-VN') : 'Chưa quét'}
                    </td>
                    <td>
                        <div class="btn-group" role="group">
                            <button class="btn btn-sm btn-outline-info btn-sm-custom" onclick="showClientDetails('${client.id}')" title="Chi Tiết">
                                <i class="fas fa-info-circle"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-primary btn-sm-custom" onclick="checkClient('${client.id}')" title="Kiểm Tra">
                                <i class="fas fa-heartbeat"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-success btn-sm-custom" onclick="scanClient('${client.id}')" title="Quét Ngay">
                                <i class="fas fa-search"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger btn-sm-custom" onclick="deleteClient('${client.id}')" title="Xóa">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                `;
                
                tbody.appendChild(row);
            });
        }
        
        // Update stats
        function updateStats() {
            let onlineCount = 0;
            let offlineCount = 0;
            let threatsCount = 0;
            
            clients.forEach(client => {
                if (client.status === 'online') {
                    onlineCount++;
                } else if (client.status === 'offline') {
                    offlineCount++;
                }
            });
            
            document.getElementById('onlineCount').textContent = onlineCount;
            document.getElementById('offlineCount').textContent = offlineCount;
            document.getElementById('threatsCount').textContent = threatsCount;
            
            // Update last scan time
            const lastScanElement = document.getElementById('lastScanTime');
            if (lastScanResults.length > 0) {
                const lastScan = new Date();
                lastScanElement.textContent = lastScan.toLocaleTimeString('vi-VN');
            }
        }
        
        // Add client modal
        function addClient() {
            const modal = new bootstrap.Modal(document.getElementById('addClientModal'));
            modal.show();
        }
        
        // Save client
        function saveClient() {
            const name = document.getElementById('clientName').value;
            const url = document.getElementById('clientUrl').value;
            const apiKey = document.getElementById('clientApiKey').value;
            
            if (!name || !url || !apiKey) {
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi',
                    text: 'Vui lòng điền đầy đủ thông tin!'
                });
                return;
            }
            
            const formData = new FormData();
            formData.append('name', name);
            formData.append('url', url);
            formData.append('api_key', apiKey);
            
            fetch('?api=add_client', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Thành công',
                        text: 'Client đã được thêm thành công!'
                    });
                    
                    // Close modal and refresh
                    bootstrap.Modal.getInstance(document.getElementById('addClientModal')).hide();
                    document.getElementById('addClientForm').reset();
                    loadClients();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi',
                        text: data.error || 'Có lỗi xảy ra!'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi',
                    text: 'Không thể kết nối đến server!'
                });
            });
        }
        
        // Delete client
        function deleteClient(id) {
            const client = clients.find(c => c.id === id);
            if (!client) return;
            
            Swal.fire({
                title: 'Xác nhận xóa',
                text: `Bạn có chắc muốn xóa client "${client.name}"?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Xóa',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('id', id);
                    
                    fetch('?api=delete_client', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Đã xóa',
                                text: 'Client đã được xóa thành công!'
                            });
                            loadClients();
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Lỗi',
                                text: data.error || 'Có lỗi xảy ra!'
                            });
                        }
                    });
                }
            });
        }
        
        // Check client health
        function checkClient(id) {
            const client = clients.find(c => c.id === id);
            if (!client) return;
            
            showLoading();
            
            fetch(`?api=check_client&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    hideLoading();
                    
                    if (data.success) {
                        const status = data.online ? 'online' : 'offline';
                        const icon = data.online ? 'success' : 'error';
                        const text = data.online ? 'Client đang online và hoạt động bình thường!' : 'Client không phản hồi hoặc đang offline.';
                        
                        Swal.fire({
                            icon: icon,
                            title: `Client ${client.name}`,
                            text: text
                        });
                        
                        loadClients(); // Refresh to update status
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Lỗi',
                            text: data.error || 'Không thể kiểm tra client!'
                        });
                    }
                })
                .catch(error => {
                    hideLoading();
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi',
                        text: 'Không thể kết nối đến server!'
                    });
                });
        }
        
        // Scan single client
        function scanClient(id) {
            const client = clients.find(c => c.id === id);
            if (!client) return;
            
            Swal.fire({
                title: `Quét ${client.name}`,
                text: 'Đang thực hiện quét bảo mật...',
                icon: 'info',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            fetch(`?api=scan_client&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    Swal.close();
                    
                    if (data.success || data.scan_results) {
                        // Hiển thị kết quả scan chi tiết
                        displayScanResults(client, data);
                        loadClients(); // Refresh to update last scan time
                        
                        // Scroll to results section
                        document.getElementById('resultsSection').scrollIntoView({ 
                            behavior: 'smooth' 
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Lỗi quét',
                            text: data.error || 'Không thể quét client!'
                        });
                    }
                })
                .catch(error => {
                    Swal.close();
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi',
                        text: 'Không thể kết nối đến server!'
                    });
                });
        }
        
        // Display scan results
        function displayScanResults(client, data) {
            const resultsContainer = document.getElementById('resultsContainer');
            const results = data.scan_results || data;

            console.log('Display scan results:', { client, data, results });

            // Show results section
            document.getElementById('resultsSection').classList.add('active');

            if (!results || results.suspicious_count === 0) {
                resultsContainer.innerHTML = `
                    <div class="alert alert-success">
                        <i class="fas fa-shield-check"></i>
                        <strong>✅ Hệ thống ${client.name} an toàn!</strong><br>
                        Không phát hiện threat nào trong ${results.scanned_files || 0} files đã quét.
                        <div class="mt-2">
                            <small class="text-muted">
                                Thời gian quét: ${results.scan_time || 0}s |
                                Memory: ${formatBytes(results.memory_used || 0)} |
                                Risk Score: ${results.risk_score || 0}/100
                            </small>
                        </div>
                    </div>
                `;
                return;
            }
            
            // Create summary card
            let summaryHtml = `
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5><i class="fas fa-chart-bar"></i> Kết Quả Quét - ${client.name}</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="text-center">
                                    <h4 class="text-primary">${results.scanned_files || 0}</h4>
                                    <small>Files Đã Quét</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <h4 class="text-danger">${results.critical_count || 0}</h4>
                                    <small>Critical Threats</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <h4 class="text-warning">${results.webshell_count || 0}</h4>
                                    <small>Webshells</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <h4 class="text-info">${results.suspicious_count || 0}</h4>
                                    <small>Total Threats</small>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-4">
                                <small class="text-muted">Thời gian quét: ${results.scan_time || 0}s</small>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted">Memory: ${formatBytes(results.memory_used || 0)}</small>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted">Risk Score: ${results.risk_score || 0}/100</small>
                            </div>
                        </div>
                        ${results.recommendations ? `
                            <div class="mt-3">
                                <h6>📋 Khuyến nghị:</h6>
                                <ul class="mb-0">
                                    ${results.recommendations.map(rec => `<li>${rec}</li>`).join('')}
                                </ul>
                            </div>
                        ` : ''}
                    </div>
                </div>
            `;

            // Process threats by category
            let threatsHtml = '';

            if (results.threats) {
                // Critical threats
                if (results.threats.critical && results.threats.critical.length > 0) {
                    threatsHtml += generateThreatSection('🚨 Critical Threats', results.threats.critical, 'danger', client);
                }

                // Webshells
                if (results.threats.webshells && results.threats.webshells.length > 0) {
                    threatsHtml += generateThreatSection('🕷️ Webshells Detected', results.threats.webshells, 'danger', client);
                }

                // Warnings
                if (results.threats.warnings && results.threats.warnings.length > 0) {
                    threatsHtml += generateThreatSection('⚠️ Warnings', results.threats.warnings, 'warning', client);
                }
            } else if (results.threats && results.threats.all) {
                // Fallback for old format
                threatsHtml += generateThreatSection('🔍 All Threats', results.threats.all, 'warning', client);
            }

            resultsContainer.innerHTML = summaryHtml + threatsHtml;
        }

        // Generate threat section HTML
        function generateThreatSection(title, threats, alertType, client) {
            if (!threats || threats.length === 0) return '';

            let html = `
                <div class="card mb-3">
                    <div class="card-header bg-${alertType} text-white">
                        <h6 class="mb-0">${title} (${threats.length})</h6>
                    </div>
                    <div class="card-body">
            `;

            threats.forEach((threat, index) => {
                html += `
                    <div class="threat-item border-bottom pb-2 mb-2">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <strong>📁 ${threat.path}</strong>
                                <div class="mt-1">
                `;

                if (threat.issues && Array.isArray(threat.issues)) {
                    threat.issues.forEach(issue => {
                        html += `
                            <div class="badge bg-${issue.severity === 'critical' ? 'danger' : 'warning'} me-1 mb-1">
                                ${issue.pattern} - ${issue.description}
                                ${issue.line ? ` (Line: ${issue.line})` : ''}
                            </div>
                        `;
                    });
                }

                html += `
                                </div>
                                ${threat.file_size ? `<small class="text-muted">Size: ${formatBytes(threat.file_size)}</small>` : ''}
                                ${threat.modified_time ? `<small class="text-muted"> | Modified: ${new Date(threat.modified_time * 1000).toLocaleString()}</small>` : ''}
                            </div>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-warning" onclick="quarantineFile('${client.id}', '${threat.path}')">
                                    <i class="fas fa-shield-alt"></i> Cách ly
                                </button>
                                <button class="btn btn-outline-danger" onclick="deleteFile('${client.id}', '${threat.path}')">
                                    <i class="fas fa-trash"></i> Xóa
                                </button>
                                <button class="btn btn-outline-info" onclick="viewFileContent('${client.id}', '${threat.path}')">
                                    <i class="fas fa-eye"></i> Xem
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            });

            html += `
                    </div>
                </div>
            `;

            return html;
        }

        // Format bytes to human readable
        function formatBytes(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        // Quarantine file function
        function quarantineFile(clientId, filePath) {
            Swal.fire({
                title: 'Cách ly file nguy hiểm',
                html: `
                    <div class="text-start">
                        <p><strong>File:</strong> <code>${filePath}</code></p>
                        <p class="text-info">
                            <i class="fas fa-shield-alt"></i>
                            File sẽ được di chuyển vào thư mục quarantine để cách ly an toàn.
                        </p>
                    </div>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#ffc107',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fas fa-shield-alt"></i> Cách ly',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('client_id', clientId);
                    formData.append('file_path', filePath);

                    fetch('?api=quarantine_file', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Cách ly thành công',
                                text: 'File đã được cách ly an toàn!'
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Lỗi cách ly',
                                text: data.error || 'Không thể cách ly file!'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Lỗi',
                            text: 'Không thể kết nối đến server!'
                        });
                    });
                }
            });
        }

        // Delete file function
        function deleteFile(clientId, filePath) {
            Swal.fire({
                title: 'Xác nhận xóa file',
                html: `
                    <div class="text-start">
                        <p><strong>File:</strong> <code>${filePath}</code></p>
                        <p class="text-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                            Thao tác này sẽ xóa file vĩnh viễn và không thể hoàn tác!
                        </p>
                    </div> 
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fas fa-trash"></i> Xóa vĩnh viễn',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('client_id', clientId);
                    formData.append('file_path', filePath);

                    fetch('?api=delete_file', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Xóa thành công',
                                text: 'File đã được xóa!'
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Lỗi xóa file',
                                text: data.error || 'Không thể xóa file!'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Lỗi',
                            text: 'Không thể kết nối đến server!'
                        });
                    });
                }
            });
        }

        // View file content function
        function viewFileContent(clientId, filePath) {
            Swal.fire({
                title: 'Đang tải nội dung file...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const client = clients.find(c => c.id === clientId);
            if (!client) {
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi',
                    text: 'Không tìm thấy client!'
                });
                return;
            }

            const url = client.url + '/security_scan_client.php?endpoint=get_file_content&api_key=' +
                       encodeURIComponent(client.api_key) + '&file_path=' + encodeURIComponent(filePath) + '&lines=100';

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    Swal.close();

                    if (data.success) {
                        const fileData = data.data;
                        Swal.fire({
                            title: `📄 ${filePath}`,
                            html: `
                                <div class="text-start">
                                    <div class="mb-3">
                                        <small class="text-muted">
                                            Size: ${formatBytes(fileData.file_size)} |
                                            Lines: ${fileData.total_lines} |
                                            Modified: ${fileData.last_modified}
                                            ${fileData.truncated ? ' | <span class="text-warning">Truncated</span>' : ''}
                                        </small>
                                    </div>
                                    <pre class="bg-light p-3 rounded" style="max-height: 400px; overflow-y: auto; font-size: 12px;">${fileData.content}</pre>
                                </div>
                            `,
                            width: '80%',
                            showCloseButton: true,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Lỗi đọc file',
                            text: data.error || 'Không thể đọc nội dung file!'
                        });
                    }
                })
                .catch(error => {
                    Swal.close();
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi',
                        text: 'Không thể kết nối đến client!'
                    });
                });
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
        
        function deleteFileFromResults(clientId, filePath, fileIndex) {
            Swal.fire({
                title: 'Xác nhận xóa file',
                html: `
                    <div class="text-start">
                        <p><strong>File:</strong> <code>${filePath}</code></p>
                        <p class="text-warning">
                            <i class="fas fa-exclamation-triangle"></i> 
                            Thao tác này sẽ xóa file khỏi server và không thể hoàn tác!
                        </p>
                    </div>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fas fa-trash"></i> Xóa File',
                cancelButtonText: 'Hủy',
                width: 600
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading
                    Swal.fire({
                        title: 'Đang xóa file...',
                        text: 'Vui lòng chờ',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    // Call API to delete file
                    fetch(`?api=delete_file`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `client_id=${clientId}&file_path=${encodeURIComponent(filePath)}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Remove file from display
                            const fileElement = document.querySelector(`[data-file-index="${fileIndex}"]`);
                            if (fileElement) {
                                fileElement.remove();
                            }
                            
                            // Update scan data
                            if (window.currentScanData && window.currentScanData.files) {
                                window.currentScanData.files.splice(fileIndex, 1);
                            }
                            
                            Swal.fire({
                                icon: 'success',
                                title: 'Đã xóa!',
                                text: 'File đã được xóa thành công.',
                                timer: 2000,
                                showConfirmButton: false
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Lỗi!',
                                text: data.error || 'Không thể xóa file.',
                                width: 600
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error deleting file:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Lỗi!',
                            text: 'Không thể kết nối đến server.',
                            width: 600
                        });
                    });
                }
            });
        }
        
        function deleteAllGroupFiles(groupKey, clientId) {
            if (!window.currentScanData || !window.currentScanData.files) return;
            
            const files = window.currentScanData.files.filter(file => {
                const isCritical = file.severity === 'critical';
                const isFilemanager = file.category === 'filemanager';
                const isSuspiciousFile = file.category === 'suspicious_file';
                
                if (groupKey === 'critical') return isCritical && !isFilemanager;
                if (groupKey === 'suspicious_file') return isSuspiciousFile;
                if (groupKey === 'filemanager') return isFilemanager;
                return !isCritical && !isFilemanager && !isSuspiciousFile;
            }).filter(file => canDeleteFile(file));
            
            if (files.length === 0) {
                Swal.fire({
                    icon: 'info',
                    title: 'Không có file nào để xóa',
                    text: 'Không có file nào trong nhóm này có thể xóa được.'
                });
                return;
            }
            
            Swal.fire({
                title: 'Xác nhận xóa tất cả files',
                html: `
                    <div class="text-start">
                        <p><strong>Sẽ xóa ${files.length} files:</strong></p>
                        <ul class="list-unstyled small" style="max-height: 200px; overflow-y: auto;">
                            ${files.map(file => `<li>• <code>${file.path}</code></li>`).join('')}
                        </ul>
                        <p class="text-warning">
                            <i class="fas fa-exclamation-triangle"></i> 
                            Thao tác này sẽ xóa tất cả files khỏi server và không thể hoàn tác!
                        </p>
                    </div>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: `<i class="fas fa-trash"></i> Xóa ${files.length} Files`,
                cancelButtonText: 'Hủy',
                width: 700
            }).then((result) => {
                if (result.isConfirmed) {
                    // Delete files one by one
                    deleteFilesSequentially(clientId, files, 0);
                }
            });
        }
        
        function deleteFilesSequentially(clientId, files, index) {
            if (index >= files.length) {
                Swal.fire({
                    icon: 'success',
                    title: 'Hoàn thành!',
                    text: `Đã xóa ${files.length} files thành công.`,
                    timer: 3000,
                    showConfirmButton: false
                });
                return;
            }
            
            const file = files[index];
            
            // Show progress
            Swal.fire({
                title: 'Đang xóa files...',
                text: `Tiến trình: ${index + 1}/${files.length} - ${file.path}`,
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            fetch(`?api=delete_file`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `client_id=${clientId}&file_path=${encodeURIComponent(file.path)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove file from display
                    const fileElement = document.querySelector(`[data-file-index="${file.index}"]`);
                    if (fileElement) {
                        fileElement.remove();
                    }
                }
                
                // Continue to next file
                deleteFilesSequentially(clientId, files, index + 1);
            })
            .catch(error => {
                console.error('Error deleting file:', error);
                // Continue to next file even if one fails
                deleteFilesSequentially(clientId, files, index + 1);
            });
        }
        
        function viewFileDetails(clientId, filePath) {
            // TODO: Implement file details view
            Swal.fire({
                icon: 'info',
                title: 'Chi tiết file',
                text: `Tính năng xem chi tiết file sẽ được bổ sung sau.\n\nFile: ${filePath}`,
                width: 600
            });
        }
        
        // Scan all clients
        function scanAllClients() {
            if (clients.length === 0) {
                Swal.fire({
                    icon: 'info',
                    title: 'Không có client',
                    text: 'Hãy thêm client trước khi quét!'
                });
                return;
            }
            
            showLoading();
            
            fetch('?api=scan_all')
                .then(response => response.json())
                .then(data => {
                    hideLoading();
                    
                    if (data.success) {
                        lastScanResults = data.results;
                        displayBulkScanResults(data.results);
                        loadClients(); // Refresh clients
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Quét hoàn tất',
                            text: `Đã quét ${data.results.length} clients thành công!`
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Lỗi quét',
                            text: 'Có lỗi xảy ra khi quét clients!'
                        });
                    }
                })
                .catch(error => {
                    hideLoading();
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi',
                        text: 'Không thể kết nối đến server!'
                    });
                });
        }
        
        // Display bulk scan results
        function displayBulkScanResults(results) {
            const resultsSection = document.getElementById('resultsSection');
            const resultsContainer = document.getElementById('resultsContainer');

            resultsContainer.innerHTML = '';

            if (Array.isArray(results)) {
                results.forEach(result => {
                const client = result.client;
                const scanResult = result.scan_result;
                
                let cardClass = 'clean';
                let statusIcon = 'fas fa-check-circle';
                let statusText = 'An toàn';
                
                if (scanResult.success) {
                    const status = scanResult.scan_results.status;
                    if (status === 'critical') {
                        cardClass = 'critical';
                        statusIcon = 'fas fa-exclamation-circle';
                        statusText = 'Nghiêm trọng';
                    } else if (status === 'warning') {
                        cardClass = 'warning';
                        statusIcon = 'fas fa-exclamation-triangle';
                        statusText = 'Cảnh báo';
                    }
                }
                
                const card = document.createElement('div');
                card.className = `result-card ${cardClass}`;
                card.innerHTML = `
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h5 class="mb-1">${client.name}</h5>
                            <small class="text-muted">${client.url}</small>
                        </div>
                        <div class="text-end">
                            <i class="${statusIcon} text-${cardClass} fs-4"></i>
                            <div class="mt-1">
                                <small class="text-${cardClass} fw-bold">${statusText}</small>
                            </div>
                        </div>
                    </div>
                    
                    ${scanResult.success ? `
                        <div class="row">
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div class="h4 mb-0">${scanResult.scan_results.scanned_files}</div>
                                    <small class="text-muted">Files</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div class="h4 mb-0">${scanResult.scan_results.suspicious_count}</div>
                                    <small class="text-muted">Threats</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div class="h4 mb-0">${scanResult.scan_results.critical_count}</div>
                                    <small class="text-muted">Critical</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div class="h4 mb-0">${scanResult.scan_results.scan_time}s</div>
                                    <small class="text-muted">Time</small>
                                </div>
                            </div>
                        </div>
                    ` : `
                        <div class="text-danger">
                            <i class="fas fa-times-circle"></i>
                            Lỗi: ${scanResult.error || 'Không thể quét client'}
                        </div>
                    `}
                `;
                
                resultsContainer.appendChild(card);
            });
            
            resultsSection.classList.add('active');
        }
        
        // Send daily report
        function sendDailyReport() {
            showLoading();
            
            fetch('?api=send_report')
                .then(response => response.json())
                .then(data => {
                    hideLoading();
                    
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Gửi báo cáo thành công',
                            text: 'Báo cáo hàng ngày đã được gửi qua email!'
                        });
                        
                        // Also display results
                        if (data.results) {
                            displayBulkScanResults(data.results);
                        }
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Lỗi gửi báo cáo',
                            text: 'Không thể gửi báo cáo qua email!'
                        });
                    }
                })
                .catch(error => {
                    hideLoading();
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi',
                        text: 'Không thể kết nối đến server!'
                    });
                });
        }
        
        // Refresh clients
        function refreshClients() {
            showLoading();
            loadClients();
            setTimeout(hideLoading, 1000);
        }
        
        // Show/hide loading
        function showLoading() {
            document.getElementById('loadingIndicator').classList.add('active');
        }
        
        function hideLoading() {
            document.getElementById('loadingIndicator').classList.remove('active');
        }
        
        // Show client details
        let currentClientId = null;
        
        function showClientDetails(clientId) {
            currentClientId = clientId;
            const client = clients.find(c => c.id === clientId);
            
            if (!client) {
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi',
                    text: 'Không tìm thấy client!'
                });
                return;
            }
            
            const modal = new bootstrap.Modal(document.getElementById('clientDetailsModal'));
            modal.show();
            
            // Load client details
            loadClientDetails(client);
        }
        
        function loadClientDetails(client) {
            const content = document.getElementById('clientDetailsContent');
            
            // Show loading
            content.innerHTML = `
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Đang tải...</span>
                    </div>
                    <p class="mt-2">Đang tải chi tiết client...</p>
                </div>
            `;
            
            // Get client status and scan results
            Promise.all([
                fetch(`?api=get_client_status&id=${client.id}`),
                fetch(`?api=get_client_scan_results&id=${client.id}`)
            ])
            .then(responses => Promise.all(responses.map(r => r.json())))
            .then(([statusResult, scanResult]) => {
                renderClientDetails(client, statusResult, scanResult);
            })
            .catch(error => {
                console.error('Error loading client details:', error);
                content.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        Có lỗi khi tải chi tiết client. Vui lòng thử lại.
                    </div>
                `;
            });
        }
        
        function renderClientDetails(client, statusResult, scanResult) {
            const content = document.getElementById('clientDetailsContent');
            
            let statusBadge = '';
            switch(client.status) {
                case 'online':
                    statusBadge = '<span class="badge bg-success">Online</span>';
                    break;
                case 'offline':
                    statusBadge = '<span class="badge bg-danger">Offline</span>';
                    break;
                default:
                    statusBadge = '<span class="badge bg-secondary">Unknown</span>';
            }
            
            let html = `
                <div class="row">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h6><i class="fas fa-info-circle"></i> Thông Tin Client</h6>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>Tên:</strong></td>
                                        <td>${client.name}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>URL:</strong></td>
                                        <td><a href="${client.url}" target="_blank">${client.url}</a></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Trạng Thái:</strong></td>
                                        <td>${statusBadge}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Lần Quét Cuối:</strong></td>
                                        <td>${client.last_scan ? new Date(client.last_scan).toLocaleString('vi-VN') : 'Chưa quét'}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Kiểm Tra Cuối:</strong></td>
                                        <td>${client.last_check ? new Date(client.last_check).toLocaleString('vi-VN') : 'Chưa kiểm tra'}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Tạo Lúc:</strong></td>
                                        <td>${new Date(client.created_at).toLocaleString('vi-VN')}</td>
                                    </tr>
                                </table>
                                <div class="mt-3">
                                    <button class="btn btn-sm btn-primary" onclick="scanClient('${client.id}')">
                                        <i class="fas fa-search"></i> Quét Ngay
                                    </button>
                                    <button class="btn btn-sm btn-outline-primary" onclick="checkClient('${client.id}')">
                                        <i class="fas fa-heartbeat"></i> Kiểm Tra
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h6><i class="fas fa-shield-alt"></i> Kết Quả Quét Bảo Mật</h6>
                            </div>
                            <div class="card-body">
                                <div id="scanResultsContainer">
                                    ${renderScanResults(scanResult)}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            content.innerHTML = html;
        }
        
        function renderScanResults(scanResult) {
            if (!scanResult || !scanResult.success) {
                return `
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        Chưa có kết quả quét nào. Hãy thực hiện quét để xem kết quả.
                    </div>
                `;
            }
            
            const data = scanResult.data;
            if (!data || !data.suspicious_files || data.suspicious_files.length === 0) {
                return `
                    <div class="alert alert-success">
                        <i class="fas fa-shield-check"></i>
                        <strong>Hệ thống an toàn!</strong><br>
                        Không phát hiện threat nào trong ${data.scanned_files || 0} files đã quét.
                    </div>
                `;
            }
            
            // Group files by severity
            const groups = {
                critical: { title: 'Files Virus/Malware Nguy Hiểm', icon: 'fa-skull-crossbones', files: [], color: 'danger' },
                suspicious_file: { title: 'Files Đáng Ngờ (.php.jpg, Empty)', icon: 'fa-exclamation-circle', files: [], color: 'warning' },
                filemanager: { title: 'Filemanager Functions', icon: 'fa-folder-open', files: [], color: 'info' },
                warning: { title: 'Cảnh Báo Bảo Mật', icon: 'fa-exclamation-triangle', files: [], color: 'warning' }
            };
            
            data.suspicious_files.forEach((file, index) => {
                file.index = index;
                
                const isCritical = file.severity === 'critical';
                const isFilemanager = file.category === 'filemanager';
                const isSuspiciousFile = file.category === 'suspicious_file';
                
                if (isSuspiciousFile) {
                    groups.suspicious_file.files.push(file);
                } else if (isCritical && !isFilemanager) {
                    groups.critical.files.push(file);
                } else if (isFilemanager) {
                    groups.filemanager.files.push(file);
                } else {
                    groups.warning.files.push(file);
                }
            });
            
            let html = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Phát hiện ${data.suspicious_count} threats!</strong><br>
                    <small>Trong đó có ${data.critical_count || 0} threats nghiêm trọng cần xử lý ngay.</small>
                </div>
            `;
            
            // Render groups
            Object.keys(groups).forEach(groupKey => {
                const group = groups[groupKey];
                if (group.files.length > 0) {
                    html += `
                        <div class="threat-group mb-3">
                            <div class="alert alert-${group.color}">
                                <h6><i class="fas ${group.icon}"></i> ${group.title} (${group.files.length})</h6>
                            </div>
                            <div class="threat-files">
                    `;
                    
                    group.files.forEach(file => {
                        const isCritical = (file.severity === 'critical' && file.category !== 'filemanager') || file.category === 'suspicious_file';
                        const firstIssue = file.issues && file.issues.length > 0 ? file.issues[0] : null;
                        
                        html += `
                            <div class="threat-item p-3 mb-2 border rounded">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1">
                                            <i class="fas fa-file-code"></i> ${file.path}
                                            ${firstIssue ? `<small class="text-muted">(dòng ${firstIssue.line})</small>` : ''}
                                        </h6>
                                        <p class="mb-1 text-muted">
                                            ${file.issues.length} vấn đề phát hiện
                                            ${firstIssue ? ` - <span class="text-danger fw-bold">${firstIssue.pattern}</span>` : ''}
                                        </p>
                                    </div>
                                    ${isCritical ? `
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteFile('${file.path}', ${file.index})">
                                            <i class="fas fa-trash-alt"></i> Xóa
                                        </button>
                                    ` : ''}
                                </div>
                            </div>
                        `;
                    });
                    
                    html += `
                            </div>
                        </div>
                    `;
                }
            });
            
            return html;
        }
        
        function refreshClientDetails() {
            if (currentClientId) {
                const client = clients.find(c => c.id === currentClientId);
                if (client) {
                    loadClientDetails(client);
                }
            }
        }
        
        function deleteFile(filePath, fileIndex) {
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
                    // Call API to delete file
                    fetch(`?api=delete_file`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `client_id=${currentClientId}&file_path=${encodeURIComponent(filePath)}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Đã xóa!', 'File đã được xóa thành công.', 'success');
                            refreshClientDetails();
                        } else {
                            Swal.fire('Lỗi!', data.error || 'Không thể xóa file.', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error deleting file:', error);
                        Swal.fire('Lỗi!', 'Không thể kết nối đến server.', 'error');
                    });
                }
            });
        }
    }
    </script>
</body>
</html> 