<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index()
    {
        return view('admin.users.index', [
            'users' => User::orderBy('id')->paginate(50),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique(User::class)],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);

        User::create($data + ['role' => 'operator', 'is_active' => true]);

        return back()->with('status', 'Operator ditambahkan.');
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'role' => ['required', Rule::in(['admin', 'operator'])],
            'is_active' => ['required', 'boolean'],
        ]);

        $this->guardLastAdmin($request, $user, $data);

        $user->update($data);

        if (! $user->is_active) {
            $user->flushSessions();
        }

        return back()->with('status', 'Akun operator diperbarui.');
    }

    public function resetPassword(Request $request, User $user)
    {
        $data = $request->validate([
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);

        $user->update(['password' => $data['password']]);
        $user->flushSessions();

        return back()->with('status', 'Sandi operator direset. Semua sesinya diputus.');
    }

    private function guardLastAdmin(Request $request, User $user, array $data): void
    {
        $me = $request->user();
        $losingAdmin = $user->isAdmin()
            && $user->is_active
            && ($data['role'] !== 'admin' || ! $data['is_active']);

        if ($user->is($me) && $losingAdmin) {
            throw ValidationException::withMessages([
                'user' => 'Tidak bisa menonaktifkan atau menurunkan akun sendiri.',
            ]);
        }

        if ($losingAdmin && User::where('role', 'admin')->where('is_active', true)->count() <= 1) {
            throw ValidationException::withMessages([
                'user' => 'Minimal harus ada satu admin aktif.',
            ]);
        }
    }
}
