<?php

namespace App\Observers;

use App\Models\Pengiriman;
use App\Services\PengirimanCacheService;

class PengirimanCacheObserver
{

    public function created(Pengiriman $pengiriman): void
    {
        PengirimanCacheService::clearAllCache();
        PengirimanCacheService::clearTokoCache($pengiriman->{Pengiriman::FIELD_TOKO_ID});
    }

    public function updated(Pengiriman $pengiriman): void
    {
        PengirimanCacheService::clearPengirimanCache($pengiriman->{Pengiriman::FIELD_NOMER_PENGIRIMAN});
        PengirimanCacheService::clearTokoCache($pengiriman->{Pengiriman::FIELD_TOKO_ID});
    }

    public function deleted(Pengiriman $pengiriman): void
    {
        PengirimanCacheService::clearPengirimanCache($pengiriman->{Pengiriman::FIELD_NOMER_PENGIRIMAN});
        PengirimanCacheService::clearTokoCache($pengiriman->{Pengiriman::FIELD_TOKO_ID});
    }
}
