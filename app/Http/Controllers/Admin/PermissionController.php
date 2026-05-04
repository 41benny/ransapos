<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PermissionController extends Controller
{
    /**
     * Daftar role dan jumlah permission.
     */
    public function index(): View
    {
        $roles = Role::query()
            ->withCount('permissions')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $totalPermissions = Permission::query()->count();
        $totalRoles = Role::query()->count();

        return view('admin.permissions.index', [
            'roles' => $roles,
            'totalPermissions' => $totalPermissions,
            'totalRoles' => $totalRoles,
        ]);
    }

    /**
     * Form checklist permission per role.
     */
    public function edit(Role $role): View|RedirectResponse
    {
        if ($role->name === 'superadmin') {
            return redirect()
                ->route('admin.permissions.index')
                ->with('error', 'Role superadmin memiliki full akses bawaan sistem dan tidak perlu checklist permission.');
        }

        $permissionsByModule = Permission::query()
            ->orderBy('module')
            ->orderBy('action')
            ->orderBy('label')
            ->get()
            ->groupBy('module');

        $assignedPermissionIds = $role->permissions()
            ->pluck('permissions.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $sourceRoles = Role::query()
            ->whereKeyNot($role->id)
            ->orderBy('name')
            ->get();

        return view('admin.permissions.edit', [
            'role' => $role,
            'permissionsByModule' => $permissionsByModule,
            'assignedPermissionIds' => $assignedPermissionIds,
            'sourceRoles' => $sourceRoles,
            'moduleLabels' => $this->moduleLabels(),
        ]);
    }

    /**
     * Simpan checklist permission.
     */
    public function update(Request $request, Role $role): RedirectResponse
    {
        if ($role->name === 'superadmin') {
            return redirect()
                ->route('admin.permissions.index')
                ->with('error', 'Role superadmin memiliki full akses bawaan sistem dan tidak dapat diubah dari checklist.');
        }

        $validated = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $permissionIds = $validated['permissions'] ?? [];
        $role->permissions()->sync($permissionIds);

        return redirect()
            ->route('admin.permissions.edit', $role)
            ->with('success', 'Permission role berhasil diperbarui.');
    }

    /**
     * Duplikasi permission dari role lain.
     */
    public function duplicate(Request $request, Role $role): RedirectResponse
    {
        if ($role->name === 'superadmin') {
            return redirect()
                ->route('admin.permissions.index')
                ->with('error', 'Role superadmin memiliki full akses bawaan sistem dan tidak dapat diubah dari checklist.');
        }

        $validated = $request->validate([
            'source_role_id' => [
                'required',
                'integer',
                Rule::exists('roles', 'id'),
                Rule::notIn([$role->id]),
            ],
        ]);

        $sourceRole = Role::query()
            ->with('permissions:id')
            ->findOrFail((int) $validated['source_role_id']);

        $role->permissions()->sync($sourceRole->permissions->pluck('id')->all());
        $sourceRoleName = $sourceRole->display_name ?: $sourceRole->name;

        return redirect()
            ->route('admin.permissions.edit', $role)
            ->with('success', "Permission berhasil disalin dari role {$sourceRoleName}.");
    }

    /**
     * Label modul untuk tampilan checklist.
     *
     * @return array<string, string>
     */
    private function moduleLabels(): array
    {
        return [
            'dashboard' => 'Dashboard',
            'products' => 'Produk',
            'outlets' => 'Outlet',
            'suppliers' => 'Supplier',
            'payment-methods' => 'Metode Pembayaran',
            'customers' => 'Customer',
            'users' => 'User',
            'cash-accounts' => 'Kas & Bank',
            'cash-transactions' => 'Transaksi Kas',
            'bank-transfers' => 'Transfer Bank',
            'coa-accounts' => 'COA',
            'stocks' => 'Stok',
            'stock-transfers' => 'Transfer Stok',
            'boms' => 'BOM',
            'purchases' => 'Purchasing',
            'expense-categories' => 'Kategori Biaya',
            'expenses' => 'Expense',
            'promo-vouchers' => 'Promo & Voucher',
            'sales' => 'Penjualan',
            'reports' => 'Reports',
            'cash-sessions' => 'Cash Session',
            'pos-dashboard' => 'Akses POS Kasir',
            'pos-devices' => 'Perangkat POS',
            'void-tokens' => 'Token Void',
            'permissions' => 'Role Permission',
        ];
    }
}
