<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Daftar users
     */
    public function index()
    {
        $users = User::with(['role', 'outlet'])
            ->orderBy('name')
            ->paginate(20);

        return view('admin.users.index', compact('users'));
    }

    /**
     * Form tambah user
     */
    public function create()
    {
        $roles = Role::orderBy('name')->get();
        $outlets = Outlet::active()->orderBy('name')->get();

        return view('admin.users.create', compact('roles', 'outlets'));
    }

    /**
     * Simpan user baru
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'role_id' => 'required|exists:roles,id',
            'outlet_id' => 'nullable|exists:outlets,id',
            'is_active' => 'nullable|boolean',
        ]);

        $role = Role::find($data['role_id']);
        if ($role && in_array($role->name, ['kasir', 'kitchen'], true) && empty($data['outlet_id'])) {
            return back()
                ->withInput()
                ->withErrors(['outlet_id' => 'Outlet wajib diisi untuk kasir/kitchen.']);
        }

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role_id' => $data['role_id'],
            'outlet_id' => $data['outlet_id'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User berhasil dibuat.');
    }

    /**
     * Form edit user
     */
    public function edit(User $user)
    {
        $roles = Role::orderBy('name')->get();
        $outlets = Outlet::active()->orderBy('name')->get();

        return view('admin.users.edit', compact('user', 'roles', 'outlets'));
    }

    /**
     * Update user
     */
    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => 'nullable|string|min:6|confirmed',
            'role_id' => 'required|exists:roles,id',
            'outlet_id' => 'nullable|exists:outlets,id',
            'is_active' => 'nullable|boolean',
        ]);

        $role = Role::find($data['role_id']);
        if ($role && in_array($role->name, ['kasir', 'kitchen'], true) && empty($data['outlet_id'])) {
            return back()
                ->withInput()
                ->withErrors(['outlet_id' => 'Outlet wajib diisi untuk kasir/kitchen.']);
        }

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'role_id' => $data['role_id'],
            'outlet_id' => $data['outlet_id'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ];

        if (!empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }

        $user->update($payload);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User berhasil diperbarui.');
    }

    /**
     * Hapus user
     */
    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Tidak bisa menghapus akun sendiri.');
        }

        $user->update(['is_active' => false]);

        return back()->with('success', 'User berhasil dinonaktifkan.');
    }

    /**
     * Set PIN absensi untuk karyawan
     */
    public function setAttendancePin(Request $request, User $user)
    {
        $request->validate([
            'pin' => 'required|numeric|digits:6'
        ], [
            'pin.required' => 'PIN wajib diisi',
            'pin.numeric' => 'PIN harus berupa angka',
            'pin.digits' => 'PIN harus 6 digit'
        ]);

        $user->update([
            'attendance_pin' => Hash::make($request->pin)
        ]);

        return back()->with('success', "PIN absensi berhasil diset untuk {$user->name}");
    }
}
