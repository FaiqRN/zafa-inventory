<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        Gate::define('view-analytics', function ($user) {
            return in_array($user->role->nama_role, ['admin', 'ketua']);
        });

        // AP hanya bisa akses Partner Performance
        Gate::define('view-partner-performance', function ($user) {
            return in_array($user->role->nama_role, ['admin', 'ketua', 'AP']);
        });

        Gate::define('view-market-map', function ($user) {
            return in_array($user->role->nama_role, ['admin', 'ketua']);
        });

        Gate::define('manage-users', function ($user) {
            return $user->role->nama_role === 'admin';
        });

        Gate::define('manage-master-data', function ($user) {
            return in_array($user->role->nama_role, ['admin', 'ketua', 'AP']);
        });

        Gate::define('view-barang', function ($user) {
            return in_array($user->role->nama_role, ['admin', 'ketua', 'karyawan', 'AP']);
        });

        Gate::define('view-reports', function ($user) {
            return in_array($user->role->nama_role, ['admin', 'ketua']);
        });

        // AP bisa akses pengaturan partner performance
        Gate::define('manage-partner-performance-settings', function ($user) {
            return in_array($user->role->nama_role, ['admin', 'AP']);
        });
    }
}
