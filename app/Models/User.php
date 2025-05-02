<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * Nama tabel yang terkait dengan model.
     *
     * @var string
     */
    protected $table = 'user';

    /**
     * Primary key tabel.
     *
     * @var string
     */
    protected $primaryKey = 'user_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'password',
        'nama_lengkap',
        'foto',
        'jenis_kelamin',
        'tempat_lahir',
        'tanggal_lahir',
        'alamat',
        'email',
        'telp',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tanggal_lahir' => 'date',
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Mendapatkan umur user (dari tanggal lahir).
     *
     * @return int|null
     */
    public function getUmurAttribute()
    {
        if (!$this->tanggal_lahir) {
            return null;
        }

        return $this->tanggal_lahir->age;
    }

    /**
     * Mendapatkan inisial nama untuk avatar.
     *
     * @return string
     */
    public function getInisialAttribute()
    {
        $namaParts = explode(' ', $this->nama_lengkap);
        $inisial = '';

        foreach ($namaParts as $part) {
            if (strlen($part) > 0) {
                $inisial .= strtoupper(substr($part, 0, 1));
            }
            
            if (strlen($inisial) >= 2) {
                break;
            }
        }

        return $inisial;
    }

    /**
     * Mendapatkan URL foto atau avatar default.
     *
     * @return string
     */
    public function getFotoUrlAttribute()
    {
        if ($this->foto) {
            return asset($this->foto);
        }
        
        return asset('dist/img/user2-160x160.jpg');
    }

    /**
     * Mendapatkan nama lengkap dalam format title case.
     *
     * @return string
     */
    public function getNamaLengkapFormattedAttribute()
    {
        return ucwords(strtolower($this->nama_lengkap));
    }

    /**
     * Mendapatkan jenis kelamin dalam format yang lebih mudah dibaca.
     *
     * @return string|null
     */
    public function getJenisKelaminTextAttribute()
    {
        if ($this->jenis_kelamin === 'L') {
            return 'Laki-laki';
        } elseif ($this->jenis_kelamin === 'P') {
            return 'Perempuan';
        }
        
        return null;
    }

    /**
     * Mendapatkan informasi pembuat akun.
     *
     * @return User|null
     */
    public function creator()
    {
        if ($this->created_by) {
            return User::where('username', $this->created_by)->first();
        }
        
        return null;
    }

    /**
     * Mendapatkan informasi terakhir yang mengubah akun.
     *
     * @return User|null
     */
    public function updater()
    {
        if ($this->updated_by) {
            return User::where('username', $this->updated_by)->first();
        }
        
        return null;
    }
}