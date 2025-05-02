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
                    <i class="nav-icon fas fa-tachometer-alt"></i>
                    <p>Dashboard</p>
                </a>
            </li>

            <!-- Master Data -->
            <li class="nav-item">
                <a href="#" class="nav-link {{ (in_array($activemenu, ['barang', 'toko', 'barang-toko', 'user']))? 'active' : '' }}">
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
                        <a href="{{ url('/user') }}" class="nav-link {{ ($activemenu == 'user')? 'active' : '' }}">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Data Pengguna</p>
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

            <!-- Laporan -->
            <li class="nav-item">
                <a href="#" class="nav-link {{ (in_array($activemenu, ['laporan-penjualan', 'laporan-toko', 'laporan-barang']))? 'active' : '' }}">
                    <i class="nav-icon fas fa-chart-line"></i>
                    <p>
                        Laporan
                        <i class="right fas fa-angle-left"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview">
                    <li class="nav-item">
                        <a href="{{ url('/laporan-penjualan') }}" class="nav-link {{ ($activemenu == 'laporan-penjualan')? 'active' : '' }}">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Laporan Penjualan</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ url('/laporan-toko') }}" class="nav-link {{ ($activemenu == 'laporan-toko')? 'active' : '' }}">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Laporan Per Toko</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ url('/laporan-barang') }}" class="nav-link {{ ($activemenu == 'laporan-barang')? 'active' : '' }}">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Laporan Per Barang</p>
                        </a>
                    </li>
                </ul>
            </li>

            <!-- Pengaturan -->
            <li class="nav-item">
                <a href="{{ url('/setting') }}" class="nav-link {{ ($activemenu == 'setting')? 'active' : '' }}">
                    <i class="nav-icon fas fa-cog"></i>
                    <p>Pengaturan</p>
                </a>
            </li>
        </ul>
    </nav>
</div>