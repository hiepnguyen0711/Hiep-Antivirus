/**
 * Enterprise Security Scanner - JavaScript
 * Author: Hiệp Nguyễn
 * Version: 3.0 Enterprise - Multi-site Support
 * Date: 2025
 */

class SecurityScanner {
    constructor() {
        this.isScanning = false;
        this.progressInterval = null;
        this.filesInterval = null;
        this.hostingSites = [];
        this.currentScanData = null;
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadHostingSites();
        this.loadAdminEmail();
        this.checkAutoScanStatus();
        this.initializeDatePicker();
    }

    setupEventListeners() {
        // Scan button
        document.getElementById('scanBtn').addEventListener('click', () => this.startScan());

        // Site selection radio buttons
        document.querySelectorAll('input[name="scanType"]').forEach(radio => {
            radio.addEventListener('change', (e) => this.handleScanTypeChange(e.target.value));
        });

        // Install cron job button
        document.getElementById('installCronBtn').addEventListener('click', () => this.installCronJob());

        // Auto scan checkbox
        document.getElementById('enableAutoScan').addEventListener('change', (e) => {
            this.handleAutoScanToggle(e.target.checked);
        });

        // Filter controls
        document.getElementById('sortBy').addEventListener('change', () => this.applyFilters());
        document.getElementById('filterByAge').addEventListener('change', () => this.applyFilters());
        document.getElementById('showRecentOnly').addEventListener('click', () => this.showRecentOnly());
        document.getElementById('resetFilters').addEventListener('click', () => this.resetFilters());
    }

    async loadHostingSites() {
        try {
            const response = await fetch('?get_sites=1');
            const data = await response.json();
            
            this.hostingSites = data.sites || [];
            this.renderHostingSites();
            this.populateSiteSelector();
        } catch (error) {
            console.error('Error loading hosting sites:', error);
            this.showError('Không thể tải danh sách hosting sites');
        }
    }

    renderHostingSites() {
        const container = document.getElementById('hostingSites');
        
        if (this.hostingSites.length === 0) {
            container.innerHTML = `
                <div class="loading-sites">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Không tìm thấy hosting sites</p>
                </div>
            `;
            return;
        }

        const sitesHTML = this.hostingSites.map(site => `
            <div class="site-item" data-domain="${site.domain}">
                <div class="site-info">
                    <div class="site-name">${site.domain}</div>
                    <div class="site-path">${site.path}</div>
                </div>
                <div class="site-badge ${site.is_current ? 'current-site' : 'other-site'}">
                    ${site.is_current ? 'Hiện tại' : 'Khác'}
                </div>
            </div>
        `).join('');

        container.innerHTML = sitesHTML;
    }

    populateSiteSelector() {
        const select = document.getElementById('siteSelect');
        
        const optionsHTML = this.hostingSites.map(site => `
            <option value="${site.domain}" ${site.is_current ? 'selected' : ''}>
                ${site.domain} ${site.is_current ? '(Hiện tại)' : ''}
            </option>
        `).join('');

        select.innerHTML = optionsHTML;
    }

    handleScanTypeChange(type) {
        const siteSelector = document.getElementById('siteSelector');
        
        if (type === 'all_sites') {
            siteSelector.style.display = 'block';
        } else {
            siteSelector.style.display = 'none';
        }
    }

    async startScan() {
        if (this.isScanning) return;

        this.isScanning = true;
        this.currentScanData = null;
        
        const scanBtn = document.getElementById('scanBtn');
        const fixBtn = document.getElementById('fixDropdown');
        const progressSection = document.getElementById('progressSection');
        const resultsPanel = document.getElementById('resultsPanel');
        
        // Update UI
        scanBtn.disabled = true;
        scanBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang Quét...';
        fixBtn.disabled = true;
        progressSection.style.display = 'block';
        resultsPanel.innerHTML = '<div class="text-center p-4"><i class="fas fa-spinner fa-spin"></i> Đang quét...</div>';

        try {
            // Get scan parameters
            const scanType = document.querySelector('input[name="scanType"]:checked').value;
            const selectedSite = document.getElementById('siteSelect').value;
            
            let scanUrl = '?scan=1';
            if (scanType === 'all_sites') {
                scanUrl += '&all_sites=1';
            } else if (scanType === 'specific' && selectedSite) {
                scanUrl += `&site=${encodeURIComponent(selectedSite)}`;
            }

            // Start real-time monitoring
            this.startProgressMonitoring();
            this.startFileMonitoring();

            // Perform scan
            const response = await fetch(scanUrl);
            const result = await response.json();

            if (result.success) {
                this.currentScanData = result;
                this.displayScanResults(result);
                this.updateStatistics(result);
                
                // Enable fix button if there are threats
                if (result.suspicious_count > 0) {
                    fixBtn.disabled = false;
                }
                
                this.showSuccess(`Quét hoàn tất! Đã quét ${result.scanned_files} files, tìm thấy ${result.suspicious_count} threats.`);
            } else {
                this.showError('Lỗi khi quét: ' + result.error);
            }

        } catch (error) {
            console.error('Scan error:', error);
            this.showError('Có lỗi xảy ra khi quét hệ thống');
        } finally {
            this.isScanning = false;
            scanBtn.disabled = false;
            scanBtn.innerHTML = '<i class="fas fa-search"></i> Bắt Đầu Quét';
            
            // Stop monitoring
            this.stopProgressMonitoring();
            this.stopFileMonitoring();
        }
    }

    startProgressMonitoring() {
        this.progressInterval = setInterval(async () => {
            try {
                const response = await fetch('?scan_progress=1');
                const progress = await response.json();
                
                this.updateProgress(progress);
                
                if (!progress.is_scanning) {
                    this.stopProgressMonitoring();
                }
            } catch (error) {
                console.error('Error monitoring progress:', error);
            }
        }, 1000);
    }

    stopProgressMonitoring() {
        if (this.progressInterval) {
            clearInterval(this.progressInterval);
            this.progressInterval = null;
        }
    }

    startFileMonitoring() {
        this.filesInterval = setInterval(async () => {
            try {
                const response = await fetch('?scan_files=1');
                const data = await response.json();
                
                this.updateFileScanner(data.files);
            } catch (error) {
                console.error('Error monitoring files:', error);
            }
        }, 500);
    }

    stopFileMonitoring() {
        if (this.filesInterval) {
            clearInterval(this.filesInterval);
            this.filesInterval = null;
        }
    }

    updateProgress(progress) {
        const progressBar = document.getElementById('progressBar');
        const progressText = document.getElementById('progressText');
        const progressPercentage = document.getElementById('progressPercentage');
        const currentAction = document.getElementById('currentAction');

        progressBar.style.width = `${progress.percentage}%`;
        progressText.textContent = `Đã quét ${progress.scanned_count} files...`;
        progressPercentage.textContent = `${progress.percentage}%`;
        currentAction.textContent = progress.current_file || 'Đang quét...';
    }

    updateFileScanner(files) {
        const scannerContent = document.getElementById('scannerContent');
        
        if (!files || files.length === 0) {
            scannerContent.innerHTML = `
                <div class="scanner-empty">
                    <i class="fas fa-search"></i>
                    <p>Chưa có files nào được quét</p>
                </div>
            `;
            return;
        }

        const filesHTML = files.slice(-10).map(file => {
            const statusClass = file.is_suspicious ? 'threat' : 'clean';
            const statusIcon = file.is_suspicious ? 'fas fa-exclamation-triangle' : 'fas fa-check';
            
            return `
                <div class="scanner-line ${statusClass}">
                    <i class="${statusIcon}"></i>
                    <span>${file.path}</span>
                    <span class="text-muted">(${file.status})</span>
                </div>
            `;
        }).join('');

        scannerContent.innerHTML = filesHTML;
        scannerContent.scrollTop = scannerContent.scrollHeight;
    }

    displayScanResults(result) {
        const resultsPanel = document.getElementById('resultsPanel');
        const filterControls = document.getElementById('filterControls');
        
        filterControls.style.display = 'block';
        
        let resultsHTML = '';
        
        if (result.suspicious_count === 0) {
            resultsHTML = `
                <div class="alert alert-success">
                    <i class="fas fa-shield-check"></i>
                    <strong>Hệ thống an toàn!</strong> Không phát hiện mối đe dọa nào.
                </div>
            `;
        } else {
            resultsHTML = this.generateThreatGroups(result);
        }
        
        resultsPanel.innerHTML = resultsHTML;
    }

    generateThreatGroups(result) {
        let html = '';
        
        // Critical threats
        if (result.critical_count > 0) {
            html += this.generateThreatGroup(
                'critical',
                '🚨 Mối Đe Dọa Nghiêm Trọng',
                result.suspicious_files.filter(f => f.severity === 'critical'),
                'danger'
            );
        }
        
        // Severe threats
        if (result.severe_count > 0) {
            html += this.generateThreatGroup(
                'severe',
                '⚠️ Mối Đe Dọa Cao',
                result.suspicious_files.filter(f => f.severity === 'severe'),
                'warning'
            );
        }
        
        // Warning threats
        if (result.warning_count > 0) {
            html += this.generateThreatGroup(
                'warning',
                '⚡ Cảnh Báo',
                result.suspicious_files.filter(f => f.severity === 'warning'),
                'info'
            );
        }
        
        // Filemanager files
        if (result.filemanager_count > 0) {
            html += this.generateThreatGroup(
                'filemanager',
                '📁 Filemanager Files',
                result.suspicious_files.filter(f => f.category === 'filemanager'),
                'secondary'
            );
        }
        
        return html;
    }

    generateThreatGroup(id, title, files, type) {
        const filesHTML = files.map(file => `
            <div class="threat-item" data-path="${file.path}">
                <div class="threat-header">
                    <div class="threat-path">
                        <i class="fas fa-file-code"></i>
                        <span>${file.path}</span>
                    </div>
                    <button class="delete-btn" onclick="scanner.deleteFile('${file.path}')">
                        <i class="fas fa-trash"></i> Xóa
                    </button>
                </div>
                <div class="threat-issues">
                    ${file.issues.map(issue => `
                        <div class="issue-item">
                            <strong>Line ${issue.line}:</strong> ${issue.description}
                            <br><code>${issue.code_snippet}</code>
                        </div>
                    `).join('')}
                </div>
                ${file.metadata ? `
                    <div class="file-metadata">
                        <small class="text-muted">
                            Size: ${file.metadata.size} bytes | 
                            Modified: ${new Date(file.metadata.modified_time * 1000).toLocaleString()} |
                            Age: ${file.metadata.age_category}
                        </small>
                    </div>
                ` : ''}
            </div>
        `).join('');

        return `
            <div class="threat-group ${type}" id="${id}Group">
                <div class="group-header">
                    <h5>${title} (${files.length})</h5>
                </div>
                <div class="threat-items">
                    ${filesHTML}
                </div>
            </div>
        `;
    }

    updateStatistics(result) {
        document.getElementById('scannedFiles').textContent = result.scanned_files || 0;
        document.getElementById('suspiciousFiles').textContent = result.suspicious_count || 0;
        document.getElementById('criticalFiles').textContent = result.critical_count || 0;
        
        // Update scan time
        const scanTime = document.getElementById('scanTime');
        if (result.scan_duration) {
            scanTime.textContent = `${result.scan_duration}s`;
        } else {
            scanTime.textContent = '0s';
        }
    }

    async deleteFile(filePath) {
        if (!confirm(`Bạn có chắc muốn xóa file: ${filePath}?`)) {
            return;
        }

        try {
            const response = await fetch('?delete_malware=1', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    malware_files: [filePath]
                })
            });

            const result = await response.json();
            
            if (result.success) {
                this.showSuccess(`Đã xóa file: ${filePath}`);
                
                // Remove from display
                const threatItem = document.querySelector(`[data-path="${filePath}"]`);
                if (threatItem) {
                    threatItem.remove();
                }
                
                // Update statistics
                this.updateStatisticsAfterDelete();
            } else {
                this.showError('Lỗi khi xóa file: ' + result.error);
            }
        } catch (error) {
            console.error('Delete error:', error);
            this.showError('Có lỗi xảy ra khi xóa file');
        }
    }

    updateStatisticsAfterDelete() {
        const suspiciousCount = document.querySelectorAll('.threat-item').length;
        const criticalCount = document.querySelectorAll('.threat-group.danger .threat-item').length;
        
        document.getElementById('suspiciousFiles').textContent = suspiciousCount;
        document.getElementById('criticalFiles').textContent = criticalCount;
    }

    async performAction(action) {
        if (!this.currentScanData) {
            this.showError('Vui lòng quét trước khi thực hiện hành động');
            return;
        }

        try {
            switch (action) {
                case 'delete_critical':
                    await this.deleteCriticalFiles();
                    break;
                case 'auto_fix_all':
                    await this.performAutoFix();
                    break;
                case 'quarantine':
                    await this.quarantineFiles();
                    break;
                case 'fix_permissions':
                    await this.fixPermissions();
                    break;
                case 'update_htaccess':
                    await this.updateHtaccess();
                    break;
                case 'clean_logs':
                    await this.cleanLogs();
                    break;
                case 'schedule_scan':
                    await this.scheduleScan();
                    break;
                default:
                    this.showError('Hành động không được hỗ trợ');
            }
        } catch (error) {
            console.error('Action error:', error);
            this.showError('Có lỗi xảy ra khi thực hiện hành động');
        }
    }

    async deleteCriticalFiles() {
        const criticalFiles = this.currentScanData.critical_files || [];
        
        if (criticalFiles.length === 0) {
            this.showInfo('Không có file nguy hiểm nào để xóa');
            return;
        }

        if (!confirm(`Bạn có chắc muốn xóa ${criticalFiles.length} file nguy hiểm?`)) {
            return;
        }

        const response = await fetch('?delete_malware=1', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                malware_files: criticalFiles
            })
        });

        const result = await response.json();
        
        if (result.success) {
            this.showSuccess(`Đã xóa ${result.deleted_files} file nguy hiểm`);
            
            // Remove from display
            criticalFiles.forEach(file => {
                const threatItem = document.querySelector(`[data-path="${file}"]`);
                if (threatItem) {
                    threatItem.remove();
                }
            });
            
            this.updateStatisticsAfterDelete();
        } else {
            this.showError('Lỗi khi xóa files: ' + result.error);
        }
    }

    async performAutoFix() {
        const response = await fetch('?autofix=1', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(this.currentScanData)
        });

        const result = await response.json();
        
        if (result.success) {
            this.showSuccess(`Auto-fix hoàn tất! Đã sửa ${result.fixes_applied} vấn đề, xóa ${result.deleted_files} file nguy hiểm`);
            
            // Show details
            if (result.details && result.details.length > 0) {
                const detailsHTML = result.details.map(detail => `<li>${detail}</li>`).join('');
                this.showInfo(`Chi tiết:<ul>${detailsHTML}</ul>`);
            }
        } else {
            this.showError('Lỗi auto-fix: ' + result.error);
        }
    }

    async installCronJob() {
        const installBtn = document.getElementById('installCronBtn');
        const cronStatus = document.getElementById('cronStatus');
        
        installBtn.disabled = true;
        installBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang cài đặt...';

        try {
            const response = await fetch('?install_cron=1');
            const result = await response.json();
            
            cronStatus.style.display = 'block';
            
            if (result.success) {
                cronStatus.className = 'cron-status success';
                cronStatus.innerHTML = `
                    <i class="fas fa-check"></i>
                    <strong>Thành công!</strong> ${result.message}
                    <br><small>Command: ${result.command}</small>
                `;
                
                // Update auto scan checkbox
                document.getElementById('enableAutoScan').checked = true;
            } else {
                cronStatus.className = 'cron-status error';
                cronStatus.innerHTML = `
                    <i class="fas fa-times"></i>
                    <strong>Lỗi!</strong> ${result.message}
                    <br><small>Command: ${result.command}</small>
                `;
            }
        } catch (error) {
            cronStatus.style.display = 'block';
            cronStatus.className = 'cron-status error';
            cronStatus.innerHTML = `
                <i class="fas fa-times"></i>
                <strong>Lỗi!</strong> Không thể cài đặt cron job
            `;
        } finally {
            installBtn.disabled = false;
            installBtn.innerHTML = '<i class="fas fa-cogs"></i> Cài Đặt Cron Job';
        }
    }

    handleAutoScanToggle(enabled) {
        if (enabled) {
            // Save admin email
            const adminEmail = document.getElementById('adminEmail').value;
            if (adminEmail) {
                localStorage.setItem('adminEmail', adminEmail);
            }
            
            this.showInfo('Tự động quét đã được bật. Hệ thống sẽ quét mỗi giờ và gửi email thông báo nếu phát hiện mối đe dọa.');
        } else {
            this.showInfo('Tự động quét đã được tắt.');
        }
    }

    loadAdminEmail() {
        const savedEmail = localStorage.getItem('adminEmail');
        if (savedEmail) {
            document.getElementById('adminEmail').value = savedEmail;
        }
    }

    checkAutoScanStatus() {
        // Check if cron job is installed
        // This would typically check a config file or database
        const autoScanEnabled = localStorage.getItem('autoScanEnabled') === 'true';
        document.getElementById('enableAutoScan').checked = autoScanEnabled;
    }

    initializeDatePicker() {
        if (typeof flatpickr !== 'undefined') {
            flatpickr("#dateRangePicker", {
                mode: "range",
                dateFormat: "Y-m-d",
                theme: "dark",
                placeholder: "Chọn khoảng thời gian...",
                onChange: () => this.applyFilters()
            });
        }
    }

    applyFilters() {
        const sortBy = document.getElementById('sortBy').value;
        const filterByAge = document.getElementById('filterByAge').value;
        const dateRange = document.getElementById('dateRangePicker').value;
        
        // Apply filters to threat items
        const threatItems = document.querySelectorAll('.threat-item');
        
        threatItems.forEach(item => {
            let show = true;
            
            // Apply age filter
            if (filterByAge !== 'all') {
                const metadata = item.querySelector('.file-metadata');
                if (metadata) {
                    const ageCategory = metadata.textContent.includes('Age: ' + filterByAge);
                    if (!ageCategory) {
                        show = false;
                    }
                }
            }
            
            // Apply date range filter
            if (dateRange && show) {
                // Implementation for date range filtering
                // Would need to parse file dates and check against range
            }
            
            item.style.display = show ? 'block' : 'none';
        });
        
        // Apply sorting
        this.sortThreatItems(sortBy);
    }

    sortThreatItems(sortBy) {
        const threatGroups = document.querySelectorAll('.threat-group');
        
        threatGroups.forEach(group => {
            const items = Array.from(group.querySelectorAll('.threat-item'));
            const container = group.querySelector('.threat-items');
            
            items.sort((a, b) => {
                switch (sortBy) {
                    case 'name':
                        return a.dataset.path.localeCompare(b.dataset.path);
                    case 'date':
                        // Sort by modification date
                        const dateA = a.querySelector('.file-metadata')?.textContent.match(/Modified: ([^|]+)/)?.[1];
                        const dateB = b.querySelector('.file-metadata')?.textContent.match(/Modified: ([^|]+)/)?.[1];
                        return new Date(dateB) - new Date(dateA);
                    case 'size':
                        // Sort by file size
                        const sizeA = parseInt(a.querySelector('.file-metadata')?.textContent.match(/Size: (\d+)/)?.[1] || 0);
                        const sizeB = parseInt(b.querySelector('.file-metadata')?.textContent.match(/Size: (\d+)/)?.[1] || 0);
                        return sizeB - sizeA;
                    case 'threat':
                        // Sort by threat level
                        const threatOrder = { 'critical': 0, 'severe': 1, 'warning': 2, 'filemanager': 3 };
                        const threatA = a.closest('.threat-group').id.replace('Group', '');
                        const threatB = b.closest('.threat-group').id.replace('Group', '');
                        return threatOrder[threatA] - threatOrder[threatB];
                    default:
                        return 0;
                }
            });
            
            // Re-append sorted items
            items.forEach(item => container.appendChild(item));
        });
    }

    showRecentOnly() {
        const threatItems = document.querySelectorAll('.threat-item');
        
        threatItems.forEach(item => {
            const metadata = item.querySelector('.file-metadata');
            if (metadata) {
                const isRecent = metadata.textContent.includes('Age: very_recent') || 
                                metadata.textContent.includes('Age: recent');
                item.style.display = isRecent ? 'block' : 'none';
            } else {
                item.style.display = 'none';
            }
        });
    }

    resetFilters() {
        document.getElementById('sortBy').value = 'date';
        document.getElementById('filterByAge').value = 'all';
        document.getElementById('dateRangePicker').value = '';
        
        // Show all threat items
        const threatItems = document.querySelectorAll('.threat-item');
        threatItems.forEach(item => {
            item.style.display = 'block';
        });
        
        this.applyFilters();
    }

    // Utility methods for notifications
    showSuccess(message) {
        this.showNotification(message, 'success');
    }

    showError(message) {
        this.showNotification(message, 'error');
    }

    showInfo(message) {
        this.showNotification(message, 'info');
    }

    showNotification(message, type) {
        // Using SweetAlert2 for better notifications
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: type,
                title: type === 'success' ? 'Thành công!' : (type === 'error' ? 'Lỗi!' : 'Thông báo'),
                html: message,
                timer: 5000,
                showConfirmButton: false,
                position: 'top-end',
                toast: true
            });
        } else {
            // Fallback to simple alert
            alert(message);
        }
    }

    // Additional utility methods
    async quarantineFiles() {
        this.showInfo('Tính năng cách ly đang được phát triển...');
    }

    async fixPermissions() {
        this.showInfo('Tính năng sửa quyền đang được phát triển...');
    }

    async updateHtaccess() {
        this.showInfo('Tính năng cập nhật .htaccess đang được phát triển...');
    }

    async cleanLogs() {
        this.showInfo('Tính năng dọn dẹp logs đang được phát triển...');
    }

    async scheduleScan() {
        this.showInfo('Tính năng lên lịch quét đang được phát triển...');
    }
}

// Initialize scanner when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.scanner = new SecurityScanner();
    
    // Test JSON endpoint on load
    fetch('?test=1')
        .then(response => response.json())
        .then(data => {
            console.log('Security Scanner initialized:', data);
        })
        .catch(error => {
            console.error('Error initializing scanner:', error);
        });
});

// Export for global access
window.SecurityScanner = SecurityScanner;