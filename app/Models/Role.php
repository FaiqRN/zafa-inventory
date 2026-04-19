<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    public const TABLE = 'role';
    public const FIELD_ROLE_ID = 'role_id';
    public const FIELD_NAMA_ROLE = 'nama_role';
    public const FIELD_DESKRIPSI = 'deskripsi';

    protected $table = self::TABLE;
    protected $primaryKey = self::FIELD_ROLE_ID;

    protected $fillable = [
        self::FIELD_NAMA_ROLE,
        self::FIELD_DESKRIPSI,
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'role_id', self::FIELD_ROLE_ID);
    }

    /**
     * Get corresponding Spatie role
     */
    public function spatieRole()
    {
        return \Spatie\Permission\Models\Role::where('name', $this->nama_role)->first();
    }
}
