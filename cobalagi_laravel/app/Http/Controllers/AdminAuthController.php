<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Admin;

class AdminAuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        $admin = Admin::where('username', $credentials['username'])->first();

        if ($admin) {
            $authenticated = false;
            $needsUpgrade = false;

            // Check using Laravel's Hash (Bcrypt)
            if (Hash::check($credentials['password'], $admin->password)) {
                $authenticated = true;
                $needsUpgrade = Hash::needsRehash($admin->password);
            }
            // Legacy MD5 fallback
            elseif (strlen($admin->password) === 32 && ctype_xdigit($admin->password) && hash_equals(strtolower($admin->password), md5($credentials['password']))) {
                $authenticated = true;
                $needsUpgrade = true;
            }

            if ($authenticated) {
                if ($needsUpgrade) {
                    $admin->password = Hash::make($credentials['password']);
                    $admin->save();
                }

                Auth::guard('admin')->login($admin, $request->has('remember'));

                // Log activity
                DB::table('activity_logs')->insert([
                    'admin_user' => $admin->username,
                    'action' => 'Login',
                    'details' => 'Admin berhasil login (Laravel)',
                    'created_at' => now(), // Assuming standard Laravel naming or fallback
                ]);

                return redirect()->intended(route('admin.dashboard'));
            }
        }

        return back()->withErrors([
            'username' => 'Username atau password salah!',
        ])->withInput($request->only('username'));
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.login');
    }
}
