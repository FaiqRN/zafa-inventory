<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EoqBiayaSimpan extends Model
{
    use HasFactory;

    public const TABLE = 'eoq_biaya_simpan';
    public const FIELD_ID = 'id';
    public const FIELD_BARANG_ID = 'barang_id';
    public const FIELD_HARGA_POKOK = 'harga_pokok';
    public const FIELD_NAMA_KOMPONEN = 'nama_komponen';
    public const FIELD_PERSENTASE = 'persentase';
    public const FIELD_KETERANGAN = 'keterangan';
    public const FIELD_CREATED_AT = 'created_at';
    public const FIELD_UPDATED_AT = 'updated_at';
    public const FIELD_USER_CREATE = 'user_create';
    public const FIELD_USER_UPDATE = 'user_update';

    protected $table = self::TABLE;
    protected $primaryKey = self::FIELD_ID;
    public $timestamps = true;

    protected $fillable = [
        self::FIELD_BARANG_ID,
        self::FIELD_HARGA_POKOK,
        self::FIELD_NAMA_KOMPONEN,
        self::FIELD_PERSENTASE,
        self::FIELD_KETERANGAN,
        self::FIELD_USER_CREATE,
        self::FIELD_USER_UPDATE,
    ];

    protected $casts = [
        self::FIELD_HARGA_POKOK => 'decimal:2',
        self::FIELD_PERSENTASE => 'decimal:2',
        self::FIELD_CREATED_AT => 'datetime',
        self::FIELD_UPDATED_AT => 'datetime',
    ];


    public function barang()
    {
        return $this->belongsTo(Barang::class, self::FIELD_BARANG_ID, Barang::FIELD_BARANG_ID);
    }

    public function getBiayaSimpanPerUnitAttribute(): float
    {
        return (float) ($this->{self::FIELD_HARGA_POKOK} * $this->{self::FIELD_PERSENTASE} / 100);
    }

    public static function getTotalBiayaSimpanForBarang(string $barangId): float
    {
        $components = self::where(self::FIELD_BARANG_ID, $barangId)->get();
        
        if ($components->isEmpty()) {
            return 0;
        }

        $hargaPokok = $components->first()->{self::FIELD_HARGA_POKOK};
        $totalPersentase = $components->sum(self::FIELD_PERSENTASE);

        return (float) ($hargaPokok * $totalPersentase / 100);
    }

    public function scopeForBarang($query, string $barangId)
    {
        return $query->where(self::FIELD_BARANG_ID, $barangId);
    }
}
