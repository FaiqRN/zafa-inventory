<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Carbon\Carbon;

class AuthController extends Controller
{
    /**
     * Menampilkan halaman login
     *
     * @return \Illuminate\View\View
     */
    public function showLoginForm(): View
    {
        if (Auth::check()) {
            return view('auth.login', ['redirectToDashboard' => true]);
        }
        
        return view('auth.login', ['redirectToDashboard' => false]);
    }

    /**
     * Proses autentikasi login
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('username', 'password');
        $remember = $request->filled('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            
            // Tambahkan data ke session
            $user = Auth::user();
            session([
                'user_id' => $user->user_id,
                'username' => $user->username,
                'nama_lengkap' => $user->nama_lengkap,
                'login_time' => now()->timestamp,
                'last_activity' => now()->timestamp
            ]);
            
            // Jika remember tidak dicentang, pastikan session hilang saat browser ditutup
            if (!$remember) {
                config(['session.expire_on_close' => true]);
            }
            
            return redirect()->intended(route('dashboard'))
                ->withHeaders([
                    'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                    'Pragma' => 'no-cache',
                    'Expires' => 'Sat, 01 Jan 1990 00:00:00 GMT',
                ]);
        }

        return back()->withErrors([
            'username' => 'Username atau password yang Anda masukkan salah.',
        ])->withInput($request->except('password'));
    }

    /**
     * Proses logout
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
    
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        // Hapus semua cookie terkait session
        $cookie = cookie()->forget('laravel_session');
        $rememberCookie = cookie()->forget(Auth::getRecallerName());
        
        // Tambahkan header cache control
        return redirect()->route('login')
            ->with('success', 'Anda telah berhasil logout')
            ->withHeaders([
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma' => 'no-cache',
                'Expires' => 'Sat, 01 Jan 1990 00:00:00 GMT',
            ])
            ->withCookie($cookie)
            ->withCookie($rememberCookie);
    }

    /**
     * Menampilkan dashboard setelah login
     * 
     * @return \Illuminate\View\View
     */
    public function dashboard(): View
    {
        // Update last activity
        session(['last_activity' => now()->timestamp]);
        
        // Tambahkan variabel yang dibutuhkan oleh dashboard
        $activemenu = 'dashboard';
        $breadcrumb = (object) [
            'title' => 'Dashboard',
            'list' => ['Home', 'Dashboard']
        ];
        
        return view('dashboard', compact('activemenu', 'breadcrumb'));
    }
    
    /**
     * Menampilkan halaman profil user
     *
     * @return \Illuminate\View\View
     */
    public function profile(): View
    {
        // Update last activity
        session(['last_activity' => now()->timestamp]);
        
        $activemenu = 'profile';
        $breadcrumb = (object) [
            'title' => 'Profil Pengguna',
            'list' => ['Home', 'Profil']
        ];
        
        return view('profile', compact('activemenu', 'breadcrumb'));
    }
}