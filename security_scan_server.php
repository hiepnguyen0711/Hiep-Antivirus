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
    
    public function scanClient($client, $priorityFiles = []) {
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
        
        $response = $this->makeApiRequest($url, 'POST', [], json_encode($scanData));
        
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
    
    public function getFileContent($client, $filePath) {
        // Xử lý URL - nếu chưa có security_scan_client.php thì thêm vào
        $url = rtrim($client['url'], '/');
        if (strpos($url, 'security_scan_client.php') === false) {
            $url .= '/security_scan_client.php';
        }
        $url .= '?endpoint=get_file&api_key=' . urlencode($client['api_key']);
        
        $response = $this->makeApiRequest($url, 'POST', ['file_path' => $filePath], null);
        
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
    
    public function saveFileContent($client, $filePath, $content) {
        // Xử lý URL - nếu chưa có security_scan_client.php thì thêm vào
        $url = rtrim($client['url'], '/');
        if (strpos($url, 'security_scan_client.php') === false) {
            $url .= '/security_scan_client.php';
        }
        $url .= '?endpoint=save_file&api_key=' . urlencode($client['api_key']);
        
        $response = $this->makeApiRequest($url, 'POST', [
            'file_path' => $filePath,
            'content' => $content
        ], null);
        
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
                                <button class="btn btn-sm btn-success" onclick="saveCode()">
                                    <i class="fas fa-save me-1"></i>Save
                                </button>
                            </div>
                        </div>
                        <div id="monacoEditor" style="height: 500px; width: 100%;"></div>
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
            
            // Get priority files from request
            $requestData = json_decode(file_get_contents('php://input'), true);
            $priorityFiles = $requestData['priority_files'] ?? [];
            
            $result = $scannerManager->scanClient($client, $priorityFiles);
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
            
        case 'get_file_content':
            $clientId = $_GET['client_id'] ?? '';
            $filePath = $_GET['file_path'] ?? '';
            
            if (empty($clientId) || empty($filePath)) {
                echo json_encode(['success' => false, 'error' => 'Missing client_id or file_path']);
                break;
            }
            
            $client = $clientManager->getClient($clientId);
            if (!$client) {
                echo json_encode(['success' => false, 'error' => 'Client not found']);
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
    <title>Security Scanner Server - Multi-Website Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/monaco-editor@0.45.0/min/vs/loader.js"></script>
    <style>
        :root {
            /* Modern Color Palette */
            --primary-blue: #4A90E2;
            --light-blue: #E8F4FD;
            --soft-blue: #B8DCF2;
            --dark-blue: #2C5282;
            --accent-blue: #63B3ED;
            
            /* Severity Colors */
            --critical-red: #DC3545;
            --priority-color: #9333ea;
            --priority-light: #faf5ff;
            --priority-border: #e9d5ff;
            --critical-bg: #FEF2F2;
            --critical-border: #FECACA;
            
            --warning-yellow: #F59E0B;
            --warning-bg: #FFFBEB;
            --warning-border: #FED7AA;
            
            --info-blue: #3B82F6;
            --info-bg: #EFF6FF;
            --info-border: #DBEAFE;
            
            /* Neutral Colors */
            --bg-primary: #FAFBFC;
            --bg-secondary: #F7F9FB;
            --bg-card: #FFFFFF;
            --border-light: #E2E8F0;
            --border-medium: #CBD5E0;
            
            --text-primary: #1F2937;
            --text-secondary: #6B7280;
            --text-muted: #9CA3AF;
            
            /* Shadows */
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: var(--text-primary);
            line-height: 1.6;
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
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
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
            padding: 0 20px;
            position: relative;
            z-index: 10;
        }

        /* Bento Grid Layout */
        .bento-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .bento-item {
            background: var(--bg-card);
            border-radius: 20px;
            padding: 24px;
            box-shadow: var(--shadow-xl);
            border: 1px solid var(--border-light);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .bento-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(74, 144, 226, 0.05), rgba(99, 179, 237, 0.05));
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .bento-item:hover::before {
            opacity: 1;
        }

        .bento-item:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl), 0 25px 50px -12px rgba(0, 0, 0, 0.25);
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
            gap: 12px;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 2px solid var(--border-light);
            position: relative;
        }

        .card-header-modern::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 60px;
            height: 2px;
            background: var(--primary-blue);
            border-radius: 2px;
        }

        .card-title-modern {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0;
        }

        .card-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary-blue), var(--accent-blue));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.1rem;
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
            background: var(--bg-card);
            border-radius: 20px;
            padding: 30px;
            margin-top: 30px;
            box-shadow: var(--shadow-xl);
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
        }

        .meta-severity.info {
            background: var(--info-blue);
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
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .bento-grid {
                grid-template-columns: 1fr 1fr;
            }
            
            .bento-item.span-3 {
                grid-column: span 2;
            }
        }

        @media (max-width: 768px) {
            .main-container {
                padding: 0 15px;
            }
            
            .bento-grid {
                grid-template-columns: 1fr;
            }
            
            .bento-item.span-2,
            .bento-item.span-3 {
                grid-column: span 1;
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
            background: white;
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
            gap: 8px;
            cursor: help;
            flex: 1;
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
            justify-content: space-between;
            align-items: center;
        }

        .recent-threat-severity {
            font-size: 10px;
            padding: 2px 8px;
            border-radius: 12px;
            font-weight: 600;
        }

        .recent-threat-time {
            font-size: 10px;
            color: #6b7280;
        }

        .sidebar-footer {
            padding: 12px 20px;
            background: #f9fafb;
            border-top: 1px solid #e5e7eb;
            border-radius: 0 0 12px 12px;
        }

        /* Tooltip Styles */
        .threat-tooltip {
            position: absolute;
            background: #1f2937;
            color: white;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 12px;
            max-width: 300px;
            z-index: 9999;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.2s ease;
            word-wrap: break-word;
        }

        .threat-tooltip.show {
            opacity: 1;
        }

        .threat-tooltip::before {
            content: '';
            position: absolute;
            top: -6px;
            left: 20px;
            border-left: 6px solid transparent;
            border-right: 6px solid transparent;
            border-bottom: 6px solid #1f2937;
        }

        .threat-issue-item {
            margin-bottom: 8px;
            padding: 8px 12px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
        }

        .threat-issue-item:last-child {
            margin-bottom: 0;
        }

        .threat-issue-line {
            font-weight: 600;
            color: #fbbf24;
        }

        .threat-issue-reason {
            color: #d1d5db;
            margin-top: 4px;
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
            
            .col-md-8, .col-md-4 {
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
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
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

        <!-- Moved Priority Files Section Above -->
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

        <!-- Bento Grid Dashboard -->
        <div class="bento-grid">
            <!-- Statistics Overview -->
            <div class="bento-item">
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

            <!-- Recent Activity -->
            <div class="bento-item">
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

            <!-- System Health -->
            <div class="bento-item">
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
                                Hiển thị file được tạo/sửa trong 7 ngày qua
                            </small>
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

    <script>
        let clients = [];
        let currentScanResults = [];
        let currentClientId = null;

        // Initialize with sample data if no clients exist
        function initializeSampleData() {
            const sampleClients = [
                {
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
            fetch('?api=get_clients')
                .then(response => response.json())
                .then(data => {
                    // Ensure data is an array
                    if (Array.isArray(data)) {
                        clients = data;
                    } else if (data && data.success && Array.isArray(data.data)) {
                        clients = data.data;
                    } else {
                        clients = [];
                        console.error('Invalid clients data:', data);
                    }
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
                const severityOrder = { critical: 3, warning: 2, info: 1 };
                return (severityOrder[b.severity] || 0) - (severityOrder[a.severity] || 0);
            });

            // Filter recent threats (last 7 days)
            const now = Date.now() / 1000;
            const recentThreats = sortedFiles.filter(file => {
                const fileTime = file.metadata?.modified_time || 0;
                const age = now - fileTime;
                return age < 7 * 24 * 3600; // 7 days
            }).slice(0, 10); // Limit to 10 most recent

            scanResultsDiv.style.display = 'block';
            
            // Update main threat count
            mainThreatCount.textContent = `${sortedFiles.length} files`;
            
            // Populate main threats container
            threatsContainer.innerHTML = sortedFiles.map(file => {
                const severity = getSeverityLevel(file);
                const ageInfo = getAgeInfo(file.metadata?.modified_time);
                const fileSize = formatFileSize(file.metadata?.size || 0);
                
                // Store issues data directly in element for tooltip
                const issuesData = JSON.stringify(file.issues || []);
                
                return `
                    <div class="threat-card ${severity} fade-in-up" 
                         data-file-path="${file.path}"
                         data-issues='${issuesData.replace(/'/g, '&#39;')}'
                         onmouseenter="showThreatTooltipFromCard(event, this)"
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
                            <span class="meta-badge meta-age ${ageInfo.class}">${ageInfo.label}</span>
                            <span class="meta-badge meta-size">${fileSize}</span>
                        </div>
                        
                        <div class="threat-details">
                            <strong>${file.issues?.length || 0} vấn đề phát hiện:</strong>
                            <div class="threat-patterns">
                                ${(file.issues || []).slice(0, 3).map(issue => 
                                    `<span class="threat-pattern">${issue.pattern}</span>`
                                ).join('')}
                                ${(file.issues || []).length > 3 ? '<span class="threat-pattern">...</span>' : ''}
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
            
            // Populate recent threats sidebar
            if (recentThreats.length > 0) {
                recentThreatsContainer.innerHTML = recentThreats.map(file => {
                    const severity = getSeverityLevel(file);
                    const timeAgo = getTimeAgo(file.metadata?.modified_time);
                    
                    return `
                        <div class="recent-threat-item">
                            <div class="recent-threat-header">
                                <div class="recent-threat-file" onclick="viewThreat('${file.path}')">${file.path}</div>
                                <button class="recent-threat-delete" onclick="deleteFile('${file.path}')" title="Xóa file">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <div class="recent-threat-meta">
                                <span class="recent-threat-severity ${severity}">${getSeverityLabel(severity)}</span>
                                <span class="recent-threat-time">${timeAgo}</span>
                            </div>
                        </div>
                    `;
                }).join('');
            } else {
                recentThreatsContainer.innerHTML = '<div class="text-center text-muted p-3"><small>Không có file nguy hiểm gần đây</small></div>';
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
            if (!timestamp) return { class: 'old', label: 'Cũ' };
            
            const now = Date.now() / 1000;
            const age = now - timestamp;
            
            if (age < 7 * 24 * 3600) { // 1 week
                return { class: 'new', label: 'Mới' };
            } else if (age < 5 * 30 * 24 * 3600) { // 5 months
                return { class: 'recent', label: 'Gần đây' };
            } else {
                return { class: 'old', label: 'Cũ' };
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
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `client_id=${currentClientId}&file_path=${encodeURIComponent(filePath)}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Đã xóa!', 'File đã được xóa thành công.', 'success');
                            // Remove card from display
                            document.querySelectorAll('.threat-card').forEach(card => {
                                if (card.querySelector('.threat-path').textContent === filePath) {
                                    card.remove();
                                }
                            });
                        } else {
                            Swal.fire('Lỗi!', data.error || 'Không thể xóa file.', 'error');
                        }
                    })
                    .catch(error => {
                        Swal.fire('Lỗi!', 'Không thể kết nối tới server.', 'error');
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
            // Check client health
        }

        function viewClient(clientId) {
            // View client details
        }

        function deleteClient(clientId) {
            // Delete client
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

        // Initialize Monaco Editor
        function initMonacoEditor() {
            require.config({ paths: { vs: 'https://cdn.jsdelivr.net/npm/monaco-editor@0.45.0/min/vs' } });
            require(['vs/editor/editor.main'], function () {
                monacoEditor = monaco.editor.create(document.getElementById('monacoEditor'), {
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

                // Update cursor position
                monacoEditor.onDidChangeCursorPosition(function (e) {
                    document.getElementById('cursorPosition').textContent = 
                        `Line ${e.position.lineNumber}, Column ${e.position.column}`;
                });

                // Auto-save on Ctrl+S
                monacoEditor.addCommand(monaco.KeyMod.CtrlCmd | monaco.KeyCode.KeyS, function () {
                    saveCode();
                });

                // Find with Ctrl+F
                monacoEditor.addCommand(monaco.KeyMod.CtrlCmd | monaco.KeyCode.KeyF, function () {
                    findInCode();
                });
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
                                                                    <button class="btn btn-sm btn-success" onclick="saveCode()">
                                                                        <i class="fas fa-save me-1"></i>Save
                                                                    </button>
                                                                </div>
                                                            </div>
                                                            <div id="monacoEditor" style="height: 500px; width: 100%;"></div>
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

        // Tooltip Functions
        let currentTooltip = null;
        
        function showThreatTooltipFromCard(event, cardElement) {
            // Remove existing tooltip
            hideThreatTooltip();
            
            const filePath = cardElement.getAttribute('data-file-path');
            const issuesDataStr = cardElement.getAttribute('data-issues');
            
            if (!issuesDataStr) {
                // Fallback - show basic info if no issues data
                showBasicTooltip(event, cardElement, filePath);
                return;
            }
            
            try {
                const issues = JSON.parse(issuesDataStr);
                if (!issues || issues.length === 0) {
                    showBasicTooltip(event, cardElement, filePath);
                    return;
                }
                
                // Create tooltip content
                const content = issues.map(issue => 
                    `<div class="threat-issue-item">
                        <div class="threat-issue-line">Dòng ${issue.line || 'N/A'}</div>
                        <div class="threat-issue-reason">${issue.description || issue.pattern || ''}</div>
                    </div>`
                ).join('');
                
                // Create tooltip
                currentTooltip = document.createElement('div');
                currentTooltip.className = 'threat-tooltip';
                currentTooltip.innerHTML = `
                    <div style="font-weight: 600; margin-bottom: 8px; color: #fbbf24;">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        Threats trong ${filePath}:
                    </div>
                    ${content}
                `;
                
                document.body.appendChild(currentTooltip);
                
                // Position tooltip
                const rect = cardElement.getBoundingClientRect();
                const tooltipRect = currentTooltip.getBoundingClientRect();
                
                let left = rect.left + (rect.width / 2) - (tooltipRect.width / 2);
                let top = rect.top - tooltipRect.height - 10;
                
                // Adjust if tooltip goes off screen
                if (left < 10) left = 10;
                if (left + tooltipRect.width > window.innerWidth - 10) {
                    left = window.innerWidth - tooltipRect.width - 10;
                }
                if (top < 10) {
                    top = rect.bottom + 10;
                }
                
                currentTooltip.style.left = left + 'px';
                currentTooltip.style.top = top + 'px';
                
                // Show tooltip
                setTimeout(() => {
                    if (currentTooltip) {
                        currentTooltip.classList.add('show');
                    }
                }, 50);
                
            } catch (e) {
                console.error('Error parsing tooltip data:', e);
            }
        }
        
        function showBasicTooltip(event, cardElement, filePath) {
            // Create basic tooltip for files without detailed issues
            currentTooltip = document.createElement('div');
            currentTooltip.className = 'threat-tooltip';
            currentTooltip.innerHTML = `
                <div style="font-weight: 600; margin-bottom: 8px; color: #fbbf24;">
                    <i class="fas fa-file-code me-1"></i>
                    File: ${filePath}
                </div>
                <div style="color: #d1d5db;">
                    Click "Sửa" để xem chi tiết và chỉnh sửa file này.
                </div>
            `;
            
            document.body.appendChild(currentTooltip);
            
            // Position tooltip
            const rect = cardElement.getBoundingClientRect();
            const tooltipRect = currentTooltip.getBoundingClientRect();
            
            let left = rect.left + (rect.width / 2) - (tooltipRect.width / 2);
            let top = rect.top - tooltipRect.height - 10;
            
            // Adjust if tooltip goes off screen
            if (left < 10) left = 10;
            if (left + tooltipRect.width > window.innerWidth - 10) {
                left = window.innerWidth - tooltipRect.width - 10;
            }
            if (top < 10) {
                top = rect.bottom + 10;
            }
            
            currentTooltip.style.left = left + 'px';
            currentTooltip.style.top = top + 'px';
            
            // Show tooltip
            setTimeout(() => {
                if (currentTooltip) {
                    currentTooltip.classList.add('show');
                }
            }, 50);
        }
        
        function hideThreatTooltip() {
            if (currentTooltip) {
                currentTooltip.classList.remove('show');
                setTimeout(() => {
                    if (currentTooltip && currentTooltip.parentNode) {
                        currentTooltip.parentNode.removeChild(currentTooltip);
                    }
                    currentTooltip = null;
                }, 200);
            }
        }
        
        // Time ago function
        function getTimeAgo(timestamp) {
            if (!timestamp) return 'Không xác định';
            
            const now = Date.now() / 1000;
            const diff = now - timestamp;
            
            if (diff < 3600) { // < 1 hour
                const minutes = Math.floor(diff / 60);
                return `${minutes} phút trước`;
            } else if (diff < 86400) { // < 1 day
                const hours = Math.floor(diff / 3600);
                return `${hours} giờ trước`;
            } else if (diff < 604800) { // < 1 week
                const days = Math.floor(diff / 86400);
                return `${days} ngày trước`;
            } else {
                return new Date(timestamp * 1000).toLocaleDateString('vi-VN');
            }
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

        // Initialize Monaco Editor when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            initMonacoEditor();
        });
    </script>
</body>
</html> 