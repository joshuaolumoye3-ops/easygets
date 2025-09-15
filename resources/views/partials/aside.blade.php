<!-- resources/views/partials/aside.blade.php -->
<div x-data="{ open: false }" class="flex">
    <!-- Mobile Menu Button -->
    <button 
        @click="open = !open" 
        class="lg:hidden fixed top-4 left-4 z-50 p-3 bg-brand-green text-white rounded-lg shadow-lg hover:bg-brand-green-medium transition-colors"
    >
        <!-- Hamburger Icon -->
        <svg 
            x-show="!open" 
            class="w-6 h-6" 
            fill="none" 
            stroke="currentColor" 
            viewBox="0 0 24 24"
            x-transition:enter="transition-opacity duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity duration-300"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
        >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
        </svg>
        
        <!-- Close Icon -->
        <svg 
            x-show="open" 
            class="w-6 h-6" 
            fill="none" 
            stroke="currentColor" 
            viewBox="0 0 24 24"
            x-transition:enter="transition-opacity duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity duration-300"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
        >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
    </button>

    <!-- Sidebar -->
    <aside
        x-show="open"
        x-cloak
        x-transition:enter="transition transform duration-300"
        x-transition:enter-start="-translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition transform duration-300"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="-translate-x-full"
        class="fixed inset-y-0 left-0 w-64 bg-brand-green text-white flex flex-col shadow-2xl z-40 lg:static lg:translate-x-0 lg:block"
        @click.away="open = false"
    >
        <div class="p-6 text-2xl font-bold border-b border-brand-green-medium">
            Easy Admin
        </div>
        <nav class="flex-1 p-4 space-y-2">
            <a href="admin" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-brand-green-medium transition">
                <span>📊</span>
                <span>Dashboard</span>
            </a>
            <a href="/admin/airtime" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-brand-green-medium transition">
                <span>💳</span>
                <span>Airtime & Data</span>
            </a>
            <a href="/admin/jamb" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-brand-green-medium transition">
                <span>🎓</span>
                <span>JAMB Pins</span>
            </a>
            <a href="/admin/electricity" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-brand-green-medium transition">
                <span>⚡</span>
                <span>Electricity Bills</span>
            </a>
            <a href="/admin/cabletv" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-brand-green-medium transition">
                <span>📺</span>
                <span>Cable/TV</span>
            </a>
            <a href="#" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-brand-green-medium transition">
                <span>📱</span>
                <span>eSIM & OTP</span>
            </a>
            <a href="/admin/social" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-brand-green-medium transition">
                <span>👥</span>
                <span>Social Media Services</span>
            </a>
            <a href="/admin/settings" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-brand-green-medium transition">
                <span>⚙️</span>
                <span>Settings</span>
            </a>
        </nav>
    </aside>

    <!-- Overlay (Mobile) -->
    <div 
        x-show="open" 
        x-cloak 
        @click="open = false" 
        class="fixed inset-0 bg-black bg-opacity-50 z-30 lg:hidden"
        x-transition:enter="transition-opacity duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity duration-300"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    ></div>
</div>