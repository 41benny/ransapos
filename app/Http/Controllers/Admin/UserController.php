<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Daftar users
     */
    public function index(Request $request)
    {
        $usersQuery = User::with(['role', 'outlet'])
            ->orderBy('name');

        if ($request->filled('name')) {
            $usersQuery->where('name', 'like', '%' . trim((string) $request->input('name')) . '%');
        }

        if ($request->filled('email')) {
            $usersQuery->where('email', 'like', '%' . trim((string) $request->input('email')) . '%');
        }

        if ($request->filled('role')) {
            $role = trim((string) $request->input('role'));
            $usersQuery->whereHas('role', function ($query) use ($role) {
                $query->where('name', 'like', '%' . $role . '%')
                    ->orWhere('display_name', 'like', '%' . $role . '%');
            });
        }

        if ($request->filled('outlet')) {
            $outlet = trim((string) $request->input('outlet'));
            $usersQuery->whereHas('outlet', function ($query) use ($outlet) {
                $query->where('name', 'like', '%' . $outlet . '%');
            });
        }

        if ($request->filled('status')) {
            $status = strtolower(trim((string) $request->input('status')));
            if (in_array($status, ['aktif', 'active', '1'], true)) {
                $usersQuery->where('is_active', true);
            } elseif (in_array($status, ['nonaktif', 'inactive', '0'], true)) {
                $usersQuery->where('is_active', false);
            }
        }

        $users = $usersQuery->paginate(20)->withQueryString();

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
        $role = Role::find($request->input('role_id'));
        $isOutletEmployee = $this->isOutletEmployeeRole($role);

        $data = $request->validate([
            'name' => 'required|string|max:150',
            'email' => $isOutletEmployee ? 'nullable|email|unique:users,email' : 'required|email|unique:users,email',
            'password' => $isOutletEmployee ? 'nullable|string|min:6|confirmed' : 'required|string|min:6|confirmed',
            'role_id' => 'required|exists:roles,id',
            'outlet_id' => 'nullable|exists:outlets,id',
            'is_active' => 'nullable|boolean',
        ]);

        $role = Role::find($data['role_id']);
        if ($role && in_array($role->name, ['kasir', 'kitchen', 'karyawan_outlet'], true) && empty($data['outlet_id'])) {
            return back()
                ->withInput()
                ->withErrors(['outlet_id' => 'Outlet wajib diisi untuk kasir/kitchen/karyawan outlet.']);
        }

        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if ($this->isOutletEmployeeRole($role)) {
            $email = $email ?: $this->generateInternalEmployeeEmail($data['name']);
            $password = $password ?: $this->generateInternalEmployeePassword();
        }

        User::create([
            'name' => $data['name'],
            'email' => $email,
            'password' => Hash::make($password),
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
        $permissionsByModule = Permission::query()
            ->orderBy('module')
            ->orderBy('label')
            ->get()
            ->groupBy('module');

        $assignedPermissionIds = $user->uses_custom_permissions
            ? $user->customPermissions()->pluck('permissions.id')->all()
            : $user->role?->permissions()->pluck('permissions.id')->all();

        return view('admin.users.edit', compact('user', 'roles', 'outlets', 'permissionsByModule', 'assignedPermissionIds'));
    }

    /**
     * Update user
     */
    public function update(Request $request, User $user)
    {
        $role = Role::find($request->input('role_id'));
        $isOutletEmployee = $this->isOutletEmployeeRole($role);

        $data = $request->validate([
            'name' => 'required|string|max:150',
            'email' => $isOutletEmployee
                ? ['nullable', 'email', Rule::unique('users', 'email')->ignore($user->id)]
                : ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => 'nullable|string|min:6|confirmed',
            'role_id' => 'required|exists:roles,id',
            'outlet_id' => 'nullable|exists:outlets,id',
            'is_active' => 'nullable|boolean',
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $role = Role::find($data['role_id']);
        if ($role && in_array($role->name, ['kasir', 'kitchen', 'karyawan_outlet'], true) && empty($data['outlet_id'])) {
            return back()
                ->withInput()
                ->withErrors(['outlet_id' => 'Outlet wajib diisi untuk kasir/kitchen/karyawan outlet.']);
        }

        $payload = [
            'name' => $data['name'],
            'role_id' => $data['role_id'],
            'outlet_id' => $data['outlet_id'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ];

        $email = $data['email'] ?? null;
        if ($this->isOutletEmployeeRole($role)) {
            if (!empty($email)) {
                $payload['email'] = $email;
            } elseif (!empty($user->email)) {
                $payload['email'] = $user->email;
            } else {
                $payload['email'] = $this->generateInternalEmployeeEmail($data['name']);
            }
        } else {
            $payload['email'] = $data['email'];
        }

        if (!empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        } elseif ($this->isOutletEmployeeRole($role) && empty($user->password)) {
            $payload['password'] = Hash::make($this->generateInternalEmployeePassword());
        }

        $user->update($payload);

        if ($role?->name === 'manager') {
            $user->customPermissions()->sync([]);
            $user->update(['uses_custom_permissions' => false]);
        } else {
            $permissionIds = array_map('intval', $data['permissions'] ?? []);
            $user->customPermissions()->sync($permissionIds);
            $user->update(['uses_custom_permissions' => true]);
        }

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
     * Cek role karyawan outlet
     */
    private function isOutletEmployeeRole(?Role $role): bool
    {
        return $role?->name === 'karyawan_outlet';
    }

    /**
     * Generate email internal untuk karyawan outlet tanpa email login
     */
    private function generateInternalEmployeeEmail(string $name): string
    {
        $base = Str::slug($name, '.');
        if ($base === '') {
            $base = 'karyawan.outlet';
        }

        do {
            $suffix = Str::lower(Str::random(6));
            $email = "{$base}.{$suffix}@internal.morest.local";
        } while (User::where('email', $email)->exists());

        return $email;
    }

    /**
     * Generate password internal acak untuk akun non-login
     */
    private function generateInternalEmployeePassword(): string
    {
        return Str::random(32);
    }

    /**
     * Set PIN absensi untuk karyawan
     */
    public function setAttendancePin(Request $request, User $user)
    {
        $validated = $request->validateWithBag('setPin', [
            'pin' => ['required', 'digits:6', 'regex:/^[0-9]{6}$/'],
        ], [
            'pin.required' => 'PIN wajib diisi',
            'pin.digits' => 'PIN harus 6 digit',
            'pin.regex' => 'PIN harus 6 digit angka',
        ]);

        $pin = $validated['pin'];

        $duplicateUser = User::query()
            ->whereKeyNot($user->id)
            ->whereNotNull('attendance_pin')
            ->get(['id', 'name', 'attendance_pin'])
            ->first(function (User $otherUser) use ($pin) {
                return Hash::check($pin, $otherUser->attendance_pin);
            });

        if ($duplicateUser) {
            return back()->withErrors([
                'pin' => "PIN sudah dipakai oleh {$duplicateUser->name}. Gunakan PIN lain.",
            ], 'setPin');
        }

        $user->update([
            'attendance_pin' => Hash::make($pin),
        ]);

        return back()->with('success', "PIN absensi berhasil diset untuk {$user->name}");
    }
}
