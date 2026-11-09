<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    public function edit()
    {
        return view('account.edit');
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $request->merge(['username' => Str::lower(trim((string) $request->input('username')))]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'min:3', 'max:30', 'regex:/^[a-z0-9._-]+$/', Rule::unique('users')->ignore($user->id)],
        ]);

        $user->update($data);

        return back()->with('status', 'Profil diperbarui.');
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $request->user()->update(['password' => $data['password']]);
        $request->user()->flushSessions($request->session()->getId());
        $request->session()->regenerate();

        return back()->with('status', 'Kata sandi diperbarui. Sesi di perangkat lain diputus.');
    }
}
