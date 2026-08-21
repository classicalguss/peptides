<x-layouts.storefront :title="site_text('login.meta_title')">

    <div class="mx-auto flex max-w-md flex-col justify-center px-4 py-20">
        <h1 class="display-title text-center text-4xl text-white">
            Sign <span class="text-foil">In</span>
        </h1>
        <p class="mt-3 text-center text-sm text-white/50">
            {{ site_text('login.introduction') }}
        </p>

        @if (session('status'))
            <div class="mt-8 rounded-xl border border-gold/25 bg-gold/[0.06] px-5 py-3.5 text-sm text-gold">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mt-8 rounded-xl border border-red-500/30 bg-red-500/[0.07] px-5 py-3.5 text-sm text-red-300">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-4 rounded-2xl panel p-7">
            @csrf

            <label class="block">
                <span class="text-[11px] font-bold tracking-wide text-white/50 uppercase">Email</span>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus
                       class="mt-1.5 w-full rounded-xl border border-white/12 bg-black/50 px-4 py-3 text-sm text-white outline-none focus:border-gold/50">
            </label>

            <label class="block">
                <span class="text-[11px] font-bold tracking-wide text-white/50 uppercase">Password</span>
                <input type="password" name="password" required
                       class="mt-1.5 w-full rounded-xl border border-white/12 bg-black/50 px-4 py-3 text-sm text-white outline-none focus:border-gold/50">
            </label>

            <label class="flex cursor-pointer items-center gap-2.5 pt-1">
                <input type="checkbox" name="remember" value="1"
                       class="size-4 appearance-none rounded border-2 border-white/25 transition checked:border-gold checked:bg-gold">
                <span class="text-xs text-white/50">{{ site_text('login.remember_label') }}</span>
            </label>

            <button type="submit"
                    class="mt-2 w-full rounded-full bg-gold px-8 py-3.5 text-sm font-extrabold tracking-widest text-black uppercase transition hover:bg-gold-bright">
                Sign In
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-white/45">
            No account yet?
            <a href="{{ route('register') }}" class="font-bold text-gold transition hover:text-gold-bright">Create one</a>
        </p>
    </div>

</x-layouts.storefront>
