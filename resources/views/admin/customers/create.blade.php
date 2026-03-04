@extends('layouts.admin')

@section('title', 'Add New Customer')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Add New Customer</h1>
            <p class="text-gray-600 mt-1">Create a new customer profile</p>
        </div>
        <a href="{{ route('admin.customers.index') }}"
           class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition">
            <i class="fas fa-arrow-left mr-2"></i>Back
        </a>
    </div>

    <form action="{{ route('admin.customers.store') }}" method="POST">
        @csrf

        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-xl shadow-md p-6">
                <!-- Basic Information -->
                <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b">Basic Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                               class="ui-input w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('name') border-red-500 @enderror">
                        @error('name')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Phone <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="phone" value="{{ old('phone') }}" required
                               class="ui-input w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('phone') border-red-500 @enderror">
                        @error('phone')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                        <input type="email" name="email" value="{{ old('email') }}"
                               class="ui-input w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('email') border-red-500 @enderror">
                        @error('email')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Birth Date</label>
                        <input type="date" name="birth_date" value="{{ old('birth_date') }}"
                               class="ui-input w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('birth_date') border-red-500 @enderror">
                        @error('birth_date')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                    <textarea name="address" rows="3"
                              class="ui-input w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('address') border-red-500 @enderror">{{ old('address') }}</textarea>
                    @error('address')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Customer Classification -->
                <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b">Customer Classification</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Customer Type <span class="text-red-500">*</span>
                        </label>
                        <select name="customer_type" required
                                class="ui-input w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('customer_type') border-red-500 @enderror">
                            <option value="">Select Type</option>
                            <option value="regular" {{ old('customer_type') == 'regular' ? 'selected' : '' }}>Regular</option>
                            <option value="member" {{ old('customer_type') == 'member' ? 'selected' : '' }}>Member</option>
                            <option value="vip" {{ old('customer_type') == 'vip' ? 'selected' : '' }}>VIP</option>
                        </select>
                        @error('customer_type')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Member Tier</label>
                        <select name="member_tier"
                                class="ui-input w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('member_tier') border-red-500 @enderror">
                            <option value="">Auto (based on spending)</option>
                            <option value="bronze" {{ old('member_tier') == 'bronze' ? 'selected' : '' }}>Bronze</option>
                            <option value="silver" {{ old('member_tier') == 'silver' ? 'selected' : '' }}>Silver</option>
                            <option value="gold" {{ old('member_tier') == 'gold' ? 'selected' : '' }}>Gold</option>
                            <option value="platinum" {{ old('member_tier') == 'platinum' ? 'selected' : '' }}>Platinum</option>
                        </select>
                        @error('member_tier')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Gender</label>
                        <select name="gender"
                                class="ui-input w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('gender') border-red-500 @enderror">
                            <option value="">Select Gender</option>
                            <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>Male</option>
                            <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>Female</option>
                            <option value="other" {{ old('gender') == 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                        @error('gender')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                                   class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">Active Customer</span>
                        </label>
                    </div>
                </div>

                <!-- Additional Information -->
                <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b">Additional Information</h3>
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                    <textarea name="notes" rows="3"
                              placeholder="Preferences, allergies, special requirements..."
                              class="ui-input w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('notes') border-red-500 @enderror">{{ old('notes') }}</textarea>
                    @error('notes')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t">
                    <a href="{{ route('admin.customers.index') }}"
                       class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition">
                        Cancel
                    </a>
                    <button type="submit"
                            class="px-4 py-2 bg-gradient-to-r from-blue-500 to-cyan-600 text-white rounded-lg hover:from-blue-600 hover:to-cyan-700 shadow-md transition">
                        <i class="fas fa-save mr-2"></i>Save Customer
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
