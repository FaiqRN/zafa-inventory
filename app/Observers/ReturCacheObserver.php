<?php

namespace App\Observers;

use App\Models\Retur;
use App\Services\ReturCacheService;

class ReturCacheObserver
{

    public function created(Retur $retur): void
    {
        ReturCacheService::clearAllCache();
        ReturCacheService::clearTokoCache($retur->{Retur::FIELD_TOKO_ID});
        ReturCacheService::clearReturCache($retur->{Retur::FIELD_NOMER_PENGIRIMAN});
    }

    public function updated(Retur $retur): void
    {
        ReturCacheService::clearReturCache($retur->{Retur::FIELD_NOMER_PENGIRIMAN});
        ReturCacheService::clearTokoCache($retur->{Retur::FIELD_TOKO_ID});
        ReturCacheService::clearPengirimanCache($retur->{Retur::FIELD_PENGIRIMAN_ID});
    }

    public function deleted(Retur $retur): void
    {
        ReturCacheService::clearReturCache($retur->{Retur::FIELD_NOMER_PENGIRIMAN});
        ReturCacheService::clearTokoCache($retur->{Retur::FIELD_TOKO_ID});
        ReturCacheService::clearPengirimanCache($retur->{Retur::FIELD_PENGIRIMAN_ID});
    }
}
