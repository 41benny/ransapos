@extends('layouts.admin')

@section('title', 'User')
@section('page-title', 'User')
@section('page-subtitle', 'Kelola akun pengguna dan outlet')

@section('content')
    <div class="w-full max-w-7xl mx-auto">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 page-card-fill">
            <!-- Header -->
            <div class="p-6 border-b border-gray-100">
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Daftar User</h3>
                        <p class="text-sm text-gray-500 mt-1">Tambahkan user dan tentukan outletnya.</p>
                    </div>
                    <a href="{{ route('admin.users.create') }}"
                        class="px-4 py-2 bg-gradient-to-r from-emerald-500 via-teal-500 to-sky-500 text-white rounded-lg transition shadow-md hover:shadow-lg inline-flex items-center">
                        <i class="fas fa-plus mr-2"></i>
                        Tambah User
                    </a>
                </div>
            </div>

            @if(session('success'))
                <div class="p-6">
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle text-lg"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            @if(session('error'))
                <div class="p-6">
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle text-lg"></i>
                        <span>{{ session('error') }}</span>
                    </div>
                </div>
            @endif

            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-left">
                        <thead class="text-xs uppercase text-gray-500 border-b">
                            <tr>
                                <th class="py-3 pr-4">Nama</th>
                                <th class="py-3 pr-4">Email</th>
                                <th class="py-3 pr-4">Role</th>
                                <th class="py-3 pr-4">Outlet</th>
                                <th class="py-3 pr-4">Status</th>
                                <th class="py-3 pr-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse($users as $user)
                                <tr>
                                    <td class="py-3 pr-4 font-medium text-gray-900">
                                        {{ $user->name }}
                                    </td>
                                    <td class="py-3 pr-4 text-gray-700">{{ $user->email }}</td>
                                    <td class="py-3 pr-4 text-gray-700">
                                        {{ $user->role?->display_name ?? $user->role?->name ?? '-' }}
                                    </td>
                                    <td class="py-3 pr-4 text-gray-700">{{ $user->outlet?->name ?? '-' }}</td>
                                    <td class="py-3 pr-4">
                                        @if($user->is_active)
                                            <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Aktif</span>
                                        @else
                                            <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-700">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td class="py-3 pr-4 text-right">
                                        <a href="{{ route('admin.users.edit', $user) }}"
                                            class="px-3 py-1.5 text-xs text-blue-700 hover:bg-blue-50 rounded-lg">Edit</a>
                                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                                            onsubmit="return confirm('Nonaktifkan user ini?')" class="inline-flex">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-3 py-1.5 text-xs text-red-700 hover:bg-red-50 rounded-lg">
                                                Nonaktifkan
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-10 text-center text-gray-500">Belum ada user.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($users->hasPages())
                    <div class="mt-6">
                        {{ $users->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
