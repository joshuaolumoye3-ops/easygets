@if(!empty($activeAnnouncement))

{{-- ============================================================
     ANNOUNCEMENT MODAL PARTIAL
     Usage: @include('partials.announcement')
     Requires: $activeAnnouncement object with ->title, ->body,
               ->id, and optionally ->created_at
     ============================================================ --}}

{{-- Modal Backdrop --}}
<div id="announcementModal"
     class="fixed inset-0 z-[60] flex items-center justify-center px-4"
     role="dialog"
     aria-modal="true"
     aria-labelledby="announcementModalTitle"
     style="background: rgba(0,0,0,0.65); backdrop-filter: blur(4px);">

    {{-- Modal Card --}}
    <div class="relative w-full max-w-md flex flex-col rounded-2xl overflow-hidden shadow-2xl"
         style="max-height: 85vh; animation: annSlideUp .3s cubic-bezier(.16,1,.3,1) both;">

        {{-- Gradient Header --}}
        <div class="relative flex-shrink-0 bg-gradient-to-br from-emerald-500 via-teal-500 to-cyan-500 px-5 py-6 overflow-hidden">

            {{-- Decorative circles --}}
            <div class="absolute -top-6 -right-6 w-32 h-32 rounded-full bg-white opacity-[0.07] pointer-events-none"></div>
            <div class="absolute -bottom-8 -left-4 w-24 h-24 rounded-full bg-white opacity-[0.05] pointer-events-none"></div>

            <div class="relative z-10">
                {{-- Badge --}}
                <div class="inline-flex items-center gap-1.5 bg-white/20 backdrop-blur-sm rounded-full px-3 py-1.5 mb-3">
                    <svg class="w-3.5 h-3.5 text-white flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 3a1 1 0 00-1.447-.894L8.763 6H5a3 3 0 000 6h.28l1.771 5.316A1 1 0 008 18h1a1 1 0 001-1v-4.382l6.553 3.276A1 1 0 0018 15V3z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-[10px] font-bold text-white uppercase tracking-wider">Announcement</span>
                </div>

                {{-- Title --}}
                <h2 id="announcementModalTitle" class="text-lg font-extrabold text-white leading-snug pr-6">
                    {{ $activeAnnouncement->title }}
                </h2>
            </div>

            {{-- Close X button in header --}}
            <button id="closeAnnouncementX"
                    onclick="closeAnnouncementModal()"
                    class="absolute top-4 right-4 z-20 w-8 h-8 flex items-center justify-center rounded-full bg-white/20 hover:bg-white/30 text-white transition-colors"
                    aria-label="Close announcement">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Scrollable Body --}}
        <div class="flex-1 overflow-y-auto bg-white px-5 py-5 min-h-0">
            <p class="text-sm text-gray-700 leading-relaxed whitespace-pre-line">{{ $activeAnnouncement->body }}</p>

            {{-- @if(!empty($activeAnnouncement->created_at))
            <p class="text-xs text-gray-400 mt-4 pt-3 border-t border-gray-100">
                Posted {{ $activeAnnouncement->created_at->diffForHumans() }}
            </p>
            @endif --}}
        </div>

        {{-- Pinned Footer (never scrolls) --}}
        <div class="flex-shrink-0 bg-white px-5 pb-5 pt-3 border-t border-gray-100">
            <button id="dismissAnnouncementBtn"
                    onclick="closeAnnouncementModal()"
                    class="w-full flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 active:scale-[0.98] text-white font-bold text-sm py-3.5 shadow-md hover:shadow-lg transition-all duration-150">
                <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                </svg>
                Got it, thanks!
            </button>
        </div>
    </div>
</div>

<style>
    @keyframes annSlideUp {
        from { opacity: 0; transform: translateY(24px) scale(0.97); }
        to   { opacity: 1; transform: translateY(0)    scale(1);    }
    }
</style>

<script>
    function closeAnnouncementModal() {
        const modal = document.getElementById('announcementModal');
        if (!modal) return;

        modal.style.transition = 'opacity 0.2s ease';
        modal.style.opacity = '0';
        setTimeout(function () {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }, 200);

        // Remember dismissal so it won't re-appear this browser session
        try {
            sessionStorage.setItem('ann_dismissed_{{ $activeAnnouncement->id }}', '1');
        } catch(e) {}
    }

    // On page load: lock scroll & check if already dismissed
    (function () {
        try {
            var dismissed = sessionStorage.getItem('ann_dismissed_{{ $activeAnnouncement->id }}');
            if (dismissed) {
                var modal = document.getElementById('announcementModal');
                if (modal) modal.style.display = 'none';
                return;
            }
        } catch(e) {}

        // Lock background scroll while modal is open
        document.body.style.overflow = 'hidden';
    })();

    // Close on backdrop click (not on the card itself)
    document.getElementById('announcementModal')?.addEventListener('click', function (e) {
        if (e.target === this) closeAnnouncementModal();
    });

    // Close on Escape key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            var modal = document.getElementById('announcementModal');
            if (modal && modal.style.display !== 'none' && modal.style.opacity !== '0') {
                closeAnnouncementModal();
            }
        }
    });
</script>

@endif