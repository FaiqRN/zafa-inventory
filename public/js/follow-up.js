/**
 * Follow Up Pelanggan JavaScript Module
 * Zafa Potato CRM System
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
    
    console.log('Follow Up Pelanggan module initialized');
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
 * Load Filtered Customers
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
    
    // Simulasi data customer yang lebih lengkap berdasarkan filter
    const allCustomers = getAllCustomersData();
    
    // Filter customers berdasarkan selected filters
    filteredCustomers = allCustomers.filter(customer => {
        return selectedFilters.includes(customer.customerType) || selectedFilters.includes(customer.orderSource);
    });
    
    displayCustomers(filteredCustomers);
}

/**
 * Get All Customers Data (Dummy Data)
 */
function getAllCustomersData() {
    return [
        // Pelanggan Lama + WhatsApp
        { 
            id: 1, 
            name: 'Budi Santoso', 
            phone: '+62 812-3456-7890', 
            email: 'budi@email.com', 
            avatar: 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=100&h=100&fit=crop&crop=face', 
            customerType: 'pelangganLama', 
            orderSource: 'whatsapp', 
            lastOrder: '2025-05-28', 
            totalOrders: 5, 
            totalSpent: 'Rp 450,000', 
            address: 'Jl. Merdeka No. 123, Malang', 
            lastProduct: 'Kentang Goreng Crispy - 2kg', 
            notes: 'Pelanggan setia, sering pesan via WhatsApp' 
        },
        { 
            id: 2, 
            name: 'Ahmad Rizki', 
            phone: '+62 821-5678-9012', 
            email: 'ahmad@email.com', 
            avatar: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=100&h=100&fit=crop&crop=face', 
            customerType: 'pelangganLama', 
            orderSource: 'whatsapp', 
            lastOrder: '2025-05-25', 
            totalOrders: 8, 
            totalSpent: 'Rp 720,000', 
            address: 'Jl. Veteran No. 78, Malang', 
            lastProduct: 'Kentang Bumbu Balado - 3kg', 
            notes: 'Pelanggan VIP, aktif di WhatsApp grup' 
        },
        // Pelanggan Lama + Shopee
        { 
            id: 3, 
            name: 'Andi Wijaya', 
            phone: '+62 819-1122-3344', 
            email: 'andi@email.com', 
            avatar: 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=100&h=100&fit=crop&crop=face', 
            customerType: 'pelangganLama', 
            orderSource: 'shopee', 
            lastOrder: '2025-05-27', 
            totalOrders: 6, 
            totalSpent: 'Rp 520,000', 
            address: 'Jl. Kawi No. 15, Malang', 
            lastProduct: 'Kentang Spicy - 2.5kg', 
            notes: 'Suka varian pedas, loyal customer Shopee' 
        },
        { 
            id: 4, 
            name: 'Sari Dewi', 
            phone: '+62 857-9988-7766', 
            email: 'sari@email.com', 
            avatar: 'https://images.unsplash.com/photo-1544725176-7c40e5a71c5e?w=100&h=100&fit=crop&crop=face', 
            customerType: 'pelangganLama', 
            orderSource: 'shopee', 
            lastOrder: '2025-05-26', 
            totalOrders: 7, 
            totalSpent: 'Rp 630,000', 
            address: 'Jl. Ijen No. 22, Malang', 
            lastProduct: 'Paket Family - 5kg', 
            notes: 'Pelanggan setia Shopee, sering review positif' 
        },
        // Pelanggan Lama + Instagram
        { 
            id: 5, 
            name: 'Joko Susilo', 
            phone: '+62 813-4455-6677', 
            email: 'joko@email.com', 
            avatar: 'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=100&h=100&fit=crop&crop=face', 
            customerType: 'pelangganLama', 
            orderSource: 'instagram', 
            lastOrder: '2025-05-29', 
            totalOrders: 4, 
            totalSpent: 'Rp 380,000', 
            address: 'Jl. Semeru No. 88, Malang', 
            lastProduct: 'Kentang BBQ - 2kg', 
            notes: 'Aktif follower Instagram, suka story produk' 
        },
        // Pelanggan Lama + Langsung
        { 
            id: 6, 
            name: 'Maya Sari', 
            phone: '+62 822-1133-4455', 
            email: 'maya@email.com', 
            avatar: 'https://images.unsplash.com/photo-1494790108755-2616b612b786?w=100&h=100&fit=crop&crop=face', 
            customerType: 'pelangganLama', 
            orderSource: 'langsung', 
            lastOrder: '2025-05-30', 
            totalOrders: 9, 
            totalSpent: 'Rp 810,000', 
            address: 'Jl. Gajayana No. 55, Malang', 
            lastProduct: 'Kentang Original - 4kg', 
            notes: 'Pelanggan tetap toko, datang rutin setiap minggu' 
        },
        // Pelanggan Baru + Shopee
        { 
            id: 7, 
            name: 'Siti Nurhaliza', 
            phone: '+62 856-7890-1234', 
            email: 'siti@email.com', 
            avatar: 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=100&h=100&fit=crop&crop=face', 
            customerType: 'pelangganBaru', 
            orderSource: 'shopee', 
            lastOrder: '2025-05-30', 
            totalOrders: 2, 
            totalSpent: 'Rp 180,000', 
            address: 'Jl. Diponegoro No. 45, Malang', 
            lastProduct: 'Paket Kentang Premium - 1kg', 
            notes: 'Baru mencoba produk via Shopee' 
        },
        { 
            id: 8, 
            name: 'Dewi Sartika', 
            phone: '+62 813-2468-1357', 
            email: 'dewi@email.com', 
            avatar: 'https://images.unsplash.com/photo-1487412720507-e7ab37603c6f?w=100&h=100&fit=crop&crop=face', 
            customerType: 'pelangganBaru', 
            orderSource: 'shopee', 
            lastOrder: '2025-06-01', 
            totalOrders: 1, 
            totalSpent: 'Rp 85,000', 
            address: 'Jl. Brawijaya No. 90, Malang', 
            lastProduct: 'Kentang Original - 1kg', 
            notes: 'Customer baru, pertama kali beli di Shopee' 
        },
        // Pelanggan Baru + WhatsApp
        { 
            id: 9, 
            name: 'Lina Maharani', 
            phone: '+62 878-5544-3322', 
            email: 'lina@email.com', 
            avatar: 'https://images.unsplash.com/photo-1508214751196-bcfd4ca60f91?w=100&h=100&fit=crop&crop=face', 
            customerType: 'pelangganBaru', 
            orderSource: 'whatsapp', 
            lastOrder: '2025-06-02', 
            totalOrders: 1, 
            totalSpent: 'Rp 125,000', 
            address: 'Jl. Tlogomas No. 33, Malang', 
            lastProduct: 'Kentang Cheese - 1.5kg', 
            notes: 'Dapat referral dari teman via WhatsApp' 
        },
        // Pelanggan Baru + Instagram
        { 
            id: 10, 
            name: 'Rina Putri', 
            phone: '+62 895-6677-8899', 
            email: 'rina@email.com', 
            avatar: 'https://images.unsplash.com/photo-1517841905240-472988babdf9?w=100&h=100&fit=crop&crop=face', 
            customerType: 'pelangganBaru', 
            orderSource: 'instagram', 
            lastOrder: '2025-06-03', 
            totalOrders: 1, 
            totalSpent: 'Rp 95,000', 
            address: 'Jl. Sukarno Hatta No. 77, Malang', 
            lastProduct: 'Kentang Mini - 1kg', 
            notes: 'Tertarik dari post Instagram, first timer' 
        },
        // Pelanggan Baru + Langsung
        { 
            id: 11, 
            name: 'Toni Hermawan', 
            phone: '+62 812-9988-7766', 
            email: 'toni@email.com', 
            avatar: 'https://images.unsplash.com/photo-1519345182560-3f2917c472ef?w=100&h=100&fit=crop&crop=face', 
            customerType: 'pelangganBaru', 
            orderSource: 'langsung', 
            lastOrder: '2025-06-04', 
            totalOrders: 1, 
            totalSpent: 'Rp 150,000', 
            address: 'Jl. Raya Tlogomas No. 12, Malang', 
            lastProduct: 'Paket Starter - 2kg', 
            notes: 'Baru kenal produk, langsung datang ke toko' 
        }
    ];
}

/**
 * Display Customers in List
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
        const customerTypeLabel = customer.customerType === 'pelangganLama' ? 'Lama' : 'Baru';
        const customerTypeBadge = customer.customerType === 'pelangganLama' ? 'primary' : 'success';
        
        const customerItem = `
            <div class="customer-item p-3 border-bottom slide-in">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center flex-grow-1">
                        <img src="${customer.avatar}" alt="${customer.name}" class="img-circle mr-3" style="width: 50px; height: 50px; object-fit: cover;">
                        <div>
                            <h6 class="mb-1 font-weight-bold">${customer.name}</h6>
                            <p class="mb-1 text-muted small">${customer.phone}</p>
                            <div>
                                <span class="badge badge-${customerTypeBadge} customer-badge mr-1">${customerTypeLabel}</span>
                                <span class="badge badge-info customer-badge">${customer.orderSource}</span>
                            </div>
                        </div>
                    </div>
                    <div class="text-right">
                        <button type="button" class="btn detail-btn btn-sm" onclick="showCustomerDetail(${customer.id})">
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
 * Show Customer Detail Modal
 */
function showCustomerDetail(customerId) {
    const customer = filteredCustomers.find(c => c.id === customerId);
    if (!customer) return;
    
    // Populate modal with customer data
    $('#modalCustomerAvatar').attr('src', customer.avatar);
    $('#modalCustomerName').text(customer.name);
    $('#modalCustomerPhone').text(customer.phone);
    $('#modalCustomerEmail').text(customer.email);
    $('#modalCustomerAddress').text(customer.address);
    $('#modalCustomerLastOrder').text(customer.lastOrder);
    $('#modalCustomerTotalOrders').text(customer.totalOrders + ' pesanan');
    $('#modalCustomerTotalSpent').text(customer.totalSpent);
    $('#modalCustomerLastProduct').text(customer.lastProduct);
    $('#modalCustomerNotes').text(customer.notes);
    
    // Set badges
    const customerTypeLabel = customer.customerType === 'pelangganLama' ? 'Pelanggan Lama' : 'Pelanggan Baru';
    const customerTypeBadge = customer.customerType === 'pelangganLama' ? 'primary' : 'success';
    
    const badges = `
        <span class="badge badge-${customerTypeBadge} mb-1">${customerTypeLabel}</span><br>
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
                addImagePreview(e.target.result, file.name);
                uploadedImages.push({
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
function addImagePreview(src, name) {
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
    $('#previewMessage').html(message);
}

/**
 * Send Mass Follow Up
 */
function sendMassFollowUp() {
    const message = $('#followUpMessage').val();
    
    // Simulasi pengiriman
    Swal.fire({
        title: 'Mengirim Follow Up...',
        text: `Mengirim ke ${filteredCustomers.length} customer`,
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Simulasi delay pengiriman
    setTimeout(() => {
        Swal.fire('Berhasil!', `Follow up berhasil dikirim ke ${filteredCustomers.length} customer`, 'success');
        
        // Buat entry riwayat baru
        const newRiwayat = {
            id: 'FU' + String(Date.now()).slice(-3),
            tanggal: new Date().toLocaleString('id-ID', {
                year: 'numeric',
                month: '2-digit', 
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            }),
            pesan: message || 'Pesan dengan gambar',
            gambar: uploadedImages.length > 0 ? uploadedImages[0].data : null,
            dikirimKe: filteredCustomers.map(c => c.name).join(', ')
        };
        
        // Tambahkan ke riwayat (simulasi)
        const currentRiwayat = getCurrentRiwayatData();
        currentRiwayat.unshift(newRiwayat);
        displayRiwayat(currentRiwayat);
        
        // Reset form
        resetForm();
        
        // Close modal
        $('#previewModal').modal('hide');
        
    }, 2000);
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
 * Get Current Riwayat Data
 */
function getCurrentRiwayatData() {
    return [
        {
            id: 'FU001',
            tanggal: '2025-06-04 14:30',
            pesan: 'Halo! Terima kasih sudah menjadi pelanggan setia Zafa Potato. Ada promo spesial untuk Anda! 🥔',
            gambar: 'https://images.unsplash.com/photo-1518977676601-b53f82aba655?w=100&h=100&fit=crop',
            dikirimKe: 'Budi Santoso, Ahmad Rizki, Andi Wijaya, Sari Dewi, Joko Susilo'
        },
        {
            id: 'FU002',
            tanggal: '2025-06-03 10:15',
            pesan: 'Produk kentang premium baru sudah tersedia! Jangan sampai kehabisan 😊',
            gambar: null,
            dikirimKe: 'Siti Nurhaliza, Dewi Sartika, Lina Maharani'
        },
        {
            id: 'FU003',
            tanggal: '2025-06-02 16:45',
            pesan: 'Stok kentang balado terbatas. Buruan pesan sebelum habis!',
            gambar: 'https://images.unsplash.com/photo-1587593810167-a84920ea0781?w=100&h=100&fit=crop',
            dikirimKe: 'Ahmad Rizki, Dewi Sartika, Rina Putri, Toni Hermawan, Maya Sari, Agus Pranoto, Linda Wati, Hendra Kusuma'
        },
        {
            id: 'FU004',
            tanggal: '2025-06-01 09:20',
            pesan: 'Selamat pagi! Weekend ini ada diskon 20% untuk pembelian minimal 2kg',
            gambar: null,
            dikirimKe: 'Budi Santoso, Siti Nurhaliza, Ahmad Rizki, Dewi Sartika, Andi Wijaya, Sari Dewi, Joko Susilo, Lina Maharani, Rina Putri, Toni Hermawan, Maya Sari, Agus Pranoto, Linda Wati, Hendra Kusuma, Dini Pratiwi'
        }
    ];
}

/**
 * Load Riwayat Data
 */
function loadRiwayatData() {
    // Data dummy riwayat pemesanan dengan nama customer individual
    const riwayatData = getCurrentRiwayatData();
    displayRiwayat(riwayatData);
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
        
        // Format daftar nama customer dengan maksimal tampil 3 nama + jumlah lainnya
        const customerNames = item.dikirimKe.split(', ');
        let displayNames = '';
        
        if (customerNames.length <= 3) {
            displayNames = customerNames.join(', ');
        } else {
            const visibleNames = customerNames.slice(0, 3).join(', ');
            const remainingCount = customerNames.length - 3;
            displayNames = `${visibleNames} +${remainingCount} lainnya`;
        }
        
        const row = `
            <tr class="slide-in">
                <td class="font-weight-bold text-primary">${item.id}</td>
                <td class="small">${item.tanggal}</td>
                <td class="small">${item.pesan.length > 50 ? item.pesan.substring(0, 50) + '...' : item.pesan}</td>
                <td class="text-center">${imageHtml}</td>
                <td class="small" title="${item.dikirimKe}">
                    <span class="text-info">${displayNames}</span>
                </td>
            </tr>
        `;
        tableBody.append(row);
    });
}

/**
 * Utility Functions
 */

// Format file size
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(amount);
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
console.log('Follow Up Pelanggan JavaScript loaded successfully');