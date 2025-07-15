// PHP 5.6+ Compatible JavaScript
var SecurityScanner = function() {
    this.isScanning = false;
    this.scannedFiles = 0;
    this.suspiciousFiles = 0;
    this.criticalFiles = 0;
    this.scanStartTime = null;
    this.progressInterval = null;
    this.speedInterval = null;
    this.fileSimulationInterval = null;
    this.lastScanData = null;
    this.init();
};

SecurityScanner.prototype.init = function() {
    var self = this;
    document.getElementById('scanBtn').addEventListener('click', function() {
        self.startScan();
    });
    
    // Initialize Bootstrap tooltips
    this.initTooltips();
};

SecurityScanner.prototype.initTooltips = function() {
    // Initialize tooltips for dynamically created elements
    document.addEventListener('DOMContentLoaded', function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
};

SecurityScanner.prototype.startScan = function() {
    if (this.isScanning) return;
    
    this.isScanning = true;
    this.scannedFiles = 0;
    this.suspiciousFiles = 0;
    this.criticalFiles = 0;
    this.scanStartTime = Date.now();
    
    var scanBtn = document.getElementById('scanBtn');
    var progressSection = document.getElementById('progressSection');
    var resultsPanel = document.getElementById('resultsPanel');
    
    // Update UI
    scanBtn.disabled = true;
    scanBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang Quét...';
    progressSection.classList.add('active');
    resultsPanel.classList.remove('active');
    
    // Clear scanner content
    document.getElementById('scannerContent').innerHTML = '';
    
    // Start file simulation
    this.startFileSimulation();
    
    // Start progress simulation
    this.simulateProgress();
    
    // Start speed counter
    this.startSpeedCounter();
    
    // Start actual scan after short delay
    var self = this;
    setTimeout(function() {
        self.performScan();
    }, 1000);
};

SecurityScanner.prototype.startFileSimulation = function() {
    // Start real-time progress and file list polling
    var self = this;
    console.log('Starting real-time scanner polling...'); // Debug log
    
    this.progressPollingInterval = setInterval(function() {
        self.checkScanProgress();
    }, 500); // Poll every 500ms for better performance
    
    this.filesPollingInterval = setInterval(function() {
        self.checkScannedFiles();
    }, 300); // Poll files more frequently
};

SecurityScanner.prototype.checkScannedFiles = function() {
    var self = this;
    
    if (!this.isScanning) {
        clearInterval(this.filesPollingInterval);
        return;
    }
    
    // Create XMLHttpRequest for files check
    var xhr = new XMLHttpRequest();
    xhr.open('GET', '?scan_files=1&t=' + Date.now(), true);
    xhr.setRequestHeader('Cache-Control', 'no-cache');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                try {
                    var filesData = JSON.parse(xhr.responseText);
                    self.updateScannedFilesList(filesData);
                } catch (e) {
                    console.log('Files parse error:', e);
                }
            }
        }
    };
    
    xhr.send();
};

SecurityScanner.prototype.updateScannedFilesList = function(filesData) {
    if (!filesData.files || filesData.files.length === 0) {
        return;
    }
    
    var scannerContent = document.getElementById('scannerContent');
    
    // Clear "empty" state if exists
    if (scannerContent.querySelector('.scanner-empty')) {
        scannerContent.innerHTML = '';
    }
    
    // Show last 10 files
    var recentFiles = filesData.files.slice(-10);
    
    // Clear current content
    scannerContent.innerHTML = '';
    
    // Add each file
    for (var i = 0; i < recentFiles.length; i++) {
        var fileData = recentFiles[i];
        this.addRealTimeFileFromData(fileData);
    }
};

SecurityScanner.prototype.addRealTimeFileFromData = function(fileData) {
    var scannerContent = document.getElementById('scannerContent');
    
    // Create file item
    var fileItem = document.createElement('div');
    fileItem.className = 'file-item slideIn';
    
    var statusClass = fileData.is_suspicious ? 'status-suspicious' : 'status-clean';
    var iconColor = fileData.is_suspicious ? 'var(--danger-text)' : 'var(--success-text)';
    
    fileItem.innerHTML = 
        '<div class="file-icon">' +
            '<i class="fas fa-file-code" style="color: ' + iconColor + ';"></i>' +
        '</div>' +
        '<div class="file-path" title="' + fileData.path + '">' + fileData.path + '</div>' +
        '<div class="file-status ' + statusClass + '">' +
            fileData.status +
        '</div>' +
        '<div class="scan-number">' +
            '#' + fileData.scan_number +
        '</div>';
    
    scannerContent.appendChild(fileItem);
    scannerContent.scrollTop = scannerContent.scrollHeight;
};

SecurityScanner.prototype.checkScanProgress = function() {
    var self = this;
    
    if (!this.isScanning) {
        clearInterval(this.progressPollingInterval);
        return;
    }
    
    // Create XMLHttpRequest for progress check
    var xhr = new XMLHttpRequest();
    xhr.open('GET', '?scan_progress=1&t=' + Date.now(), true); // Add timestamp to prevent caching
    xhr.setRequestHeader('Cache-Control', 'no-cache');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                try {
                    var progress = JSON.parse(xhr.responseText);
                    console.log('Progress update:', progress); // Debug log
                    self.updateRealTimeProgress(progress);
                } catch (e) {
                    console.log('Progress parse error:', e, xhr.responseText.substring(0, 100));
                }
            } else {
                console.log('Progress request failed:', xhr.status, xhr.statusText);
            }
        }
    };
    
    xhr.onerror = function() {
        console.log('Progress request error');
    };
    
    xhr.send();
};

SecurityScanner.prototype.updateRealTimeProgress = function(progress) {
    // Update stats - use the latest count from server (it's cumulative)
    if (progress.scanned_count !== undefined) {
        this.scannedFiles = progress.scanned_count;
    }
    this.updateStats();
    
    // Update progress bar
    var progressBar = document.getElementById('progressBar');
    var progressPercentage = document.getElementById('progressPercentage');
    var currentAction = document.getElementById('currentAction');
    
    if (progressBar && progress.percentage !== undefined) {
        progressBar.style.width = progress.percentage + '%';
        progressPercentage.textContent = Math.round(progress.percentage) + '%';
    }
    
    if (currentAction && progress.current_file) {
        var fileName = progress.current_file.split('/').pop();
        currentAction.innerHTML = '<i class="fas fa-spinner pulse" style="color: var(--primary-blue);"></i> ' + 
                                'Quét: ' + fileName;
    }
    
    // Check if scan completed
    if (progress.completed || !progress.is_scanning) {
        clearInterval(this.progressPollingInterval);
        clearInterval(this.filesPollingInterval);
    }
};

// Old addRealTimeFile function removed - using addRealTimeFileFromData instead

SecurityScanner.prototype.simulateProgress = function() {
    var progress = 0;
    var progressBar = document.getElementById('progressBar');
    var progressText = document.getElementById('progressText');
    var progressPercentage = document.getElementById('progressPercentage');
    var currentAction = document.getElementById('currentAction');
    
    var actions = [
        'Khởi tạo scanner...',
        'Quét sources...',
        'Phân tích admin...',
        'Kiểm tra filemanager...',
        'Quét virus-files...',
        'Hoàn thiện...'
    ];
    
    var actionIndex = 0;
    var self = this;
    
    this.progressInterval = setInterval(function() {
        progress += Math.random() * 8 + 4;
        if (progress > 100) progress = 100;
        
        progressBar.style.width = progress + '%';
        progressPercentage.textContent = Math.round(progress) + '%';
        
        if (Math.floor(progress / 17) > actionIndex && actionIndex < actions.length - 1) {
            actionIndex++;
            currentAction.textContent = actions[actionIndex];
        }
        
        if (progress >= 100) {
            clearInterval(self.progressInterval);
            progressText.textContent = 'Hoàn tất!';
            currentAction.textContent = 'Tạo báo cáo...';
        }
    }, 150);
};

SecurityScanner.prototype.startSpeedCounter = function() {
    var self = this;
    this.speedInterval = setInterval(function() {
        var elapsed = (Date.now() - self.scanStartTime) / 1000;
        document.getElementById('scanTime').textContent = Math.round(elapsed) + 's';
    }, 500);
};

SecurityScanner.prototype.addFileToScanner = function(filePath, isClean) {
    var scannerContent = document.getElementById('scannerContent');
    
    var fileItem = document.createElement('div');
    fileItem.className = 'file-item slideIn';
    fileItem.innerHTML = 
        '<div class="file-icon">' +
            '<i class="fas fa-file-code" style="color: ' + (isClean ? 'var(--success-text)' : 'var(--danger-text)') + ';"></i>' +
        '</div>' +
        '<div class="file-path">' + filePath + '</div>' +
        '<div class="file-status ' + (isClean ? 'status-clean' : 'status-suspicious') + '">' +
            (isClean ? 'Clean' : 'Threat') +
        '</div>';
    
    scannerContent.appendChild(fileItem);
    scannerContent.scrollTop = scannerContent.scrollHeight;
    
    // Keep only last 10 items
    while (scannerContent.children.length > 10) {
        scannerContent.removeChild(scannerContent.firstChild);
    }
};

SecurityScanner.prototype.updateStats = function() {
    document.getElementById('scannedFiles').textContent = this.scannedFiles;
    document.getElementById('suspiciousFiles').textContent = this.suspiciousFiles;
    document.getElementById('criticalFiles').textContent = this.criticalFiles;
};

SecurityScanner.prototype.performScan = function() {
    var self = this;
    
    // Show comprehensive scan message
    var currentAction = document.getElementById('currentAction');
    if (currentAction) {
        currentAction.innerHTML = '<i class="fas fa-spinner fa-spin pulse" style="color: var(--primary-blue);"></i> ' + 
                                'Quét toàn bộ dự án không giới hạn - Đang tìm shells như app.php, style.php...';
    }
    
    // Create XMLHttpRequest for PHP 5.6+ compatibility
    var xhr = new XMLHttpRequest();
    xhr.open('GET', '?scan=1', true);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.setRequestHeader('Cache-Control', 'no-cache');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                try {
                    var text = xhr.responseText;
                    console.log('Raw response received, length:', text.length);
                    
                    if (!text || text.trim() === '') {
                        throw new Error('Empty response from server');
                    }
                    
                    var data = JSON.parse(text);
                    console.log('Parsed data:', data);
                    self.displayResults(data);
                    
                } catch (e) {
                    console.error('JSON Parse Error:', e);
                    self.displayError('Response không phải JSON hợp lệ. Server có thể đang xử lý quá nhiều files.');
                }
            } else {
                self.displayError('Lỗi HTTP: ' + xhr.status + ' ' + xhr.statusText + '. Có thể server đang quá tải.');
            }
        }
    };
    
    xhr.onerror = function() {
        self.displayError('Lỗi kết nối mạng. Kiểm tra kết nối và thử lại.');
    };
    
    xhr.ontimeout = function() {
        self.displayError('Quét bị timeout sau 10 phút. Hosting có quá nhiều files hoặc quá chậm.<br>' +
                         'Scanner đã quét được nhiều files. Hãy thử:<br>' +
                         '• Refresh trang và xem kết quả hiện tại<br>' +
                         '• Hoặc quét lại để tiếp tục');
    };
    
    xhr.timeout = 600000; // 10 minutes timeout for comprehensive scan
    
    xhr.send();
};

SecurityScanner.prototype.displayResults = function(data) {
    var self = this;
    
    // Update final stats with real data
    this.scannedFiles = data.scanned_files || this.scannedFiles;
    this.suspiciousFiles = data.suspicious_count || this.suspiciousFiles;
    this.criticalFiles = data.critical_count || this.criticalFiles;
    this.updateStats();
    
    // Show real scanned files
    if (data.suspicious_files && data.suspicious_files.length > 0) {
        // Show last few files scanned
        var lastFiles = data.suspicious_files.slice(-5);
        for (var i = 0; i < lastFiles.length; i++) {
            var file = lastFiles[i];
            var isClean = file.severity === 'warning' || file.category === 'filemanager';
            self.addFileToScanner(file.path, isClean);
        }
    }
    
    // Store scan data for auto-fix
    this.lastScanData = data;
    
    setTimeout(function() {
        var resultsPanel = document.getElementById('resultsPanel');
        var scanResults = document.getElementById('scanResults');
        
        resultsPanel.classList.add('active');
        
        if (data.suspicious_count === 0) {
            scanResults.innerHTML = 
                '<div class="alert alert-success">' +
                    '<i class="fas fa-shield-check"></i>' +
                    '<div>' +
                        '<strong>Hệ thống an toàn!</strong><br>' +
                        '<small>Không phát hiện threat nào trong ' + data.scanned_files + ' files đã quét.</small>' +
                    '</div>' +
                '</div>';
            document.getElementById('fixDropdown').disabled = true;
        } else {
            var resultHtml = 
                '<div class="alert alert-danger">' +
                    '<i class="fas fa-exclamation-triangle"></i>' +
                    '<div>' +
                        '<strong>Phát hiện ' + data.suspicious_count + ' threats!</strong><br>' +
                        '<small>Trong đó có ' + (data.critical_count || 0) + ' threats nghiêm trọng cần xử lý ngay.</small>' +
                    '</div>' +
                '</div>' +
                '<div class="results-grid">';
            
            // Group files by category and severity
            var groups = {
                hacker_planted: { title: '🚨 Files Hacker Chèn Vào (NGUY HIỂM NHẤT)', icon: 'fa-user-ninja', files: [] },
                suspicious_file: { title: 'Files Đáng Ngờ (.php.jpg, Empty)', icon: 'fa-exclamation-circle', files: [] },
                critical: { title: 'Files Virus/Malware Nguy Hiểm', icon: 'fa-skull-crossbones', files: [] },
                filemanager: { title: 'Filemanager Functions', icon: 'fa-folder-open', files: [] },
                warning: { title: 'Cảnh Báo Bảo Mật', icon: 'fa-exclamation-triangle', files: [] }
            };
            
            for (var i = 0; i < data.suspicious_files.length; i++) {
                var file = data.suspicious_files[i];
                file.index = i;
                
                var isCritical = file.severity === 'critical';
                var isFilemanager = file.category === 'filemanager';
                var isSuspiciousFile = file.category === 'suspicious_file';
                
                if (isSuspiciousFile) {
                    groups.suspicious_file.files.push(file);
                } else if (isCritical && !isFilemanager) {
                    groups.critical.files.push(file);
                } else if (isFilemanager) {
                    groups.filemanager.files.push(file);
                } else {
                    groups.warning.files.push(file);
                }
            }
            
            // Render groups
            for (var groupKey in groups) {
                var group = groups[groupKey];
                if (group.files.length > 0) {
                    resultHtml += 
                        '<div class="threat-group ' + groupKey + '">' +
                            '<div class="group-header ' + groupKey + '">' +
                                '<i class="fas ' + group.icon + '"></i>' +
                                '<span>' + group.title + ' (' + group.files.length + ')</span>' +
                            '</div>';
                    
                    for (var j = 0; j < group.files.length; j++) {
                        var file = group.files[j];
                        var isCritical = (file.severity === 'critical' && file.category !== 'filemanager') || file.category === 'suspicious_file';
                        var tooltipContent = self.generateTooltipContent(file.issues);
                        var firstIssue = file.issues && file.issues.length > 0 ? file.issues[0] : null;
                        var metadata = file.metadata || {};
                        var ageClass = metadata.age_category || 'old';
                        var modifiedDate = metadata.modified_time ? new Date(metadata.modified_time * 1000) : new Date();
                        var fileSize = metadata.size ? self.formatFileSize(metadata.size) : '0 B';
                        
                        resultHtml += 
                            '<div class="threat-item ' + ageClass + '" ' +
                                 'data-bs-toggle="tooltip" ' +
                                 'data-bs-placement="top" ' +
                                 'data-bs-html="true" ' +
                                 'title="' + tooltipContent + '" ' +
                                 'data-modified="' + metadata.modified_time + '" ' +
                                 'data-age="' + ageClass + '" ' +
                                 'data-size="' + metadata.size + '">' +
                                '<div class="threat-header">' +
                                    '<div class="threat-path">' +
                                        '<i class="fas fa-file-code"></i> ' + file.path +
                                        (firstIssue ? ' <span style="color: var(--warning-text); font-size: 0.7rem;">(dòng ' + firstIssue.line + ')</span>' : '') +
                                    '</div>' +
                                    (isCritical ? 
                                        '<button class="delete-btn" onclick="scanner.deleteSingleFile(\'' + file.path + '\', ' + file.index + ')">' +
                                            '<i class="fas fa-trash-alt"></i> Xóa' +
                                        '</button>' 
                                        : '') +
                                '</div>' +
                                '<div class="threat-issues">' +
                                    file.issues.length + ' vấn đề phát hiện' +
                                    (firstIssue ? ' - <span style="color: var(--danger-text); font-weight: 600;">' + firstIssue.pattern + '</span>' : '') +
                                '</div>' +
                                '<div class="file-date">' +
                                    '<i class="fas fa-clock"></i>' +
                                    '<span>' + self.formatDate(modifiedDate) + '</span>' +
                                    '<span class="age-badge ' + ageClass + '">' + self.getAgeLabel(ageClass) + '</span>' +
                                    '<span style="margin-left: 8px;"><i class="fas fa-hdd"></i> ' + fileSize + '</span>' +
                                '</div>' +
                            '</div>';
                    }
                    
                    resultHtml += '</div>';
                }
            }
            
            resultHtml += '</div>';
            
            scanResults.innerHTML = resultHtml;
            
            // Initialize tooltips for new elements
            setTimeout(function() {
                var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });
            }, 100);
            
            // Enable fix dropdown and show filter controls
            document.getElementById('fixDropdown').disabled = false;
            document.getElementById('filterControls').style.display = 'flex';
            
            // Initialize filter event listeners
            self.initializeFilters();
        }
        
        self.completeScan();
    }, 1000);
};

SecurityScanner.prototype.formatDate = function(date) {
    var now = new Date();
    var diff = now - date;
    var minutes = Math.floor(diff / (1000 * 60));
    var hours = Math.floor(diff / (1000 * 60 * 60));
    var days = Math.floor(diff / (1000 * 60 * 60 * 24));
    
    if (minutes < 60) {
        return minutes + ' phút trước';
    } else if (hours < 24) {
        return hours + ' giờ trước';
    } else if (days < 7) {
        return days + ' ngày trước';
    } else {
        return date.toLocaleDateString('vi-VN');
    }
};

SecurityScanner.prototype.formatFileSize = function(bytes) {
    if (bytes === 0) return '0 B';
    var k = 1024;
    var sizes = ['B', 'KB', 'MB', 'GB'];
    var i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

SecurityScanner.prototype.getAgeLabel = function(ageCategory) {
    var labels = {
        'very_recent': 'HOT',
        'recent': 'Mới',
        'medium': 'Gần đây',
        'old': 'Cũ'
    };
    return labels[ageCategory] || 'Cũ';
};

SecurityScanner.prototype.initializeFilters = function() {
    var self = this;
    
    // Initialize Flatpickr date range picker
    self.dateRangePicker = flatpickr("#dateRangePicker", {
        mode: "range",
        dateFormat: "d/m/Y",
        theme: "dark",
        locale: {
            rangeSeparator: " đến "
        },
        onChange: function(selectedDates, dateStr, instance) {
            self.selectedDateRange = selectedDates;
            self.applySortAndFilter();
        }
    });
    
    // Sort dropdown
    document.getElementById('sortBy').addEventListener('change', function() {
        self.applySortAndFilter();
    });
    
    // Age filter dropdown
    document.getElementById('filterByAge').addEventListener('change', function() {
        // Clear date picker when using quick filters
        if (this.value !== 'all') {
            self.dateRangePicker.clear();
            self.selectedDateRange = null;
        }
        self.applySortAndFilter();
    });
    
    // Quick filter buttons
    document.getElementById('showRecentOnly').addEventListener('click', function() {
        document.getElementById('filterByAge').value = 'very_recent';
        document.getElementById('sortBy').value = 'date';
        self.dateRangePicker.clear();
        self.selectedDateRange = null;
        self.applySortAndFilter();
    });
    
    document.getElementById('resetFilters').addEventListener('click', function() {
        document.getElementById('filterByAge').value = 'all';
        document.getElementById('sortBy').value = 'threat';
        self.dateRangePicker.clear();
        self.selectedDateRange = null;
        self.applySortAndFilter();
    });
};

SecurityScanner.prototype.applySortAndFilter = function() {
    var sortBy = document.getElementById('sortBy').value;
    var filterByAge = document.getElementById('filterByAge').value;
    var threatItems = document.querySelectorAll('.threat-item');
    var itemsArray = Array.from(threatItems);
    var self = this;
    
    // Filter items
    itemsArray.forEach(function(item) {
        var shouldShow = true;
        
        // Age filter (Quick filters)
        if (filterByAge !== 'all') {
            var itemAge = item.dataset.age;
            shouldShow = shouldShow && (itemAge === filterByAge);
        }
        
        // Date range filter (Date picker)
        if (self.selectedDateRange && self.selectedDateRange.length === 2) {
            var itemModified = parseInt(item.dataset.modified) * 1000; // Convert to milliseconds
            var startDate = self.selectedDateRange[0].getTime();
            var endDate = self.selectedDateRange[1].getTime() + (24 * 60 * 60 * 1000); // Add 1 day to include end date
            
            shouldShow = shouldShow && (itemModified >= startDate && itemModified <= endDate);
        }
        
        // Always show the item first, then apply display
        item.style.display = shouldShow ? 'block' : 'none';
    });
    
    // Get visible items for sorting
    var visibleItems = itemsArray.filter(function(item) {
        return item.style.display !== 'none';
    });
    
    // Sort visible items
    visibleItems.sort(function(a, b) {
        switch(sortBy) {
            case 'date':
                var aModified = parseInt(a.dataset.modified) || 0;
                var bModified = parseInt(b.dataset.modified) || 0;
                return bModified - aModified;
            case 'size':
                var aSize = parseInt(a.dataset.size) || 0;
                var bSize = parseInt(b.dataset.size) || 0;
                return bSize - aSize;
            case 'name':
                var nameA = a.querySelector('.threat-path').textContent.toLowerCase();
                var nameB = b.querySelector('.threat-path').textContent.toLowerCase();
                return nameA.localeCompare(nameB);
            case 'threat':
            default:
                // Threat level: very_recent > recent > medium > old
                var priorities = {'very_recent': 4, 'recent': 3, 'medium': 2, 'old': 1};
                var aPriority = priorities[a.dataset.age] || 1;
                var bPriority = priorities[b.dataset.age] || 1;
                return bPriority - aPriority;
        }
    });
    
    // Reorder DOM elements
    var container = visibleItems[0] ? visibleItems[0].parentElement : null;
    if (container) {
        // Clear container first
        var allItems = Array.from(container.querySelectorAll('.threat-item'));
        
        // Append visible items in sorted order
        visibleItems.forEach(function(item) {
            container.appendChild(item);
        });
        
        // Append hidden items at the end (maintain DOM structure)
        allItems.forEach(function(item) {
            if (item.style.display === 'none') {
                container.appendChild(item);
            }
        });
    }
    
    // Update filter info
    this.updateFilterInfo(visibleItems.length, itemsArray.length);
};

SecurityScanner.prototype.updateFilterInfo = function(visibleCount, totalCount) {
    var filterControls = document.getElementById('filterControls');
    var existingInfo = filterControls.querySelector('.filter-info');
    
    if (existingInfo) {
        existingInfo.remove();
    }
    
    if (visibleCount !== totalCount) {
        var filterInfo = document.createElement('div');
        filterInfo.className = 'filter-info';
        filterInfo.innerHTML = '<i class="fas fa-info-circle"></i> Hiển thị ' + visibleCount + '/' + totalCount + ' threats';
        filterInfo.style.cssText = 'color: var(--primary-blue); font-size: 0.75rem; font-weight: 600;';
        filterControls.appendChild(filterInfo);
    }
};

SecurityScanner.prototype.generateTooltipContent = function(issues) {
    if (!issues || issues.length === 0) return 'Không có thông tin chi tiết';
    
    var content = '<div style="text-align: left;">';
    for (var i = 0; i < issues.length; i++) {
        var issue = issues[i];
        content += '<div style="margin-bottom: 4px;">';
        content += '<strong>Dòng ' + issue.line + ':</strong> ' + issue.pattern + '<br>';
        content += '<small>' + issue.description + '</small><br>';
        content += '<code style="font-size: 0.7rem;">' + issue.code_snippet.substring(0, 50) + '...</code>';
        content += '</div>';
    }
    content += '</div>';
    
    return content.replace(/"/g, '&quot;');
};

// Editor functions removed - only show warning tooltip now

SecurityScanner.prototype.displayError = function(message) {
    var resultsPanel = document.getElementById('resultsPanel');
    var scanResults = document.getElementById('scanResults');
    
    resultsPanel.classList.add('active');
    scanResults.innerHTML = 
        '<div class="alert alert-danger">' +
            '<i class="fas fa-times-circle"></i>' +
            '<div>' +
                '<strong>Lỗi quét!</strong><br>' +
                '<small>' + message + '</small>' +
            '</div>' +
        '</div>';
    
    this.completeScan();
};

SecurityScanner.prototype.completeScan = function() {
    var self = this;
    
    clearInterval(this.speedInterval);
    clearInterval(this.fileSimulationInterval);
    clearInterval(this.progressPollingInterval); // Stop real-time polling
    clearInterval(this.filesPollingInterval); // Stop files polling
    
    setTimeout(function() {
        var scanBtn = document.getElementById('scanBtn');
        scanBtn.disabled = false;
        scanBtn.innerHTML = '<i class="fas fa-redo"></i> Quét Lại';
        document.getElementById('progressSection').classList.remove('active');
        self.isScanning = false;
        
        // Update final scanner message
        var currentAction = document.getElementById('currentAction');
        if (currentAction) {
            currentAction.innerHTML = '<i class="fas fa-check-circle" style="color: var(--success-text);"></i> ' + 
                                    'Scan hoàn tất!';
        }
    }, 1000);
};

SecurityScanner.prototype.performAction = function(action) {
    var self = this;
    
    if (!this.lastScanData || this.lastScanData.suspicious_count === 0) {
        Swal.fire({
            icon: 'info',
            title: 'Không có dữ liệu để xử lý',
            text: 'Vui lòng quét hệ thống trước khi thực hiện khắc phục!',
            confirmButtonColor: 'var(--primary-blue)'
        });
        return;
    }

    var actions = {
        'delete_critical': {
            title: 'Xóa Files Nguy Hiểm',
            text: 'Sẽ xóa ' + (this.lastScanData.critical_count || 0) + ' files nguy hiểm được phát hiện.',
            icon: 'warning',
            confirmText: 'Xóa Ngay',
            action: function() { self.performAutoFix(); }
        },
        'quarantine': {
            title: 'Cách Ly Files Đáng Ngờ',
            text: 'Di chuyển files đáng ngờ vào thư mục cách ly để kiểm tra sau.',
            icon: 'info',
            confirmText: 'Cách Ly',
            action: function() { self.showDemo('Cách ly files thành công! Files đã được di chuyển vào /quarantine/'); }
        },
        'fix_permissions': {
            title: 'Sửa Quyền Files',
            text: 'Thiết lập lại quyền truy cập an toàn cho tất cả files PHP.',
            icon: 'info',
            confirmText: 'Sửa Quyền',
            action: function() { self.showDemo('Đã thiết lập quyền 644 cho files PHP và 755 cho thư mục!'); }
        },
        'update_htaccess': {
            title: 'Cập Nhật .htaccess',
            text: 'Cập nhật rules bảo mật trong file .htaccess.',
            icon: 'info',
            confirmText: 'Cập Nhật',
            action: function() { self.showDemo('Đã cập nhật .htaccess với rules bảo mật mới!'); }
        },
        'clean_logs': {
            title: 'Dọn Dẹp Logs',
            text: 'Xóa logs cũ và tối ưu hóa hệ thống.',
            icon: 'info',
            confirmText: 'Dọn Dẹp',
            action: function() { self.showDemo('Đã dọn dẹp 15 MB logs cũ và tối ưu hệ thống!'); }
        },
        'auto_fix_all': {
            title: 'Khắc Phục Toàn Bộ',
            text: 'Thực hiện tất cả các biện pháp khắc phục tự động.',
            icon: 'warning',
            confirmText: 'Khắc Phục Tất Cả',
            action: function() { self.performAutoFix(); }
        },
        'schedule_scan': {
            title: 'Lên Lịch Quét',
            text: 'Thiết lập lịch quét tự động hàng ngày.',
            icon: 'info',
            confirmText: 'Thiết Lập',
            action: function() { self.showDemo('Đã thiết lập lịch quét tự động lúc 2:00 AM hàng ngày!'); }
        }
    };

    var actionConfig = actions[action];
    if (!actionConfig) return;

    Swal.fire({
        title: actionConfig.title,
        text: actionConfig.text,
        icon: actionConfig.icon,
        showCancelButton: true,
        confirmButtonColor: action === 'delete_critical' || action === 'auto_fix_all' ? '#E53E3E' : 'var(--primary-blue)',
        cancelButtonColor: 'var(--text-light)',
        confirmButtonText: actionConfig.confirmText,
        cancelButtonText: 'Hủy'
    }).then(function(result) {
        if (result.isConfirmed) {
            actionConfig.action();
        }
    });
};

SecurityScanner.prototype.showDemo = function(message) {
    Swal.fire({
        icon: 'success',
        title: 'Demo - Thành Công!',
        text: message,
        confirmButtonColor: 'var(--success-text)',
        timer: 3000,
        timerProgressBar: true
    });
};

SecurityScanner.prototype.deleteSingleFile = function(filePath, index) {
    var self = this;
    
    Swal.fire({
        title: 'XÓA FILE ĐỘC HẠI?',
        html: '<strong style="color: var(--danger-text);">CẢNH BÁO:</strong> Sẽ xóa vĩnh viễn file:<br><br><code style="color: var(--warning-text); background: var(--warning-bg); padding: 8px; border-radius: 4px; display: inline-block; margin: 8px 0;">' + filePath + '</code>',
        icon: 'error',
        showCancelButton: true,
        confirmButtonColor: 'var(--danger-text)',
        cancelButtonColor: 'var(--text-light)',
        confirmButtonText: 'XÓA NGAY',
        cancelButtonText: 'Hủy',
        dangerMode: true
    }).then(function(result) {
        if (result.isConfirmed) {
            self.performSingleFileDeletion(filePath, index);
        }
    });
};

SecurityScanner.prototype.performSingleFileDeletion = function(filePath, index) {
    var self = this;
    var deleteBtn = document.querySelector('button[onclick*="' + index + '"]');
    
    if (deleteBtn) {
        deleteBtn.disabled = true;
        deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Xóa...';
    }
    
    // Use XMLHttpRequest for PHP 5.6+ compatibility
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '?delete_malware=1', true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                try {
                    var data = JSON.parse(xhr.responseText);
                    
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'XÓA THÀNH CÔNG!',
                            text: 'File ' + filePath + ' đã được xóa thành công.',
                            confirmButtonColor: 'var(--success-text)'
                        }).then(function() {
                            // Remove the card from display
                            var threatItem = deleteBtn.closest('.threat-item');
                            if (threatItem) {
                                threatItem.style.transition = 'all 0.3s ease';
                                threatItem.style.opacity = '0';
                                threatItem.style.transform = 'translateX(-100%)';
                                setTimeout(function() {
                                    threatItem.remove();
                                }, 300);
                            }
                        });
                    } else {
                        throw new Error(data.error || 'Unknown error');
                    }
                } catch (e) {
                    Swal.fire({
                        icon: 'error',
                        title: 'LỖI XÓA FILE',
                        text: e.message,
                        confirmButtonColor: 'var(--danger-text)'
                    });
                }
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'LỖI XÓA FILE',
                    text: 'HTTP Error: ' + xhr.status,
                    confirmButtonColor: 'var(--danger-text)'
                });
            }
            
            if (deleteBtn) {
                deleteBtn.disabled = false;
                deleteBtn.innerHTML = '<i class="fas fa-trash-alt"></i> Xóa';
            }
        }
    };
    
    xhr.send(JSON.stringify({ malware_files: [filePath] }));
};

SecurityScanner.prototype.performAutoFix = function() {
    var self = this;
    
    if (!this.lastScanData || this.lastScanData.suspicious_count === 0) {
        Swal.fire({
            icon: 'info',
            title: 'Không có lỗi để khắc phục',
            text: 'Không phát hiện lỗi nào cần khắc phục!',
            confirmButtonColor: 'var(--primary-blue)'
        });
        return;
    }
    
    var fixDropdown = document.getElementById('fixDropdown');
    fixDropdown.disabled = true;
    fixDropdown.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang Khắc Phục...';
    
    // Use XMLHttpRequest for PHP 5.6+ compatibility
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '?autofix=1', true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                try {
                    var data = JSON.parse(xhr.responseText);
                    
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Khắc Phục Thành Công!',
                            html: '<div style="text-align: left; font-size: 0.9rem;">' +
                                  '<strong>Files đã sửa:</strong> ' + data.fixed_files + '<br>' +
                                  '<strong>Files độc hại đã xóa:</strong> ' + (data.deleted_files || 0) + '<br>' +
                                  '<strong>Lỗi đã khắc phục:</strong> ' + data.fixes_applied + '<br>' +
                                  '<strong>Backup:</strong> ' + (data.backup_created ? '✅ Đã tạo' : '❌ Không có') +
                                  '</div>',
                            confirmButtonColor: 'var(--success-text)'
                        }).then(function() {
                            // Auto scan lại sau khi fix
                            self.startScan();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Lỗi Khắc Phục',
                            text: data.error || 'Unknown error',
                            confirmButtonColor: 'var(--danger-text)'
                        });
                    }
                } catch (e) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi Khắc Phục',
                        text: e.message,
                        confirmButtonColor: 'var(--danger-text)'
                    });
                }
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi Khắc Phục',
                    text: 'HTTP Error: ' + xhr.status,
                    confirmButtonColor: 'var(--danger-text)'
                });
            }
            
            fixDropdown.disabled = false;
            fixDropdown.innerHTML = '<i class="fas fa-tools"></i> Khắc Phục';
        }
    };
    
    xhr.send(JSON.stringify(this.lastScanData));
};

// Quick fix functions removed

// Initialize scanner when page loads
var scanner;
document.addEventListener('DOMContentLoaded', function() {
    scanner = new SecurityScanner();
    
    // Make scanner globally accessible
    window.scanner = scanner;
});