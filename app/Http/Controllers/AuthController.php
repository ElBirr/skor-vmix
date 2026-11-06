<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials)) {
            return back()->withErrors(['email' => 'Email atau kata sandi salah.'])->onlyInput('email');
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

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique(User::class)],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);

        $user = User::create([...$data, 'role' => 'admin']);
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

    public function users(Request $request)
    {
        $this->ensureAdmin($request);

        return view('auth.users', ['users' => User::orderBy('name')->get()]);
    }

    public function storeUser(Request $request)
    {
        $this->ensureAdmin($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique(User::class)],
            'role' => ['required', Rule::in(['operator', 'referee'])],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);

        User::create($data);

        return back()->with('status', 'Akun operator ditambahkan.');
    }

    private function ensureAdmin(Request $request): void
    {
        abort_unless($request->user()->role === 'admin', 403);
    }
}
