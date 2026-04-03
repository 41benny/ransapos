@extends('layouts.admin')

@section('title', 'Expense Categories')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Expense Categories</h1>
            <p class="text-gray-600 mt-1">Manage expense category hierarchy</p>
        </div>
        <a href="{{ route('admin.expense-categories.create') }}"
           class="px-4 py-2 bg-gradient-to-r from-blue-500 to-indigo-600 text-white rounded-lg hover:from-blue-600 hover:to-indigo-700 shadow-md transition">
            <i class="fas fa-plus mr-2"></i> Add Category
        </a>
    </div>

    @if(session('success'))
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg">
        <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg">
        <i class="fas fa-exclamation-circle mr-2"></i> {{ session('error') }}
    </div>
    @endif

    <!-- Categories Table -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-sm font-semibold text-gray-900">Daftar Kategori Biaya</h2>
            <p class="text-xs text-gray-500">
                @if($parentCategories->total() > 0)
                    Menampilkan {{ $parentCategories->firstItem() }}-{{ $parentCategories->lastItem() }} dari {{ $parentCategories->total() }} kategori induk
                @else
                    Tidak ada data
                @endif
            </p>
        </div>
        <table class="ui-table min-w-full divide-y divide-gray-200">
            <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">COA Account</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($parentCategories as $parent)
                    <!-- Parent Category -->
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="font-semibold text-gray-900">{{ $parent->code }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-semibold text-gray-900">
                                <i class="fas fa-folder text-yellow-500 mr-2"></i>
                                {{ $parent->name }}
                            </div>
                            @if($parent->description)
                            <div class="text-xs text-gray-500 mt-1">{{ Str::limit($parent->description, 50) }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($parent->coaAccount)
                            <span class="text-sm text-gray-600">{{ $parent->coaAccount->code }} - {{ $parent->coaAccount->name }}</span>
                            @else
                            <span class="text-sm text-gray-400 italic">Not linked</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($parent->is_active)
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                            @else
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Inactive</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right space-x-2">
                            <a href="{{ route('admin.expense-categories.edit', $parent) }}"
                               class="text-blue-600 hover:text-blue-800">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('admin.expense-categories.destroy', $parent) }}"
                                  method="POST" class="inline-block"
                                  onsubmit="return confirm('Are you sure you want to delete this category?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>

                    <!-- Child Categories -->
                    @foreach($parent->children as $child)
                    <tr class="hover:bg-gray-50 bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap pl-12">
                            <span class="text-gray-700">{{ $child->code }}</span>
                        </td>
                        <td class="px-6 py-4 pl-12">
                            <div class="text-gray-800">
                                <i class="fas fa-level-up-alt fa-rotate-90 text-gray-400 mr-2"></i>
                                {{ $child->name }}
                            </div>
                            @if($child->description)
                            <div class="text-xs text-gray-500 mt-1">{{ Str::limit($child->description, 50) }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($child->coaAccount)
                            <span class="text-sm text-gray-600">{{ $child->coaAccount->code }} - {{ $child->coaAccount->name }}</span>
                            @else
                            <span class="text-sm text-gray-400 italic">Not linked</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($child->is_active)
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                            @else
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Inactive</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right space-x-2">
                            <a href="{{ route('admin.expense-categories.edit', $child) }}"
                               class="text-blue-600 hover:text-blue-800">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('admin.expense-categories.destroy', $child) }}"
                                  method="POST" class="inline-block"
                                  onsubmit="return confirm('Are you sure you want to delete this sub-category?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-folder-open text-4xl mb-3"></i>
                            <p>No expense categories found</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if($parentCategories->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 bg-white">
                {{ $parentCategories->onEachSide(1)->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
