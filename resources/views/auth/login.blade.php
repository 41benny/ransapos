<!DOCTYPE html>
<html class="dark" lang="en">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Moresto POS Login</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Outfit:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,400..900;1,400..900&display=swap"
        rel="stylesheet" />
    <link
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap"
        rel="stylesheet" />
    <style>
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }

        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-up {
            animation: fadeUp 0.8s ease-out forwards;
        }

        .delay-100 {
            animation-delay: 0.1s;
        }

        .delay-200 {
            animation-delay: 0.2s;
        }

        .delay-300 {
            animation-delay: 0.3s;
        }
        
        /* Fallback for bg colors to ensure visibility if tailwind build fails */
        .force-bg-dark { background-color: #221510 !important; }
        .force-bg-surface { background-color: #1e1411 !important; }
        .force-text-primary { color: #ec4913 !important; }
        .force-border-primary { border-color: #ec4913 !important; }
        .force-bg-primary { background-color: #ec4913 !important; }
    </style>
</head>

<body
    class="font-display antialiased text-white transition-colors duration-200" style="background-color: #221510;">
    <div class="flex h-screen w-full overflow-hidden">
        <!-- Left Side: Image & Jargon -->
        <div class="hidden lg:flex w-1/2 relative flex-col justify-between p-12 bg-cover bg-center bg-no-repeat"
            style="background-image: url('{{ asset('images/login-bg.jpg') }}');">

            <!-- Overlays -->
            <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/50 to-black/30 mix-blend-multiply"></div>
            <div class="absolute inset-0 bg-primary/20 mix-blend-overlay"></div>

            <!-- Badge -->
            <div class="relative z-10 animate-fade-up">
                <div class="inline-flex items-center gap-2 bg-black/40 backdrop-blur-md px-4 py-2 rounded-full border border-white/20 shadow-lg">
                    <span class="material-symbols-outlined" style="color: #ec4913;">restaurant_menu</span>
                    <span class="text-white font-semibold tracking-wide text-sm font-['Outfit']">Moresto POS</span>
                </div>
            </div>

            <!-- Jargon -->
            <div class="relative z-10 max-w-2xl mb-12">
                <h1 class="text-7xl font-black text-white mb-6 leading-[0.9] font-['Outfit'] tracking-tighter animate-fade-up delay-100 drop-shadow-2xl">
                    Authentic Dimsum,<br/>Modern Service.
                </h1>
                <p class="text-xl text-white/90 font-medium animate-fade-up delay-200 leading-relaxed font-['Plus_Jakarta_Sans'] drop-shadow-lg">
                    Streamlining your restaurant operations with precision and taste.
                    <br><span class="text-white/70 text-lg mt-3 block font-normal">Experience the future of dining management.</span>
                </p>
            </div>
        </div>

        <!-- Right Side: Login Form -->
        <div class="w-full lg:w-1/2 flex flex-col h-full relative border-l border-white/5" style="background-color: #1e1411;">

            <!-- Mobile Header -->
            <div class="lg:hidden p-6 flex justify-center border-b border-white/5">
                <h2 class="text-2xl font-bold text-white font-['Outfit']">Moresto</h2>
            </div>

            <div class="flex-1 flex flex-col justify-center items-center px-6 sm:px-12 max-w-lg mx-auto w-full">
                <div class="w-full mb-10 text-center">
                    <div class="w-16 h-16 rounded-2xl mx-auto mb-6 flex items-center justify-center shadow-lg shadow-orange-900/20" style="background: linear-gradient(to top right, #ec4913, #ea580c);">
                        <span class="material-symbols-outlined text-white text-3xl">restaurant</span>
                    </div>
                    <h2 class="text-3xl font-bold text-white mb-2 font-['Outfit']">Welcome Back</h2>
                    <p class="text-gray-400">Please enter your credentials to access the POS.</p>
                </div>

                <form action="{{ route('login.post') }}" method="POST" class="w-full space-y-5">
                    @csrf
                    @if(session('error'))
                    <div class="bg-red-500/10 border border-red-500/20 text-red-500 px-4 py-3 rounded-xl text-sm text-center font-medium">
                        {{ session('error') }}
                    </div>
                    @endif

                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-300 ml-1" for="email">Email / Staff
                            ID</label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <span class="material-symbols-outlined text-gray-400 group-focus-within:force-text-primary transition-colors">badge</span>
                            </div>
                            <!-- Added inline style for background to prevent white-on-white -->
                            <input name="email" value="{{ old('email') }}"
                                class="block w-full pl-11 pr-4 py-3.5 border border-white/10 rounded-xl text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[#ec4913]/50 focus:border-[#ec4913] transition-all duration-200 sm:text-sm shadow-sm"
                                style="background-color: rgba(255, 255, 255, 0.05);"
                                id="email" placeholder="Enter your Email / Staff ID" type="text" required autofocus />
                        </div>
                    </div>

                    <div class="space-y-2">
                        <div class="flex justify-between items-center ml-1">
                            <label class="block text-sm font-semibold text-gray-300" for="password">Password</label>
                            <a class="text-sm font-medium hover:opacity-80 transition-colors" style="color: #ec4913;"
                                href="#">Forgot Password?</a>
                        </div>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <span class="material-symbols-outlined text-gray-400 group-focus-within:force-text-primary transition-colors">lock</span>
                            </div>
                            <input name="password"
                                class="block w-full pl-11 pr-12 py-3.5 border border-white/10 rounded-xl text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[#ec4913]/50 focus:border-[#ec4913] transition-all duration-200 sm:text-sm shadow-sm"
                                style="background-color: rgba(255, 255, 255, 0.05);"
                                id="password" placeholder="Enter your password" type="password" required />
                            <button type="button" onclick="togglePasswordVisibility()"
                                class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-white transition-colors">
                                <span class="material-symbols-outlined text-xl"
                                    id="password-toggle-icon">visibility_off</span>
                            </button>
                        </div>
                    </div>

                    <div class="pt-2">
                        <button class="w-full h-14 text-white rounded-xl font-bold text-lg shadow-lg shadow-orange-900/30 active:scale-[0.98] transition-all flex items-center justify-center gap-2 group hover:opacity-90"
                            style="background-color: #ec4913;"
                            type="submit">
                            <span>Login to POS</span>
                            <span class="material-symbols-outlined group-hover:translate-x-1 transition-transform">arrow_forward</span>
                        </button>
                    </div>
                </form>

                <div class="mt-8 text-center">
                    <p class="text-sm text-white/30">Need help? <a href="#"
                            class="text-white/50 hover:text-white transition-colors underline decoration-dotted">Contact
                            Support</a></p>
                </div>
            </div>

            <!-- Footer -->
            <div
                class="p-6 flex justify-between items-center text-xs text-white/20 border-t border-white/5">
                <div class="flex items-center gap-2">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                    </span>
                    <span class="font-medium">System Online</span>
                </div>
                <span>v2.5.0 POS Edition</span>
            </div>
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