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
            @can('manage-master-data')
            <li class="nav-item has-treeview {{ (in_array($activemenu, ['barang', 'toko', 'barang-toko', 'customer']))? 'menu-open' : '' }}">
                <a href="#" class="nav-link {{ (in_array($activemenu, ['barang', 'toko', 'barang-toko', 'customer']))? 'active' : '' }}">
                    <i class="nav-icon fas fa-database"></i>
                    <p>
                        Master Data
                        <i class="right fas fa-angle-left"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview">
                    <li class="nav-item">
                        <a href="{{ route('barang.index') }}" class="nav-link {{ ($activemenu == 'barang')? 'active' : '' }}">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Data Barang</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('toko.index') }}" class="nav-link {{ ($activemenu == 'toko')? 'active' : '' }}">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Data Toko</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('barang-toko.index') }}" class="nav-link {{ ($activemenu == 'barang-toko')? 'active' : '' }}">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Barang per Toko</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('customer.index') }}" class="nav-link {{ ($activemenu == 'customer')? 'active' : '' }}">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Data Customer</p>
                        </a>
                    </li>
                </ul>
            </li>
            @endcan

            <!-- Data Barang for Karyawan (who can't access full Master Data) -->
            @cannot('manage-master-data')
                @can('view-barang')
                <li class="nav-item">
                    <a href="{{ route('barang.index') }}" class="nav-link {{ ($activemenu == 'barang')? 'active' : '' }}">
                        <i class="nav-icon fas fa-box"></i>
                        <p>Data Barang</p>
                    </a>
                </li>
                @endcan
            @endcannot

            <!-- Transaksi -->
            <li class="nav-item has-treeview {{ (in_array($activemenu, ['pengiriman', 'retur', 'pemesanan']))? 'menu-open' : '' }}">
                <a href="#" class="nav-link {{ (in_array($activemenu, ['pengiriman', 'retur', 'pemesanan']))? 'active' : '' }}">
                    <i class="nav-icon fas fa-exchange-alt"></i>
                    <p>
                        Transaksi
                        <i class="right fas fa-angle-left"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview">
                    <li class="nav-item">
                        <a href="{{ route('pengiriman.index') }}" class="nav-link {{ ($activemenu == 'pengiriman')? 'active' : '' }}">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Pengiriman Barang</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('retur.index') }}" class="nav-link {{ ($activemenu == 'retur')? 'active' : '' }}">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Retur Barang</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('pemesanan.index') }}" class="nav-link {{ ($activemenu == 'pemesanan')? 'active' : '' }}">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Pemesanan</p>
                        </a>
                    </li>
                </ul>
            </li>

            <!-- Analytics -->
            @can('view-analytics')
            <li class="nav-item has-treeview {{ (in_array($activemenu, ['analytics', 'analytics.partner-performance', 'analytics.inventory-optimization', 'analytics.product-velocity', 'analytics.profitability-analysis']))? 'menu-open' : '' }}">
                <a href="#" class="nav-link {{ (in_array($activemenu, ['analytics', 'analytics.partner-performance', 'analytics.inventory-optimization', 'analytics.product-velocity', 'analytics.profitability-analysis']))? 'active' : '' }}">
                    <i class="nav-icon fas fa-brain"></i>
                    <p>
                        Report
                        <i class="right fas fa-angle-left"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview">
                    <li class="nav-item">
                        <a href="{{ route('analytics.partner-performance.index') }}" class="nav-link {{ ($activemenu == 'analytics.partner-performance')? 'active' : '' }}">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Partner Performance</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('analytics.inventory-optimization.index') }}" class="nav-link {{ ($activemenu == 'analytics.inventory-optimization')?'active': ''}}">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Inventory Optimization</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('analytics.product-velocity.index') }}" class="nav-link {{ ($activemenu == 'analytics.product-velocity')? 'active' : '' }}">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Product Velocity</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('analytics.profitability-analysis.index') }}" class="nav-link {{ ($activemenu == 'analytics.profitability-analysis')? 'active' : '' }}">
                            <i class="far fa-circle nav-icon"></i>
                            <p>True Profitability</p>
                        </a>
                    </li>
                </ul>
            </li>
            @endcan
            
            <!-- Market Map -->
            @can('view-market-map')
            <li class="nav-item">
                <a href="{{ route('market-map.index') }}" class="nav-link {{ ($activemenu == 'market-map')? 'active' : '' }}">
                    <i class="nav-icon fas fa-map-marked-alt"></i>
                    <p>Market Map</p>
                </a>
            </li>
            @endcan

            <!-- Follow Up Pelanggan -->
            <li class="nav-item">
                <a href="{{ route('follow-up-pelanggan.index') }}" class="nav-link {{ ($activemenu == 'follow-up-pelanggan')? 'active' : '' }}">
                    <i class="nav-icon fas fa-envelope-open-text"></i>
                    <p>Follow Up Pelanggan</p>
                </a>
            </li>

            <!-- Sistem Pengaturan -->
            @can('manage-users')
            <li class="nav-item has-treeview {{ (in_array($activemenu, ['user', 'market-map-settings', 'partner-performance-settings', 'seasonal-inventory-settings']))? 'menu-open' : '' }}">
                <a href="#" class="nav-link {{ (in_array($activemenu, ['user', 'market-map-settings', 'partner-performance-settings', 'seasonal-inventory-settings']))? 'active' : '' }}">
                    <i class="nav-icon fas fa-users-cog"></i>
                    <p>
                        Sistem Pengaturan
                        <i class="right fas fa-angle-left"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview">
                    <li class="nav-item">
                        <a href="{{ route('user.index') }}" class="nav-link {{ ($activemenu == 'user')? 'active' : '' }}">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Manajemen User</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('market-map-settings.index') }}" class="nav-link {{ ($activemenu == 'market-map-settings')? 'active' : '' }}">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Pengaturan Market Map</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('partner-performance-settings.index') }}" class="nav-link {{ ($activemenu == 'partner-performance-settings')? 'active' : '' }}">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Pengaturan Partner Performance</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('seasonal-inventory-settings.index') }}" class="nav-link {{ ($activemenu == 'seasonal-inventory-settings')? 'active' : '' }}">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Pengaturan Seasonal Inventory</p>
                        </a>
                    </li>
                </ul>
            </li>
            @endcan

        </ul>
    </nav>
</div>