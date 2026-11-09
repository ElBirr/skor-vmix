<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function create()
    {
        return view('auth.login', ['needsSetup' => User::doesntExist()]);
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $login = Str::lower(trim($credentials['login']));
        $field = str_contains($login, '@') ? 'email' : 'username';

        if (! Auth::attempt([$field => $login, 'password' => $credentials['password'], 'is_active' => true])) {
            return back()->withErrors(['login' => 'Nama pengguna atau kata sandi salah.'])->onlyInput('login');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('scoreboard'));
    }

    public function createFirstUser()
    {
        abort_unless(User::doesntExist(), 404);

        return view('auth.register');
    }

    public function storeFirstUser(Request $request)
    {
        abort_unless(User::doesntExist(), 404);

        $request->merge(['username' => Str::lower(trim((string) $request->input('username')))]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'min:3', 'max:30', 'regex:/^[a-z0-9._-]+$/', Rule::unique(User::class)],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);

        $user = User::create($data + ['role' => 'admin', 'is_active' => true]);
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('scoreboard');
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
