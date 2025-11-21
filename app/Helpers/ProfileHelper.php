<?php

namespace App\Helpers;

use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ProfileHelper
{
    /**
     * Validate profile data
     *
     * @param array $data
     * @param int $userId
     * @return array
     */
    public static function getValidationRules(int $userId): array
    {
        return [
            'firstname' => 'required|string|max:100',
            'lastname' => 'nullable|string|max:100',
            'email' => 'required|string|email|max:100|unique:' . User::TABLE . ',' . User::FIELD_EMAIL . ',' . $userId . ',' . User::FIELD_USER_ID,
            'username' => 'required|string|max:20|unique:' . User::TABLE . ',' . User::FIELD_USERNAME . ',' . $userId . ',' . User::FIELD_USER_ID,
            'telp' => 'required|string|min:10|max:50',
            'alamat' => 'required|string',
            'jenis_kelamin' => 'nullable|in:L,P',
            'tempat_lahir' => 'nullable|string|max:100',
            'tanggal_lahir' => 'nullable|date',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ];
    }

    /**
     * Get validation messages
     *
     * @return array
     */
    public static function getValidationMessages(): array
    {
        return [
            'firstname.required' => 'Nama depan wajib diisi',
            'email.required' => 'Email wajib diisi',
            'email.email' => 'Format email tidak valid',
            'email.unique' => 'Email sudah digunakan',
            'username.required' => 'Username wajib diisi',
            'username.unique' => 'Username sudah digunakan',
            'telp.required' => 'Nomor telepon wajib diisi',
            'telp.min' => 'Nomor telepon minimal 10 digit',
            'alamat.required' => 'Alamat wajib diisi',
            'foto.image' => 'File harus berupa gambar',
            'foto.mimes' => 'Format gambar harus jpeg, png, atau jpg',
            'foto.max' => 'Ukuran gambar maksimal 2MB',
        ];
    }

    /**
     * Update user profile data
     *
     * @param User $user
     * @param array $data
     * @return User
     */
    public static function updateProfile(User $user, array $data): User
    {
        $user->{User::FIELD_FIRSTNAME} = $data['firstname'];
        $user->{User::FIELD_LASTNAME} = $data['lastname'] ?? null;
        $user->{User::FIELD_EMAIL} = $data['email'];
        $user->{User::FIELD_USERNAME} = $data['username'];
        $user->{User::FIELD_TELP} = $data['telp'];
        $user->{User::FIELD_ALAMAT} = $data['alamat'];
        $user->{User::FIELD_JENIS_KELAMIN} = $data['jenis_kelamin'] ?? null;
        $user->{User::FIELD_TEMPAT_LAHIR} = $data['tempat_lahir'] ?? null;
        $user->{User::FIELD_TANGGAL_LAHIR} = $data['tanggal_lahir'] ?? null;
        $user->{User::FIELD_UPDATED_BY} = $user->{User::FIELD_USERNAME};

        return $user;
    }

    /**
     * Handle profile photo upload
     *
     * @param User $user
     * @param \Illuminate\Http\UploadedFile $file
     * @return string|null
     */
    public static function handlePhotoUpload(User $user, $file): ?string
    {
        try {
            Log::info('Attempting to upload photo for user: ' . $user->{User::FIELD_USER_ID});
            
            // Buat direktori jika belum ada
            if (!Storage::exists('public/profile')) {
                Storage::makeDirectory('public/profile');
                Log::info('Created directory: public/profile');
            }
            
            // Hapus foto lama jika ada
            if ($user->{User::FIELD_FOTO}) {
                $oldPhotoPath = 'public/profile/' . $user->{User::FIELD_FOTO};
                if (Storage::exists($oldPhotoPath)) {
                    Storage::delete($oldPhotoPath);
                    Log::info('Deleted old photo: ' . $user->{User::FIELD_FOTO});
                }
            }

            // Upload foto baru
            $fileName = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('public/profile', $fileName);
            
            Log::info('Uploaded new photo: ' . $fileName);
            Log::info('Storage path: ' . $path);
            
            return $fileName;
        } catch (\Exception $e) {
            Log::error('Failed to upload photo: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Delete profile photo
     *
     * @param User $user
     * @return bool
     */
    public static function deletePhoto(User $user): bool
    {
        try {
            if ($user->{User::FIELD_FOTO}) {
                $photoPath = 'public/profile/' . $user->{User::FIELD_FOTO};
                if (Storage::exists($photoPath)) {
                    Storage::delete($photoPath);
                    Log::info('Deleted photo for user: ' . $user->{User::FIELD_USER_ID});
                    return true;
                }
            }
            return false;
        } catch (\Exception $e) {
            Log::error('Failed to delete photo: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get profile photo URL
     *
     * @param User $user
     * @return string
     */
    public static function getPhotoUrl(User $user): string
    {
        if ($user->{User::FIELD_FOTO} && Storage::exists('public/profile/' . $user->{User::FIELD_FOTO})) {
            return asset('storage/profile/' . $user->{User::FIELD_FOTO});
        }
        
        return asset('adminlte/dist/img/user-default.jpg');
    }

    /**
     * Get password validation rules
     *
     * @return array
     */
    public static function getPasswordValidationRules(): array
    {
        return [
            'current_password' => 'required',
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    /**
     * Get password validation messages
     *
     * @return array
     */
    public static function getPasswordValidationMessages(): array
    {
        return [
            'current_password.required' => 'Password saat ini wajib diisi',
            'password.required' => 'Password baru wajib diisi',
            'password.min' => 'Password minimal 8 karakter',
            'password.confirmed' => 'Konfirmasi password tidak cocok',
        ];
    }

    /**
     * Format user info for display
     *
     * @param User $user
     * @return array
     */
    public static function formatUserInfo(User $user): array
    {
        return [
            'nama_lengkap' => $user->nama_lengkap,
            'firstname' => $user->{User::FIELD_FIRSTNAME} ?? '-',
            'lastname' => $user->{User::FIELD_LASTNAME} ?? '-',
            'email' => $user->{User::FIELD_EMAIL},
            'username' => $user->{User::FIELD_USERNAME},
            'telp' => $user->{User::FIELD_TELP} ?? '-',
            'jenis_kelamin' => $user->jenis_kelamin_text ?? '-',
            'tempat_lahir' => $user->{User::FIELD_TEMPAT_LAHIR} ?? '-',
            'tanggal_lahir' => $user->{User::FIELD_TANGGAL_LAHIR} ? $user->{User::FIELD_TANGGAL_LAHIR}->format('d-m-Y') : '-',
            'alamat' => $user->{User::FIELD_ALAMAT} ?? '-',
            'foto_url' => self::getPhotoUrl($user),
        ];
    }
}
