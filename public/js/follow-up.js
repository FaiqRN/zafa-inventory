/**
 * Follow Up Pelanggan JavaScript Module
 * Zafa Potato CRM System - Database Integrated (No Dummy Data)
 */

// Global Variables
let selectedFilters = [];
let uploadedImages = [];
let filteredCustomers = [];

// Document Ready
$(document).ready(function() {
    initializeFollowUp();
});

/**
 * Initialize Follow Up Module
 */
function initializeFollowUp() {
    // Load initial data
    loadRiwayatData();
    
    // Setup event listeners
    setupEventListeners();
    
    // Setup image upload functionality
    setupImageUpload();
    
    console.log('Follow Up Pelanggan module initialized (Database Mode)');
}

/**
 * Setup Event Listeners
 */
function setupEventListeners() {
    // Filter checkbox change handler
    $('.filter-checkbox').on('change', function() {
        updateFilters();
        loadFilteredCustomers();
    });
    
    // Character count for message
    $('#followUpMessage').on('input', function() {
        const length = $(this).val().length;
        $('#charCount').text(length);
        
        if (length > 1000) {
            $(this).addClass('is-invalid');
        } else {
            $(this).removeClass('is-invalid');
        }
        
        updateSendButton();
    });
    
    // Form submission
    $('#massFollowUpForm').on('submit', function(e) {
        e.preventDefault();
        $('#previewModal').modal('show');
        showPreview();
    });
    
    // Preview button
    $('#previewBtn').on('click', function() {
        $('#previewModal').modal('show');
        showPreview();
    });
    
    // Confirm send button
    $('#confirmSendBtn').on('click', function() {
        sendMassFollowUp();
    });
    
    // Refresh riwayat
    $('#refreshRiwayatBtn').on('click', function() {
        loadRiwayatData();
    });
}

/**
 * Update Filter System
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
 * Load Filtered Customers from Database
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
    
    // Show loading
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
        success: function(response) {
            hideLoadingState('#customerList');
            
            if (response.status === 'success') {
                filteredCustomers = response.data;
                displayCustomers(filteredCustomers);
            } else {
                console.error('Error loading customers:', response);
                showErrorMessage('Gagal memuat data customer');
            }
        },
        error: function(xhr, status, error) {
            hideLoadingState('#customerList');
            console.error('AJAX Error:', error);
            showErrorMessage('Terjadi kesalahan saat memuat data customer');
        }
    });
}

/**
 * Display Customers in List (without avatars)
 */
function displayCustomers(customers) {
    const customerList = $('#customerList');
    const defaultState = $('#defaultCustomerState');
    const noDataState = $('#noCustomerData');
    
    defaultState.hide();
    
    if (customers.length === 0) {
        customerList.empty();
        noDataState.show();
        $('#customerListCount').text('0 customer');
        $('#customerCount').text('0');
        return;
    }
    
    noDataState.hide();
    customerList.empty();
    
    customers.forEach(customer => {
        const customerTypeLabel = getCustomerTypeLabel(customer.customerType);
        const customerTypeBadge = getCustomerTypeBadge(customer.customerType);
        
        const customerItem = `
            <div class="customer-item p-3 border-bottom slide-in" data-customer-type="${customer.customerType}">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center flex-grow-1">
                        <div class="customer-initial-small">
                            <span class="initial-text-small">${customer.initial || getInitialFromName(customer.name)}</span>
                        </div>
                        <div>
                            <h6 class="mb-1 font-weight-bold">${customer.name}</h6>
                            <p class="mb-1 text-muted small">${customer.phone}</p>
                            <div>
                                <span class="badge ${customerTypeBadge} customer-badge mr-1">${customerTypeLabel}</span>
                                <span class="badge badge-info customer-badge">${customer.orderSource}</span>
                            </div>
                        </div>
                    </div>
                    <div class="text-right">
                        <small class="text-muted d-block">${customer.totalOrders} pesanan</small>
                        <small class="text-success d-block font-weight-bold">${customer.totalSpent}</small>
                        <button type="button" class="btn detail-btn btn-sm mt-1" onclick="showCustomerDetail('${customer.id}')">
                            Detail
                        </button>
                    </div>
                </div>
            </div>
        `;
        customerList.append(customerItem);
    });
    
    $('#customerListCount').text(`${customers.length} customer`);
    $('#customerCount').text(customers.length);
    updateSendButton();
}

/**
 * Show Customer Detail Modal (without avatar)
 */
function showCustomerDetail(customerId) {
    const customer = filteredCustomers.find(c => c.id === customerId);
    if (!customer) return;
    
    // Populate modal with customer data
    $('#modalCustomerInitial').text(customer.initial || getInitialFromName(customer.name));
    $('#modalCustomerName').text(customer.name);
    $('#modalCustomerPhone').text(customer.phone);
    $('#modalCustomerEmail').text(customer.email || '-');
    $('#modalCustomerAddress').text(customer.address || '-');
    $('#modalCustomerLastOrder').text(customer.lastOrder);
    $('#modalCustomerTotalOrders').text(customer.totalOrders + ' pesanan');
    $('#modalCustomerTotalSpent').text(customer.totalSpent);
    $('#modalCustomerLastProduct').text(customer.lastProduct || '-');
    $('#modalCustomerNotes').text(customer.notes || '-');
    
    // Set badges
    const customerTypeLabel = getCustomerTypeLabel(customer.customerType);
    const customerTypeBadge = getCustomerTypeBadge(customer.customerType);
    
    const badges = `
        <span class="badge ${customerTypeBadge} mb-1">${customerTypeLabel}</span><br>
        <span class="badge badge-info">${customer.orderSource}</span>
    `;
    $('#modalCustomerBadges').html(badges);
    
    // Show modal
    $('#customerDetailModal').modal('show');
}

/**
 * Setup Image Upload Functionality
 */
function setupImageUpload() {
    const uploadArea = $('#uploadArea');
    const imageInput = $('#imageInput');
    
    // Drag and drop handlers
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
    
    // Click to upload
    uploadArea.on('click', function() {
        imageInput.click();
    });
    
    // File input change
    imageInput.on('change', function() {
        handleImageFiles(this.files);
    });
}

/**
 * Handle Image Files Upload
 */
function handleImageFiles(files) {
    Array.from(files).forEach(file => {
        if (file.type.startsWith('image/')) {
            if (file.size > 5 * 1024 * 1024) { // 5MB limit
                Swal.fire('Error', `File ${file.name} terlalu besar. Maksimal 5MB.`, 'error');
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
            };
            reader.readAsDataURL(file);
        } else {
            Swal.fire('Error', `File ${file.name} bukan format gambar yang valid.`, 'error');
        }
    });
}

/**
 * Add Image Preview
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
 * Remove Image from Preview
 */
function removeImage(index) {
    uploadedImages.splice(index, 1);
    $(`.image-preview-container[data-index="${index}"]`).remove();
    
    if (uploadedImages.length === 0) {
        $('#imagePreviewArea').hide();
    }
    
    // Update indexes
    $('.image-preview-container').each(function(i) {
        $(this).attr('data-index', i);
        $(this).find('.remove-image-btn').attr('onclick', `removeImage(${i})`);
    });
    
    updateSendButton();
}

/**
 * Show Full Image in Modal
 */
function showFullImage(src) {
    $('#fullImageView').attr('src', src);
    $('#imageFullModal').modal('show');
}

/**
 * Update Send Button State
 */
function updateSendButton() {
    const hasMessage = $('#followUpMessage').val().trim().length > 0;
    const hasImages = uploadedImages.length > 0;
    const hasTargets = filteredCustomers.length > 0;
    const isValidMessage = $('#followUpMessage').val().length <= 1000;
    
    const canSend = (hasMessage || hasImages) && hasTargets && isValidMessage;
    
    $('#sendMassFollowUpBtn').prop('disabled', !canSend);
    $('#targetCount').text(filteredCustomers.length);
}

/**
 * Show Preview Modal
 */
function showPreview() {
    $('#previewTargetCount').text(filteredCustomers.length);
    
    // Show images
    const previewImages = $('#previewImages');
    previewImages.empty();
    
    if (uploadedImages.length > 0) {
        uploadedImages.forEach(image => {
            previewImages.append(`
                <img src="${image.data}" alt="${image.name}" style="width: 100px; height: 100px; object-fit: cover; margin: 5px;" class="img-thumbnail">
            `);
        });
    }
    
    // Show message
    const message = $('#followUpMessage').val() || '<em class="text-muted">Tidak ada pesan teks</em>';
    $('#previewMessage').html(message.replace(/\n/g, '<br>'));
}

/**
 * Send Mass Follow Up via API
 */
function sendMassFollowUp() {
    const message = $('#followUpMessage').val();
    const targetType = determineTargetType();
    
    if (filteredCustomers.length === 0) {
        Swal.fire('Error', 'Tidak ada customer yang dipilih', 'error');
        return;
    }
    
    // Prepare form data
    const formData = new FormData();
    formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
    formData.append('message', message);
    formData.append('target_type', targetType);
    formData.append('customers', JSON.stringify(filteredCustomers));
    
    // Add images
    uploadedImages.forEach((imageData, index) => {
        formData.append(`images[${index}]`, imageData.file);
    });
    
    // Show loading
    Swal.fire({
        title: 'Mengirim Follow Up...',
        text: `Mengirim ke ${filteredCustomers.length} customer`,
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Send request
    $.ajax({
        url: '/follow-up-pelanggan/send',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.status === 'success') {
                Swal.fire({
                    title: 'Berhasil!',
                    text: response.message,
                    icon: 'success',
                    confirmButtonText: 'OK'
                }).then(() => {
                    // Show detailed results if there were failures
                    if (response.summary.failed > 0) {
                        showSendResults(response.results);
                    }
                });
                
                // Reset form and reload history
                resetForm();
                loadRiwayatData();
                
                // Close modal
                $('#previewModal').modal('hide');
            } else {
                Swal.fire('Error', response.message || 'Terjadi kesalahan', 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error('Send follow up error:', error);
            let errorMessage = 'Terjadi kesalahan saat mengirim follow up';
            
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }
            
            Swal.fire('Error', errorMessage, 'error');
        }
    });
}

/**
 * Show detailed send results
 */
function showSendResults(results) {
    let resultHtml = '<div class="table-responsive"><table class="table table-sm">';
    resultHtml += '<thead><tr><th>Customer</th><th>Phone</th><th>Status</th><th>Keterangan</th></tr></thead><tbody>';
    
    results.forEach(result => {
        const statusBadge = result.status === 'success' ? 'badge-success' : 'badge-danger';
        const statusText = result.status === 'success' ? 'Berhasil' : 'Gagal';
        const keterangan = result.status === 'success' ? 
            (result.message_id ? `ID: ${result.message_id}` : 'Terkirim') : 
            (result.error || 'Error tidak diketahui');
        
        resultHtml += `
            <tr>
                <td>${result.customer}</td>
                <td>${result.phone}</td>
                <td><span class="badge ${statusBadge}">${statusText}</span></td>
                <td><small>${keterangan}</small></td>
            </tr>
        `;
    });
    
    resultHtml += '</tbody></table></div>';
    
    Swal.fire({
        title: 'Detail Hasil Pengiriman',
        html: resultHtml,
        width: '80%',
        confirmButtonText: 'Tutup'
    });
}

/**
 * Reset Form After Send
 */
function resetForm() {
    $('#followUpMessage').val('');
    $('#charCount').text('0');
    uploadedImages = [];
    $('#imagePreviewArea').hide();
    $('#imagePreviewContainer').empty();
    $('.filter-checkbox').prop('checked', false);
    updateFilters();
}

/**
 * Load Riwayat Data from Database
 */
function loadRiwayatData() {
    showLoadingState('#riwayatTableBody');
    
    $.ajax({
        url: '/follow-up-pelanggan/history',
        type: 'GET',
        success: function(response) {
            hideLoadingState('#riwayatTableBody');
            
            if (response.status === 'success') {
                displayRiwayat(response.data);
            } else {
                console.error('Error loading history:', response);
                showErrorMessage('Gagal memuat riwayat');
            }
        },
        error: function(xhr, status, error) {
            hideLoadingState('#riwayatTableBody');
            console.error('AJAX Error:', error);
            showErrorMessage('Terjadi kesalahan saat memuat riwayat');
        }
    });
}

/**
 * Display Riwayat in Table
 */
function displayRiwayat(data) {
    const tableBody = $('#riwayatTableBody');
    const noDataMessage = $('#noRiwayatMessage');
    
    if (data.length === 0) {
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
                <td class="font-weight-bold text-primary">${item.id}</td>
                <td class="small">${item.tanggal}</td>
                <td class="small">${item.pesan.length > 50 ? item.pesan.substring(0, 50) + '...' : item.pesan}</td>
                <td class="text-center">${imageHtml}</td>
                <td class="small">
                    <div class="mb-1">${item.customerName}</div>
                    <small class="text-muted">${item.phone}</small>
                </td>
                <td class="small">${item.status}</td>
            </tr>
        `;
        tableBody.append(row);
    });
}

/**
 * Helper Functions
 */

// Get customer type label
function getCustomerTypeLabel(type) {
    const labels = {
        'pelangganLama': 'Lama',
        'pelangganBaru': 'Baru',
        'pelangganTidakKembali': 'Tidak Kembali',
        'keseluruhan': 'Keseluruhan'
    };
    return labels[type] || 'Unknown';
}

// Get customer type badge class
function getCustomerTypeBadge(type) {
    const badges = {
        'pelangganLama': 'badge-primary',
        'pelangganBaru': 'badge-success',
        'pelangganTidakKembali': 'badge-warning',
        'keseluruhan': 'badge-secondary'
    };
    return badges[type] || 'badge-secondary';
}

// Get initial from name
function getInitialFromName(name) {
    const words = name.split(' ');
    if (words.length >= 2) {
        return (words[0].charAt(0) + words[1].charAt(0)).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
}

// Determine target type from selected filters
function determineTargetType() {
    if (selectedFilters.includes('pelangganLama')) return 'pelangganLama';
    if (selectedFilters.includes('pelangganBaru')) return 'pelangganBaru';
    if (selectedFilters.includes('pelangganTidakKembali')) return 'pelangganTidakKembali';
    if (selectedFilters.includes('keseluruhan')) return 'keseluruhan';
    return 'keseluruhan';
}

// Format file size
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Show loading state
function showLoadingState(element) {
    const $element = $(element);
    $element.append('<div class="loading-overlay"><div class="loading-spinner"></div></div>');
}

// Hide loading state
function hideLoadingState(element) {
    $(element).find('.loading-overlay').remove();
}

// Show error message
function showErrorMessage(message) {
    Swal.fire('Error', message, 'error');
}

// Debounce function for search
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
 * Export Functions for Global Access
 */
window.FollowUpModule = {
    showCustomerDetail,
    removeImage,
    showFullImage,
    sendMassFollowUp,
    loadRiwayatData,
    resetForm
};

// Console log for debugging
console.log('Follow Up Pelanggan JavaScript loaded successfully (Database Mode)');