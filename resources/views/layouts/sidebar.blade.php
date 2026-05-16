@php
    $activemenu = $activemenu ?? '';
@endphp
<div class="sidebar">
    <!-- Sidebar Menu -->
    <nav class="mt-3">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
            
            <!-- Dashboard -->
            <li class="nav-item">
                <a href="{{ route('dashboard') }}" class="nav-link {{ ($activemenu == 'dashboard')? 'active' : '' }}">
                    <i class="nav-icon fas fa-home"></i>
                    <p>Beranda</p>
                </a>
            </li>

            <!-- Master Data -->
            @canany(['manage-master-data', 'view-barang', 'view-toko', 'view-customer'])
            <li class="nav-item has-treeview {{ (in_array($activemenu, ['barang', 'toko', 'barang-toko', 'customer']))? 'menu-open' : '' }}">
                <a href="#" class="nav-link {{ (in_array($activemenu, ['barang', 'toko', 'barang-toko', 'customer']))? 'active' : '' }}">
                    <i class="nav-icon fas fa-database"></i>
                    <p>
                        Master Data
                        <i class="right fas fa-angle-left"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview">
                    @can('view-barang')
                    <li class="nav-item">
                        <a href="{{ route('barang.index') }}" class="nav-link {{ ($activemenu == 'barang')? 'active' : '' }}">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Data Barang</p>
                        </a>
                    </li>
                    @endcan
                    
                    @can('view-toko')
                    <li class="nav-item">
                        <a href="{{ route('toko.index') }}" class="nav-link {{ ($activemenu == 'toko')? 'active' : '' }}">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Data Toko</p>
                        </a>
                    </li>
                    @endcan
                    
                    @can('view-barang-toko')
                    <li class="nav-item">
                        <a href="{{ route('barang-toko.index') }}" class="nav-link {{ ($activemenu == 'barang-toko')? 'active' : '' }}">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Barang per Toko</p>
                        </a>
                    </li>
                    @endcan
                    
                    @can('view-customer')
                    <li class="nav-item">
                        <a href="{{ route('customer.index') }}" class="nav-link {{ ($activemenu == 'customer')? 'active' : '' }}">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Data Customer</p>
                        </a>
                    </li>
                    @endcan
                </ul>
            </li>
            @endcanany

            <!-- Transaksi -->
            @canany(['view-pengiriman', 'view-retur', 'view-pemesanan'])
            <li class="nav-item has-treeview {{ (in_array($activemenu, ['pengiriman', 'retur', 'pemesanan']))? 'menu-open' : '' }}">
                <a href="#" class="nav-link {{ (in_array($activemenu, ['pengiriman', 'retur', 'pemesanan']))? 'active' : '' }}">
                    <i class="nav-icon fas fa-exchange-alt"></i>
                    <p>
                        Transaksi
                        <i class="right fas fa-angle-left"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview">
                    @can('view-pengiriman')
                    <li class="nav-item">
                        <a href="{{ route('pengiriman.index') }}" class="nav-link {{ ($activemenu == 'pengiriman')? 'active' : '' }}">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Pengiriman Barang</p>
                        </a>
                    </li>
                    @endcan
                    
                    @can('view-retur')
                    <li class="nav-item">
                        <a href="{{ route('retur.index') }}" class="nav-link {{ ($activemenu == 'retur')? 'active' : '' }}">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Retur Barang</p>
                        </a>
                    </li>
                    @endcan
                    
                    @can('view-pemesanan')
                    <li class="nav-item">
                        <a href="{{ route('pemesanan.index') }}" class="nav-link {{ ($activemenu == 'pemesanan')? 'active' : '' }}">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Pemesanan</p>
                        </a>
                    </li>
                    @endcan
                </ul>
            </li>
            @endcanany
            
            <!-- Analytics Partner Performance -->
            @can('view-partner-performance')
            <li class="nav-item">
                <a href="{{ route('analytics.partner-performance.index') }}" class="nav-link {{ ($activemenu == 'analytics.partner-performance')? 'active' : '' }}">
                    <i class="nav-icon fas fa-chart-line"></i>
                    <p>Partner Performance</p>
                </a>
            </li>
            @endcan

            <!-- Follow Up Pelanggan -->
            @can('view-follow-up')
            <li class="nav-item">
                <a href="{{ route('follow-up-pelanggan.index') }}" class="nav-link {{ ($activemenu == 'follow-up-pelanggan')? 'active' : '' }}">
                    <i class="nav-icon fas fa-envelope-open-text"></i>
                    <p>Follow Up Pelanggan</p>
                </a>
            </li>
            @endcan
            <!-- Sistem Pengaturan -->
            @canany(['manage-users', 'manage-notification-settings', 'view-eoq-setting', 'view-zscore-setting', 'view-config-interval-kirim'])
            <li class="nav-item has-treeview {{ (in_array($activemenu, ['user', 'role', 'notification-settings', 'eoq-setting', 'zscore-setting', 'config-interval-kirim']))? 'menu-open' : '' }}">
                <a href="#" class="nav-link {{ (in_array($activemenu, ['user', 'role', 'notification-settings', 'eoq-setting', 'zscore-setting', 'config-interval-kirim']))? 'active' : '' }}">
                    <i class="nav-icon fas fa-users-cog"></i>
                    <p>
                        Sistem Pengaturan
                        <i class="right fas fa-angle-left"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview">
                    @can('manage-users')
                    <li class="nav-item">
                        <a href="{{ route('user.index') }}" class="nav-link {{ ($activemenu == 'user')? 'active' : '' }}">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Manajemen User</p>
                        </a>
                    </li>
                    @endcan
                    @can('manage-users')
                    <li class="nav-item">
                        <a href="{{ route('role.index') }}" class="nav-link {{ ($activemenu == 'role')? 'active' : '' }}">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Manajemen Role</p>
                        </a>
                    </li>
                    @endcan
                    @can('manage-notification-settings')
                    <li class="nav-item">
                        <a href="{{ route('notification-settings.index') }}" class="nav-link {{ ($activemenu == 'notification-settings')? 'active' : '' }}">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Pengaturan Notifikasi</p>
                        </a>
                    </li>
                    @endcan
                    @can('view-eoq-setting')
                    <li class="nav-item">
                        <a href="{{ route('eoq-setting.index') }}" class="nav-link {{ ($activemenu == 'eoq-setting')? 'active' : '' }}">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Setting EOQ</p>
                        </a>
                    </li>
                    @endcan
                    @can('view-zscore-setting')
                    <li class="nav-item">
                        <a href="{{ route('zscore-setting.index') }}" class="nav-link {{ ($activemenu == 'zscore-setting')? 'active' : '' }}">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Setting Z-Score</p>
                        </a>
                    </li>
                    @endcan
                    @can('view-config-interval-kirim')
                    <li class="nav-item">
                        <a href="{{ route('config-interval-kirim.index') }}" class="nav-link {{ ($activemenu == 'config-interval-kirim')? 'active' : '' }}">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Interval Pengiriman</p>
                        </a>
                    </li>
                    @endcan
                </ul>
            </li>
            @endcanany

        </ul>
    </nav>
</div>
