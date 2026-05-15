<!-- resources/views/layouts/header.blade.php -->
<nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
        <li class="nav-item" style="z-index: 10000; position: relative;">
            <button type="button" class="nav-link" data-widget="pushmenu" aria-label="Toggle sidebar menu" title="Toggle sidebar menu"
               style="pointer-events: auto !important; z-index: 99999 !important; display: inline-flex !important; visibility: visible !important; opacity: 1 !important;">
                <i class="fas fa-chevron-left pushmenu-icon" style="display: block !important; visibility: visible !important; opacity: 1 !important; font-size: 1.3rem !important;"></i>
            </button>
        </li>
    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
        @can('view-notifications')
            <!-- Notifications Dropdown Menu -->
            <li class="nav-item dropdown" id="notificationNavItem">
                <a class="nav-link position-relative" data-toggle="dropdown" href="#" id="notificationDropdown">
                    <i class="far fa-bell" id="notificationBellIcon"></i>
                    <span class="badge badge-danger notification-badge-custom" id="notificationCount" style="display: none;">0</span>
                </a>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right p-0" style="width: 360px; max-height: 450px; overflow: hidden;">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center px-3 py-2 bg-light border-bottom">
                        <span class="font-weight-bold"><i class="fas fa-bell mr-1"></i> Notifikasi</span>
                        <button type="button" class="btn btn-sm btn-link text-primary p-0" id="markAllReadBtn" title="Tandai Semua Dibaca">
                            <i class="fas fa-check-double"></i> Tandai Dibaca
                        </button>
                    </div>
                    
                    <!-- Loading state -->
                    <div id="notificationLoading" class="text-center py-4">
                        <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                        <p class="text-muted mb-0 mt-2">Memuat...</p>
                    </div>
                    
                    <!-- Notification items (scrollable) -->
                    <div id="notificationList" style="display: none; max-height: 350px; overflow-y: auto;">
                        <!-- Dynamic content will be loaded here -->
                    </div>
                    
                    <!-- Empty state -->
                    <div id="notificationEmpty" class="text-center py-5" style="display: none;">
                        <i class="far fa-check-circle fa-3x text-success mb-3"></i>
                        <p class="text-muted mb-0">Semua aman! Tidak ada notifikasi.</p>
                    </div>
                </div>
            </li>
        @endcan
        @if(Auth::check())
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#">
                    @if(Auth::user()->foto)
                        <img src="{{ asset('storage/profile/'.Auth::user()->foto) }}" class="img-circle elevation-2" alt="User" style="width: 30px; height: 30px; object-fit: cover;">
                    @else
                        <i class="far fa-user-circle"></i>
                    @endif
                    <span class="ml-1">{{ Auth::user()->nama_lengkap ?? 'User' }}</span>
                </a>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                    <a href="{{ route('profile') }}" class="dropdown-item">
                        <i class="fas fa-user mr-2"></i> Profile
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="{{ route('profile.edit') }}" class="dropdown-item">
                        <i class="fas fa-pencil-alt mr-2"></i> Edit Profil
                    </a>
                    <a href="{{ route('profile.change-password') }}" class="dropdown-item">
                        <i class="fas fa-sync-alt mr-2"></i> Ubah Password
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="{{ route('logout') }}" class="dropdown-item"
                    onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="fas fa-sign-out-alt mr-2"></i> Logout
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                        @csrf
                    </form>
                </div>
            </li>
        @elseif(session()->has('user_id'))
            <li class="nav-item">
                <a href="{{ route('profile') }}" class="btn btn-primary mr-2">PROFILE</a>
            </li>
            <li class="nav-item">
                <a href="{{ route('logout') }}" class="btn btn-danger">LOGOUT</a>
            </li>
        @endif
    </ul>
</nav>

@can('view-notifications')
<!-- Notification Scripts (Using LocalStorage) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const STORAGE_KEY = 'zafasys_read_notifications';
    const notificationDropdown = document.getElementById('notificationDropdown');
    const notificationList = document.getElementById('notificationList');
    const notificationEmpty = document.getElementById('notificationEmpty');
    const notificationLoading = document.getElementById('notificationLoading');
    const notificationCount = document.getElementById('notificationCount');
    const markAllReadBtn = document.getElementById('markAllReadBtn');
    
    let allNotifications = [];
    let checkInterval = 60000; // Default 60 seconds, will be updated from settings
    let intervalId = null;
    
    // Get read notifications from LocalStorage
    function getReadNotifications() {
        try {
            return JSON.parse(localStorage.getItem(STORAGE_KEY)) || [];
        } catch (e) {
            return [];
        }
    }
    
    // Save read notifications to LocalStorage
    function saveReadNotification(id) {
        let readList = getReadNotifications();
        if (!readList.includes(id)) {
            readList.push(id);
            // Keep only last 100 entries to avoid storage bloat
            if (readList.length > 100) {
                readList = readList.slice(-100);
            }
            localStorage.setItem(STORAGE_KEY, JSON.stringify(readList));
        }
    }
    
    // Mark all as read
    function markAllAsRead() {
        let readList = getReadNotifications();
        allNotifications.forEach(n => {
            if (!readList.includes(n.id)) {
                readList.push(n.id);
            }
        });
        localStorage.setItem(STORAGE_KEY, JSON.stringify(readList));
        updateNotificationBadge(0);
        loadNotifications(); // Refresh display
    }
    
    // Load notifications when dropdown is opened
    if (notificationDropdown) {
        notificationDropdown.addEventListener('click', function(e) {
            loadNotifications();
        });
    }
    
    // Mark all read button
    if (markAllReadBtn) {
        markAllReadBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            markAllAsRead();
        });
    }
    
    // Initial count check
    loadNotificationsCount();
    
    // Start periodic check with dynamic interval
    function startPeriodicCheck() {
        if (intervalId) clearInterval(intervalId);
        intervalId = setInterval(loadNotificationsCount, checkInterval);
    }
    startPeriodicCheck();
    
    function loadNotificationsCount() {
        fetch('{{ route("notifications.get") }}')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    allNotifications = data.notifications;
                    const readList = getReadNotifications();
                    const unreadCount = data.notifications.filter(n => !readList.includes(n.id)).length;
                    updateNotificationBadge(unreadCount);
                    
                    // Update check interval from settings if changed
                    if (data.settings && data.settings.check_interval) {
                        const newInterval = data.settings.check_interval * 1000;
                        if (newInterval !== checkInterval) {
                            checkInterval = newInterval;
                            startPeriodicCheck();
                        }
                    }
                }
            })
            .catch(error => console.error('Error checking notifications:', error));
    }
    
    function loadNotifications() {
        notificationLoading.style.display = 'block';
        notificationList.style.display = 'none';
        notificationEmpty.style.display = 'none';
        
        fetch('{{ route("notifications.get") }}')
            .then(response => response.json())
            .then(data => {
                notificationLoading.style.display = 'none';
                
                if (data.success && data.notifications.length > 0) {
                    allNotifications = data.notifications;
                    renderNotifications(data.notifications);
                    notificationList.style.display = 'block';
                    notificationEmpty.style.display = 'none';
                    
                    // Update unread count
                    const readList = getReadNotifications();
                    const unreadCount = data.notifications.filter(n => !readList.includes(n.id)).length;
                    updateNotificationBadge(unreadCount);
                } else {
                    notificationList.style.display = 'none';
                    notificationEmpty.style.display = 'block';
                    updateNotificationBadge(0);
                }
            })
            .catch(error => {
                console.error('Error loading notifications:', error);
                notificationLoading.style.display = 'none';
                notificationEmpty.style.display = 'block';
            });
    }
    
    function renderNotifications(notifications) {
        const readList = getReadNotifications();
        let html = '';
        
        notifications.forEach(function(notification) {
            const isRead = readList.includes(notification.id);
            const bgClass = isRead ? '' : 'bg-light';
            
            html += `
                     <a href="${notification.url || '#'}"
                   class="dropdown-item notification-item border-bottom py-2 ${bgClass}"
                   data-id="${notification.id}"
                   onclick="handleNotificationClick(event, '${notification.id}', '${notification.url || ''}')">
                    <div class="d-flex align-items-start">
                        <div class="notification-icon bg-${notification.icon_color} text-white rounded-circle mr-2" style="min-width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                            <i class="${notification.icon}"></i>
                        </div>
                        <div class="flex-grow-1" style="max-width: calc(100% - 55px);">
                            <div class="d-flex justify-content-between align-items-start">
                                <p class="mb-0 text-sm font-weight-bold">${notification.title}</p>
                                ${!isRead ? '<span class="badge badge-primary badge-sm">Baru</span>' : ''}
                            </div>
                            <p class="mb-1 text-xs text-muted" style="white-space: normal; line-height: 1.4;">${notification.message}</p>
                        </div>
                    </div>
                </a>
            `;
        });
        
        notificationList.innerHTML = html;
    }
    
    let bellShakeIntervalId = null;
    
    function startBellShake() {
        const bellIcon = document.getElementById('notificationBellIcon');
        if (!bellIcon) return;
        
        // Clear existing interval
        if (bellShakeIntervalId) clearInterval(bellShakeIntervalId);
        
        // Shake immediately
        triggerBellShake();
        
        // Shake every 8 seconds
        bellShakeIntervalId = setInterval(triggerBellShake, 8000);
    }
    
    function stopBellShake() {
        if (bellShakeIntervalId) {
            clearInterval(bellShakeIntervalId);
            bellShakeIntervalId = null;
        }
        const bellIcon = document.getElementById('notificationBellIcon');
        if (bellIcon) bellIcon.classList.remove('bell-shake');
    }
    
    function triggerBellShake() {
        const bellIcon = document.getElementById('notificationBellIcon');
        if (!bellIcon) return;
        
        // Remove and re-add class to restart animation
        bellIcon.classList.remove('bell-shake');
        void bellIcon.offsetWidth; // Force reflow
        bellIcon.classList.add('bell-shake');
    }
    
    function updateNotificationBadge(count) {
        const bellIcon = document.getElementById('notificationBellIcon');
        const navItem = document.getElementById('notificationNavItem');
        
        if (count > 0) {
            notificationCount.textContent = count > 99 ? '99+' : count;
            notificationCount.style.display = 'inline';
            // Add red alert indicators
            bellIcon.classList.add('text-danger');
            bellIcon.classList.remove('far');
            bellIcon.classList.add('fas');
            navItem.classList.add('has-unread');
            // Start periodic bell shake
            startBellShake();
        } else {
            notificationCount.style.display = 'none';
            // Remove red alert indicators
            bellIcon.classList.remove('text-danger');
            bellIcon.classList.remove('fas');
            bellIcon.classList.add('far');
            navItem.classList.remove('has-unread');
            // Stop bell shake
            stopBellShake();
        }
    }
    
    // Expose to global scope
    window.handleNotificationClick = function(event, id, url) {
        event.preventDefault();
        
        // Save as read in LocalStorage
        saveReadNotification(id);
        
        // Update badge count
        const readList = getReadNotifications();
        const unreadCount = allNotifications.filter(n => !readList.includes(n.id)).length;
        updateNotificationBadge(unreadCount);
        
        // Navigate to URL if provided
        if (url) {
            window.location.href = url;
        }
    };
});
</script>

<style>
.notification-item {
    transition: background-color 0.2s;
}
.notification-item:hover {
    background-color: #e9ecef !important;
}
.notification-icon {
    min-width: 40px;
    height: 40px;
}
#notificationList .dropdown-item {
    white-space: normal;
}
#notificationList::-webkit-scrollbar {
    width: 6px;
}
#notificationList::-webkit-scrollbar-track {
    background: #f1f1f1;
}
#notificationList::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}
#notificationList::-webkit-scrollbar-thumb:hover {
    background: #a1a1a1;
}

/* Custom badge position - lower and further from icon */
.notification-badge-custom {
    position: absolute;
    bottom: 2px;
    right: -2px;
    font-size: 0.65rem;
    padding: 2px 5px;
    min-width: 18px;
    height: 18px;
    line-height: 14px;
    border-radius: 10px;
    animation: pulse-badge 1.5s infinite;
}

/* Bell shake animation for unread notifications */
.bell-shake {
    animation: bell-ring 0.5s ease-in-out 3;
}
@keyframes pulse-badge {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.1);
    }
}
@keyframes bell-ring {
    0%, 100% {
        transform: rotate(0);
    }
    20%, 60% {
        transform: rotate(15deg);
    }
    40%, 80% {
        transform: rotate(-15deg);
    }
}
#notificationBellIcon.text-danger {
    color: #dc3545 !important;
}
</style>
@endcan
