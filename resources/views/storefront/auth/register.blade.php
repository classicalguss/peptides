<x-layouts.storefront title="Create Account — Powered Up Peptides">

    <div class="mx-auto flex max-w-md flex-col justify-center px-4 py-20">
        <h1 class="display-title text-center text-4xl text-white">
            Create <span class="text-foil">Account</span>
        </h1>
        <p class="mt-3 text-center text-sm text-white/50">
            {{ site_text('register.introduction') }}
        </p>

        @if ($errors->any())
            <div class="mt-8 rounded-xl border border-red-500/30 bg-red-500/[0.07] px-5 py-4">
                <ul class="space-y-1 text-xs text-red-200/85">
                    @foreach ($errors->all() as $error)
                        <li>&middot; {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('register') }}" class="mt-8 space-y-4 rounded-2xl panel p-7">
            @csrf

            <div class="grid gap-4 sm:grid-cols-2">
                <label class="block">
                    <span class="text-[11px] font-bold tracking-wide text-white/50 uppercase">First name</span>
                    <input type="text" name="first_name" value="{{ old('first_name') }}" required autofocus
                           class="mt-1.5 w-full rounded-xl border border-white/12 bg-black/50 px-4 py-3 text-sm text-white outline-none focus:border-gold/50">
                </label>
                <label class="block">
                    <span class="text-[11px] font-bold tracking-wide text-white/50 uppercase">Last name</span>
                    <input type="text" name="last_name" value="{{ old('last_name') }}" required
                           class="mt-1.5 w-full rounded-xl border border-white/12 bg-black/50 px-4 py-3 text-sm text-white outline-none focus:border-gold/50">
                </label>
            </div>

            <label class="block">
                <span class="text-[11px] font-bold tracking-wide text-white/50 uppercase">Email</span>
                <input type="email" name="email" value="{{ old('email') }}" required
                       class="mt-1.5 w-full rounded-xl border border-white/12 bg-black/50 px-4 py-3 text-sm text-white outline-none focus:border-gold/50">
            </label>

            <label class="block">
                <span class="text-[11px] font-bold tracking-wide text-white/50 uppercase">Password</span>
                <input type="password" name="password" required
                       class="mt-1.5 w-full rounded-xl border border-white/12 bg-black/50 px-4 py-3 text-sm text-white outline-none focus:border-gold/50">
                <span class="mt-1.5 block text-[11px] text-white/30">Minimum 8 characters.</span>
            </label>

            <label class="block">
                <span class="text-[11px] font-bold tracking-wide text-white/50 uppercase">Confirm password</span>
                <input type="password" name="password_confirmation" required
                       class="mt-1.5 w-full rounded-xl border border-white/12 bg-black/50 px-4 py-3 text-sm text-white outline-none focus:border-gold/50">
            </label>

            <label class="flex cursor-pointer gap-3 pt-1">
                <input type="checkbox" name="research_use_confirmed" value="1" @checked(old('research_use_confirmed'))
                       class="mt-0.5 size-4 shrink-0 appearance-none rounded border-2 border-white/25 transition checked:border-gold checked:bg-gold">
                <span class="text-[11px] leading-relaxed text-white/50">{{ site_text('register.disclaimer') }}</span>
            </label>

            <button type="submit"
                    class="mt-2 w-full rounded-full bg-gold px-8 py-3.5 text-sm font-extrabold tracking-widest text-black uppercase transition hover:bg-gold-bright">
                Create Account
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-white/45">
            Already registered?
            <a href="{{ route('login') }}" class="font-bold text-gold transition hover:text-gold-bright">Sign in</a>
        </p>
    </div>

</x-layouts.storefront>
