{{--
    Server-side rendered product card untuk SEO (Google indexable).
    Visual mendekati card JS supaya tidak ada flicker saat JS hydrate.
    Setelah DOMContentLoaded, JS akan re-render dengan markup interaktif
    (cart state, stepper, dll).
--}}
@php
    $isSoldOut = ! ($p['stock'] > 0);
    $packParts = array_filter([$p['pack_content'] ?? null, $p['pack_weight'] ?? null]);
    $packStr   = implode(' · ', $packParts);
@endphp
<article itemscope itemtype="https://schema.org/Product"
    class="group fade-in bg-white rounded-3xl overflow-hidden border-2 {{ $isSoldOut ? 'border-outline-variant/40 opacity-70 grayscale-[35%]' : 'border-outline-variant/50' }} transition-all duration-300 flex flex-col relative">

    <meta itemprop="sku" content="{{ $p['sku'] }}" />
    <meta itemprop="category" content="{{ $p['parent_cat'] }}" />

    <div class="absolute top-3 left-3 right-3 z-10 flex items-start justify-between gap-2 pointer-events-none">
        @if($isSoldOut)
            <span class="px-2.5 py-1 bg-on-surface-variant text-white text-[10px] font-bold uppercase tracking-wide rounded-full shadow-md">Stok Habis</span>
        @elseif(!empty($p['badge']))
            @php
                $badgeBg = match($p['badge']['color'] ?? 'primary') {
                    'tertiary'  => 'bg-tertiary text-on-tertiary',
                    'secondary' => 'bg-secondary text-on-secondary',
                    default     => 'bg-primary text-on-primary',
                };
            @endphp
            <span class="px-2.5 py-1 {{ $badgeBg }} text-[10px] font-bold uppercase tracking-wide rounded-full shadow-md">{{ $p['badge']['label'] }}</span>
        @endif
    </div>

    <div class="relative h-40 md:h-48 overflow-hidden bg-surface-container">
        <img src="{{ $p['image_url'] }}" alt="{{ $p['name'] }}" itemprop="image" loading="lazy"
             class="w-full h-full object-cover" />
        <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent pointer-events-none"></div>
    </div>

    <div class="p-4 md:p-5 flex flex-col flex-1">
        <h2 itemprop="name" class="font-bold text-base md:text-lg leading-tight mb-1 line-clamp-2">{{ $p['name'] }}</h2>
        @if($packStr)
            <div class="text-xs text-on-surface-variant mb-2">{{ $packStr }}</div>
        @endif

        <div class="mt-auto pt-3 border-t border-outline-variant/30"
             itemprop="offers" itemscope itemtype="https://schema.org/Offer">
            <meta itemprop="priceCurrency" content="IDR" />
            <meta itemprop="price" content="{{ (int) $p['price'] }}" />
            <link itemprop="availability" href="https://schema.org/{{ $isSoldOut ? 'OutOfStock' : 'InStock' }}" />

            <div class="text-[10px] text-on-surface-variant font-bold uppercase tracking-wider mb-0.5">Per {{ $p['uom'] }}</div>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2 md:gap-3">
                <div class="text-lg md:text-xl font-bold {{ $isSoldOut ? 'text-on-surface-variant' : 'text-primary' }} leading-tight whitespace-nowrap">
                    Rp {{ number_format($p['price'], 0, ',', '.') }}
                </div>
            </div>
        </div>
    </div>
</article>
