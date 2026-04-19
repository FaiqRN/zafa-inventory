<?php

namespace App\Observers;

use App\Models\Toko;
use App\Services\TokoCacheService;

class TokoCacheObserver
{

    public function created(Toko $toko): void
    {
        TokoCacheService::clearAllCache();
    }

    public function updated(Toko $toko): void
    {
        TokoCacheService::clearTokoCache($toko->{Toko::FIELD_TOKO_ID});
    }

    public function deleted(Toko $toko): void
    {
        TokoCacheService::clearTokoCache($toko->{Toko::FIELD_TOKO_ID});
    }
}
