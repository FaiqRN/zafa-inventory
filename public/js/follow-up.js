/**
 * Follow Up Pelanggan JavaScript Module - FIXED VERSION
 * Zafa Potato CRM System - WhatsApp Integration Ready
 * FIXED: Proper error handling, device status checking, and broadcast functionality
 */

// Global Variables
let selectedFilters = [];
let uploadedImages = [];
let filteredCustomers = [];
let isLoadingCustomers = false;
let deviceStatus = null;
let deviceCheckInterval = null;

// Document Ready
$(document).ready(function() {
    initializeFollowUp();
});

/**
 * Initialize Follow Up Module - FIXED
 */
function initializeFollowUp() {
    console.log('🚀 Initializing Follow Up Pelanggan module (FIXED VERSION)');
    
    // Check WhatsApp device status first (CRITICAL)
    checkDeviceStatus();
    
    // Load initial data
    loadRiwayatData();
    
    // Setup event listeners
    setupEventListeners();
    
    // Setup image upload functionality
    setupImageUpload();
    
    // Setup periodic device status check (CRITICAL)
    deviceCheckInterval = setInterval(checkDeviceStatus, 30000); // Check every 30 seconds
    
    console.log('✅ Follow Up Pelanggan module initialized (FIXED VERSION)');
}

/**
 * FIXED: Check WhatsApp Device Status with better error handling
 */
function checkDeviceStatus() {
    $.ajax({
        url: '/follow-up-pelanggan/device-status',
        type: 'GET',
        timeout: 15000,
        success: function(response) {
            if (response && response.status === 'success') {
                deviceStatus = response.data;
                updateDeviceStatusUI(deviceStatus);
                console.log('✅ Device status updated:', deviceStatus);
            } else {
                console.warn('⚠️ Invalid device status response:', response);
                deviceStatus = { isConnected: false, message: 'Invalid response' };
                updateDeviceStatusUI(deviceStatus);
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ Failed to check device status:', error);
            
            let errorMessage = 'Connection check failed';
            if (xhr.status === 500) {
                errorMessage = 'Server error - check WhatsApp configuration';
            } else if (xhr.status === 404) {
                errorMessage = 'Endpoint not found';
            } else if (status === 'timeout') {
                errorMessage = 'Request timeout';
            }
            
            deviceStatus = { 
                isConnected: false, 
                message: errorMessage 
            };
            updateDeviceStatusUI(deviceStatus);
        }
    });
}

/**
 * FIXED: Update Device Status UI with better visual feedback
 */
function updateDeviceStatusUI(status) {
    const statusIndicator = $('#deviceStatusIndicator');
    const sendButton = $('#sendMassFollowUpBtn');
    const testButton = $('#testConnectionBtn');
    
    // Create status indicator if it doesn't exist
    if (statusIndicator.length === 0) {
        $('.card-title:contains("Follow Up Pelanggan")').parent().append(`
            <div id="deviceStatusIndicator" class="ml-auto">
                <small id="deviceStatusText" class="text-muted">Checking...</small>
            </div>
        `);
    }
    
    if (status.isConnected) {
        $('#deviceStatusIndicator').html(`
            <div class="d-flex align-items-center">
                <div class="bg-success rounded-circle mr-2 pulse" style="width: 8px; height: 8px;"></div>
                <small class="text-success font-weight-bold">WhatsApp Connected</small>
            </div>
        `);
        
        // Enable functionality
        if (sendButton.length) {
            sendButton.prop('title', '');
            sendButton.removeClass('btn-secondary').addClass('btn-success');
        }
        
        if (testButton.length) {
            testButton.prop('disabled', false);
            testButton.removeClass('btn-outline-secondary').addClass('btn-outline-success');
        }
        
        console.log('✅ Device connected successfully');
    } else {
        $('#deviceStatusIndicator').html(`
            <div class="d-flex align-items-center">
                <div class="bg-danger rounded-circle mr-2" style="width: 8px; height: 8px;"></div>
                <small class="text-danger font-weight-bold">WhatsApp Disconnected</small>
                <button class="btn btn-xs btn-outline-warning ml-2" onclick="showDeviceHelp()" title="Help">
                    <i class="fas fa-question"></i>
                </button>
            </div>
        `);
        
        // Show warning on send button
        if (sendButton.length) {
            sendButton.prop('title', 'WhatsApp device tidak terhubung - klik untuk bantuan');
            sendButton.addClass('btn-secondary').removeClass('btn-success');
        }
        
        if (testButton.length) {
            testButton.prop('disabled', false); // Allow testing even when disconnected
            testButton.removeClass('btn-outline-success').addClass('btn-outline-secondary');
        }
        
        console.warn('⚠️ Device disconnected:', status.message);
    }
    
    updateSendButton();
}

/**
 * NEW: Show device connection help
 */
function showDeviceHelp() {
    Swal.fire({
        title: '📱 WhatsApp Device Help',
        html: `
            <div class="text-left">
                <h6><i class="fas fa-exclamation-triangle text-warning mr-2"></i>Device Tidak Terhubung</h6>
                <p class="mb-3">WhatsApp device Anda belum terhubung. Ikuti langkah berikut:</p>
                
                <ol class="text-left">
                    <li><strong>Buka Texas Wablas Dashboard</strong></li>
                    <li><strong>Pilih device Anda</strong></li>
                    <li><strong>Scan QR Code dengan WhatsApp</strong></li>
                    <li><strong>Tunggu status berubah menjadi "Connected"</strong></li>
                </ol>
                
                <div class="alert alert-info mt-3">
                    <i class="fas fa-info-circle mr-2"></i>
                    <strong>Tips:</strong> Pastikan WhatsApp di HP Anda aktif dan tersambung internet
                </div>
            </div>
        `,
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-test-tube mr-1"></i>Test Connection',
        cancelButtonText: 'Tutup',
        confirmButtonColor: '#28a745'
    }).then((result) => {
        if (result.isConfirmed) {
            testWhatsAppConnection();
        }
    });
}

/**
 * Setup Event Listeners - FIXED
 */
function setupEventListeners() {
    // Filter checkbox change handler
    $('.filter-checkbox').off('change').on('change', function(e) {
        e.stopPropagation();
        updateFilters();
        loadFilteredCustomers();
    });
    
    // Character count for message
    $('#followUpMessage').off('input').on('input', function() {
        const length = $(this).val().length;
        $('#charCount').text(length);
        
        if (length > 1000) {
            $(this).addClass('is-invalid');
        } else {
            $(this).removeClass('is-invalid');
        }
        
        updateSendButton();
    });
    
    // FIXED: Form submission with better validation
    $('#massFollowUpForm').off('submit').on('submit', function(e) {
        e.preventDefault();
        
        // CRITICAL: Check device status before proceeding
        if (!deviceStatus || !deviceStatus.isConnected) {
            Swal.fire({
                title: '❌ WhatsApp Tidak Terhubung',
                html: `
                    <div class="text-center">
                        <p class="mb-3">Device WhatsApp tidak terhubung. Pesan tidak dapat dikirim.</p>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            Status: <strong>${deviceStatus?.message || 'Unknown'}</strong>
                        </div>
                    </div>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-wifi mr-1"></i>Test Connection',
                cancelButtonText: 'Tutup',
                confirmButtonColor: '#28a745'
            }).then((result) => {
                if (result.isConfirmed) {
                    testWhatsAppConnection();
                }
            });
            return;
        }
        
        // Validate customers
        if (!filteredCustomers || filteredCustomers.length === 0) {
            Swal.fire('❌ Error', 'Tidak ada customer yang dipilih', 'error');
            return;
        }
        
        // Validate message or images
        const message = $('#followUpMessage').val().trim();
        if (!message && uploadedImages.length === 0) {
            Swal.fire('❌ Error', 'Pesan atau gambar harus diisi minimal salah satu', 'error');
            return;
        }
        
        $('#previewModal').modal('show');
        showPreview();
    });
    
    // Preview button
    $('#previewBtn').off('click').on('click', function(e) {
        e.preventDefault();
        
        if (!deviceStatus || !deviceStatus.isConnected) {
            Swal.fire('⚠️ Warning', 'WhatsApp device tidak terhubung', 'warning');
            return;
        }
        
        $('#previewModal').modal('show');
        showPreview();
    });
    
    // FIXED: Confirm send button with better error handling
    $('#confirmSendBtn').off('click').on('click', function(e) {
        e.preventDefault();
        sendMassFollowUp();
    });
    
    // Refresh riwayat
    $('#refreshRiwayatBtn').off('click').on('click', function(e) {
        e.preventDefault();
        loadRiwayatData();
    });

    // Search input with debounce
    $('#searchCustomer').off('input').on('input', debounce(function() {
        if (selectedFilters.length > 0) {
            loadFilteredCustomers();
        }
    }, 500));
    
    // FIXED: Test connection button
    $('#testConnectionBtn').off('click').on('click', function(e) {
        e.preventDefault();
        testWhatsAppConnection();
    });
}

/**
 * FIXED: Test WhatsApp Connection with comprehensive feedback
 */
function testWhatsAppConnection() {
    Swal.fire({
        title: '🧪 Testing WhatsApp Connection...',
        html: `
            <div class="text-center">
                <p>Mengirim pesan test ke nomor admin</p>
                <div class="spinner-border text-primary mt-3" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                <p class="text-muted mt-3"><small>Proses ini memerlukan waktu 10-30 detik</small></p>
            </div>
        `,
        allowOutsideClick: false,
        showConfirmButton: false
    });
    
    $.ajax({
        url: '/follow-up-pelanggan/test-connection',
        type: 'POST',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        timeout: 45000, // Increased timeout
        success: function(response) {
            if (response && response.status === 'success') {
                Swal.fire({
                    title: '✅ Test Berhasil!',
                    html: `
                        <div class="text-center">
                            <p class="text-success mb-3">${response.message}</p>
                            <div class="alert alert-success">
                                <i class="fab fa-whatsapp mr-2"></i>
                                WhatsApp connection is working properly!
                            </div>
                        </div>
                    `,
                    icon: 'success',
                    timer: 5000,
                    timerProgressBar: true
                });
                
                // Refresh device status after successful test
                setTimeout(() => {
                    checkDeviceStatus();
                }, 2000);
            } else {
                Swal.fire({
                    title: '❌ Test Gagal',
                    html: `
                        <div class="text-center">
                            <p class="text-danger mb-3">${response.message || 'Test koneksi gagal'}</p>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                Periksa konfigurasi WhatsApp Anda
                            </div>
                        </div>
                    `,
                    icon: 'error'
                });
            }
        },
        error: function(xhr, status, error) {
            let errorMessage = 'Test koneksi gagal';
            
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            } else if (status === 'timeout') {
                errorMessage = 'Request timeout - pastikan token Wablas valid dan device terhubung';
            } else if (xhr.status === 500) {
                errorMessage = 'Server error - periksa konfigurasi .env file';
            } else if (xhr.status === 404) {
                errorMessage = 'Endpoint tidak ditemukan - periksa route';
            }
            
            Swal.fire({
                title: '❌ Connection Test Failed',
                html: `
                    <div class="text-left">
                        <p class="text-danger mb-3"><strong>Error:</strong> ${errorMessage}</p>
                        
                        <h6>🔧 Troubleshooting Steps:</h6>
                        <ol class="text-left">
                            <li>Check your .env file configuration</li>
                            <li>Verify WABLAS_TOKEN and WABLAS_SECRET_KEY</li>
                            <li>Make sure device is connected in Texas Wablas</li>
                            <li>Check server logs for detailed errors</li>
                        </ol>
                        
                        <div class="alert alert-info mt-3">
                            <strong>Debug Command:</strong><br>
                            <code>php artisan whatsapp:debug --test-send</code>
                        </div>
                    </div>
                `,
                icon: 'error',
                width: '600px'
            });
            
            console.error('❌ Test connection failed:', {
                status: xhr.status,
                statusText: xhr.statusText,
                error: error,
                response: xhr.responseText
            });
        }
    });
}

/**
 * Update Filter System - FIXED
 */
function updateFilters() {
    selectedFilters = [];
    $('.filter-checkbox:checked').each(function() {
        selectedFilters.push($(this).val());
    });
    
    if (selectedFilters.length > 0) {
        $('#filterSummary').show().addClass('fade-in');
        $('#filterList').text(selectedFilters.join(', '));
        updateSendButton();
    } else {
        $('#filterSummary').hide();
        $('#customerList').empty();
        $('#defaultCustomerState').show();
        $('#noCustomerData').hide();
        $('#customerListCount').text('0 customer');
        updateSendButton();
    }
}

/**
 * FIXED: Load Filtered Customers with better error handling
 */
function loadFilteredCustomers() {
    if (selectedFilters.length === 0) {
        filteredCustomers = [];
        $('#customerList').empty();
        $('#defaultCustomerState').show();
        $('#noCustomerData').hide();
        $('#customerListCount').text('0 customer');
        $('#customerCount').text('0');
        updateSendButton();
        return;
    }

    if (isLoadingCustomers) {
        return;
    }
    
    isLoadingCustomers = true;
    
    showLoadingState('#customerList');
    $('#defaultCustomerState').hide();
    $('#noCustomerData').hide();
    
    $.ajax({
        url: '/follow-up-pelanggan/filtered-customers',
        type: 'GET',
        data: {
            filters: selectedFilters,
            search: $('#searchCustomer').val() || ''
        },
        timeout: 45000, // Increased timeout
        success: function(response) {
            hideLoadingState('#customerList');
            isLoadingCustomers = false;
            
            if (response && response.status === 'success') {
                filteredCustomers = response.data || [];
                displayCustomers(filteredCustomers);
                console.log(`✅ Loaded ${filteredCustomers.length} customers`);
            } else {
                console.error('❌ Error loading customers:', response);
                showErrorState('Gagal memuat data customer: ' + (response.message || 'Response tidak valid'));
            }
        },
        error: function(xhr, status, error) {
            hideLoadingState('#customerList');
            isLoadingCustomers = false;
            
            let errorMessage = 'Terjadi kesalahan saat memuat data customer';
            
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            } else if (status === 'timeout') {
                errorMessage = 'Request timeout - server terlalu lama merespons';
            } else if (xhr.status === 404) {
                errorMessage = 'Endpoint tidak ditemukan (404)';
            } else if (xhr.status === 500) {
                errorMessage = 'Server error (500) - silakan cek log server';
            }
            
            console.error('❌ AJAX Error:', {
                status: xhr.status,
                statusText: xhr.statusText,
                error: error,
                response: xhr.responseText
            });
            
            showErrorState(errorMessage);
        }
    });
}

/**
 * Display Customers in List - FIXED
 */
function displayCustomers(customers) {
    const customerList = $('#customerList');
    const defaultState = $('#defaultCustomerState');
    const noDataState = $('#noCustomerData');
    
    defaultState.hide();
    
    if (!customers || customers.length === 0) {
        customerList.empty();
        noDataState.show();
        $('#customerListCount').text('0 customer');
        $('#customerCount').text('0');
        return;
    }
    
    noDataState.hide();
    customerList.empty();
    
    customers.forEach((customer, index) => {
        try {
            const customerTypeLabel = getCustomerTypeLabel(customer.customerType || 'keseluruhan');
            const customerTypeBadge = getCustomerTypeBadge(customer.customerType || 'keseluruhan');
            const initial = customer.initial || getInitialFromName(customer.name || 'Unknown');
            
            // FIXED: Better validation for phone numbers
            const phoneDisplay = customer.phone ? 
                (customer.phone.startsWith('62') ? customer.phone : 'Invalid: ' + customer.phone) : 
                'No phone';
            
            const customerItem = `
                <div class="customer-item p-3 border-bottom slide-in" data-customer-type="${customer.customerType || 'keseluruhan'}" data-customer-index="${index}">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center flex-grow-1">
                            <div class="customer-initial-small">
                                <span class="initial-text-small">${initial}</span>
                            </div>
                            <div>
                                <h6 class="mb-1 font-weight-bold">${customer.name || 'Unknown'}</h6>
                                <p class="mb-1 text-muted small">${phoneDisplay}</p>
                                <div>
                                    <span class="badge ${customerTypeBadge} customer-badge mr-1">${customerTypeLabel}</span>
                                    <span class="badge badge-info customer-badge">${customer.orderSource || 'unknown'}</span>
                                </div>
                            </div>
                        </div>
                        <div class="text-right">
                            <small class="text-muted d-block">${customer.totalOrders || 0} pesanan</small>
                            <small class="text-success d-block font-weight-bold">${customer.totalSpent || 'Rp 0'}</small>
                            <button type="button" class="btn detail-btn btn-sm mt-1" onclick="showCustomerDetail(${index})">
                                Detail
                            </button>
                        </div>
                    </div>
                </div>
            `;
            customerList.append(customerItem);
        } catch (error) {
            console.error('❌ Error rendering customer:', customer, error);
        }
    });
    
    $('#customerListCount').text(`${customers.length} customer`);
    $('#customerCount').text(customers.length);
    updateSendButton();
}

/**
 * FIXED: Update Send Button State with comprehensive validation
 */
function updateSendButton() {
    const hasMessage = $('#followUpMessage').val().trim().length > 0;
    const hasImages = uploadedImages.length > 0;
    const hasTargets = filteredCustomers.length > 0;
    const isValidMessage = $('#followUpMessage').val().length <= 1000;
    const isDeviceConnected = deviceStatus && deviceStatus.isConnected;
    
    const canSend = (hasMessage || hasImages) && hasTargets && isValidMessage && isDeviceConnected;
    
    const sendButton = $('#sendMassFollowUpBtn');
    sendButton.prop('disabled', !canSend);
    
    // FIXED: Update button text and styling based on various states
    if (!isDeviceConnected) {
        sendButton.addClass('btn-secondary').removeClass('btn-success');
        sendButton.html('<i class="fas fa-exclamation-triangle mr-2"></i>Device Disconnected');
    } else if (!hasTargets) {
        sendButton.addClass('btn-secondary').removeClass('btn-success');
        sendButton.html('<i class="fas fa-users mr-2"></i>Pilih Customer Dulu');
    } else if (!hasMessage && !hasImages) {
        sendButton.addClass('btn-secondary').removeClass('btn-success');
        sendButton.html('<i class="fas fa-edit mr-2"></i>Tulis Pesan/Upload Gambar');
    } else if (!isValidMessage) {
        sendButton.addClass('btn-danger').removeClass('btn-success btn-secondary');
        sendButton.html('<i class="fas fa-exclamation-triangle mr-2"></i>Pesan Terlalu Panjang');
    } else {
        sendButton.removeClass('btn-secondary btn-danger').addClass('btn-success');
        sendButton.html(`<i class="fab fa-whatsapp mr-2"></i>Kirim ke <span id="targetCount">${filteredCustomers.length}</span> Customer`);
    }
    
    $('#targetCount').text(filteredCustomers.length);
}

/**
 * Show Preview Modal - FIXED
 */
function showPreview() {
    $('#previewTargetCount').text(filteredCustomers.length);
    
    const previewImages = $('#previewImages');
    previewImages.empty();
    
    if (uploadedImages.length > 0) {
        uploadedImages.forEach(image => {
            previewImages.append(`
                <img src="${image.data}" alt="${image.name}" style="width: 100px; height: 100px; object-fit: cover; margin: 5px;" class="img-thumbnail">
            `);
        });
    }
    
    const message = $('#followUpMessage').val() || '<em class="text-muted">Tidak ada pesan teks</em>';
    $('#previewMessage').html(message.replace(/\n/g, '<br>'));
    
    // FIXED: Update confirm button based on device status
    const confirmBtn = $('#confirmSendBtn');
    if (deviceStatus && deviceStatus.isConnected) {
        confirmBtn.prop('disabled', false);
        confirmBtn.removeClass('btn-secondary').addClass('btn-success');
        confirmBtn.html('<i class="fab fa-whatsapp mr-1"></i>Kirim WhatsApp Sekarang');
    } else {
        confirmBtn.prop('disabled', true);
        confirmBtn.addClass('btn-secondary').removeClass('btn-success');
        confirmBtn.html('<i class="fas fa-exclamation-triangle mr-1"></i>Device Disconnected');
    }
}

/**
 * FIXED: Send Mass Follow Up with comprehensive error handling and progress tracking
 */
function sendMassFollowUp() {
    const message = $('#followUpMessage').val();
    const targetType = determineTargetType();
    
    // CRITICAL: Final validation before sending
    if (filteredCustomers.length === 0) {
        showErrorMessage('Tidak ada customer yang dipilih');
        return;
    }
    
    if (!message.trim() && uploadedImages.length === 0) {
        showErrorMessage('Pesan atau gambar harus diisi minimal salah satu');
        return;
    }
    
    // CRITICAL: Final device status check
    if (!deviceStatus || !deviceStatus.isConnected) {
        showErrorMessage('WhatsApp device tidak terhubung. Pesan tidak dapat dikirim.');
        return;
    }
    
    const formData = new FormData();
    formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
    formData.append('message', message);
    formData.append('target_type', targetType);
    formData.append('customers', JSON.stringify(filteredCustomers));
    
    uploadedImages.forEach((imageData, index) => {
        formData.append(`images[${index}]`, imageData.file);
    });
    
    // FIXED: Show loading with realistic time estimation
    const estimatedTime = Math.ceil(filteredCustomers.length * 3.5); // 3.5 seconds per customer (more realistic)
    const estimatedMinutes = Math.ceil(estimatedTime / 60);
    
    Swal.fire({
        title: '📤 Mengirim Follow Up via WhatsApp...',
        html: `
            <div class="text-center">
                <div class="mb-3">
                    <i class="fab fa-whatsapp fa-3x text-success mb-3"></i>
                    <p>Mengirim ke <strong>${filteredCustomers.length}</strong> customer</p>
                </div>
                <div class="progress mb-3">
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 0%"></div>
                </div>
                <div class="alert alert-info">
                    <small>
                        <i class="fas fa-clock mr-1"></i>
                        Estimasi waktu: ~${estimatedMinutes} menit<br>
                        <i class="fas fa-info-circle mr-1"></i>
                        Jangan tutup halaman ini selama proses berlangsung
                    </small>
                </div>
            </div>
        `,
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
            
            // FIXED: More realistic progress simulation
            let progress = 0;
            const progressInterval = setInterval(() => {
                progress += 2;
                if (progress <= 85) {
                    $('.progress-bar').css('width', progress + '%');
                } else {
                    clearInterval(progressInterval);
                }
            }, (estimatedTime * 1000) / 42); // Smoother progress
        }
    });
    
    $.ajax({
        url: '/follow-up-pelanggan/send',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        timeout: (estimatedTime + 60) * 1000, // Add 60 seconds buffer
        xhr: function() {
            // FIXED: Add upload progress tracking for images
            var xhr = new window.XMLHttpRequest();
            xhr.upload.addEventListener("progress", function(evt) {
                if (evt.lengthComputable) {
                    var percentComplete = (evt.loaded / evt.total) * 20; // Upload is 20% of total progress
                    $('.progress-bar').css('width', Math.min(percentComplete, 20) + '%');
                }
            }, false);
            return xhr;
        },
        success: function(response) {
            $('.progress-bar').css('width', '100%');
            
            if (response && response.status === 'success') {
                Swal.fire({
                    title: '🎉 Follow Up Berhasil Dikirim!',
                    html: `
                        <div class="text-center">
                            <div class="mb-4">
                                <i class="fab fa-whatsapp fa-4x text-success mb-3"></i>
                                <p class="text-success font-weight-bold">${response.message}</p>
                            </div>
                            
                            <div class="row text-center">
                                <div class="col-4">
                                    <div class="border rounded p-3 bg-light">
                                        <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                                        <h4 class="text-success mb-0">${response.summary.success}</h4>
                                        <small class="text-muted">Berhasil Dikirim</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="border rounded p-3 bg-light">
                                        <i class="fas fa-times-circle fa-2x text-danger mb-2"></i>
                                        <h4 class="text-danger mb-0">${response.summary.failed}</h4>
                                        <small class="text-muted">Gagal Dikirim</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="border rounded p-3 bg-light">
                                        <i class="fas fa-users fa-2x text-info mb-2"></i>
                                        <h4 class="text-info mb-0">${response.summary.total}</h4>
                                        <small class="text-muted">Total Customer</small>
                                    </div>
                                </div>
                            </div>
                            
                            ${response.summary.success > 0 ? `
                                <div class="alert alert-success mt-4">
                                    <i class="fab fa-whatsapp mr-2"></i>
                                    <strong>WhatsApp berhasil dikirim!</strong><br>
                                    Customer akan menerima pesan dalam beberapa detik.
                                </div>
                            ` : ''}
                            
                            ${response.summary.failed > 0 ? `
                                <div class="alert alert-warning mt-3">
                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                    Ada ${response.summary.failed} pesan yang gagal dikirim
                                </div>
                            ` : ''}
                        </div>
                    `,
                    icon: response.summary.success > 0 ? 'success' : 'warning',
                    confirmButtonText: 'OK',
                    showCancelButton: response.summary.failed > 0,
                    cancelButtonText: response.summary.failed > 0 ? 'Lihat Detail Error' : '',
                    cancelButtonColor: '#6c757d',
                    width: '700px'
                }).then((result) => {
                    if (result.dismiss === Swal.DismissReason.cancel && response.summary.failed > 0) {
                        showSendResults(response.results);
                    }
                });
                
                resetForm();
                loadRiwayatData();
                $('#previewModal').modal('hide');
            } else {
                Swal.fire({
                    title: '❌ Error Pengiriman',
                    text: response.message || 'Terjadi kesalahan',
                    icon: 'error'
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ Send follow up error:', {
                status: xhr.status,
                statusText: xhr.statusText,
                error: error,
                response: xhr.responseText
            });
            
            let errorMessage = 'Terjadi kesalahan saat mengirim follow up';
            let errorDetails = '';
            
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            } else if (status === 'timeout') {
                errorMessage = 'Request timeout - pengiriman memerlukan waktu lama untuk banyak customer';
                errorDetails = 'Coba kurangi jumlah customer atau periksa koneksi internet Anda.';
            } else if (xhr.status === 422) {
                errorMessage = 'Data tidak valid - periksa input Anda';
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    const errors = Object.values(xhr.responseJSON.errors).flat();
                    errorDetails = errors.join('<br>');
                }
            } else if (xhr.status === 500) {
                errorMessage = 'Server error - cek koneksi WhatsApp atau token Wablas';
                errorDetails = 'Periksa konfigurasi .env file dan pastikan device WhatsApp terhubung.';
            }
            
            Swal.fire({
                title: '❌ Error Pengiriman WhatsApp',
                html: `
                    <div class="text-left">
                        <p class="text-danger mb-3"><strong>${errorMessage}</strong></p>
                        ${errorDetails ? `<div class="alert alert-info"><small>${errorDetails}</small></div>` : ''}
                        
                        <h6 class="mt-4">🔧 Troubleshooting:</h6>
                        <ul class="text-left">
                            <li>Periksa koneksi WhatsApp device</li>
                            <li>Validasi token dan secret key Wablas</li>
                            <li>Coba kirim ke customer lebih sedikit</li>
                            <li>Periksa log server untuk detail error</li>
                        </ul>
                    </div>
                `,
                icon: 'error',
                confirmButtonText: 'OK',
                width: '600px',
                footer: `
                    <small class="text-muted">
                        💡 Tips: Gunakan command <code>php artisan whatsapp:debug</code> untuk troubleshooting
                    </small>
                `
            });
        }
    });
}

/**
 * FIXED: Show detailed send results with better formatting
 */
function showSendResults(results) {
    if (!results || results.length === 0) {
        showErrorMessage('Tidak ada detail hasil untuk ditampilkan');
        return;
    }
    
    let resultHtml = '<div class="table-responsive" style="max-height: 400px; overflow-y: auto;"><table class="table table-sm table-striped">';
    resultHtml += '<thead class="thead-light sticky-top"><tr><th>Customer</th><th>Phone</th><th>Status</th><th>Keterangan</th></tr></thead><tbody>';
    
    results.forEach(result => {
        const statusBadge = result.status === 'success' ? 'badge-success' : 'badge-danger';
        const statusIcon = result.status === 'success' ? 'fa-check' : 'fa-times';
        const statusText = result.status === 'success' ? 'Berhasil' : 'Gagal';
        const keterangan = result.status === 'success' ? 
            (result.message_id ? `WhatsApp ID: ${result.message_id}` : 'Terkirim ke WhatsApp') : 
            (result.error || 'Error tidak diketahui');
        
        resultHtml += `
            <tr>
                <td><strong>${result.customer}</strong></td>
                <td><small class="text-muted">${result.phone}</small></td>
                <td><span class="badge ${statusBadge}"><i class="fas ${statusIcon} mr-1"></i>${statusText}</span></td>
                <td><small>${keterangan}</small></td>
            </tr>
        `;
    });
    
    resultHtml += '</tbody></table></div>';
    
    const successCount = results.filter(r => r.status === 'success').length;
    const failedCount = results.filter(r => r.status === 'failed').length;
    
    Swal.fire({
        title: `📊 Detail Hasil Pengiriman WhatsApp`,
        html: `
            <div class="mb-3 text-center">
                <span class="badge badge-success mr-2">✅ ${successCount} Berhasil</span>
                <span class="badge badge-danger">❌ ${failedCount} Gagal</span>
            </div>
            ${resultHtml}
        `,
        width: '90%',
        confirmButtonText: 'Tutup',
        customClass: {
            popup: 'swal-wide'
        }
    });
}

/**
 * Reset Form After Send - FIXED
 */
function resetForm() {
    $('#followUpMessage').val('');
    $('#charCount').text('0');
    uploadedImages = [];
    $('#imagePreviewArea').hide();
    $('#imagePreviewContainer').empty();
    $('.filter-checkbox').prop('checked', false);
    updateFilters();
    
    console.log('✅ Form reset successfully');
}

// FIXED: Setup Image Upload Functionality
function setupImageUpload() {
    const uploadArea = $('#uploadArea');
    const imageInput = $('#imageInput');
    
    uploadArea.off('dragover dragleave drop click');
    imageInput.off('change');
    
    uploadArea.on('dragover', function(e) {
        e.preventDefault();
        $(this).addClass('dragover');
    });
    
    uploadArea.on('dragleave', function(e) {
        e.preventDefault();
        $(this).removeClass('dragover');
    });
    
    uploadArea.on('drop', function(e) {
        e.preventDefault();
        $(this).removeClass('dragover');
        
        const files = e.originalEvent.dataTransfer.files;
        handleImageFiles(files);
    });
    
    uploadArea.on('click', function() {
        imageInput.click();
    });
    
    imageInput.on('change', function() {
        handleImageFiles(this.files);
    });
}

/**
 * FIXED: Handle Image Files Upload with better validation
 */
function handleImageFiles(files) {
    Array.from(files).forEach(file => {
        if (file.type.startsWith('image/')) {
            if (file.size > 5 * 1024 * 1024) { // 5MB limit
                showErrorMessage(`File ${file.name} terlalu besar. Maksimal 5MB.`);
                return;
            }
            
            // Check total images limit
            if (uploadedImages.length >= 5) {
                showErrorMessage('Maksimal 5 gambar per broadcast');
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                addImagePreview(e.target.result, file.name, file);
                uploadedImages.push({
                    file: file,
                    data: e.target.result,
                    name: file.name,
                    size: file.size
                });
                updateSendButton();
                console.log(`✅ Image added: ${file.name} (${formatFileSize(file.size)})`);
            };
            reader.readAsDataURL(file);
        } else {
            showErrorMessage(`File ${file.name} bukan format gambar yang valid.`);
        }
    });
}

/**
 * Add Image Preview - FIXED
 */
function addImagePreview(src, name, file) {
    const previewArea = $('#imagePreviewArea');
    const previewContainer = $('#imagePreviewContainer');
    
    const imageIndex = uploadedImages.length;
    const imagePreview = `
        <div class="image-preview-container fade-in" data-index="${imageIndex}">
            <img src="${src}" alt="${name}" style="width: 100px; height: 100px; object-fit: cover;" class="img-thumbnail" onclick="showFullImage('${src}')">
            <button type="button" class="remove-image-btn" onclick="removeImage(${imageIndex})" title="Hapus gambar">
                <i class="fas fa-times"></i>
            </button>
            <div class="text-center mt-1">
                <small class="text-muted">${name}</small>
                <br>
                <small class="text-muted">${formatFileSize(file.size)}</small>
            </div>
        </div>
    `;
    
    previewContainer.append(imagePreview);
    previewArea.show();
}

/**
 * Remove Image from Preview - FIXED
 */
function removeImage(index) {
    uploadedImages.splice(index, 1);
    $(`.image-preview-container[data-index="${index}"]`).remove();
    
    if (uploadedImages.length === 0) {
        $('#imagePreviewArea').hide();
    }
    
    // Re-index remaining images
    $('.image-preview-container').each(function(i) {
        $(this).attr('data-index', i);
        $(this).find('.remove-image-btn').attr('onclick', `removeImage(${i})`);
    });
    
    updateSendButton();
    console.log(`✅ Image removed, ${uploadedImages.length} images remaining`);
}

/**
 * Show Full Image in Modal
 */
function showFullImage(src) {
    $('#fullImageView').attr('src', src);
    $('#imageFullModal').modal('show');
}

/**
 * Show Customer Detail Modal
 */
function showCustomerDetail(customerIndex) {
    try {
        const customer = filteredCustomers[customerIndex];
        if (!customer) {
            console.error('❌ Customer not found at index:', customerIndex);
            return;
        }
        
        $('#modalCustomerInitial').text(customer.initial || getInitialFromName(customer.name || 'Unknown'));
        $('#modalCustomerName').text(customer.name || 'Unknown');
        $('#modalCustomerPhone').text(customer.phone || '-');
        $('#modalCustomerEmail').text(customer.email || '-');
        $('#modalCustomerAddress').text(customer.address || '-');
        $('#modalCustomerLastOrder').text(customer.lastOrder || '-');
        $('#modalCustomerTotalOrders').text((customer.totalOrders || 0) + ' pesanan');
        $('#modalCustomerTotalSpent').text(customer.totalSpent || 'Rp 0');
        $('#modalCustomerLastProduct').text(customer.lastProduct || '-');
        $('#modalCustomerNotes').text(customer.notes || '-');
        
        const customerTypeLabel = getCustomerTypeLabel(customer.customerType || 'keseluruhan');
        const customerTypeBadge = getCustomerTypeBadge(customer.customerType || 'keseluruhan');
        
        const badges = `
            <span class="badge ${customerTypeBadge} mb-1">${customerTypeLabel}</span><br>
            <span class="badge badge-info">${customer.orderSource || 'unknown'}</span>
        `;
        $('#modalCustomerBadges').html(badges);
        
        $('#customerDetailModal').modal('show');
    } catch (error) {
        console.error('❌ Error showing customer detail:', error);
        showErrorMessage('Terjadi kesalahan saat menampilkan detail customer');
    }
}

/**
 * Load Riwayat Data from Database - FIXED
 */
function loadRiwayatData() {
    showLoadingState('#riwayatTableBody');
    
    $.ajax({
        url: '/follow-up-pelanggan/history',
        type: 'GET',
        timeout: 20000, // Increased timeout
        success: function(response) {
            hideLoadingState('#riwayatTableBody');
            
            if (response && response.status === 'success') {
                displayRiwayat(response.data || []);
                console.log(`✅ Loaded ${response.data?.length || 0} history records`);
            } else {
                console.error('❌ Error loading history:', response);
                displayRiwayat([]);
            }
        },
        error: function(xhr, status, error) {
            hideLoadingState('#riwayatTableBody');
            console.error('❌ AJAX Error loading history:', error);
            displayRiwayat([]);
        }
    });
}

/**
 * Display Riwayat in Table - FIXED
 */
function displayRiwayat(data) {
    const tableBody = $('#riwayatTableBody');
    const noDataMessage = $('#noRiwayatMessage');
    
    if (!data || data.length === 0) {
        tableBody.empty();
        noDataMessage.show();
        return;
    }
    
    noDataMessage.hide();
    tableBody.empty();
    
    data.forEach(item => {
        const imageHtml = item.gambar ? 
            `<img src="${item.gambar}" alt="Gambar" style="width: 40px; height: 40px; object-fit: cover;" class="img-thumbnail" onclick="showFullImage('${item.gambar}')">` : 
            '<span class="text-muted">-</span>';
        
        const row = `
            <tr class="slide-in">
                <td class="font-weight-bold text-primary">${item.id || '-'}</td>
                <td class="small">${item.tanggal || '-'}</td>
                <td class="small" title="${item.pesan || ''}">${item.pesan ? (item.pesan.length > 50 ? item.pesan.substring(0, 50) + '...' : item.pesan) : '-'}</td>
                <td class="text-center">${imageHtml}</td>
                <td class="small">
                    <div class="mb-1 font-weight-bold">${item.customerName || '-'}</div>
                    <small class="text-muted">${item.phone || '-'}</small>
                </td>
                <td class="small">${item.status || '-'}</td>
            </tr>
        `;
        tableBody.append(row);
    });
}

/**
 * Show error state in customer list
 */
function showErrorState(message) {
    const customerList = $('#customerList');
    const defaultState = $('#defaultCustomerState');
    const noDataState = $('#noCustomerData');
    
    defaultState.hide();
    noDataState.hide();
    
    customerList.html(`
        <div class="text-center p-4">
            <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
            <h6 class="text-muted">Terjadi Kesalahan</h6>
            <p class="text-muted mb-3">${message}</p>
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="loadFilteredCustomers()">
                <i class="fas fa-redo mr-1"></i>
                Coba Lagi
            </button>
        </div>
    `);
    
    $('#customerListCount').text('0 customer');
    $('#customerCount').text('0');
    filteredCustomers = [];
    updateSendButton();
}

/**
 * Helper Functions
 */
function getCustomerTypeLabel(type) {
    const labels = {
        'pelangganLama': 'Lama',
        'pelangganBaru': 'Baru',
        'pelangganTidakKembali': 'Tidak Kembali',
        'keseluruhan': 'Keseluruhan'
    };
    return labels[type] || 'Unknown';
}

function getCustomerTypeBadge(type) {
    const badges = {
        'pelangganLama': 'badge-primary',
        'pelangganBaru': 'badge-success',
        'pelangganTidakKembali': 'badge-warning',
        'keseluruhan': 'badge-secondary'
    };
    return badges[type] || 'badge-secondary';
}

function getInitialFromName(name) {
    if (!name || name.trim() === '') {
        return 'UN';
    }
    
    const words = name.trim().split(' ');
    if (words.length >= 2) {
        return (words[0].charAt(0) + words[1].charAt(0)).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
}

function determineTargetType() {
    if (selectedFilters.includes('pelangganLama')) return 'pelangganLama';
    if (selectedFilters.includes('pelangganBaru')) return 'pelangganBaru';
    if (selectedFilters.includes('pelangganTidakKembali')) return 'pelangganTidakKembali';
    if (selectedFilters.includes('keseluruhan')) return 'keseluruhan';
    return 'keseluruhan';
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function showLoadingState(element) {
    const $element = $(element);
    if ($element.find('.loading-overlay').length === 0) {
        $element.append('<div class="loading-overlay"><div class="loading-spinner"></div></div>');
    }
}

function hideLoadingState(element) {
    $(element).find('.loading-overlay').remove();
}

function showErrorMessage(message) {
    if (typeof Swal !== 'undefined') {
        Swal.fire('❌ Error', message, 'error');
    } else {
        alert('Error: ' + message);
    }
    console.error('❌ Error:', message);
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Cleanup on page unload
 */
$(window).on('beforeunload', function() {
    if (deviceCheckInterval) {
        clearInterval(deviceCheckInterval);
    }
});

/**
 * Export Functions for Global Access
 */
window.FollowUpModule = {
    showCustomerDetail,
    removeImage,
    showFullImage,
    sendMassFollowUp,
    loadRiwayatData,
    resetForm,
    testWhatsAppConnection,
    checkDeviceStatus,
    showDeviceHelp
};

console.log('✅ Follow Up Pelanggan JavaScript loaded successfully (FIXED VERSION - WhatsApp Integration Ready)');