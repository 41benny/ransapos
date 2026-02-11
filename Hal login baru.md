<!DOCTYPE html>
<html class="dark" lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Moresto POS Login - Variant 2</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#ec4913",
                        "background-light": "#f8f6f6",
                        "background-dark": "#221510",
                        "surface-dark": "#2f1f1a",
                        "surface-light": "#ffffff"
                    },
                    fontFamily: {
                        "display": ["Plus Jakarta Sans", "sans-serif"]
                    },
                    borderRadius: {"DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "full": "9999px"},
                },
            },
        }
    </script>
<style>.no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark font-display antialiased text-gray-900 dark:text-white transition-colors duration-200">
<div class="flex h-screen w-full overflow-hidden">
<div class="hidden lg:flex w-1/2 relative flex-col justify-between p-12 bg-cover bg-center bg-no-repeat" data-alt="Minimalist top-down view of dimsum baskets on a dark textured table" style="background-image: url('https://lh3.googleusercontent.com/aida-public/AB6AXuCiV8Uevc18SbYATNTDNjD5z4I0io1u0xesdc4NNoGfoNPePpH2gkJE-Sj93fWAsOjC9KNIBwpGki8LurNxELRUgdBZQMGl_NQX5XU5JkAJ5tvdX5-Fx-TRkQ5S9IyN4IZVOX2NFwWNpkKmI76llOiG4MDzJppQaRHJNS0UtafNu9S3VrIfLl0ssIdBR2LsnAyqJXBRVn3c4Tszw29wIdly_7K6SeNC8ZBg7xRndhmQzuP3Je-zitbtBb6IhgPkd2adWCja3czj5lAl');">
<div class="absolute inset-0 bg-gradient-to-t from-background-dark/90 via-background-dark/40 to-background-dark/20 mix-blend-multiply"></div>
<div class="absolute inset-0 bg-primary/10"></div>
<div class="relative z-10">
<div class="inline-flex items-center gap-2 bg-background-dark/30 backdrop-blur-md px-4 py-2 rounded-full border border-white/10">
<span class="material-symbols-outlined text-primary">restaurant_menu</span>
<span class="text-white font-semibold tracking-wide text-sm">Moresto POS</span>
</div>
</div>
<div class="relative z-10 max-w-md">
<h1 class="text-5xl font-bold text-white mb-4 leading-tight">Authentic Dimsum,<br/>Modern Service.</h1>
<p class="text-lg text-white/80 font-medium">Streamlining your restaurant operations with precision and taste.</p>
</div>
</div>
<div class="w-full lg:w-1/2 flex flex-col h-full bg-surface-light dark:bg-background-dark relative">
<div class="lg:hidden p-6 flex justify-center">
<h2 class="text-2xl font-bold text-gray-900 dark:text-white">Moresto</h2>
</div>
<div class="flex-1 flex flex-col justify-center items-center px-6 sm:px-12 max-w-lg mx-auto w-full">
<div class="w-full mb-10 text-center">
<h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Welcome Back</h2>
<p class="text-gray-500 dark:text-gray-400">Please enter your credentials to access the POS.</p>
</div>
<form class="w-full space-y-6" onsubmit="event.preventDefault();">
<div class="space-y-2">
<label class="block text-sm font-semibold text-gray-700 dark:text-gray-300" for="username">Username</label>
<div class="relative group">
<div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
<span class="material-symbols-outlined text-gray-400 group-focus-within:text-primary transition-colors">person</span>
</div>
<input class="block w-full pl-11 pr-4 py-3.5 bg-gray-50 dark:bg-surface-dark border border-gray-200 dark:border-white/10 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all duration-200 sm:text-sm" id="username" placeholder="Enter your username" type="text"/>
</div>
</div>
<div class="space-y-2">
<div class="flex justify-between items-center">
<label class="block text-sm font-semibold text-gray-700 dark:text-gray-300" for="password">Password</label>
<a class="text-sm font-medium text-primary hover:text-primary/80 transition-colors" href="#">Forgot Password?</a>
</div>
<div class="relative group">
<div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
<span class="material-symbols-outlined text-gray-400 group-focus-within:text-primary transition-colors">lock</span>
</div>
<input class="block w-full pl-11 pr-4 py-3.5 bg-gray-50 dark:bg-surface-dark border border-gray-200 dark:border-white/10 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all duration-200 sm:text-sm" id="password" placeholder="Enter your password" type="password"/>
</div>
</div>
<div class="pt-2">
<button class="w-full h-14 bg-primary hover:bg-primary/90 text-white rounded-xl font-bold text-lg shadow-lg shadow-primary/20 active:translate-y-0.5 transition-all flex items-center justify-center gap-2 group" type="submit">
<span>Login</span>
<span class="material-symbols-outlined group-hover:translate-x-1 transition-transform">arrow_forward</span>
</button>
</div>
</form>
<div class="mt-8 text-center">
<p class="text-sm text-gray-400 dark:text-gray-500">Need help? Contact support</p>
</div>
</div>
<div class="p-6 flex justify-between items-center text-xs text-gray-400 dark:text-gray-500 border-t border-gray-100 dark:border-white/5">
<div class="flex items-center gap-2">
<span class="w-2 h-2 rounded-full bg-green-500 shadow-[0_0_8px_rgba(34,197,94,0.5)]"></span>
<span>System Online</span>
</div>
<span>v2.5.0 Standard</span>
</div>
</div>
</div>

</body></html>