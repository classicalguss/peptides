@php
    // Reads the existing cart only; it never creates one for a passing visitor.
    $cartCount = \Lunar\Facades\CartSession::current(calculate: false)?->lines->sum('quantity') ?? 0;
@endphp

{{-- Drives the mobile drawer purely with CSS; no JavaScript needed. The checkbox sits
     outside the blurred header so the drawer and backdrop position against the viewport. --}}
<div class="group/nav">
<input type="checkbox" id="nav-toggle" class="peer sr-only" aria-label="Toggle navigation menu">

<header class="sticky top-0 z-50 border-b border-white/5 bg-ink/85 backdrop-blur-xl">

    <nav class="mx-auto flex max-w-7xl items-center gap-6 px-4 py-3.5">

        <a href="{{ route('home') }}" class="shrink-0" aria-label="Powered Up Peptides home">
            <img src="{{ asset('assets/brand/logo-wordmark.png') }}" alt="Powered Up Peptides"
                 class="h-9 w-auto sm:h-11">
        </a>

        <div class="ml-auto hidden items-center gap-5 lg:flex xl:gap-7">
            @foreach (config('theme.nav') as $item)
                @php $isActive = request()->routeIs($item['active'] ?? $item['route']); @endphp
                <a href="{{ route($item['route'], $item['params'] ?? []) }}"
                   @if ($isActive) aria-current="page" @endif
                   class="relative text-[13px] font-semibold tracking-wide uppercase transition hover:text-gold
                          {{ $isActive ? 'text-gold' : 'text-white/70' }}">
                    {{ site_text($item['text_key'], $item['label']) }}
                    @if ($isActive)
                        <span class="absolute -bottom-1.5 left-0 h-0.5 w-full rounded-full bg-gold"></span>
                    @endif
                </a>
            @endforeach
        </div>

        <div class="ml-auto flex items-center gap-3 lg:ml-0">
            <a href="{{ auth()->check() ? route('account') : route('login') }}"
               class="rounded-full border border-white/10 p-2.5 text-white/70 transition hover:border-gold/40 hover:text-gold"
               aria-label="{{ auth()->check() ? 'Your account' : 'Sign in' }}">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <circle cx="12" cy="8" r="3.6"/>
                    <path d="M4.5 20a7.5 7.5 0 0 1 15 0" stroke-linecap="round"/>
                </svg>
            </a>

            <a href="{{ route('cart') }}"
               class="relative rounded-full border border-white/10 p-2.5 text-white/70 transition hover:border-gold/40 hover:text-gold"
               aria-label="Cart{{ $cartCount ? ', '.$cartCount.' items' : '' }}">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M3 4h2l2.4 12.2a2 2 0 0 0 2 1.6h7.8a2 2 0 0 0 2-1.6L21 8H6" stroke-linecap="round" stroke-linejoin="round"/>
                    <circle cx="10" cy="20" r="1.4" fill="currentColor" stroke="none"/>
                    <circle cx="17.5" cy="20" r="1.4" fill="currentColor" stroke="none"/>
                </svg>

                @if ($cartCount)
                    <span class="absolute -top-1 -right-1 grid size-5 place-items-center rounded-full bg-gold text-[10px] font-extrabold text-black">
                        {{ $cartCount > 9 ? '9+' : $cartCount }}
                    </span>
                @endif
            </a>

        <label for="nav-toggle"
               class="cursor-pointer rounded-full border border-white/10 p-2.5 text-white/70 transition
                      peer-focus-visible:border-gold peer-focus-visible:text-gold
                      hover:border-gold/40 hover:text-gold lg:hidden">
            <svg class="size-5 group-has-checked/nav:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
                <path d="M4 7h16M4 12h16M4 17h16" stroke-linecap="round"/>
            </svg>
            <svg class="hidden size-5 group-has-checked/nav:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
                <path d="M6 6l12 12M18 6L6 18" stroke-linecap="round"/>
            </svg>
        </label>
        </div>
    </nav>
</header>

    {{-- Tapping the backdrop unchecks the toggle and closes the drawer. --}}
    <label for="nav-toggle" aria-hidden="true"
           class="invisible fixed top-0 left-0 z-40 h-dvh w-screen bg-black/70 opacity-0 backdrop-blur-sm transition-opacity duration-300
                  peer-checked:visible peer-checked:opacity-100 lg:hidden"></label>

    {{-- The wrapper clips the drawer while it sits off-screen, so it can never widen the page. --}}
    <div class="pointer-events-none fixed top-0 left-0 z-50 h-dvh w-screen overflow-hidden lg:hidden">
    <div class="pointer-events-auto absolute top-0 right-0 flex h-full w-[19rem] max-w-[85vw] translate-x-full flex-col
                border-l border-white/8 bg-ink shadow-2xl transition-transform duration-300 ease-out
                group-has-checked/nav:translate-x-0">

        <div class="flex items-center justify-between border-b border-white/8 px-5 py-4">
            <img src="{{ asset('assets/brand/logo-wordmark.png') }}" alt="Powered Up Peptides" class="h-8 w-auto">
            <label for="nav-toggle"
                   class="cursor-pointer rounded-full border border-white/10 p-2 text-white/60 transition hover:border-gold/40 hover:text-gold"
                   aria-label="Close menu">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M6 6l12 12M18 6L6 18" stroke-linecap="round"/>
                </svg>
            </label>
        </div>

        <nav class="flex-1 overflow-y-auto px-3 py-5">
            <p class="px-3 text-[10px] font-extrabold tracking-widest text-white/30 uppercase">Browse</p>
            <div class="mt-2 space-y-0.5">
                @foreach (config('theme.nav') as $item)
                    @php $isActive = request()->routeIs($item['active'] ?? $item['route']); @endphp
                    <a href="{{ route($item['route'], $item['params'] ?? []) }}"
                       @if ($isActive) aria-current="page" @endif
                       class="block rounded-xl border-l-2 px-3 py-3 text-sm font-bold tracking-wide uppercase transition
                              {{ $isActive
                                  ? 'border-gold bg-gold/10 text-gold'
                                  : 'border-transparent text-white/75 hover:bg-white/5 hover:text-gold' }}">
                        {{ site_text($item['text_key'], $item['label']) }}
                    </a>
                @endforeach
            </div>

            <p class="mt-6 px-3 text-[10px] font-extrabold tracking-widest text-white/30 uppercase">Account</p>
            <div class="mt-2 space-y-0.5">
                @auth
                    <a href="{{ route('account') }}"
                       @if (request()->routeIs('account*')) aria-current="page" @endif
                       class="block rounded-xl border-l-2 px-3 py-3 text-sm font-bold tracking-wide uppercase transition
                              {{ request()->routeIs('account*')
                                  ? 'border-gold bg-gold/10 text-gold'
                                  : 'border-transparent text-white/75 hover:bg-white/5 hover:text-gold' }}">
                        Your Account
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="w-full rounded-xl px-3 py-3 text-left text-sm font-bold tracking-wide text-white/75 uppercase transition hover:bg-white/5 hover:text-red-400">
                            Sign Out
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}"
                       @if (request()->routeIs('login')) aria-current="page" @endif
                       class="block rounded-xl border-l-2 px-3 py-3 text-sm font-bold tracking-wide uppercase transition
                              {{ request()->routeIs('login')
                                  ? 'border-gold bg-gold/10 text-gold'
                                  : 'border-transparent text-white/75 hover:bg-white/5 hover:text-gold' }}">
                        Sign In
                    </a>
                    <a href="{{ route('register') }}"
                       @if (request()->routeIs('register')) aria-current="page" @endif
                       class="block rounded-xl border-l-2 px-3 py-3 text-sm font-bold tracking-wide uppercase transition
                              {{ request()->routeIs('register')
                                  ? 'border-gold bg-gold/10 text-gold'
                                  : 'border-transparent text-white/75 hover:bg-white/5 hover:text-gold' }}">
                        Create Account
                    </a>
                @endauth
            </div>
        </nav>

        <div class="border-t border-white/8 p-4">
            <a href="{{ route('cart') }}"
               class="flex items-center justify-center gap-2 rounded-full bg-gold px-6 py-3.5 text-xs font-extrabold tracking-widest text-black uppercase">
                View Cart
                @if ($cartCount)
                    <span class="grid size-5 place-items-center rounded-full bg-black/25 text-[10px]">{{ $cartCount }}</span>
                @endif
            </a>
            <p class="mt-3 text-center text-[10px] tracking-wide text-white/30 uppercase">Research use only</p>
        </div>
    </div>
    </div>
</div>
