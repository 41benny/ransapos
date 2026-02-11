<!DOCTYPE html>
<html class="dark" lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Moresto POS Login</title>

    {{-- Tailwind & Vite --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&amp;display=swap"
        rel="stylesheet" />
    <link
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap"
        rel="stylesheet" />

    <style>
        /* Custom scrollbar for better aesthetic if needed */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #2d1b16;
        }

        ::-webkit-scrollbar-thumb {
            background: #4a2c23;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #5e382d;
        }
    </style>
</head>

<body
    class="font-display bg-background-light dark:bg-background-dark text-slate-900 dark:text-white antialiased overflow-hidden">
    <div class="relative flex h-screen w-full flex-col overflow-hidden">
        <!-- Background Image with Overlay -->
        <div class="absolute inset-0 z-0 h-full w-full bg-[length:100%_100%] bg-center bg-no-repeat"
            style="background-image: url('{{ asset('images/login-bg.jpg') }}');">
            <!-- Gradient Overlay for readability -->
            <div class="absolute inset-0 bg-gradient-to-b from-black/60 via-black/50 to-black/70 backdrop-blur-[2px]">
            </div>
        </div>

        <!-- Top Bar: Clock & Terminal Info -->
        <div class="relative z-10 flex w-full justify-between px-6 py-4 text-white/90">
            <div
                class="flex items-center gap-2 rounded-full bg-black/30 px-4 py-2 backdrop-blur-md border border-white/10">
                <span class="material-symbols-outlined text-primary text-sm">dns</span>
                <span class="text-sm font-medium tracking-wide">Terminal ID: POS-001</span>
            </div>
            <div
                class="flex items-center gap-2 rounded-full bg-black/30 px-4 py-2 backdrop-blur-md border border-white/10">
                <span class="material-symbols-outlined text-primary text-sm">schedule</span>
                <span class="text-sm font-medium tracking-wide">{{ now()->format('H:i A | M d, Y') }}</span>
            </div>
        </div>

        <!-- Main Content Area: Centered Login Card -->
        <div class="relative z-10 flex flex-1 items-center justify-center p-4 overflow-y-auto">
            <div
                class="w-full max-w-[400px] rounded-2xl bg-[#1e1411]/95 dark:bg-[#1e1411]/95 p-6 shadow-[0_8px_32px_rgba(0,0,0,0.5)] border border-white/10 backdrop-blur-sm my-auto">
                <!-- Logo Area -->
                <div class="mb-6 flex flex-col items-center text-center">
                    <div
                        class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-gradient-to-tr from-primary to-orange-600 shadow-lg shadow-orange-900/50">
                        <span class="material-symbols-outlined text-white text-2xl">restaurant</span>
                    </div>
                    <h1 class="text-2xl font-black tracking-tight text-white">Moresto</h1>
                    <p class="mt-1 text-xs font-medium text-white/50">Dimsum &amp; Tea House POS</p>
                </div>

                <!-- Login Form -->
                <form action="{{ route('login.post') }}" method="POST" class="flex flex-col gap-4">
                    @csrf

                    @if(session('error'))
                        <div
                            class="bg-red-500/10 border border-red-500/20 text-red-400 px-3 py-2 rounded-lg text-xs text-center">
                            {{ session('error') }}
                        </div>
                    @endif

                    @error('email')
                        <div
                            class="bg-red-500/10 border border-red-500/20 text-red-400 px-3 py-2 rounded-lg text-xs text-center">
                            {{ $message }}
                        </div>
                    @enderror

                    <!-- Staff ID Input -->
                    <label class="flex flex-col gap-1.5">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-white/70 ml-1">Email / Staff
                            ID</span>
                        <div class="relative flex items-center">
                            <span
                                class="absolute left-3 text-white/40 material-symbols-outlined text-[18px]">badge</span>
                            <input name="email" value="{{ old('email') }}"
                                class="h-10 w-full rounded-lg border border-white/10 bg-white/5 pl-10 pr-3 text-sm text-white placeholder-white/30 focus:border-primary focus:bg-white/10 focus:outline-none focus:ring-1 focus:ring-primary transition-all duration-200"
                                placeholder="Enter your Email / Staff ID" type="text" required autofocus />
                        </div>
                    </label>

                    <!-- Password Input -->
                    <label class="flex flex-col gap-1.5">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-white/70 ml-1">Password</span>
                        <div class="relative flex items-center">
                            <span
                                class="absolute left-3 text-white/40 material-symbols-outlined text-[18px]">lock</span>
                            <input name="password"
                                class="h-10 w-full rounded-lg border border-white/10 bg-white/5 pl-10 pr-10 text-sm text-white placeholder-white/30 focus:border-primary focus:bg-white/10 focus:outline-none focus:ring-1 focus:ring-primary transition-all duration-200"
                                placeholder="••••••••" type="password" required />
                            <button
                                class="absolute right-3 flex items-center justify-center text-white/40 hover:text-white transition-colors"
                                type="button" onclick="togglePasswordVisibility()">
                                <span class="material-symbols-outlined text-[18px]"
                                    id="password-toggle-icon">visibility_off</span>
                            </button>
                        </div>
                    </label>

                    <!-- Forgot Password Link -->
                    <div class="flex justify-between items-center">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="remember"
                                class="w-3.5 h-3.5 rounded border-white/10 bg-white/5 text-primary focus:ring-primary">
                            <span class="text-xs font-medium text-white/50">Remember me</span>
                        </label>
                        <a class="text-xs font-medium text-primary hover:text-orange-400 transition-colors"
                            href="#">Forgot Password?</a>
                    </div>

                    <!-- Login Button -->
                    <button type="submit"
                        class="mt-1 flex h-10 w-full items-center justify-center gap-2 rounded-lg bg-primary text-white shadow-lg shadow-orange-900/40 hover:bg-orange-600 hover:shadow-orange-900/60 active:scale-[0.98] transition-all duration-200">
                        <span class="text-sm font-bold tracking-wide">Login to POS</span>
                        <span class="material-symbols-outlined text-sm">arrow_forward</span>
                    </button>
                </form>

                <!-- Footer Help -->
                <div class="mt-6 text-center">
                    <p class="text-[10px] text-white/30">Having trouble signing in?</p>
                    <a class="mt-1 inline-flex items-center gap-1 text-[10px] font-medium text-white/50 hover:text-white transition-colors"
                        href="#">
                        <span class="material-symbols-outlined text-[14px]">support_agent</span>
                        Contact Manager
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Branding/Copyright -->
    <div class="relative z-10 w-full px-6 py-4 text-center">
        <p class="text-xs font-medium text-white/20">© {{ date('Y') }} Moresto Restaurant Group. All rights
            reserved.</p>
    </div>
    </div>

    <script>
        function togglePasswordVisibility() {
            const passwordInput = document.querySelector('input[name="password"]');
            const toggleIcon = document.getElementById('password-toggle-icon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.textContent = 'visibility';
            } else {
                passwordInput.type = 'password';
                toggleIcon.textContent = 'visibility_off';
            }
        }
    </script>
</body>

</html>