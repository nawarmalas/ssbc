{{-- resources/views/pages/partials/board-members.blade.php --}}
@php $locale = app()->getLocale(); @endphp

@if($boardMembers->isNotEmpty())
<section class="bg-ssbc-beige">
    <div class="ssbc-container py-20">
        <div class="ssbc-rule"></div>
        <p class="ssbc-eyebrow mb-3">Board Members</p>
        <h2 class="text-3xl lg:text-4xl font-display font-bold text-ssbc-green leading-tight" dir="rtl" lang="ar">
            أعضاء مجلس الإدارة
        </h2>

        <div class="mt-12 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-5">
            @foreach($boardMembers as $member)
                <div class="group bg-white rounded-xl overflow-hidden shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-lg cursor-pointer"
                     x-data="{ open: false }"
                     @mouseenter="open = true"
                     @mouseleave="open = false"
                     @click.outside="open = false">

                    {{-- Photo + bio overlay --}}
                    <div class="relative overflow-hidden" style="aspect-ratio:3/4;">
                        @if($member->photoUrl())
                            <img src="{{ $member->photoUrl() }}"
                                 alt="{{ $member->name() }}"
                                 class="w-full h-full object-cover object-top">
                        @else
                            <div class="w-full h-full bg-ssbc-green/10 flex items-center justify-center">
                                <svg class="w-16 h-16 text-ssbc-green/20" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/>
                                </svg>
                            </div>
                        @endif

                        {{-- Bio overlay - CSS hover fallback plus Alpine tap toggle. --}}
                        <div class="absolute bottom-0 left-0 right-0 translate-y-full text-white p-4 transition-transform duration-300 group-hover:translate-y-0"
                             style="background:rgba(21,62,53,0.93);"
                             :class="open ? 'translate-y-0' : 'translate-y-full'">
                            <p class="text-xs font-bold uppercase tracking-wide mb-2" style="color:#daa900;">
                                {{ $locale === 'ar' ? 'نبذة مختصرة' : 'About' }}
                            </p>
                            <p class="text-xs leading-relaxed text-white/90">{{ $member->bio() }}</p>
                        </div>
                    </div>

                    {{-- Name + role — tap here on mobile to toggle bio --}}
                    <div class="text-center px-3 py-3 border-t-2 border-ssbc-beige"
                         @click="open = !open">
                        <p class="font-display font-bold text-sm text-ssbc-dark">{{ $member->name() }}</p>
                        <p class="text-xs text-ssbc-sage mt-1">{{ $member->role() }}</p>
                    </div>

                </div>
            @endforeach
        </div>
    </div>
</section>
@endif
