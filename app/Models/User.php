<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    public const TABLE = 'user';
    public const FIELD_USER_ID = 'user_id';
    public const FIELD_ROLE_ID = 'role_id';
    public const FIELD_USERNAME = 'username';
    public const FIELD_PASSWORD = 'password';
    public const FIELD_FIRSTNAME = 'firstname';
    public const FIELD_LASTNAME = 'lastname';
    public const FIELD_FOTO = 'foto';
    public const FIELD_JENIS_KELAMIN = 'jenis_kelamin';
    public const FIELD_TEMPAT_LAHIR = 'tempat_lahir';
    public const FIELD_TANGGAL_LAHIR = 'tanggal_lahir';
    public const FIELD_ALAMAT = 'alamat';
    public const FIELD_EMAIL = 'email';
    public const FIELD_EMAIL_VERIFIED_AT = 'email_verified_at';
    public const FIELD_TELP = 'telp';
    public const FIELD_REMEMBER_TOKEN = 'remember_token';
    public const FIELD_CREATED_BY = 'created_by';
    public const FIELD_UPDATED_BY = 'updated_by';
    public const FIELD_CREATED_AT = 'created_at';
    public const FIELD_UPDATED_AT = 'updated_at';
    public const FIELD_USER_CREATE = 'user_create';
    public const FIELD_USER_UPDATE = 'user_update';
    public const FIELD_DELETED_AT = 'deleted_at';

    protected $table = self::TABLE;
    protected $primaryKey = self::FIELD_USER_ID;

    protected $fillable = [
        self::FIELD_ROLE_ID,
        self::FIELD_USERNAME,
        self::FIELD_PASSWORD,
        self::FIELD_FIRSTNAME,
        self::FIELD_LASTNAME,
        self::FIELD_FOTO,
        self::FIELD_JENIS_KELAMIN,
        self::FIELD_TEMPAT_LAHIR,
        self::FIELD_TANGGAL_LAHIR,
        self::FIELD_ALAMAT,
        self::FIELD_EMAIL,
        self::FIELD_TELP,
        self::FIELD_CREATED_BY,
        self::FIELD_UPDATED_BY,
        self::FIELD_USER_CREATE,
        self::FIELD_USER_UPDATE,
    ];

    protected $hidden = [
        self::FIELD_PASSWORD,
        self::FIELD_REMEMBER_TOKEN,
    ];

    protected $casts = [
        self::FIELD_TANGGAL_LAHIR => 'date',
        self::FIELD_EMAIL_VERIFIED_AT => 'datetime',
        self::FIELD_CREATED_AT => 'datetime',
        self::FIELD_UPDATED_AT => 'datetime',
        self::FIELD_DELETED_AT => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::updating(function ($user) {
            if ($user->isDirty(self::FIELD_PASSWORD) && !empty($user->{self::FIELD_PASSWORD})) {
                if (!preg_match('/^\$2[ay]\$\d{2}\$/', $user->{self::FIELD_PASSWORD})) {
                    $user->{self::FIELD_PASSWORD} = Hash::make($user->{self::FIELD_PASSWORD});
                }
            }
        });

        static::creating(function ($user) {
            if (!empty($user->{self::FIELD_PASSWORD}) && !preg_match('/^\$2[ay]\$\d{2}\$/', $user->{self::FIELD_PASSWORD})) {
                $user->{self::FIELD_PASSWORD} = Hash::make($user->{self::FIELD_PASSWORD});
            }
        });
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, self::FIELD_ROLE_ID, Role::FIELD_ROLE_ID);
    }

    public function getUmurAttribute()
    {
        if (!$this->{self::FIELD_TANGGAL_LAHIR}) {
            return null;
        }

        return $this->{self::FIELD_TANGGAL_LAHIR}->age;
    }

    public function getNamaLengkapAttribute()
    {
        return trim($this->{self::FIELD_FIRSTNAME} . ' ' . $this->{self::FIELD_LASTNAME});
    }

    public function getInisialAttribute()
    {
        $inisial = '';
        
        if (strlen($this->{self::FIELD_FIRSTNAME}) > 0) {
            $inisial .= strtoupper(substr($this->{self::FIELD_FIRSTNAME}, 0, 1));
        }
        
        if (strlen($this->{self::FIELD_LASTNAME}) > 0) {
            $inisial .= strtoupper(substr($this->{self::FIELD_LASTNAME}, 0, 1));
        }

        return $inisial;
    }

    public function getFotoUrlAttribute()
    {
        if ($this->{self::FIELD_FOTO}) {
            return asset($this->{self::FIELD_FOTO});
        }
        
        return asset('dist/img/user2-160x160.jpg');
    }

    public function getNamaLengkapFormattedAttribute()
    {
        return ucwords(strtolower($this->nama_lengkap));
    }

    public function getJenisKelaminTextAttribute()
    {
        if ($this->{self::FIELD_JENIS_KELAMIN} === 'L') {
            return 'Laki-laki';
        } elseif ($this->{self::FIELD_JENIS_KELAMIN} === 'P') {
            return 'Perempuan';
        }
        
        return null;
    }

    public function creator()
    {
        if ($this->{self::FIELD_CREATED_BY}) {
            return User::where(self::FIELD_CREATED_BY, $this->{self::FIELD_CREATED_BY})->first();
        }
        
        return null;
    }

    public function updater()
    {
        if ($this->{self::FIELD_UPDATED_BY}) {
            return User::where(self::FIELD_UPDATED_BY, $this->{self::FIELD_UPDATED_BY})->first();
        }
        
        return null;
    }

    /**
     * Get the name of the unique identifier for the user.
     *
     * @return string
     */
    public function getAuthIdentifierName(): string
    {
        return self::FIELD_USERNAME;
    }

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword(): string
    {
        return $this->{self::FIELD_PASSWORD};
    }
}
