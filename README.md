# ZafaSys - Inventory Management Made Easy

![Laravel](https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)

> **Solusi All-in-One untuk pengelolaan bisnis yang lebih cerdas**

ZafaSys adalah sistem manajemen inventory dan CRM terintegrasi yang dirancang khusus untuk bisnis Zafa Potato. Kelola pelanggan, pemesanan, dan stok dengan analitik pintar dalam satu platform untuk pengambilan keputusan yang lebih tepat berbasis data.

## 🎯 Tentang Proyek

ZafaSys dikembangkan untuk membantu bisnis Zafa Kering Kentang dalam mengelola operasional bisnis secara efisien, mulai dari manajemen inventory, analisis performa penjualan, hingga optimalisasi strategi bisnis melalui data-driven insights.

## ✨ Fitur Utama

### 🏪 POS (Point of Sale)
Sistem pencatatan transaksi penjualan real-time dengan fitur:
- **Master Data**: Kelola data barang, toko, dan customer
- **Manajemen Stok**: Atur ketersediaan barang per toko dan harga
- **Transaksi**: Pemesanan, pengiriman, dan retur barang
- **Data Customer**: Sistem CRM terintegrasi

### 📊 Sales Report
Laporan penjualan komprehensif dengan visualisasi data:
- **Laporan Pemesanan**: Analisis transaksi berdasarkan rentang waktu
- **Laporan Per Toko**: Perbandingan performa antar cabang
- **Export Data**: Unduh laporan dalam format PDF/Excel

### 🔍 CRM Analytics
Dashboard analitik canggih untuk business intelligence:
- **Analytics Dashboard**: Metrik kinerja bisnis real-time
- **Partner Performance**: Analisis performa mitra berdasarkan grade
- **Inventory Optimization**: Rekomendasi pengelolaan stok optimal dengan algoritma cerdas
- **Product Velocity**: Analisis kecepatan perputaran produk (slow mover hingga hot sellers)
- **True Profitability**: Kalkulasi profit bersih dengan COGS, logistics, dan opportunity cost

### 🗺️ Market Map
Visualisasi distribusi toko berbasis geografis:
- **Peta Interaktif**: Lokasi dan performa toko pada peta
- **Analisis Ekspansi**: Data-driven planning untuk cabang baru
- **Performance Mapping**: Visualisasi performa per lokasi

### 👥 Follow Up
Sistem manajemen komunikasi pelanggan:
- **Scheduled Follow-up**: Jadwal komunikasi tindak lanjut
- **Customer Engagement**: Maintenance loyalitas pelanggan
- **Sales Pipeline**: Tracking prospek dan konversi

## 🛠 Teknologi

- **Backend**: Laravel 10.x
- **Frontend**: Blade Templates + Custom JavaScript
- **Styling**: Custom CSS dengan animasi interaktif
- **Database**: MySQL
- **Maps**: Leaflet.js / Google Maps API
- **Charts**: Chart.js untuk visualisasi data
- **Authentication**: Laravel Auth

## 📋 Persyaratan Sistem

- PHP >= 8.1
- Composer
- Node.js >= 16.x
- MySQL >= 8.0
- Git

## 🚀 Instalasi

### 1. Clone Repository

```bash
git clone https://github.com/FaiqRN/zafa-inventory.git
cd zafa-inventory
```

### 2. Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install Node.js dependencies (jika ada)
npm install
```

### 3. Environment Setup

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 4. Database Configuration

Edit file `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=zafasys_inventory
DB_USERNAME=root
DB_PASSWORD=
```

### 5. Database Migration

```bash
# Run migrations
php artisan migrate

# Seed database dengan data sample
php artisan db:seed
```

### 6. Storage Link

```bash
# Create symbolic link untuk storage
php artisan storage:link
```

### 7. Run Application

```bash
# Start development server
php artisan serve
```

Akses aplikasi di `http://localhost:8000`

## 📱 Video Demo

### Video Demo
[![Demo ZafaSys](https://img.youtube.com/vi/DqcifhglA-s/0.jpg)](https://www.youtube.com/watch?v=DqcifhglA-s)

## 🎮 Penggunaan

### Login Default

Sistem akan memiliki user default setelah seeding:

- **Admin/User**: 
  - Email: `User1`
  - Password: `User123`

### Menu Utama

1. **Master Data**
   - Data Barang
   - Data Toko  
   - Barang Per Toko
   - Data Customer

2. **Transaksi**
   - Pemesanan
   - Pengiriman Barang
   - Retur Barang

3. **Laporan**
   - Laporan Pemesanan
   - Laporan Per Toko

4. **Smart Analytics**
   - Analytics Dashboard
   - Partner Performance
   - Inventory Optimization
   - Product Velocity
   - True Profitability

5. **Market Map**
   - Visualisasi geografis toko

6. **Follow Up**
   - Manajemen komunikasi pelanggan

## 🔧 Konfigurasi

### Maps API

Untuk fitur Market Map, tambahkan API key pada `.env`:

```env
GOOGLE_MAPS_API_KEY=your_google_maps_api_key
```

### Email Configuration

Untuk notifikasi email:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@zafasys.com
MAIL_FROM_NAME="ZafaSys"
```

## 📊 Business Context

### Zafa Potato Business
ZafaSys dikembangkan untuk mendukung bisnis **Zafa Kering Kentang**:
- 🛒 **Beli Produk**: [Shopee Zafa Potato](https://shopee.co.id/tirtomulyo_coffee)
- 🤝 **Jadi Mitra**: [WhatsApp](https://api.whatsapp.com/send/?phone=6282121441930)
- 📱 **Instagram**: [@zafaapotato_](https://www.instagram.com/zafaapotato_/)

### Target Users
- Pemilik bisnis retail/distributor
- Manager toko dan cabang
- Tim sales dan marketing
- Operator gudang dan inventory

## 🧪 Testing

```bash
# Run all tests
php artisan test

# Run specific test
php artisan test tests/Feature/InventoryTest.php

# Generate test coverage
php artisan test --coverage
```

## 🤝 Kontribusi

Kami menyambut kontribusi untuk pengembangan ZafaSys:

1. Fork repository
2. Buat feature branch (`git checkout -b feature/NewFeature`)
3. Commit changes (`git commit -m 'Add NewFeature'`)
4. Push branch (`git push origin feature/NewFeature`)
5. Create Pull Request

### Development Guidelines

- Ikuti PSR-12 coding standards
- Tulis dokumentasi untuk fitur baru
- Pastikan semua tests passing
- Update CHANGELOG.md

## 👥 Tim Pengembang

- **Faiq Ramzy Nabighah** - *Lead Developer* - [@FaiqRN](https://github.com/FaiqRN)
- **Annisa Prissilya** - *Programmer* - [@AnnisaPrisil](https://github.com/AnnisaPrisil)
- **Khoirul Hidayah** - *Programmer* - [@KhoirulHidayah](https://github.com/KhoirulHidayah)

## 🙏 Acknowledgments

- Laravel Framework
- Chart.js untuk visualisasi data
- Leaflet.js untuk mapping
- Bootstrap untuk UI components
- Komunitas open source

## 📞 Support

Butuh bantuan atau ingin bergabung sebagai mitra bisnis?

- 📧 **Email**: zafapotatokitchen@gmail.com
- 💬 **WhatsApp**: [+62 821-2144-1930](https://api.whatsapp.com/send/?phone=6282121441930)
- 🛒 **Beli Produk**: [Shopee](https://shopee.co.id/tirtomulyo_coffee)
- 📱 **Follow Instagram**: [@zafaapotato_](https://www.instagram.com/zafaapotato_/)

---

⭐ **Suka ngemil? Beli produknya, suka jualan? Gabung jadi reseller dan nikmati untungnya!**

💡 *ZafaSys - Inventory Management Made Easy*