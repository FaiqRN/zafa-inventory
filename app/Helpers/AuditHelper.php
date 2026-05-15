<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;

class AuditHelper
{
    /**
     * Mendapatkan username user yang sedang login.
     *
     * Sistem ini menggunakan username sebagai auth identifier
     * (lihat User::getAuthIdentifierName()), sehingga Auth::id()
     * sudah mengembalikan nilai username — tidak perlu query tambahan.
     *
     * @return string|null  username, atau null jika tidak ada sesi aktif
     */
    public static function currentUsername(): ?string
    {
        $user = Auth::user();

        if ($user === null) {
            return null;
        }

        return $user->username ?? null;
    }
}
