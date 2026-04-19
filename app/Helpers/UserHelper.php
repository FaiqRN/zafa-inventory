<?php

namespace App\Helpers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Collection;

class UserHelper
{

    public static function generateUserId(): int
    {
        $lastUser = User::orderBy(User::FIELD_USER_ID, 'desc')->first();
        return $lastUser ? $lastUser->{User::FIELD_USER_ID} + 1 : 1;
    }

    public static function getAllUsersWithRole(): Collection
    {
        return User::with('role')
            ->whereNull(User::FIELD_DELETED_AT)
            ->orderBy(User::FIELD_CREATED_AT, 'desc')
            ->get();
    }

    public static function getUserById(int $userId): ?User
    {
        return User::with('role')->find($userId);
    }

    public static function getAllRoles(bool $forCreate = false): Collection
    {
        $query = Role::orderBy(Role::FIELD_NAMA_ROLE, 'asc');
        
        if ($forCreate) {
            $query->where(Role::FIELD_NAMA_ROLE, '!=', 'Admin')
                  ->where(Role::FIELD_NAMA_ROLE, '!=', 'admin')
                  ->where(Role::FIELD_NAMA_ROLE, '!=', 'Administrator');
        }
        
        return $query->get();
    }

    public static function getValidationRules(?int $userId = null): array
    {
        $uniqueEmail = 'required|string|email|max:100|unique:' . User::TABLE . ',' . User::FIELD_EMAIL;
        $uniqueUsername = 'required|string|max:50|unique:' . User::TABLE . ',' . User::FIELD_USERNAME;

        if ($userId) {
            $uniqueEmail .= ',' . $userId . ',' . User::FIELD_USER_ID;
            $uniqueUsername .= ',' . $userId . ',' . User::FIELD_USER_ID;
        }

        $rules = [
            'role_id' => 'required|exists:' . Role::TABLE . ',' . Role::FIELD_ROLE_ID,
            'username' => $uniqueUsername,
            'firstname' => 'required|string|max:100',
            'lastname' => 'nullable|string|max:100',
            'email' => $uniqueEmail,
            'telp' => 'required|string|min:10|max:50',
            'alamat' => 'required|string',
            'jenis_kelamin' => 'nullable|in:L,P',
            'tempat_lahir' => 'nullable|string|max:100',
            'tanggal_lahir' => 'nullable|date',
        ];

        if (!$userId) {
            $rules['password'] = 'required|string|min:8|confirmed';
        } else {
            $rules['password'] = 'nullable|string|min:8|confirmed';
        }

        return $rules;
    }

    public static function getValidationMessages(): array
    {
        return [
            'role_id.required' => 'Role wajib dipilih',
            'role_id.exists' => 'Role tidak valid',
            'username.required' => 'Username wajib diisi',
            'username.unique' => 'Username sudah digunakan',
            'firstname.required' => 'Nama depan wajib diisi',
            'email.required' => 'Email wajib diisi',
            'email.email' => 'Format email tidak valid',
            'email.unique' => 'Email sudah digunakan',
            'telp.required' => 'Nomor telepon wajib diisi',
            'telp.min' => 'Nomor telepon minimal 10 digit',
            'alamat.required' => 'Alamat wajib diisi',
            'password.required' => 'Password wajib diisi',
            'password.min' => 'Password minimal 8 karakter',
            'password.confirmed' => 'Konfirmasi password tidak cocok',
        ];
    }

    public static function createUser(array $data, string $createdBy): User
    {
        $user = new User();
        $user->{User::FIELD_ROLE_ID} = $data['role_id'];
        $user->{User::FIELD_USERNAME} = $data['username'];
        $user->{User::FIELD_PASSWORD} = Hash::make($data['password']);
        $user->{User::FIELD_FIRSTNAME} = $data['firstname'];
        $user->{User::FIELD_LASTNAME} = $data['lastname'] ?? null;
        $user->{User::FIELD_EMAIL} = $data['email'];
        $user->{User::FIELD_TELP} = $data['telp'];
        $user->{User::FIELD_ALAMAT} = $data['alamat'];
        $user->{User::FIELD_JENIS_KELAMIN} = $data['jenis_kelamin'] ?? null;
        $user->{User::FIELD_TEMPAT_LAHIR} = $data['tempat_lahir'] ?? null;
        $user->{User::FIELD_TANGGAL_LAHIR} = $data['tanggal_lahir'] ?? null;
        $user->{User::FIELD_CREATED_BY} = $createdBy;
        $user->{User::FIELD_USER_CREATE} = $createdBy;
        $user->save();
        if ($user->role) {
            $user->syncRoles([$user->role->nama_role]);
        }
        return $user->fresh(['role']);
    }

    public static function updateUser(User $user, array $data, string $updatedBy): User
    {
        $user->{User::FIELD_ROLE_ID} = $data['role_id'];
        $user->{User::FIELD_USERNAME} = $data['username'];
        $user->{User::FIELD_FIRSTNAME} = $data['firstname'];
        $user->{User::FIELD_LASTNAME} = $data['lastname'] ?? null;
        $user->{User::FIELD_EMAIL} = $data['email'];
        $user->{User::FIELD_TELP} = $data['telp'];
        $user->{User::FIELD_ALAMAT} = $data['alamat'];
        $user->{User::FIELD_JENIS_KELAMIN} = $data['jenis_kelamin'] ?? null;
        $user->{User::FIELD_TEMPAT_LAHIR} = $data['tempat_lahir'] ?? null;
        $user->{User::FIELD_TANGGAL_LAHIR} = $data['tanggal_lahir'] ?? null;
        $user->{User::FIELD_UPDATED_BY} = $updatedBy;
        $user->{User::FIELD_USER_UPDATE} = $updatedBy;
        if (!empty($data['password'])) {
            $user->{User::FIELD_PASSWORD} = Hash::make($data['password']);
        }
        $user->save();
        if ($user->role) {
            $user->syncRoles([$user->role->nama_role]);
        }
        return $user->fresh(['role']);
    }

    public static function deleteUser(User $user): bool
    {
        return $user->delete();
    }

    public static function formatForDataTable(Collection $users): array
    {
        return $users->map(function ($user) {
            return [
                'user_id' => $user->{User::FIELD_USER_ID},
                'username' => $user->{User::FIELD_USERNAME},
                'nama_lengkap' => $user->nama_lengkap,
                'email' => $user->{User::FIELD_EMAIL},
                'telp' => $user->{User::FIELD_TELP},
                'role_nama' => $user->role->{Role::FIELD_NAMA_ROLE} ?? '-',
                'jenis_kelamin' => $user->jenis_kelamin_text ?? '-',
                'created_at' => $user->{User::FIELD_CREATED_AT}->format('d-m-Y H:i'),
            ];
        })->toArray();
    }
}
