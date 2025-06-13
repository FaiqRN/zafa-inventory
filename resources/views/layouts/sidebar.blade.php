@php
    $activemenu = $activemenu ?? '';
@endphp
<div class="sidebar">
    <!-- Sidebar Menu -->
    <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
            <!-- Dashboard -->
            <li class="nav-item">
                <a href="{{ url('/') }}" class="nav-link {{ ($activemenu == 'dashboard')? 'active' : '' }}">
                    <i class="nav-icon fas fa-home"></i>
                    <p>Beranda</p>
                </a>
            </li>

            <!-- Master Data -->
            <li class="nav-item">
                <a href="#" class="nav-link {{ (in_array($activemenu, ['barang', 'toko', 'barang-toko', 'customer']))? 'active' : '' }}">
                    <i class="nav-icon fas fa-database"></i>
                    <p>
                        Master Data
                        <i class="right fas fa-angle-left"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview">
                    <li class="nav-item">
                        <a href="{{ url('/barang') }}" class="nav-link {{ ($activemenu == 'barang')? 'active' : '' }}">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Data Barang</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ url('/toko') }}" class="nav-link {{ ($activemenu == 'toko')? 'active' : '' }}">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Data Toko</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ url('/barang-toko') }}" class="nav-link {{ ($activemenu == 'barang-toko')? 'active' : '' }}">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Barang per Toko</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ url('/customer') }}" class="nav-link {{ ($activemenu == 'customer')? 'active' : '' }}">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Data Customer</p>
                        </a>
                    </li>
                </ul>
            </li>

            <!-- Transaksi -->
            <li class="nav-item">
                <a href="#" class="nav-link {{ (in_array($activemenu, ['pengiriman', 'retur', 'pemesanan']))? 'active' : '' }}">
                    <i class="nav-icon fas fa-exchange-alt"></i>
                    <p>
                        Transaksi
                        <i class="right fas fa-angle-left"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview">
                    <li class="nav-item">
                        <a href="{{ url('/pengiriman') }}" class="nav-link {{ ($activemenu == 'pengiriman')? 'active' : '' }}">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Pengiriman Barang</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ url('/retur') }}" class="nav-link {{ ($activemenu == 'retur')? 'active' : '' }}">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Retur Barang</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ url('/pemesanan') }}" class="nav-link {{ ($activemenu == 'pemesanan')? 'active' : '' }}">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Pemesanan</p>
                        </a>
                    </li>
                </ul>
            </li>

            <!-- Analytics - NEW ADVANCED SECTION -->
            <li class="nav-item">
                <a href="#" class="nav-link {{ (in_array($activemenu, ['analytics', 'analytics.partner-performance', 'analytics.inventory-optimization', 'analytics.product-velocity', 'analytics.profitability-analysis']))? 'active' : '' }}">
                    <i class="nav-icon fas fa-brain"></i>
                    <p>
                        Analytics
                        <i class="right fas fa-angle-left"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview">
                    <li class="nav-item">
                        <a href="{{ route('analytics.index') }}" class="nav-link {{ ($activemenu == 'analytics')? 'active' : '' }}">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Analytics Dashboard</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('analytics.partner-performance.index') }}" class="nav-link {{ ($activemenu == 'analytics.partner-performance')? 'active' : '' }}">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Partner Performance</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('analytics.inventory-optimization.index') }}" class="nav-link {{ ($activemenu == 'analytics.inventory-optimization')? 'active' : '' }}">
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

            <!-- Laporan -->
            <li class="nav-item">
                <a href="#" class="nav-link {{ (in_array($activemenu, ['laporan-pemesanan', 'laporan-toko']))? 'active' : '' }}">
                    <i class="nav-icon fas fa-file-alt"></i>
                    <p>
                        Laporan 
                        <i class="right fas fa-angle-left"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview">
                    <li class="nav-item">
                        <a href="{{ url('/laporan-pemesanan') }}" class="nav-link {{ ($activemenu == 'laporan-pemesanan')? 'active' : '' }}">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Laporan Pemesanan</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ url('/laporan-toko') }}" class="nav-link {{ ($activemenu == 'laporan-toko')? 'active' : '' }}">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Laporan Per Toko</p>
                        </a>
                    </li>
                </ul>
            </li>

            <!-- Market Map -->
            <li class="nav-item">
                <a href="{{ url('/market-map') }}" class="nav-link {{ ($activemenu == 'market-map')? 'active' : '' }}">
                    <i class="nav-icon fas fa-map"></i>
                    <p>Market Map</p>
                </a>
            </li>

            <!-- Follow Up Pelanggan -->
            <li class="nav-item">
                <a href="{{ url('/follow-up-pelanggan') }}" class="nav-link {{ ($activemenu == 'follow-up-pelanggan')? 'active' : '' }}">
                    <i class="nav-icon fas fa-envelope-open-text"></i>
                    <p>Follow Up Pelanggan</p>
                </a>
            </li>

            <!-- Pengaturan -->
            <li class="nav-item">
                <a href="#" class="nav-link {{ (in_array($activemenu, ['profile.edit', 'profile.change-password']))? 'active' : '' }}">
                    <i class="nav-icon fas fa-cog"></i>
                    <p>
                        Pengaturan
                        <i class="right fas fa-angle-left"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview">
                    <li class="nav-item">
                        <a href="{{ route('profile.edit') }}" class="nav-link {{ ($activemenu == 'profile.edit')? 'active' : '' }}">
                            <i class="fas fa-pencil-alt nav-icon"></i>
                            <p>Edit Profile</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('profile.change-password') }}" class="nav-link {{ ($activemenu == 'profile.change-password')? 'active' : '' }}">
                            <i class="fas fa-sync-alt nav-icon"></i>
                            <p>Ubah Password</p>
                        </a>
                    </li>
                </ul>
            </li>
        </ul>
    </nav>
</div>

