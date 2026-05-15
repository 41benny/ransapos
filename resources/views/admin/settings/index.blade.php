@extends('layouts.admin')

@section('title', 'Settings')
@section('page-title', 'Pengaturan')
@section('page-subtitle', 'Konfigurasi data perusahaan dan logo')

@section('content')
    <div class="w-full animate-in fade-in slide-in-from-bottom-2 duration-500">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-normal text-slate-900 tracking-tight">Pengaturan Perusahaan</h1>
                <p class="text-xs font-normal text-slate-700 mt-0.5">Kelola identitas restoran dan logo untuk kebutuhan cetak struk dll.</p>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-6 rounded-xl bg-emerald-50 border border-emerald-100 p-4 flex items-center gap-3 text-emerald-600 animate-in slide-in-from-top-2">
                <i class="fas fa-check-circle"></i>
                <p class="text-xs font-normal">{{ session('success') }}</p>
            </div>
        @endif

        <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Data Perusahaan -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="ui-card bg-white rounded-2xl shadow-sm border border-slate-200 p-5 md:p-6">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="h-8 w-8 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-600">
                                <i class="fas fa-building text-sm"></i>
                            </div>
                            <h3 class="text-sm font-medium text-slate-800">Identitas Restoran</h3>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div class="space-y-1.5">
                                <label for="company_name" class="text-[11px] font-medium uppercase tracking-wider text-slate-500">Nama Restoran / PT</label>
                                <input type="text" name="company_name" id="company_name" value="{{ old('company_name', $settings['company_name']) }}" 
                                    class="w-full px-3 py-2.5 text-xs font-normal bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all placeholder:text-slate-400 @error('company_name') border-rose-500 @enderror"
                                    placeholder="Contoh: Ransa Central">
                                @error('company_name') <p class="text-[10px] text-rose-500 mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div class="space-y-1.5">
                                <label for="company_phone" class="text-[11px] font-medium uppercase tracking-wider text-slate-500">Nomor Telepon</label>
                                <input type="text" name="company_phone" id="company_phone" value="{{ old('company_phone', $settings['company_phone']) }}" 
                                    class="w-full px-3 py-2.5 text-xs font-normal bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all placeholder:text-slate-400 @error('company_phone') border-rose-500 @enderror"
                                    placeholder="Contoh: 08123456789">
                                @error('company_phone') <p class="text-[10px] text-rose-500 mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div class="md:col-span-2 space-y-1.5">
                                <label for="company_email" class="text-[11px] font-medium uppercase tracking-wider text-slate-500">Email Perusahaan</label>
                                <input type="email" name="company_email" id="company_email" value="{{ old('company_email', $settings['company_email']) }}" 
                                    class="w-full px-3 py-2.5 text-xs font-normal bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all placeholder:text-slate-400 @error('company_email') border-rose-500 @enderror"
                                    placeholder="Contoh: hello@Ransa.com">
                                @error('company_email') <p class="text-[10px] text-rose-500 mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div class="md:col-span-2 space-y-1.5">
                                <label for="company_address" class="text-[11px] font-medium uppercase tracking-wider text-slate-500">Alamat Lengkap</label>
                                <textarea name="company_address" id="company_address" rows="3"
                                    class="w-full px-3 py-2.5 text-xs font-normal bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all placeholder:text-slate-400 @error('company_address') border-rose-500 @enderror"
                                    placeholder="Alamat lengkap restoran...">{{ old('company_address', $settings['company_address']) }}</textarea>
                                @error('company_address') <p class="text-[10px] text-rose-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="ui-card bg-white rounded-2xl shadow-sm border border-slate-200 p-5 md:p-6">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="h-8 w-8 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-600">
                                <i class="fas fa-receipt text-sm"></i>
                            </div>
                            <h3 class="text-sm font-medium text-slate-800">Pengaturan Struk (Receipt)</h3>
                        </div>

                        <div class="space-y-5">
                            <div class="space-y-1.5">
                                <label for="receipt_header" class="text-[11px] font-medium uppercase tracking-wider text-slate-500">Header Struk</label>
                                <textarea name="receipt_header" id="receipt_header" rows="2"
                                    class="w-full px-3 py-2.5 text-xs font-normal bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all placeholder:text-slate-400"
                                    placeholder="Pesan di bagian atas struk...">{{ old('receipt_header', $settings['receipt_header']) }}</textarea>
                            </div>

                            <div class="space-y-1.5">
                                <label for="receipt_footer" class="text-[11px] font-medium uppercase tracking-wider text-slate-500">Footer Struk</label>
                                <textarea name="receipt_footer" id="receipt_footer" rows="2"
                                    class="w-full px-3 py-2.5 text-xs font-normal bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all placeholder:text-slate-400"
                                    placeholder="Pesan di bagian bawah struk...">{{ old('receipt_footer', $settings['receipt_footer']) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Logo Perusahaan -->
                <div class="space-y-6">
                    <div class="ui-card bg-white rounded-2xl shadow-sm border border-slate-200 p-5 md:p-6">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="h-8 w-8 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-600">
                                <i class="fas fa-image text-sm"></i>
                            </div>
                            <h3 class="text-sm font-medium text-slate-800">Logo Perusahaan</h3>
                        </div>

                        <div class="space-y-6">
                            <div class="flex flex-col items-center justify-center p-6 border-2 border-dashed border-slate-200 rounded-2xl bg-slate-50/50 hover:bg-slate-50 transition-all cursor-pointer group" onclick="document.getElementById('company_logo').click()">
                                @if($settings['company_logo'])
                                    <div class="relative w-32 h-32 mb-4 bg-white rounded-xl shadow-sm border border-slate-200 p-2 overflow-hidden flex items-center justify-center">
                                        <img src="{{ asset('storage/' . $settings['company_logo']) }}" alt="Logo" class="max-w-full max-h-full object-contain">
                                        <div class="absolute inset-0 bg-indigo-600/10 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                            <i class="fas fa-camera text-indigo-600 text-xl"></i>
                                        </div>
                                    </div>
                                @else
                                    <div class="w-20 h-20 mb-4 bg-white rounded-full flex items-center justify-center shadow-sm border border-slate-200 text-slate-400 group-hover:text-indigo-600 group-hover:border-indigo-200 transition-all">
                                        <i class="fas fa-cloud-upload-alt text-2xl"></i>
                                    </div>
                                @endif
                                <p class="text-[11px] font-medium text-slate-600 group-hover:text-indigo-600">Klik untuk upload logo</p>
                                <p class="text-[10px] text-slate-400 mt-1 text-center px-4">Format PNG/JPG, maksimal 2MB. Disarankan latar belakang transparan.</p>
                                <input type="file" name="company_logo" id="company_logo" class="hidden" accept="image/*" onchange="previewLogo(this)">
                            </div>

                            <div id="logo_preview_container" class="hidden space-y-2">
                                <p class="text-[10px] font-medium uppercase tracking-wider text-slate-500">Preview Logo Baru:</p>
                                <div class="w-full h-32 bg-slate-100 rounded-xl overflow-hidden flex items-center justify-center border border-slate-200">
                                    <img id="logo_preview" src="#" alt="New Logo Preview" class="max-w-full max-h-full object-contain">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="sticky top-6 space-y-4">
                        <button type="submit" class="w-full flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-6 py-3.5 text-xs font-medium text-white shadow-lg shadow-indigo-200 hover:bg-indigo-700 hover:shadow-indigo-300 transition-all active:scale-[0.98]">
                            <i class="fas fa-save text-xs"></i>
                            <span>Simpan Perubahan</span>
                        </button>
                        <a href="{{ route('admin.dashboard') }}" class="w-full flex items-center justify-center gap-2 rounded-xl bg-white border border-slate-200 px-6 py-3.5 text-xs font-medium text-slate-600 hover:bg-slate-50 transition-all active:scale-[0.98]">
                            <i class="fas fa-arrow-left text-xs text-slate-400"></i>
                            <span>Batal & Kembali</span>
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        function previewLogo(input) {
            const container = document.getElementById('logo_preview_container');
            const preview = document.getElementById('logo_preview');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    container.classList.remove('hidden');
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
@endpush


