<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use stdClass;

class ProfileController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Menampilkan halaman profile user
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $user = Auth::user();
        
        // Buat objek breadcrumb yang sesuai
        $breadcrumb = new stdClass();
        $breadcrumb->title = 'Profile';
        $breadcrumb->list = ['Home', 'Profile'];
        
        return view('profile.index', compact('user', 'breadcrumb'));
    }

    /**
     * Menampilkan form edit profile
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function edit()
    {
        $user = Auth::user();
        
        // Buat objek breadcrumb yang sesuai
        $breadcrumb = new stdClass();
        $breadcrumb->title = 'Edit Profile';
        $breadcrumb->list = ['Home', 'Profile', 'Edit Profile'];
        
        return view('profile.edit', compact('user', 'breadcrumb'));
    }

    /**
     * Memproses update profile
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        // Validasi input
        $validator = Validator::make($request->all(), [
            'nama_lengkap' => 'required|string|max:255',
            'email' => 'required|string|email|max:100|unique:user,email,' . Auth::user()->user_id . ',user_id',
            'username' => 'required|string|max:20|unique:user,username,' . Auth::user()->user_id . ',user_id',
            'telp' => 'required|string|min:10|max:50',
            'alamat' => 'required|string',
            'jenis_kelamin' => 'nullable|in:L,P',
            'tempat_lahir' => 'nullable|string|max:100',
            'tanggal_lahir' => 'nullable|date',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ], [
            'nama_lengkap.required' => 'Nama lengkap wajib diisi',
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
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $user = User::find(Auth::user()->user_id);
        $user->nama_lengkap = $request->nama_lengkap;
        $user->email = $request->email;
        $user->username = $request->username;
        $user->telp = $request->telp;
        $user->alamat = $request->alamat;
        $user->jenis_kelamin = $request->jenis_kelamin;
        $user->tempat_lahir = $request->tempat_lahir;
        $user->tanggal_lahir = $request->tanggal_lahir;
        $user->updated_by = $user->username;

        // Handle foto profil jika ada
        if ($request->hasFile('foto')) {
            Log::info('Attempting to upload photo');
            
            // Buat direktori jika belum ada
            if (!Storage::exists('public/profile')) {
                Storage::makeDirectory('public/profile');
                Log::info('Created directory: public/profile');
            }
            
            // Hapus foto lama jika ada
            if ($user->foto && Storage::exists('public/profile/' . $user->foto)) {
                Storage::delete('public/profile/' . $user->foto);
                Log::info('Deleted old photo: ' . $user->foto);
            }

            // Upload foto baru
            $file = $request->file('foto');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('public/profile', $fileName);
            $user->foto = $fileName;
            
            Log::info('Uploaded new photo: ' . $fileName);
            Log::info('Path: ' . $path);
        }

        $user->save();

        return redirect()->route('profile')
            ->with('success', 'Profile berhasil diperbarui');
    }

    /**
     * Menampilkan form ubah password
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function changePassword()
    {
        // Buat objek breadcrumb yang sesuai
        $breadcrumb = new stdClass();
        $breadcrumb->title = 'Ubah Password';
        $breadcrumb->list = ['Home', 'Profile', 'Ubah Password'];
        
        return view('profile.change-password', compact('breadcrumb'));
    }
    
    /**
     * Memproses update password
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updatePassword(Request $request)
    {
        // Validasi input
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'current_password.required' => 'Password saat ini wajib diisi',
            'password.required' => 'Password baru wajib diisi',
            'password.min' => 'Password minimal 8 karakter',
            'password.confirmed' => 'Konfirmasi password tidak cocok',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Cek password lama
        if (!Hash::check($request->current_password, Auth::user()->password)) {
            return redirect()->back()
                ->withErrors(['current_password' => 'Password saat ini tidak cocok'])
                ->withInput();
        }

        // Update password
        $user = User::find(Auth::user()->user_id);
        $user->password = Hash::make($request->password);
        $user->updated_by = $user->username;
        $user->save();

        return redirect()->route('profile')
            ->with('success', 'Password berhasil diperbarui');
    }
}