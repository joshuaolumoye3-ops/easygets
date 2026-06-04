@if(!empty($activeAnnouncement))
<!-- Professional Mobile-First Announcement -->
<div id="announcementContainer" class="w-full">
    <!-- Announcement Card -->
    <div class="mx-4 md:mx-auto md:max-w-6xl mt-6 mb-8">
        <div class="bg-white rounded-2xl md:rounded-3xl border border-slate-200/60 shadow-lg hover:shadow-xl transition-shadow duration-300 overflow-hidden">
            <!-- Header with Gradient Background -->
            <div class="relative bg-gradient-to-br from-emerald-500 via-teal-500 to-cyan-500 px-6 py-8 md:px-8 md:py-10 overflow-hidden">
                <!-- Decorative background element -->
                <div class="absolute inset-0 opacity-10">
                    <svg class="absolute top-0 right-0 w-40 h-40 md:w-56 md:h-56 text-white" fill="currentColor" viewBox="0 0 100 100">
                        <circle cx="70" cy="30" r="25" opacity="0.3"/>
                        <circle cx="20" cy="80" r="20" opacity="0.2"/>
                    </svg>
                </div>

                <div class="relative z-10">
                    <!-- Badge -->
                    <div class="inline-flex items-center rounded-full bg-white/20 backdrop-blur-sm px-4 py-2 mb-4">
                        <svg class="w-4 h-4 text-white mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 5v8a2 2 0 01-2 2h-5l-5 4v-4H4a2 2 0 01-2-2V5a2 2 0 012-2h12a2 2 0 012 2zm-11-1a1 1 0 11-2 0 1 1 0 012 0zm3 0a1 1 0 11-2 0 1 1 0 012 0zm3 0a1 1 0 11-2 0 1 1 0 012 0z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-xs font-bold text-white uppercase tracking-wider">Important Update</span>
                    </div>

                    <!-- Title -->
                    <h2 class="text-2xl md:text-3xl lg:text-4xl font-extrabold text-white leading-tight mt-2">
                        {{ $activeAnnouncement->title }}
                    </h2>
                </div>
            </div>

            <!-- Content Section -->
            <div class="p-6 md:p-8 lg:p-10">
                <!-- Main Message -->
                <div class="space-y-4 mb-8">
                    <p class="text-base md:text-lg text-slate-700 leading-relaxed">
                        {{ $activeAnnouncement->body }}
                    </p>
                </div>

                <!-- Visual Divider -->
                <div class="h-1 bg-gradient-to-r from-emerald-400 via-teal-400 to-cyan-400 rounded-full mb-8 opacity-60"></div>

                <!-- Footer Section -->
                <div class="text-center md:text-right">
                    <button id="dismissAnnouncementBtn" class="inline-flex items-center justify-center rounded-full bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 px-8 py-3 md:px-10 md:py-3 font-bold text-white text-base md:text-lg shadow-lg hover:shadow-xl transition-all duration-200 transform hover:-translate-y-1 active:translate-y-0 cursor-pointer">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        Got It
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dismissBtn = document.getElementById('dismissAnnouncementBtn');
            const container = document.getElementById('announcementContainer');

            if (dismissBtn) {
                dismissBtn.addEventListener('click', function() {
                    // Smooth fade-out animation
                    container.style.transition = 'opacity 0.3s ease-out';
                    container.style.opacity = '0';
                    
                    // Remove after animation
                    setTimeout(() => {
                        container.style.display = 'none';
                    }, 300);

                    // Optional: Store in localStorage that user dismissed it (session-based)
                    sessionStorage.setItem('announcement_dismissed', 'true');
                });
            }
        });
    </script>
</div>
@endif
