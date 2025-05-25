<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Toko extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terkait dengan model.
     *
     * @var string
     */
    protected $table = 'toko';

    /**
     * Primary key tabel.
     *
     * @var string
     */
    protected $primaryKey = 'toko_id';

    /**
     * Tipe primary key.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Menentukan apakah model menggunakan timestamps.
     *
     * @var bool
     */
    public $timestamps = true; // Ubah ke true untuk mendukung created_at dan updated_at

    /**
     * Atribut yang dapat diisi (mass assignable).
     *
     * @var array
     */
    protected $fillable = [
        'toko_id',
        'nama_toko',
        'pemilik',
        'alamat',
        'wilayah_kecamatan',
        'wilayah_kelurahan',
        'wilayah_kota_kabupaten',
        'nomer_telpon',
        // Tambahan untuk geocoding
        'latitude',
        'longitude',
        'is_active',
        'catatan_lokasi',
        'alamat_lengkap_geocoding'
    ];

    /**
     * Tipe casting untuk kolom
     *
     * @var array
     */
    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_active' => 'boolean'
    ];

    /**
     * Relasi ke tabel barang_toko.
     */
    public function barangToko()
    {
        return $this->hasMany(BarangToko::class, 'toko_id', 'toko_id');
    }

    /**
     * Relasi ke tabel pengiriman.
     */
    public function pengiriman()
    {
        return $this->hasMany(Pengiriman::class, 'toko_id', 'toko_id');
    }

    /**
     * Relasi ke tabel retur.
     */
    public function retur()
    {
        return $this->hasMany(Retur::class, 'toko_id', 'toko_id');
    }
    
    /**
     * Relasi ke tabel barang melalui barang_toko.
     */
    public function barang()
    {
        return $this->belongsToMany(Barang::class, 'barang_toko', 'toko_id', 'barang_id')
                    ->withPivot('barang_toko_id', 'harga_barang_toko');
    }

    /**
     * Scope untuk toko aktif
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope untuk toko dengan koordinat
     */
    public function scopeWithCoordinates($query)
    {
        return $query->whereNotNull('latitude')
                    ->whereNotNull('longitude');
    }

    /**
     * Scope berdasarkan wilayah
     */
    public function scopeByWilayah($query, $kota = null, $kecamatan = null, $kelurahan = null)
    {
        if ($kota) {
            $query->where('wilayah_kota_kabupaten', $kota);
        }
        
        if ($kecamatan) {
            $query->where('wilayah_kecamatan', $kecamatan);
        }
        
        if ($kelurahan) {
            $query->where('wilayah_kelurahan', $kelurahan);
        }
        
        return $query;
    }

    /**
     * Accessor untuk full address
     */
    public function getFullAddressAttribute()
    {
        return $this->alamat . ', ' . $this->wilayah_kelurahan . ', ' . $this->wilayah_kecamatan . ', ' . $this->wilayah_kota_kabupaten;
    }

    /**
     * Accessor untuk koordinat dalam format string
     */
    public function getCoordinatesAttribute()
    {
        if ($this->latitude && $this->longitude) {
            return $this->latitude . ',' . $this->longitude;
        }
        return null;
    }

    /**
     * Method untuk menghitung jarak dari koordinat lain (dalam km)
     */
    public function getDistanceFrom($lat, $lng)
    {
        if (!$this->latitude || !$this->longitude) {
            return null;
        }

        $earthRadius = 6371; // Radius bumi dalam km

        $dLat = deg2rad($lat - $this->latitude);
        $dLng = deg2rad($lng - $this->longitude);

        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($this->latitude)) * cos(deg2rad($lat)) *
             sin($dLng/2) * sin($dLng/2);

        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        $distance = $earthRadius * $c;

        return round($distance, 2);
    }

    /**
     * Method untuk mendapatkan statistik toko
     */
    public function getStatistics()
    {
        $totalPengiriman = $this->pengiriman()->where('status', 'terkirim')->count();
        $totalRetur = $this->retur()->count();
        $jenisBarang = $this->barangToko()->count();
        $pengirimanBulanIni = $this->pengiriman()
            ->where('status', 'terkirim')
            ->whereMonth('tanggal_pengiriman', date('m'))
            ->whereYear('tanggal_pengiriman', date('Y'))
            ->count();

        $successRate = $totalPengiriman > 0 ? 
            round((($totalPengiriman - $totalRetur) / $totalPengiriman) * 100, 2) : 0;

        return [
            'total_pengiriman' => $totalPengiriman,
            'total_retur' => $totalRetur,
            'jenis_barang' => $jenisBarang,
            'pengiriman_bulan_ini' => $pengirimanBulanIni,
            'success_rate' => $successRate
        ];
    }

    /**
     * Method untuk mendapatkan toko terdekat
     */
    public static function getNearbyTokos($lat, $lng, $radiusKm = 5, $limit = 10)
    {
        return self::withCoordinates()
            ->active()
            ->selectRaw("
                *,
                (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance
            ", [$lat, $lng, $lat])
            ->having('distance', '<=', $radiusKm)
            ->orderBy('distance')
            ->limit($limit)
            ->get();
    }

    /**
     * Method untuk validasi koordinat
     */
    public function isValidCoordinates()
    {
        return $this->latitude >= -90 && $this->latitude <= 90 &&
               $this->longitude >= -180 && $this->longitude <= 180;
    }

    /**
     * Method untuk mendapatkan alamat lengkap untuk geocoding
     */
    public function getGeocodingAddressAttribute()
    {
        return trim($this->alamat . ', ' . $this->wilayah_kelurahan . ', ' . $this->wilayah_kecamatan . ', ' . $this->wilayah_kota_kabupaten . ', Indonesia');
    }
}