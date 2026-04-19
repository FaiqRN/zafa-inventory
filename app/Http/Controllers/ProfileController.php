<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Helpers\ProfileHelper;
use App\Rules\StrongPassword;
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
        * @return \Illuminate\Contracts\Support\Renderable|\Illuminate\Http\RedirectResponse
     */
    public function index()
    {
        $user = $this->resolveAuthenticatedUser();

        if (!$user) {
            return redirect()->route('login');
        }
        
        // Buat objek breadcrumb yang sesuai
        $breadcrumb = new stdClass();
        $breadcrumb->title = 'Profile';
        $breadcrumb->list = ['Home', 'Profile'];
        
        return view('profile.index', compact('user', 'breadcrumb'));
    }

    /**
     * Menampilkan form edit profile
     *
        * @return \Illuminate\Contracts\Support\Renderable|\Illuminate\Http\RedirectResponse
     */
    public function edit()
    {
        $user = $this->resolveAuthenticatedUser();

        if (!$user) {
            return redirect()->route('login');
        }
        
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
        $currentUser = $this->resolveAuthenticatedUser();

        if (!$currentUser) {
            return redirect()->route('login')
                ->with('error', 'Sesi tidak valid, silakan login kembali.');
        }

        $userId = $currentUser->{User::FIELD_USER_ID};
        
        // Validasi input menggunakan ProfileHelper
        $validator = Validator::make(
            $request->all(), 
            ProfileHelper::getValidationRules($userId),
            ProfileHelper::getValidationMessages()
        );

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $user = User::find($userId);
        
        // Update profile data menggunakan ProfileHelper
        $user = ProfileHelper::updateProfile($user, $request->all());

        // Handle foto profil jika ada
        if ($request->hasFile('foto')) {
            $fileName = ProfileHelper::handlePhotoUpload($user, $request->file('foto'));
            if ($fileName) {
                $user->{User::FIELD_FOTO} = $fileName;
            }
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
        // Validasi input dengan strong password requirement
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'password' => ['required', 'string', 'min:8', 'confirmed', new StrongPassword()],
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

        $currentUser = $this->resolveAuthenticatedUser();

        if (!$currentUser) {
            return redirect()->route('login')
                ->with('error', 'Sesi tidak valid, silakan login kembali.');
        }
        
        // Cek password lama
        if (!Hash::check($request->current_password, $currentUser->{User::FIELD_PASSWORD})) {
            return redirect()->back()
                ->withErrors(['current_password' => 'Password saat ini tidak cocok'])
                ->withInput();
        }

        // Update password
        $user = User::find($currentUser->{User::FIELD_USER_ID});
        $user->{User::FIELD_PASSWORD} = Hash::make($request->password);
        $user->{User::FIELD_UPDATED_BY} = $user->{User::FIELD_USERNAME};
        $user->save();

        return redirect()->route('profile')
            ->with('success', 'Password berhasil diperbarui');
    }

    private function resolveAuthenticatedUser(): ?User
    {
        $authIdentifier = Auth::id();

        if ($authIdentifier === null) {
            return null;
        }

        return User::query()
            ->where(User::FIELD_USERNAME, (string) $authIdentifier)
            ->first();
    }
}