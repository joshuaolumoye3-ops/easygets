<footer class="fixed bottom-0 left-0 right-0 max-w-xl mx-auto bg-white border-t border-gray-200 shadow-[0_-1px_4px_rgba(0,0,0,0.05)]">
    <nav class="flex justify-around items-center h-20">
        <!-- Nav Item: Home -->
                <a href="{{ route('dashboard') }}"
                     class="flex min-h-16 flex-col items-center justify-center text-center w-full
                        {{ request()->routeIs('dashboard') ? 'text-brand-green font-bold' : 'text-black hover:text-brand-green transition-colors' }}">
            <!-- Icon -->
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 mb-1"  stroke="{{ request()->routeIs('dashboard') ? '' : 'currentColor' }}"
             viewBox="0 0 260 236" preserveAspectRatio="xMidYMid meet">
            <!-- Main rounded house shape (filled) -->
            <path d="M130 8
                    C138 8 218 44 230 52
                    C243 60 250 68 250 84
                    L250 192
                    C250 208 242 216 226 216
                    L34 216
                    C18 216 10 208 10 192
                    L10 84
                    C10 68 17 60 30 52
                    C42 44 122 8 130 8 Z"
                    fill="{{ request()->routeIs('dashboard') ? 'currentColor' : '#000000' }}"
                    stroke="{{ request()->routeIs('dashboard') ? '' : 'currentColor' }}" />
            <!-- bottom corners slightly rounded by path shape above -->

            <!-- White minus bar (centered) -->
            <rect x="70" y="138" width="120" height="18" rx="4" ry="4" fill="#ffffff"/>
            </svg>
            <!-- Text -->
            <span class="text-xs {{ request()->routeIs('dashboard') ? 'font-bold' : 'font-medium' }}">Home</span>
        </a>

        <!-- Nav Item: Activity -->
                <a href="{{ route('history') }}"
                     class="flex min-h-16 flex-col items-center justify-center text-center w-full
                        {{ request()->routeIs('history') ? 'text-brand-green font-bold' : 'text-black hover:text-brand-green transition-colors' }}">
            <!-- Icon -->
            <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <!-- Text -->
            <span class="text-xs {{ request()->routeIs('history') ? 'font-bold' : 'font-medium' }}">Activity</span>
        </a>

        <!-- Nav Item: Profile -->
        <a href="{{ route('profile') }}"
            class="flex min-h-16 flex-col items-center justify-center text-center w-full
                {{ request()->routeIs('profile') ? 'text-brand-green font-bold' : 'text-black hover:text-brand-green transition-colors' }}">
                <!-- Icon -->
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 mb-1" viewBox="0 0 230 230" preserveAspectRatio="xMidYMid meet">
                    <!-- outer circle -->
                    <circle cx="115" cy="115" r="96" fill="none" stroke="currentColor" stroke-width="18" />

                    <!-- head (circle) -->
                    <circle cx="115" cy="86" r="30" fill="none" stroke="currentColor" stroke-width="14" />

                    <!-- shoulders / torso -->
                    <path d="M70 168
                            C70 140 95 124 115 124
                            C135 124 160 140 160 168
                            Z"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="14"
                        stroke-linejoin="round"
                        stroke-linecap="round" />
                </svg>
                <!-- Text -->
                <span class="text-xs {{ request()->routeIs('profile') ? 'font-bold' : 'font-medium' }}">Profile</span>
            </a>
    </nav>
</footer>
