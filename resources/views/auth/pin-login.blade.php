<!DOCTYPE html>
<html class="dark" lang="en">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Moresto POS PIN Login</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Outfit:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet" />
    <link
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap"
        rel="stylesheet" />
</head>
<body class="font-['Plus_Jakarta_Sans'] antialiased text-white" style="background-color: #221510;">
    <div class="flex h-screen w-full overflow-hidden">
        <div class="hidden lg:flex w-1/2 relative flex-col justify-between p-12 bg-cover bg-center bg-no-repeat overflow-hidden"
            style="background-image: url('{{ asset('images/login-bg.jpg') }}');">
            <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/50 to-black/30 mix-blend-multiply"></div>
            <div class="absolute inset-0 bg-[#ec4913]/20 mix-blend-overlay"></div>
            <div class="relative z-10 max-w-sm">
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-black/30 backdrop-blur-sm border border-white/10 mb-8">
                    <span class="material-symbols-outlined text-[#ec4913] text-lg">restaurant</span>
                    <span class="text-sm font-semibold text-white">Moresto POS</span>
                </div>
                <h1 class="text-5xl leading-tight font-bold font-['Outfit'] text-white drop-shadow-[0_2px_8px_rgba(0,0,0,0.45)]">Authentic Dimsum,<br />Modern Service.</h1>
                <p class="text-white mt-5 text-lg drop-shadow-[0_1px_4px_rgba(0,0,0,0.4)]">Masuk cepat dengan PIN untuk perangkat POS yang sudah terdaftar.</p>
            </div>
        </div>

        <div class="w-full lg:w-1/2 flex flex-col h-full border-l border-white/5 overflow-y-auto" style="background-color: #1e1411;">
            <div class="flex-1 flex flex-col justify-center items-center px-6 py-8 sm:px-12 max-w-lg mx-auto w-full min-h-[min-content]">
                @php($hasUsers = $users->isNotEmpty())
                <div class="w-full text-center mb-4">
                    <h2 class="text-xl tracking-[0.2em] text-white/60 font-semibold">SELECT USER</h2>
                </div>

                @if ($errors->any())
                    <div class="w-full mb-4 bg-red-500/10 border border-red-500/20 text-red-300 px-4 py-3 rounded-xl text-sm text-center">
                        {{ $errors->first() }}
                    </div>
                @endif

                @if (session('success'))
                    <div class="w-full mb-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-300 px-4 py-3 rounded-xl text-sm text-center">
                        {{ session('success') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('pos.pin.login') }}" class="w-full" id="pinLoginForm">
                    @csrf
                    <input type="hidden" name="user_id" id="selectedUserId" value="">
                    <input type="hidden" name="pin" id="pinInput" value="">

                    <div class="flex justify-center gap-3 mb-4">
                        @forelse ($users as $user)
                            <button
                                type="button"
                                class="user-select-btn w-12 h-12 md:w-14 md:h-14 rounded-full border-2 border-transparent bg-white/10 text-white text-lg font-semibold transition-all shrink-0"
                                data-user-id="{{ $user->id }}"
                                data-user-name="{{ $user->name }}"
                                aria-label="{{ $user->name }}">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </button>
                        @empty
                            <p class="text-sm text-white/80 text-center">Belum ada user tersimpan di perangkat ini. Login dulu dengan email.</p>
                        @endforelse
                    </div>

                    <p class="text-center text-white font-semibold mb-1" id="selectedUserLabel">Pilih user</p>
                    <p class="text-center text-[#ec4913] mb-3">Enter PIN Code</p>

                    <div class="flex justify-center gap-2 mb-5">
                        @for ($i = 0; $i < 6; $i++)
                            <span class="w-3 h-3 rounded-full border border-white/30 bg-white/5 pin-dot transition-all" data-index="{{ $i }}"></span>
                        @endfor
                    </div>

                    <div class="grid grid-cols-3 gap-2 md:gap-3 max-w-xs mx-auto mb-5">
                        @foreach ([1,2,3,4,5,6,7,8,9] as $digit)
                            <button type="button" class="pin-key h-12 md:h-14 rounded-xl bg-white/5 border border-white/10 text-white text-xl md:text-2xl hover:bg-white/10 transition-colors" data-key="{{ $digit }}">{{ $digit }}</button>
                        @endforeach
                        <button type="button" class="pin-key h-12 md:h-14 rounded-xl bg-white/5 border border-white/10 text-white text-xs md:text-sm hover:bg-white/10 transition-colors" data-key="clear">CLEAR</button>
                        <button type="button" class="pin-key h-12 md:h-14 rounded-xl bg-white/5 border border-white/10 text-white text-xl md:text-2xl hover:bg-white/10 transition-colors" data-key="0">0</button>
                        <button type="button" class="pin-key h-12 md:h-14 rounded-xl bg-white/5 border border-white/10 text-white text-[10px] md:text-xs hover:bg-white/10 transition-colors" data-key="backspace">DEL</button>
                    </div>

                    <button
                        type="submit"
                        id="submitPinButton"
                        class="w-full h-12 md:h-14 text-white rounded-xl font-bold text-base transition-all flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed mt-2"
                        style="background-color: #ec4913;"
                        disabled>
                        <span id="submitPinSpinner" class="hidden items-center justify-center" aria-hidden="true">
                            <svg class="w-5 h-5 animate-spin" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                            </svg>
                        </span>
                        <span id="submitPinText">Login</span>
                        <span id="submitPinIcon" class="material-symbols-outlined">arrow_forward</span>
                    </button>
                </form>

                <a href="{{ route('login', ['email' => 1]) }}" class="mt-4 px-4 py-2 flex-none rounded-lg border border-white/20 bg-white/5 text-sm text-white hover:bg-white/10 transition">
                    Login dengan email
                </a>
            </div>

            <div class="p-4 md:p-6 flex-none flex justify-between items-center text-xs text-white/20 border-t border-white/5">
                <span>PIN Quick Access</span>
                <span>v2.5.0 POS Edition</span>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const selectedUserId = document.getElementById('selectedUserId');
            const selectedUserLabel = document.getElementById('selectedUserLabel');
            const pinInput = document.getElementById('pinInput');
            const submitButton = document.getElementById('submitPinButton');
            const form = document.getElementById('pinLoginForm');
            const submitText = document.getElementById('submitPinText');
            const submitIcon = document.getElementById('submitPinIcon');
            const submitSpinner = document.getElementById('submitPinSpinner');
            const userButtons = document.querySelectorAll('.user-select-btn');
            const keyButtons = document.querySelectorAll('.pin-key');
            const dots = document.querySelectorAll('.pin-dot');
            let isSubmitting = false;

            function updateUiState() {
                const pinLength = pinInput.value.length;
                dots.forEach((dot, idx) => {
                    if (idx < pinLength) {
                        dot.style.backgroundColor = '#ffffff';
                        dot.style.borderColor = '#ffffff';
                    } else {
                        dot.style.backgroundColor = 'rgba(255, 255, 255, 0.05)';
                        dot.style.borderColor = 'rgba(255, 255, 255, 0.30)';
                    }
                });

                if (!isSubmitting) {
                    submitButton.disabled = !selectedUserId.value || pinLength !== 6;
                }
            }

            userButtons.forEach((button, index) => {
                button.addEventListener('click', function () {
                    userButtons.forEach(btn => btn.classList.remove('border-[#ec4913]', 'ring-2', 'ring-[#ec4913]/30'));
                    this.classList.add('border-[#ec4913]', 'ring-2', 'ring-[#ec4913]/30');
                    selectedUserId.value = this.dataset.userId;
                    selectedUserLabel.textContent = this.dataset.userName;
                    updateUiState();
                });

                if (index === 0) {
                    button.click();
                }
            });

            keyButtons.forEach((button) => {
                button.addEventListener('click', function () {
                    const key = this.dataset.key;
                    if (key === 'clear') {
                        pinInput.value = '';
                        updateUiState();
                        return;
                    }

                    if (key === 'backspace') {
                        pinInput.value = pinInput.value.slice(0, -1);
                        updateUiState();
                        return;
                    }

                    if (pinInput.value.length < 6) {
                        pinInput.value += key;
                        updateUiState();
                    }
                });
            });

            if (userButtons.length === 0) {
                keyButtons.forEach((button) => {
                    button.disabled = true;
                    button.classList.add('opacity-40', 'cursor-not-allowed');
                });
            }

            if (form) {
                form.addEventListener('submit', function (e) {
                    if (isSubmitting) {
                        e.preventDefault();
                        return;
                    }

                    // Only show loading if the form is actually submittable.
                    if (!selectedUserId.value || pinInput.value.length !== 6) {
                        e.preventDefault();
                        return;
                    }

                    isSubmitting = true;
                    submitButton.disabled = true;
                    submitButton.setAttribute('aria-busy', 'true');
                    if (submitText) submitText.textContent = 'Memproses...';
                    if (submitIcon) submitIcon.classList.add('hidden');
                    if (submitSpinner) submitSpinner.classList.remove('hidden');
                    if (submitSpinner) submitSpinner.classList.add('inline-flex');

                    userButtons.forEach((btn) => {
                        btn.disabled = true;
                        btn.classList.add('opacity-40', 'cursor-not-allowed');
                    });
                    keyButtons.forEach((btn) => {
                        btn.disabled = true;
                        btn.classList.add('opacity-40', 'cursor-not-allowed');
                    });
                });
            }

            updateUiState();
        });
    </script>
</body>
</html>
