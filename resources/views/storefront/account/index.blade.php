@php use App\Support\Catalog; @endphp

<x-layouts.storefront :title="site_text('account.meta_title')">

    <div class="mx-auto max-w-6xl px-4 py-12">
        <nav class="flex items-center gap-2 text-xs text-white/40">
            <a href="{{ route('home') }}" class="transition hover:text-gold">Home</a>
            <span>/</span>
            <span class="text-white/70">Account</span>
        </nav>

        <div class="mt-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="display-title text-4xl text-white sm:text-5xl">
                    Your <span class="text-foil">Account</span>
                </h1>
                <p class="mt-2 text-sm text-white/50">Signed in as {{ $user->email }}</p>
            </div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="rounded-full border border-white/15 px-5 py-2.5 text-[11px] font-extrabold tracking-widest text-white/60 uppercase transition hover:border-red-400/50 hover:text-red-400">
                    Sign Out
                </button>
            </form>
        </div>

        @if (session('status'))
            <div class="mt-6 rounded-xl border border-gold/25 bg-gold/[0.06] px-5 py-3.5 text-sm text-gold">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mt-6 rounded-xl border border-red-500/30 bg-red-500/[0.07] px-5 py-4">
                <ul class="space-y-1 text-xs text-red-200/85">
                    @foreach ($errors->all() as $error)
                        <li>&middot; {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Stats --}}
        <div class="mt-8 grid gap-4 sm:grid-cols-3">
            <div class="rounded-2xl panel p-6">
                <p class="text-[10px] font-extrabold tracking-widest text-white/40 uppercase">Orders</p>
                <p class="display-title mt-2 text-3xl text-foil">{{ $orders->count() }}</p>
            </div>
            <div class="rounded-2xl panel p-6">
                <p class="text-[10px] font-extrabold tracking-widest text-white/40 uppercase">Lifetime Spend</p>
                <p class="display-title mt-2 text-3xl text-foil">{{ Catalog::money($spend) }}</p>
            </div>
            <div class="rounded-2xl panel p-6">
                <p class="text-[10px] font-extrabold tracking-widest text-white/40 uppercase">Member Since</p>
                <p class="display-title mt-2 text-3xl text-foil">{{ $user->created_at->format('M Y') }}</p>
            </div>
        </div>

        <div class="mt-10 grid gap-8 lg:grid-cols-[1.5fr_1fr] lg:items-start">

            {{-- Orders --}}
            <section>
                <h2 class="text-[11px] font-extrabold tracking-widest text-gold uppercase">Order History</h2>

                @if ($orders->isEmpty())
                    <div class="mt-5 rounded-2xl panel p-12 text-center">
                        <p class="display-title text-2xl text-white">No orders yet</p>
                        <p class="mt-3 text-sm text-white/50">{{ site_text('account.empty_orders_description') }}</p>
                        <a href="{{ route('shop') }}"
                           class="mt-6 inline-block rounded-full bg-gold px-6 py-3 text-xs font-extrabold tracking-widest text-black uppercase">
                            Start Shopping
                        </a>
                    </div>
                @else
                    <div class="mt-5 space-y-3">
                        @foreach ($orders as $order)
                            <a href="{{ route('account.order', $order->reference) }}"
                               class="block rounded-2xl panel p-5 transition hover:border-gold/30">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <p class="font-mono text-sm font-bold text-white">{{ $order->reference }}</p>
                                        <p class="mt-1 text-xs text-white/40">
                                            {{ $order->created_at->format('M j, Y') }}
                                            &middot; {{ $order->lines->count() }} {{ Str::plural('item', $order->lines->count()) }}
                                        </p>
                                    </div>

                                    <div class="flex items-center gap-4">
                                        <span class="rounded-full bg-white/8 px-3 py-1 text-[10px] font-extrabold tracking-widest text-white/60 uppercase">
                                            {{ Str::headline($order->status) }}
                                        </span>
                                        <span class="display-title text-xl text-white">{{ Catalog::money($order->total->value) }}</span>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </section>

            {{-- Settings --}}
            <aside class="space-y-5">
                <section class="rounded-2xl panel p-6">
                    <h2 class="text-[11px] font-extrabold tracking-widest text-gold uppercase">Profile</h2>
                    <form method="POST" action="{{ route('account.profile') }}" class="mt-5 space-y-4">
                        @csrf
                        @method('PATCH')
                        <label class="block">
                            <span class="text-[11px] font-bold tracking-wide text-white/50 uppercase">Name</span>
                            <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                                   class="mt-1.5 w-full rounded-xl border border-white/12 bg-black/50 px-4 py-2.5 text-sm text-white outline-none focus:border-gold/50">
                        </label>
                        <label class="block">
                            <span class="text-[11px] font-bold tracking-wide text-white/50 uppercase">Email</span>
                            <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                                   class="mt-1.5 w-full rounded-xl border border-white/12 bg-black/50 px-4 py-2.5 text-sm text-white outline-none focus:border-gold/50">
                        </label>
                        <button type="submit"
                                class="w-full rounded-full border border-white/15 px-6 py-2.5 text-[11px] font-extrabold tracking-widest text-white uppercase transition hover:border-gold/50 hover:text-gold">
                            Save Profile
                        </button>
                    </form>
                </section>

                <section class="rounded-2xl panel p-6">
                    <h2 class="text-[11px] font-extrabold tracking-widest text-gold uppercase">Password</h2>
                    <form method="POST" action="{{ route('account.password') }}" class="mt-5 space-y-4">
                        @csrf
                        @method('PATCH')
                        <label class="block">
                            <span class="text-[11px] font-bold tracking-wide text-white/50 uppercase">Current password</span>
                            <input type="password" name="current_password" required
                                   class="mt-1.5 w-full rounded-xl border border-white/12 bg-black/50 px-4 py-2.5 text-sm text-white outline-none focus:border-gold/50">
                        </label>
                        <label class="block">
                            <span class="text-[11px] font-bold tracking-wide text-white/50 uppercase">New password</span>
                            <input type="password" name="password" required
                                   class="mt-1.5 w-full rounded-xl border border-white/12 bg-black/50 px-4 py-2.5 text-sm text-white outline-none focus:border-gold/50">
                        </label>
                        <label class="block">
                            <span class="text-[11px] font-bold tracking-wide text-white/50 uppercase">Confirm new password</span>
                            <input type="password" name="password_confirmation" required
                                   class="mt-1.5 w-full rounded-xl border border-white/12 bg-black/50 px-4 py-2.5 text-sm text-white outline-none focus:border-gold/50">
                        </label>
                        <button type="submit"
                                class="w-full rounded-full border border-white/15 px-6 py-2.5 text-[11px] font-extrabold tracking-widest text-white uppercase transition hover:border-gold/50 hover:text-gold">
                            Update Password
                        </button>
                    </form>
                </section>
            </aside>
        </div>
    </div>

</x-layouts.storefront>
