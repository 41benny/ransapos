<!DOCTYPE html>

<html class="light" lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Domsteak POS Cashier Interface</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#f20d0d",
                        "primary-hover": "#d90b0b",
                        "accent-gold": "#C5A005",
                        "background-light": "#f8f5f5",
                        "background-dark": "#221010",
                        "surface-light": "#ffffff",
                        "surface-dark": "#2d1515",
                    },
                    fontFamily: {
                        "display": ["Plus Jakarta Sans", "sans-serif"]
                    },
                    borderRadius: {"DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "2xl": "1rem", "full": "9999px"},
                },
            },
        }
    </script>
<style>
        /* Custom Scrollbar for better aesthetics */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: transparent; 
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1; 
            border-radius: 3px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8; 
        }
        .dark ::-webkit-scrollbar-thumb {
            background: #4a2b2b;
        }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark font-display text-slate-900 dark:text-gray-100 overflow-hidden h-screen flex flex-col">
<!-- Top Header -->
<header class="h-16 bg-surface-light dark:bg-surface-dark border-b border-gray-200 dark:border-red-900/30 flex items-center justify-between px-6 shrink-0 z-20 shadow-sm">
<div class="flex items-center gap-3 w-64">
<div class="size-8 bg-primary rounded-lg flex items-center justify-center text-white">
<span class="material-symbols-outlined">restaurant_menu</span>
</div>
<h1 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">Domsteak<span class="text-primary">POS</span></h1>
</div>
<!-- Search Bar -->
<div class="flex-1 max-w-xl px-4">
<div class="relative group">
<div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400 group-focus-within:text-primary transition-colors">
<span class="material-symbols-outlined">search</span>
</div>
<input class="block w-full pl-10 pr-3 py-2.5 border-none rounded-xl bg-gray-100 dark:bg-red-950/20 text-sm focus:ring-2 focus:ring-primary/50 placeholder-gray-400 dark:text-white transition-all" placeholder="Search menu items (e.g. Siomay, Hakau)..." type="text"/>
</div>
</div>
<!-- Right Header Actions -->
<div class="flex items-center gap-4 w-auto justify-end">
<div class="flex flex-col items-end mr-2 hidden md:flex">
<span class="text-xs text-gray-500 dark:text-gray-400 font-medium">Server</span>
<span class="text-sm font-bold text-slate-800 dark:text-white">Sarah Jenkins</span>
</div>
<button class="flex items-center gap-2 bg-yellow-50 dark:bg-yellow-900/20 text-yellow-700 dark:text-yellow-500 px-4 py-2 rounded-lg border border-yellow-200 dark:border-yellow-900/50 hover:bg-yellow-100 transition-colors">
<span class="material-symbols-outlined text-[20px]">table_restaurant</span>
<span class="font-bold text-sm">Table #05</span>
</button>
<div class="h-10 w-10 rounded-full bg-cover bg-center border-2 border-white dark:border-red-900 shadow-sm" data-alt="User profile picture showing a smiling woman" style="background-image: url('https://lh3.googleusercontent.com/aida-public/AB6AXuAw6EE-H8TVVAItraWLAXuUSNaekq111hVpyLTMJgUebO90i8IW5fHoXFNzweNwofAL4JCTl4jc6kMbh9jsmQZpeHD7wgBmZUvgAtwvYQT-plZeYIt39PhHrgAPfFDv5H-STWfLbqzcOlERbB8YPb3vLnyCgOrK-KSp-cJS33GLQ2h9WuQoq6AH30PL-j-MpfbtCb5nih2lsNiyN2Jm6b_8xquRZE2M1oD049dIruAYR-YQ68QoHssqaxA8R5dhyUWNMfYFCCQd54EQ');"></div>
</div>
</header>
<!-- Main Content Layout -->
<div class="flex flex-1 overflow-hidden">
<!-- Left Sidebar: Categories -->
<aside class="w-24 lg:w-64 bg-surface-light dark:bg-surface-dark border-r border-gray-200 dark:border-red-900/30 flex flex-col shrink-0 overflow-y-auto py-6">
<div class="px-4 mb-4">
<p class="text-xs font-bold text-gray-400 uppercase tracking-wider hidden lg:block mb-2">Menu Categories</p>
</div>
<nav class="flex flex-col gap-2 px-3">
<!-- Active Item -->
<button class="flex flex-col lg:flex-row items-center lg:gap-3 p-3 rounded-xl bg-primary text-white shadow-md shadow-red-500/20 transition-all hover:translate-x-1">
<span class="material-symbols-outlined">grid_view</span>
<span class="text-xs lg:text-base font-semibold mt-1 lg:mt-0">All Items</span>
</button>
<!-- Inactive Items -->
<button class="flex flex-col lg:flex-row items-center lg:gap-3 p-3 rounded-xl text-gray-500 dark:text-gray-400 hover:bg-red-50 dark:hover:bg-red-900/20 hover:text-primary transition-all group">
<span class="material-symbols-outlined group-hover:scale-110 transition-transform">rice_bowl</span>
<span class="text-xs lg:text-base font-medium mt-1 lg:mt-0">Steamed</span>
</button>
<button class="flex flex-col lg:flex-row items-center lg:gap-3 p-3 rounded-xl text-gray-500 dark:text-gray-400 hover:bg-red-50 dark:hover:bg-red-900/20 hover:text-primary transition-all group">
<span class="material-symbols-outlined group-hover:scale-110 transition-transform">skillet</span>
<span class="text-xs lg:text-base font-medium mt-1 lg:mt-0">Fried</span>
</button>
<button class="flex flex-col lg:flex-row items-center lg:gap-3 p-3 rounded-xl text-gray-500 dark:text-gray-400 hover:bg-red-50 dark:hover:bg-red-900/20 hover:text-primary transition-all group">
<span class="material-symbols-outlined group-hover:scale-110 transition-transform">local_bar</span>
<span class="text-xs lg:text-base font-medium mt-1 lg:mt-0">Drinks</span>
</button>
<button class="flex flex-col lg:flex-row items-center lg:gap-3 p-3 rounded-xl text-gray-500 dark:text-gray-400 hover:bg-red-50 dark:hover:bg-red-900/20 hover:text-primary transition-all group">
<span class="material-symbols-outlined group-hover:scale-110 transition-transform">inventory_2</span>
<span class="text-xs lg:text-base font-medium mt-1 lg:mt-0">Packages</span>
</button>
</nav>
</aside>
<!-- Center: Product Grid -->
<main class="flex-1 bg-background-light dark:bg-background-dark p-6 overflow-y-auto">
<div class="flex items-center justify-between mb-6">
<h2 class="text-2xl font-bold text-slate-800 dark:text-white">All Items</h2>
<span class="text-sm text-gray-500 dark:text-gray-400">showing 12 items</span>
</div>
<div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4">
<!-- Product Card 1 -->
<div class="group bg-surface-light dark:bg-surface-dark rounded-2xl p-3 shadow-sm hover:shadow-lg border border-transparent hover:border-red-100 dark:hover:border-red-900/50 transition-all cursor-pointer flex flex-col h-full">
<div class="relative w-full aspect-square rounded-xl overflow-hidden mb-3 bg-gray-100">
<img class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" data-alt="Steamed dimsum dumplings in a bamboo steamer" src="https://lh3.googleusercontent.com/aida-public/AB6AXuDWsqx2W-nt43qAK7Bx5aPFKG921ifjVocyZ611n9Qp5GGGpUUoY44guQuBkxR7Y5oq8Y-mrQVt46MhtNuvQbgxo_mQI5RdJ3m4sX3cWJPjrtJ03UNEvEStshGouY9IrIuEVnjEhlvbLXzsuDYFy8t8cAgMVMMRgkjfmPWJLpPUJaMyJNO_ZtMMFR5Hh5ClWMYucuJydQ8MHm48TghG_Ke-WQp4Mg5276Jh7eNEZame2vBok40qTbUyPQzX96ulJv52BUG_lXJzWHcP"/>
<div class="absolute top-2 right-2 bg-white/90 dark:bg-black/60 backdrop-blur-sm px-2 py-1 rounded-lg text-xs font-bold text-primary shadow-sm">
                            Best Seller
                        </div>
</div>
<div class="flex flex-col flex-1">
<h3 class="text-base font-bold text-slate-800 dark:text-white leading-tight mb-1">Siomay Ayam</h3>
<p class="text-xs text-gray-500 dark:text-gray-400 mb-2 line-clamp-2">Traditional steamed chicken dumplings with shrimp topping.</p>
<div class="mt-auto flex items-center justify-between">
<span class="text-primary font-bold text-lg">Rp 20.000</span>
<button class="bg-red-50 dark:bg-red-900/30 text-primary p-1.5 rounded-lg hover:bg-primary hover:text-white transition-colors">
<span class="material-symbols-outlined text-[20px]">add</span>
</button>
</div>
</div>
</div>
<!-- Product Card 2 -->
<div class="group bg-surface-light dark:bg-surface-dark rounded-2xl p-3 shadow-sm hover:shadow-lg border border-transparent hover:border-red-100 dark:hover:border-red-900/50 transition-all cursor-pointer flex flex-col h-full">
<div class="relative w-full aspect-square rounded-xl overflow-hidden mb-3 bg-gray-100">
<img class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" data-alt="Translucent shrimp dumplings known as Hakau" src="https://lh3.googleusercontent.com/aida-public/AB6AXuB9JKXHN4a4N7-QjfL26GSIwt0-pVRo7MXqB87GyG2eNYR38m4Y6mMb4ZDXvplzizm7sMBpVmH4l_RwpVNRLCxGnk2SE-Azs--lfGMZeEf5qcPlyHe995JABeYoOi1ts3SrSAXMvE2Gv2ot8zOohiAippjykwzAt4Cxp1D4072Kmxqkvk-LgQMdz8AJJoWXsz5dwfu2rAcviZzwn0_6aXa8C_Vd-F8AtUt14PE5uOWErnOfPsgV71IN1a4V4KjlMNJ8mMYM0KcQjTMd"/>
</div>
<div class="flex flex-col flex-1">
<h3 class="text-base font-bold text-slate-800 dark:text-white leading-tight mb-1">Hakau Udang</h3>
<p class="text-xs text-gray-500 dark:text-gray-400 mb-2 line-clamp-2">Crystal shrimp dumplings with bamboo shoots.</p>
<div class="mt-auto flex items-center justify-between">
<span class="text-primary font-bold text-lg">Rp 25.000</span>
<button class="bg-red-50 dark:bg-red-900/30 text-primary p-1.5 rounded-lg hover:bg-primary hover:text-white transition-colors">
<span class="material-symbols-outlined text-[20px]">add</span>
</button>
</div>
</div>
</div>
<!-- Product Card 3 -->
<div class="group bg-surface-light dark:bg-surface-dark rounded-2xl p-3 shadow-sm hover:shadow-lg border border-transparent hover:border-red-100 dark:hover:border-red-900/50 transition-all cursor-pointer flex flex-col h-full">
<div class="relative w-full aspect-square rounded-xl overflow-hidden mb-3 bg-gray-100">
<img class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" data-alt="Fried tofu skin spring rolls on a plate" src="https://lh3.googleusercontent.com/aida-public/AB6AXuBjXfhGgs1Hpv4dtD9X154IMj6LEuk_yJCNveAo2XSh9f8te6Ig6PMpf8JBb7L10f8xrkF96HDnn3EcEXnWr5WR6MTAN5kiAcRmFTKtKPoVajHWXF9ksAWc1FP97VFXnM6QDmKhzjIDEki1Ec5hHY3LjRHM_RMGc6XRO4c_cW9GcyTByaYPcTHEjYbL3j9rnP5B3VIGNEWeE6YOOlm9_E-VBrgFpBy5kQ0IjOxEDgM9fiiJOmOQQMDV5ixuC3XiPslHLn0uh2Bk6s91"/>
</div>
<div class="flex flex-col flex-1">
<h3 class="text-base font-bold text-slate-800 dark:text-white leading-tight mb-1">Lumpia Kulit Tahu</h3>
<p class="text-xs text-gray-500 dark:text-gray-400 mb-2 line-clamp-2">Deep fried tofu skin rolls filled with savory shrimp.</p>
<div class="mt-auto flex items-center justify-between">
<span class="text-primary font-bold text-lg">Rp 22.000</span>
<button class="bg-red-50 dark:bg-red-900/30 text-primary p-1.5 rounded-lg hover:bg-primary hover:text-white transition-colors">
<span class="material-symbols-outlined text-[20px]">add</span>
</button>
</div>
</div>
</div>
<!-- Product Card 4 -->
<div class="group bg-surface-light dark:bg-surface-dark rounded-2xl p-3 shadow-sm hover:shadow-lg border border-transparent hover:border-red-100 dark:hover:border-red-900/50 transition-all cursor-pointer flex flex-col h-full">
<div class="relative w-full aspect-square rounded-xl overflow-hidden mb-3 bg-gray-100">
<img class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" data-alt="Glass of iced tea with lemon slice" src="https://lh3.googleusercontent.com/aida-public/AB6AXuDUA-kCZS8UHA3OUn81N0CvBBtpJ4CTeURljGHzJ0NTpAmT7JSHDCRkpquE0CnIjfQ7u_R4N-kYM4v1arZbyZdHsgD5vndmjt6jcAIFeetx5crpkNWeKBhubaAk-xoQCCIOuK4lE8BlrlEX7NyNfTDe1uAW9oAsX3v61MA_CXmKYouXnlCFcc-KQ-yFCnl33JCiOlR8rzK_QOjxKI-f5ZlCgOo2BIeiRcdyuun9mJ9MT0h2Ni2t947VR80NTGHSvUow6mJ95-_snala"/>
</div>
<div class="flex flex-col flex-1">
<h3 class="text-base font-bold text-slate-800 dark:text-white leading-tight mb-1">Es Teh Manis</h3>
<p class="text-xs text-gray-500 dark:text-gray-400 mb-2 line-clamp-2">Sweet iced tea, refreshing companion for dimsum.</p>
<div class="mt-auto flex items-center justify-between">
<span class="text-primary font-bold text-lg">Rp 8.000</span>
<button class="bg-red-50 dark:bg-red-900/30 text-primary p-1.5 rounded-lg hover:bg-primary hover:text-white transition-colors">
<span class="material-symbols-outlined text-[20px]">add</span>
</button>
</div>
</div>
</div>
<!-- Product Card 5 -->
<div class="group bg-surface-light dark:bg-surface-dark rounded-2xl p-3 shadow-sm hover:shadow-lg border border-transparent hover:border-red-100 dark:hover:border-red-900/50 transition-all cursor-pointer flex flex-col h-full">
<div class="relative w-full aspect-square rounded-xl overflow-hidden mb-3 bg-gray-100">
<img class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" data-alt="Steamed fluffy white buns filled with salted egg custard" src="https://lh3.googleusercontent.com/aida-public/AB6AXuCjKHkUZ6Q5-TJz7QlLAtgHRkfENqw2b3Ba17k7aLJRU6SqkOUwHn-o9HaCxI6DO5cXXxuG-7rOoLizrmPiKr_pA3cU9npVmOdafs3DgUwPog58mFk0Y1KUyM1WUh6_gmS3QYNmLoW8myhl8mm2DOcO9jtbjRhAhvQN9SNsl11-1PRtFul-3agBXdYRF-QOn7LbGWjGhTY-pG0Dgt-c5qNNq9SpG3Sk0BPc7yjamB3FxFkygqiuh1xxQ0J9zEB9_r5DTm6p_eDuCibn"/>
<div class="absolute top-2 right-2 bg-accent-gold text-white px-2 py-1 rounded-lg text-xs font-bold shadow-sm">
                            New
                        </div>
</div>
<div class="flex flex-col flex-1">
<h3 class="text-base font-bold text-slate-800 dark:text-white leading-tight mb-1">Bakpao Telur Asin</h3>
<p class="text-xs text-gray-500 dark:text-gray-400 mb-2 line-clamp-2">Soft buns with melting salted egg custard filling.</p>
<div class="mt-auto flex items-center justify-between">
<span class="text-primary font-bold text-lg">Rp 18.000</span>
<button class="bg-red-50 dark:bg-red-900/30 text-primary p-1.5 rounded-lg hover:bg-primary hover:text-white transition-colors">
<span class="material-symbols-outlined text-[20px]">add</span>
</button>
</div>
</div>
</div>
<!-- Product Card 6 -->
<div class="group bg-surface-light dark:bg-surface-dark rounded-2xl p-3 shadow-sm hover:shadow-lg border border-transparent hover:border-red-100 dark:hover:border-red-900/50 transition-all cursor-pointer flex flex-col h-full">
<div class="relative w-full aspect-square rounded-xl overflow-hidden mb-3 bg-gray-100">
<img class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" data-alt="Crispy fried wontons served with sweet chili sauce" src="https://lh3.googleusercontent.com/aida-public/AB6AXuDnZyu4wdcHQ25-eydkePObxG2fPvjvf0j8VaNmuE1jF_jRDcijMCER15twDiZdHhTJbUG2aUBjjawUG-p63JsX8FNArHPNEO2TKieaP-3qWrWhaRZTGF5NNN3jVTG-IVQFX9gOiisiwiOF4DFRcagdYvD48RuVn90MvdyVAqpXtW7YZfGCTX0kvYNvfo5Qbcm56RJ5lU87dFY9V3k2nLKREzt8t8LMi2piG4UclrqB5dgmCcn5ptJEkHtT3Kc0vvspnXoGrm7CxeGs"/>
</div>
<div class="flex flex-col flex-1">
<h3 class="text-base font-bold text-slate-800 dark:text-white leading-tight mb-1">Pangsit Goreng</h3>
<p class="text-xs text-gray-500 dark:text-gray-400 mb-2 line-clamp-2">Crispy fried wontons served with sweet &amp; sour sauce.</p>
<div class="mt-auto flex items-center justify-between">
<span class="text-primary font-bold text-lg">Rp 19.000</span>
<button class="bg-red-50 dark:bg-red-900/30 text-primary p-1.5 rounded-lg hover:bg-primary hover:text-white transition-colors">
<span class="material-symbols-outlined text-[20px]">add</span>
</button>
</div>
</div>
</div>
<!-- Product Card 7 -->
<div class="group bg-surface-light dark:bg-surface-dark rounded-2xl p-3 shadow-sm hover:shadow-lg border border-transparent hover:border-red-100 dark:hover:border-red-900/50 transition-all cursor-pointer flex flex-col h-full">
<div class="relative w-full aspect-square rounded-xl overflow-hidden mb-3 bg-gray-100">
<img class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" data-alt="Chicken feet dimsum in savory red sauce" src="https://lh3.googleusercontent.com/aida-public/AB6AXuB5eFc30HRkPWYHfBoO2M_QtiHZIBiPs7n5PgoLFatuQwRGF_dFZSFYofIVNZVTLtCrUhbunGbPgf1BibNoHas6rvWJBMYTdfMnKvAqIKXbFqIEo_1z_iC7F76ewDYdskvGlve7U1MR-nWRAOcb1_to0cpyY7sbjFC5mZC2R724tmTBPF8Kjdx_GSxSM8eXJFYCjvfQzvEE24YEMUehL2nyRoD6rf0B5Uxpm0TcmXQ8aUXfl-grYax8BORBkubOk8u5PXwWDywcBYd7"/>
</div>
<div class="flex flex-col flex-1">
<h3 class="text-base font-bold text-slate-800 dark:text-white leading-tight mb-1">Ceker Ayam</h3>
<p class="text-xs text-gray-500 dark:text-gray-400 mb-2 line-clamp-2">Tender chicken feet braised in savory black bean sauce.</p>
<div class="mt-auto flex items-center justify-between">
<span class="text-primary font-bold text-lg">Rp 18.000</span>
<button class="bg-red-50 dark:bg-red-900/30 text-primary p-1.5 rounded-lg hover:bg-primary hover:text-white transition-colors">
<span class="material-symbols-outlined text-[20px]">add</span>
</button>
</div>
</div>
</div>
<!-- Product Card 8 -->
<div class="group bg-surface-light dark:bg-surface-dark rounded-2xl p-3 shadow-sm hover:shadow-lg border border-transparent hover:border-red-100 dark:hover:border-red-900/50 transition-all cursor-pointer flex flex-col h-full">
<div class="relative w-full aspect-square rounded-xl overflow-hidden mb-3 bg-gray-100">
<img class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" data-alt="Iced tea with milk, also known as Teh Tarik" src="https://lh3.googleusercontent.com/aida-public/AB6AXuBXxYsr8r4E_B_gYa7682P57oXO7bbgMh7zOGkXl58NrQBBGqiNDu9uz_QJBL4RQ0dqhHGYrwNVro_FLDZOQkacr7X1E8R7UEZHaZYVTr6Mf0ll4gc02egDmoYWaHZT8QtPNNvNbaO8UqhceYTJODGHf_8XrZR6Ho4Mg9kO8olWwr7zvBHT25yk-iFeBfjSlDjZcN0VpmNiRyIwMqN-1h5Pz8hqz4qyDbB0ZrOIsu7VMhw95uKrdrr0pAQ0xifKayyi3M2fmcr9QlN8"/>
</div>
<div class="flex flex-col flex-1">
<h3 class="text-base font-bold text-slate-800 dark:text-white leading-tight mb-1">Teh Tarik</h3>
<p class="text-xs text-gray-500 dark:text-gray-400 mb-2 line-clamp-2">Creamy milk tea pulled to perfection.</p>
<div class="mt-auto flex items-center justify-between">
<span class="text-primary font-bold text-lg">Rp 15.000</span>
<button class="bg-red-50 dark:bg-red-900/30 text-primary p-1.5 rounded-lg hover:bg-primary hover:text-white transition-colors">
<span class="material-symbols-outlined text-[20px]">add</span>
</button>
</div>
</div>
</div>
</div>
</main>
<!-- Right Sidebar: Current Order -->
<aside class="w-96 bg-surface-light dark:bg-surface-dark border-l border-gray-200 dark:border-red-900/30 flex flex-col shrink-0 shadow-xl z-10">
<!-- Order Header -->
<div class="p-5 border-b border-gray-100 dark:border-red-900/30 flex items-center justify-between">
<div>
<h2 class="text-lg font-bold text-slate-900 dark:text-white">Current Order</h2>
<p class="text-sm text-gray-500 dark:text-gray-400">Order #20392</p>
</div>
<button class="p-2 text-primary bg-red-50 dark:bg-red-900/30 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/50 transition-colors" title="Clear All">
<span class="material-symbols-outlined">delete_sweep</span>
</button>
</div>
<!-- Order List -->
<div class="flex-1 overflow-y-auto p-4 flex flex-col gap-4">
<!-- Order Item 1 -->
<div class="flex gap-3 items-center">
<div class="size-14 rounded-lg bg-gray-100 bg-cover bg-center shrink-0" data-alt="Small thumbnail of Siomay Ayam" style="background-image: url('https://lh3.googleusercontent.com/aida-public/AB6AXuDMTX1V5fzw23mQolHPKVsIJzE-0Xr4HD0rVVeSd4H-HcNDC3svSQmI5isgPomFSErRoOUtxhyKJI-_CPD7gi4zTKMCZUGn8lS1sP_9j-wq3_n9TId2iaJ9lyx3mNg98QzsVL0CiMSCDjebWjfCusidxes1MRYf7zUgEfLsuxFHXRMkDTIpzkmF0lYBrr1XmuyjmgB_vqolczIXVm9pjUbDvOzWyDQLdmUHlfzK6c-aiXdTVzc9FoTjVY7WG4qKnna99Gy7yBVVCk96')"></div>
<div class="flex-1 min-w-0">
<h4 class="font-bold text-slate-800 dark:text-white truncate">Siomay Ayam</h4>
<p class="text-primary text-sm font-semibold">Rp 20.000</p>
</div>
<div class="flex items-center gap-2 bg-gray-100 dark:bg-red-950/30 rounded-lg p-1">
<button class="size-6 flex items-center justify-center bg-white dark:bg-surface-dark rounded shadow-sm hover:text-primary transition-colors text-slate-700 dark:text-white">
<span class="material-symbols-outlined text-[16px]">remove</span>
</button>
<span class="text-sm font-bold w-4 text-center">2</span>
<button class="size-6 flex items-center justify-center bg-primary text-white rounded shadow-sm hover:bg-primary-hover transition-colors">
<span class="material-symbols-outlined text-[16px]">add</span>
</button>
</div>
</div>
<!-- Order Item 2 -->
<div class="flex gap-3 items-center">
<div class="size-14 rounded-lg bg-gray-100 bg-cover bg-center shrink-0" data-alt="Small thumbnail of Es Teh Manis" style="background-image: url('https://lh3.googleusercontent.com/aida-public/AB6AXuAZiYcRoY4F9au9_qqPPqQa-yvE6a7P_rJzW0ZiZ2_H4cS6k0ckVcUQvRppylQjEToH1qyZKfrmCjm4YUvzPzudr5BXSsmQoV4c2QMlCfhS2FQoqGDAezfVPV09Z9vjF44NZ2Bdhu95UAICyFM3WG-u3tXOK3RGMuigA1VneGR-ZIxZ_ai-XJdQCwFxn3vd9XdGsMX97-AlBw_N9ECOq9XYJ5OIYxvVSOGln08riBbZhwJyl-nmZnhWFK5_IobaJESfxpT7R1W-C4tO')"></div>
<div class="flex-1 min-w-0">
<h4 class="font-bold text-slate-800 dark:text-white truncate">Es Teh Manis</h4>
<p class="text-primary text-sm font-semibold">Rp 8.000</p>
</div>
<div class="flex items-center gap-2 bg-gray-100 dark:bg-red-950/30 rounded-lg p-1">
<button class="size-6 flex items-center justify-center bg-white dark:bg-surface-dark rounded shadow-sm hover:text-primary transition-colors text-slate-700 dark:text-white">
<span class="material-symbols-outlined text-[16px]">remove</span>
</button>
<span class="text-sm font-bold w-4 text-center">1</span>
<button class="size-6 flex items-center justify-center bg-primary text-white rounded shadow-sm hover:bg-primary-hover transition-colors">
<span class="material-symbols-outlined text-[16px]">add</span>
</button>
</div>
</div>
<!-- Order Item 3 -->
<div class="flex gap-3 items-center">
<div class="size-14 rounded-lg bg-gray-100 bg-cover bg-center shrink-0" data-alt="Small thumbnail of Lumpia" style="background-image: url('https://lh3.googleusercontent.com/aida-public/AB6AXuAQyCCm8kPoyI-_GgA4szIgjl7JavIHKG95ec0NIIFwl04h8czCOgbRg3sINU4CoNmuf6YLfDyuLL6KwIXkwQdEsbWmVIhjZoJiT0vuHMCN8dKllhjJrK-iTFv9BVpL9ZQBrJ2cU70V2zBZ3J59fKs2V_6HIFbiy2WILLTBZvbkww0jEe94kRFKnd08EUmrgumDctjCetWOOWasnNtTzxqDABvBRI8G18D3OJku4oXne70STRlMtOyaLB2W8d_HcacWpKC-Wz0KP2Kw')"></div>
<div class="flex-1 min-w-0">
<h4 class="font-bold text-slate-800 dark:text-white truncate">Lumpia Kulit Tahu</h4>
<p class="text-primary text-sm font-semibold">Rp 22.000</p>
</div>
<div class="flex items-center gap-2 bg-gray-100 dark:bg-red-950/30 rounded-lg p-1">
<button class="size-6 flex items-center justify-center bg-white dark:bg-surface-dark rounded shadow-sm hover:text-primary transition-colors text-slate-700 dark:text-white">
<span class="material-symbols-outlined text-[16px]">remove</span>
</button>
<span class="text-sm font-bold w-4 text-center">1</span>
<button class="size-6 flex items-center justify-center bg-primary text-white rounded shadow-sm hover:bg-primary-hover transition-colors">
<span class="material-symbols-outlined text-[16px]">add</span>
</button>
</div>
</div>
<!-- Add Note Input (Optional UX enhancement) -->
<div class="mt-2">
<input class="w-full text-xs bg-gray-50 dark:bg-red-950/10 border-none rounded-lg py-2 px-3 text-slate-600 dark:text-gray-300 focus:ring-1 focus:ring-primary/50" placeholder="Add order note..." type="text"/>
</div>
</div>
<!-- Footer: Totals & Actions -->
<div class="bg-surface-light dark:bg-surface-dark border-t border-dashed border-gray-300 dark:border-red-900/50 p-5 shadow-[0_-5px_15px_rgba(0,0,0,0.02)]">
<div class="flex flex-col gap-2 mb-4">
<div class="flex justify-between items-center text-sm text-gray-500 dark:text-gray-400">
<span>Subtotal</span>
<span class="font-medium text-slate-800 dark:text-white">Rp 70.000</span>
</div>
<div class="flex justify-between items-center text-sm text-gray-500 dark:text-gray-400">
<span>Tax (10%)</span>
<span class="font-medium text-slate-800 dark:text-white">Rp 7.000</span>
</div>
<div class="flex justify-between items-center text-lg font-bold text-slate-900 dark:text-white mt-2 pt-2 border-t border-gray-100 dark:border-red-900/30">
<span>Total</span>
<span class="text-primary">Rp 77.000</span>
</div>
</div>
<div class="grid grid-cols-4 gap-3">
<button class="col-span-1 flex flex-col items-center justify-center p-3 rounded-xl border border-primary text-primary hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors">
<span class="material-symbols-outlined">save</span>
<span class="text-xs font-bold mt-1">Save</span>
</button>
<button class="col-span-3 flex items-center justify-center gap-2 bg-primary hover:bg-primary-hover text-white rounded-xl py-3 shadow-lg shadow-red-500/30 transition-all transform hover:-translate-y-0.5 active:translate-y-0">
<span class="font-bold text-lg">Pay Now</span>
<span class="material-symbols-outlined">arrow_forward</span>
</button>
</div>
</div>
</aside>
</div>
</body></html>