<div class="ui-card bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/70">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h3 class="text-sm font-normal text-slate-900">{{ $title }}</h3>
                <p class="text-[10px] font-normal uppercase tracking-widest text-slate-500 mt-0.5">{{ $subtitle }}</p>
            </div>
            <span class="inline-flex items-center rounded-full bg-white px-2.5 py-1 text-[10px] font-normal uppercase tracking-widest text-slate-600 ring-1 ring-slate-200">
                {{ $users->total() }} User
            </span>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-100">
            <thead class="bg-white">
                <tr>
                    <th class="px-4 py-2.5 text-left text-[9px] font-normal uppercase tracking-widest text-slate-500">Nama & Email</th>
                    <th class="px-4 py-2.5 text-left text-[9px] font-normal uppercase tracking-widest text-slate-500">Role & Outlet</th>
                    <th class="px-4 py-2.5 text-center text-[9px] font-normal uppercase tracking-widest text-slate-500">Status</th>
                    <th class="px-4 py-2.5 text-center text-[9px] font-normal uppercase tracking-widest text-slate-500">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($users as $user)
                    <tr class="group hover:bg-slate-50/50 transition-colors">
                        <td class="px-4 py-3">
                            <div class="flex flex-col">
                                <span class="text-sm font-normal text-slate-900 leading-tight">{{ $user->name }}</span>
                                <span class="text-xs font-normal text-slate-500 mt-0.5">{{ $user->email }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-col gap-0.5">
                                <span class="text-[11px] font-normal text-indigo-600 uppercase tracking-wider">{{ $user->role?->display_name ?? $user->role?->name ?? '-' }}</span>
                                <div class="flex items-center gap-1.5 mt-0.5">
                                    <i class="fas fa-store text-[8px] text-slate-400"></i>
                                    <span class="text-[10px] font-normal text-slate-600">{{ $user->outlet?->name ?? 'Semua Outlet' }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($user->is_active)
                                <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-[9px] font-normal text-emerald-600 ring-1 ring-inset ring-emerald-200 uppercase tracking-widest">Aktif</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-slate-50 px-2.5 py-0.5 text-[9px] font-normal text-slate-400 ring-1 ring-inset ring-slate-200 uppercase tracking-widest">Nonaktif</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="relative inline-block text-left product-actions-dropdown">
                                <button type="button" class="action-dropdown-btn ui-btn ui-btn-ghost ui-btn-sm inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-normal text-slate-700 shadow-sm transition-all hover:bg-slate-50 active:scale-95">
                                    <span>Aksi</span>
                                    <i class="fas fa-chevron-down text-[8px] text-slate-400"></i>
                                </button>
                                <div class="action-dropdown-menu hidden absolute right-0 z-[100] mt-2 w-44 origin-top-right rounded-xl bg-white shadow-xl border border-slate-100 ring-1 ring-black ring-opacity-5 animate-in fade-in slide-in-from-top-2 duration-200">
                                    <div class="py-1">
                                        <a href="{{ route('admin.users.edit', $user) }}" class="flex items-center gap-2 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50 hover:text-amber-600 transition-colors">
                                            <i class="fas fa-edit w-4 text-center opacity-70"></i>
                                            Edit User
                                        </a>

                                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="block border-t border-slate-50" onsubmit="return confirm('Nonaktifkan user ini?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="flex w-full items-center gap-2 px-4 py-2 text-[11px] text-rose-500 hover:bg-rose-50 transition-colors">
                                                <i class="fas fa-user-slash w-4 text-center opacity-70"></i>
                                                Nonaktifkan
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-16 text-center">
                            <div class="flex flex-col items-center justify-center opacity-40">
                                <i class="fas fa-users-slash text-3xl text-slate-300 mb-3"></i>
                                <p class="text-[11px] font-normal text-slate-500 italic uppercase tracking-widest">{{ $emptyText }}</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($users->hasPages())
        <div class="px-4 py-3 border-t border-slate-100 bg-slate-50/50">
            {{ $users->links() }}
        </div>
    @endif
</div>
