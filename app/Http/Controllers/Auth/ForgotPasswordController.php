<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ForgotPasswordController extends Controller
{

    public function showForgotPasswordForm()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:50',
            'email' => 'required|email',
        ], [
            'username.required' => 'Username wajib diisi.',
            'username.max' => 'Username maksimal 50 karakter.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
        ]);

        $user = User::where('username', $request->username)
            ->where('email', $request->email)
            ->first();

        if (!$user) {
            return back()->with([
                'message' => 'Username dan email tidak sesuai atau tidak terdaftar.',
                'class' => 'error'
            ])->withInput();
        }

        $existingToken = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if ($existingToken) {
            $createdAt = Carbon::parse($existingToken->created_at);
            $cooldownMinutes = 5;
            
            if (!$createdAt->addMinutes($cooldownMinutes)->isPast()) {
                $remainingSeconds = $createdAt->diffInSeconds(Carbon::now());
                $remainingMinutes = ceil($remainingSeconds / 60);
                
                return back()->with([
                    'message' => "Anda sudah mengirim permintaan reset password. Silakan tunggu {$remainingMinutes} menit sebelum mencoba lagi.",
                    'class' => 'warning',
                    'email_sent' => true,
                    'sent_email' => $request->email,
                    'cooldown_until' => $createdAt->timestamp
                ])->withInput();
            }
        }

        $token = Str::random(64);

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        DB::table('password_reset_tokens')->insert([
            'email' => $request->email,
            'username' => $request->username,
            'token' => Hash::make($token),
            'created_at' => Carbon::now()
        ]);

        $user = User::where('email', $request->email)
            ->where('username', $request->username)
            ->first();

        Mail::send('emails.reset-password', [
            'token' => $token,
            'email' => $request->email,
            'user' => $user
        ], function ($message) use ($request) {
            $message->to($request->email);
            $message->subject('Reset Password - ZafaSys');
        });

        $cooldownUntil = Carbon::now()->addMinutes(5)->timestamp;

        return back()->with([
            'message' => 'Link reset password telah dikirim ke email Anda. Silakan cek inbox atau folder spam.',
            'class' => 'success',
            'email_sent' => true,
            'sent_email' => $request->email,
            'cooldown_until' => $cooldownUntil
        ]);
    }

    public function showResetPasswordForm(Request $request, $token)
    {
        $email = $request->email;

        $tokenData = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if (!$tokenData || !Hash::check($token, $tokenData->token)) {
            return redirect()->route('password.request')->with([
                'message' => 'Link reset password tidak valid atau sudah kadaluarsa.',
                'class' => 'error'
            ]);
        }

        if (Carbon::parse($tokenData->created_at)->addMinutes(60)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            return redirect()->route('password.request')->with([
                'message' => 'Link reset password sudah kadaluarsa. Silakan request ulang.',
                'class' => 'error'
            ]);
        }

        return view('auth.reset-password', [
            'token' => $token,
            'email' => $email,
            'username' => $tokenData->username
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:50',
            'email' => 'required|email|exists:user,email',
            'token' => 'required',
            'password' => 'required|min:8|confirmed',
        ], [
            'username.required' => 'Username wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.exists' => 'Email tidak terdaftar.',
            'password.required' => 'Password baru wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        $tokenData = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('username', $request->username)
            ->first();

        if (!$tokenData || !Hash::check($request->token, $tokenData->token)) {
            return back()->withErrors(['email' => 'Token tidak valid atau username/email tidak sesuai.']);
        }

        if (Carbon::parse($tokenData->created_at)->addMinutes(60)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return redirect()->route('password.request')->with([
                'message' => 'Link reset password sudah kadaluarsa. Silakan request ulang.',
                'class' => 'error'
            ]);
        }

        User::where('email', $request->email)
            ->where('username', $request->username)
            ->update([
                'password' => Hash::make($request->password)
            ]);

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return redirect()->route('login')->with([
            'message' => 'Password berhasil direset! Silakan login dengan password baru Anda.',
            'class' => 'success'
        ]);
    }
}
