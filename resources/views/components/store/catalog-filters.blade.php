@props([
    'route',
    'categories',
    'activeCategory' => 'all',
    'sort' => 'featured',
    'totalCount' => 0,
    'allLabel' => 'All',
])

<div class="flex flex-col gap-5 border-b border-white/8 pb-6 lg:flex-row lg:items-center lg:justify-between">
    <div class="flex flex-wrap gap-2">
        <a href="{{ route($route, ['sort' => $sort]) }}"
           class="rounded-full border px-4 py-2 text-[11px] font-extrabold tracking-widest uppercase transition
                  {{ $activeCategory === 'all' ? 'border-gold bg-gold text-black' : 'border-white/12 text-white/60 hover:border-gold/40 hover:text-gold' }}">
            {{ $allLabel }}
            <span class="ml-1 opacity-60">{{ $totalCount }}</span>
        </a>

        @foreach ($categories as $slug => $category)
            <a href="{{ route($route, ['category' => $slug, 'sort' => $sort]) }}"
               class="rounded-full border px-4 py-2 text-[11px] font-extrabold tracking-widest uppercase transition
                      {{ $activeCategory === $slug ? 'border-gold bg-gold text-black' : 'border-white/12 text-white/60 hover:border-gold/40 hover:text-gold' }}">
                {{ $category['label'] }}
                <span class="ml-1 opacity-60">{{ $category['count'] }}</span>
            </a>
        @endforeach
    </div>

    <form method="GET" action="{{ route($route) }}" class="flex shrink-0 items-center gap-3">
        <input type="hidden" name="category" value="{{ $activeCategory }}">
        <label for="sort" class="text-[11px] font-extrabold tracking-widest text-white/40 uppercase">Sort</label>
        <select name="sort" id="sort" onchange="this.form.submit()"
                class="rounded-full border border-white/12 bg-panel px-4 py-2 text-xs font-semibold text-white/80 outline-none focus:border-gold/50">
            <option value="featured" @selected($sort === 'featured')>Featured</option>
            <option value="price-asc" @selected($sort === 'price-asc')>Price: Low to High</option>
            <option value="price-desc" @selected($sort === 'price-desc')>Price: High to Low</option>
            <option value="name" @selected($sort === 'name')>Name: A&ndash;Z</option>
        </select>
    </form>
</div>
