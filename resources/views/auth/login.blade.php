<!DOCTYPE html>
<html class="dark" lang="en">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Ransa POS Login</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Outfit:wght@400;500;600;700;800;900&family=Playfair+Display:ital,wght@0,400..900;1,400..900&display=swap"
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
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes smoke-rise {
            0% { transform: translateY(0) scale(1); opacity: 0; }
            20% { opacity: 0.4; }
            50% { opacity: 0.6; transform: translateY(-40px) scale(1.5); }
            80% { opacity: 0.2; }
            100% { transform: translateY(-100px) scale(2); opacity: 0; }
        }

        .animate-fade-up {
            animation: fadeUp 1s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            opacity: 0; /* Identify start state */
        }
        
        .smoke-particle {
            position: absolute;
            bottom: -20px;
            background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0) 70%);
            border-radius: 50%;
            animation: smoke-rise 6s infinite ease-in-out;
            pointer-events: none;
        }

        .delay-100 { animation-delay: 0.1s; }
        .delay-300 { animation-delay: 0.3s; }
        .delay-500 { animation-delay: 0.5s; }
        .delay-700 { animation-delay: 0.7s; }
        
        /* Fallback for bg colors to ensure visibility if tailwind build fails */
        .force-bg-dark { background-color: #221510 !important; }
        .force-bg-surface { background-color: #1e1411 !important; }
        .force-text-primary { color: #ec4913 !important; }
        .force-border-primary { border-color: #ec4913 !important; }
        .force-bg-primary { background-color: #ec4913 !important; }

        .rotating-term {
            display: inline-block;
            min-height: 1.1em;
            min-width: 23ch;
            transition: opacity 0.9s ease, transform 0.9s ease;
            will-change: opacity, transform;
        }

        .rotating-term.is-changing {
            opacity: 0;
            transform: translateY(14px);
        }

        @keyframes luxury-glow {
            0%, 100% {
                opacity: 0.7;
                transform: translateY(0) scale(1);
                filter: drop-shadow(0 0 4px rgba(246, 197, 106, 0.25));
            }
            50% {
                opacity: 1;
                transform: translateY(-2px) scale(1.08);
                filter: drop-shadow(0 0 10px rgba(246, 197, 106, 0.45));
            }
        }

        .luxury-symbol {
            color: #f6c56a;
            font-size: 0.72em;
            animation: luxury-glow 5.2s ease-in-out infinite;
        }
    </style>
</head>

<body
    class="font-display antialiased text-white transition-colors duration-200" style="background-color: #221510;">
    <div class="flex h-screen w-full overflow-hidden">
        <!-- Left Side: Image & Jargon -->
        <div class="hidden lg:flex w-1/2 relative flex-col justify-between p-12 bg-cover bg-center bg-no-repeat overflow-hidden"
            style="background-image: url('{{ asset('images/boba-bg.jpg') }}');">

            <!-- Overlays -->
            <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/50 to-black/30 mix-blend-multiply"></div>
            <div class="absolute inset-0 bg-primary/20 mix-blend-overlay"></div>
            
            <!-- Smoke Effect Container -->
            <div class="absolute inset-x-0 bottom-0 h-96 overflow-hidden pointer-events-none z-0">
                <div class="smoke-particle w-32 h-32 left-10 delay-100 duration-[5s]"></div>
                <div class="smoke-particle w-40 h-40 left-1/4 delay-500 duration-[7s]"></div>
                <div class="smoke-particle w-24 h-24 left-1/3 delay-300 duration-[4s]"></div>
                <div class="smoke-particle w-48 h-48 left-1/2 delay-[0s] duration-[8s]"></div>
                <div class="smoke-particle w-36 h-36 right-1/4 delay-200 duration-[6s]"></div>
                <div class="smoke-particle w-28 h-28 right-10 delay-700 duration-[5s]"></div>
            </div>

            <!-- Badge -->
            <div class="relative z-10 animate-fade-up">
                <div class="inline-flex items-center gap-2 bg-black/40 backdrop-blur-md px-4 py-2 rounded-full border border-white/20 shadow-lg">
                    <span class="material-symbols-outlined" style="color: #ec4913;">restaurant_menu</span>
                    <span class="text-white font-semibold tracking-wide text-sm font-['Outfit']">Ransa POS</span>
                </div>
            </div>

            <!-- Jargon -->
            <div class="relative z-10 max-w-4xl mb-12">
                <h1 class="text-5xl xl:text-6xl font-black text-white mb-6 leading-[0.9] font-['Outfit'] tracking-tighter animate-fade-up delay-300 drop-shadow-2xl">
                    Fresh & Sweet,<br/>
                    <span class="text-white/90 inline-flex items-center gap-2.5">
                        <span class="material-symbols-outlined luxury-symbol" aria-hidden="true">bubble_chart</span>
                        <span class="rotating-term" id="hero-rotating-term">Ganxie Boba.</span>
                    </span>
                </h1>
                <div class="w-24 h-1 bg-[#ec4913] mb-6 rounded-full animate-fade-up delay-500"></div>
                <p class="text-lg text-white/90 font-medium animate-fade-up delay-700 leading-relaxed font-['Plus_Jakarta_Sans'] drop-shadow-lg max-w-2xl">
                    Authentic Boba Tea & Beverages for every moment.
                    <br><span class="text-white/60 text-sm mt-3 block font-normal">Experience the sweetness of modern POS management.</span>
                </p>
            </div>
        </div>

        <!-- Right Side: Login Form -->
        <div class="w-full lg:w-1/2 flex flex-col h-full relative border-l border-white/5 overflow-y-auto" style="background-color: #1e1411;">

            <!-- Mobile Header -->
            <div class="lg:hidden p-4 md:p-6 flex-none flex justify-center border-b border-white/5">
                <h2 class="text-2xl font-bold text-white font-['Outfit']">Ransa POS</h2>
            </div>

            <div class="flex-1 flex flex-col justify-center items-center px-6 py-8 sm:px-12 max-w-lg mx-auto w-full min-h-[min-content]">
                <div class="w-full mb-8 text-center animate-fade-up delay-100">
                    <div class="w-20 h-20 rounded-2xl mx-auto mb-4 md:mb-6 flex items-center justify-center shadow-lg shadow-orange-900/20 bg-white p-2">
                        <img src="{{ asset('images/boba-logo.png') }}" alt="Logo" class="w-full h-full object-contain">
                    </div>
                    <h2 class="text-2xl font-bold text-white mb-2 font-['Outfit']">Welcome Back</h2>
                    <p class="text-gray-400 text-sm md:text-base">Please enter your credentials to access the POS.</p>
                </div>

                <form action="{{ route('login.post') }}" method="POST" class="w-full space-y-4 animate-fade-up delay-200">
                    @csrf
                    @if(session('error'))
                    <div class="bg-red-500/10 border border-red-500/20 text-red-500 px-4 py-3 rounded-xl text-sm text-center font-medium">
                        {{ session('error') }}
                    </div>
                    @endif
                    @if($errors->any())
                    <div class="bg-red-500/10 border border-red-500/20 text-red-500 px-4 py-3 rounded-xl text-sm text-center font-medium">
                        {{ $errors->first('email') ?: $errors->first() }}
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
                        <button class="w-full h-14 text-white rounded-xl font-bold text-base shadow-lg shadow-orange-900/30 active:scale-[0.98] transition-all flex items-center justify-center gap-2 group hover:opacity-90"
                            style="background-color: #ec4913;"
                            type="submit">
                            <span>Login to POS</span>
                            <span class="material-symbols-outlined group-hover:translate-x-1 transition-transform">arrow_forward</span>
                        </button>
                    </div>
                </form>

                <div class="mt-8 text-center animate-fade-up delay-300">
                    <p class="text-sm text-white/30">Quick access:
                        <a href="{{ route('pos.pin.show') }}"
                            class="text-white/60 hover:text-white transition-colors underline decoration-dotted">
                            Login dengan PIN
                        </a>
                    </p>
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

        document.addEventListener('DOMContentLoaded', function () {
            const heroRotatingTerm = document.getElementById('hero-rotating-term');
            if (!heroRotatingTerm) {
                return;
            }

            const rotatingTerms = [
                'Ganxie Boba.',
                'Premium Taste.',
                'Refreshing Drinks.'
            ];
            const rotateIntervalMs = 5600;
            const transitionDurationMs = 900;

            let termIndex = 0;
            setInterval(() => {
                heroRotatingTerm.classList.add('is-changing');
                setTimeout(() => {
                    termIndex = (termIndex + 1) % rotatingTerms.length;
                    heroRotatingTerm.textContent = rotatingTerms[termIndex];
                    heroRotatingTerm.classList.remove('is-changing');
                }, transitionDurationMs);
            }, rotateIntervalMs);
        });
    </script>
</body>
</html>

