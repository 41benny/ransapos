@extends('layouts.app')

@section('page-title', 'Kasir')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 h-full">
    <!-- Left Side - Order Items -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl shadow-lg p-6 h-full">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-semibold text-amber-900">Item Pesanan</h3>
                <button class="px-4 py-2 bg-white border border-amber-300 text-amber-900 rounded-lg hover:bg-amber-50 transition-colors">
                    + Tambah Item
                </button>
            </div>
            
            <!-- Order Items List -->
            <div class="space-y-4">
                <!-- Sample Order Item 1 -->
                <div class="bg-amber-50 rounded-lg p-4 border border-amber-200">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-amber-200 rounded-lg flex items-center justify-center">
                                <span class="text-amber-800 font-semibold">1x</span>
                            </div>
                            <div>
                                <h4 class="font-semibold text-amber-900">Cappuccino</h4>
                                <p class="text-sm text-amber-700">Ukuran: Medium</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold text-amber-900">Rp 25.000</p>
                            <button class="text-amber-600 hover:text-amber-800 text-sm">Hapus</button>
                        </div>
                    </div>
                </div>
                
                <!-- Sample Order Item 2 -->
                <div class="bg-amber-50 rounded-lg p-4 border border-amber-200">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-amber-200 rounded-lg flex items-center justify-center">
                                <span class="text-amber-800 font-semibold">2x</span>
                            </div>
                            <div>
                                <h4 class="font-semibold text-amber-900">Croissant</h4>
                                <p class="text-sm text-amber-700">Varian: Butter</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold text-amber-900">Rp 30.000</p>
                            <button class="text-amber-600 hover:text-amber-800 text-sm">Hapus</button>
                        </div>
                    </div>
                </div>
                
                <!-- Sample Order Item 3 -->
                <div class="bg-amber-50 rounded-lg p-4 border border-amber-200">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-amber-200 rounded-lg flex items-center justify-center">
                                <span class="text-amber-800 font-semibold">1x</span>
                            </div>
                            <div>
                                <h4 class="font-semibold text-amber-900">Latte Art</h4>
                                <p class="text-sm text-amber-700">Ukuran: Large</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold text-amber-900">Rp 35.000</p>
                            <button class="text-amber-600 hover:text-amber-800 text-sm">Hapus</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Right Side - Payment Summary -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-xl font-semibold text-amber-900 mb-6">Ringkasan Pembayaran</h3>
            
            <!-- Payment Details -->
            <div class="space-y-4 mb-6">
                <div class="flex justify-between text-amber-800">
                    <span>Subtotal</span>
                    <span>Rp 90.000</span>
                </div>
                <div class="flex justify-between text-amber-800">
                    <span>Diskon</span>
                    <span>-Rp 5.000</span>
                </div>
                <div class="flex justify-between text-amber-800">
                    <span>PPN (10%)</span>
                    <span>Rp 8.500</span>
                </div>
                <hr class="border-amber-200">
                <div class="flex justify-between font-semibold text-lg text-amber-900">
                    <span>Total</span>
                    <span>Rp 93.500</span>
                </div>
            </div>
            
            <!-- Payment Method -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-amber-900 mb-2">Metode Pembayaran</label>
                <select class="w-full px-3 py-2 border border-amber-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                    <option>Tunai</option>
                    <option>QRIS</option>
                    <option>Debit Card</option>
                    <option>Credit Card</option>
                </select>
            </div>
            
            <!-- Action Buttons -->
            <div class="space-y-3">
                <button class="w-full px-4 py-3 bg-gradient-to-r from-emerald-500 via-teal-500 to-sky-500 text-white font-semibold rounded-lg shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200">
                    Proses Pembayaran
                </button>
                <button class="w-full px-4 py-3 bg-white border border-amber-300 text-amber-900 rounded-lg hover:bg-amber-50 transition-colors">
                    Simpan sebagai Draft
                </button>
                <button class="w-full px-4 py-3 bg-white border border-amber-300 text-amber-900 rounded-lg hover:bg-amber-50 transition-colors">
                    Cetak Struk
                </button>
            </div>
        </div>
    </div>
</div>
@endsection