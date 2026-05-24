<!DOCTYPE html>
<html lang="id" class="light">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>{{ config('app.name', 'Pesisir Fresh Fish') }} — Booking Order</title>
<link rel="icon" type="image/png" href="{{ asset('assets/media/logos/logo-pesisir-web.png') }}" />
<link rel="shortcut icon" href="{{ asset('assets/media/logos/logo-pesisir-web.png') }}" />
<link rel="apple-touch-icon" href="{{ asset('assets/media/logos/logo-pesisir-web.png') }}" />
<script src="https://cdn.tailwindcss.com?plugins=forms"></script>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script>
tailwind.config = {
  theme: { extend: {
    colors: {
      surface: '#faf8ff', 'surface-container': '#ebedff', 'surface-container-low': '#f3f3ff', 'surface-container-high': '#e3e7ff',
      'on-surface': '#131a33', 'on-surface-variant': '#404850', outline: '#707881', 'outline-variant': '#bfc7d1',
      primary: '#005d90', 'on-primary': '#ffffff', 'primary-container': '#0077b6', 'primary-fixed': '#cde5ff', 'on-primary-fixed': '#001d32',
      secondary: '#006a60', 'on-secondary': '#ffffff', 'secondary-container': '#8cf5e4', 'on-secondary-container': '#007166',
      tertiary: '#6f5500', 'on-tertiary': '#ffffff',
      error: '#ba1a1a', 'on-error': '#ffffff', 'error-container': '#ffdad6',
    },
    fontFamily: { sans: ['Manrope', 'system-ui', 'sans-serif'] },
    borderRadius: { '3xl': '24px', '4xl': '32px' },
  }}
}
</script>
<style>
body { font-family: 'Manrope', sans-serif; }
.material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
.cart-fab { box-shadow: 0 12px 32px rgba(0,93,144,0.35); }
.fade-in { animation: fadeIn 0.3s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
.sheet-slide-up { animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
@keyframes slideUp { from { transform: translateY(100%); } to { transform: translateY(0); } }
.no-scrollbar::-webkit-scrollbar { display: none; }
</style>
</head>
<body class="bg-surface text-on-surface min-h-screen pb-32">

{{-- ===== HEADER ===== --}}
<header class="sticky top-0 z-40 bg-surface/90 backdrop-blur-md border-b border-outline-variant">
  <div class="max-w-7xl mx-auto px-4 md:px-8 h-16 flex items-center justify-between">
    <div class="flex items-center gap-3 min-w-0">
      <img src="{{ asset('assets/media/logos/logo-pesisir-web.png') }}" alt="{{ config('app.name', 'Pesisir Fresh Fish') }}"
           class="h-10 md:h-12 w-auto object-contain flex-shrink-0" />
      <div class="leading-tight min-w-0">
        <h1 class="font-bold text-sm md:text-lg text-primary uppercase tracking-tight truncate">{{ config('app.name', 'Pesisir Fresh Fish') }}</h1>
        <p class="text-[10px] md:text-xs text-on-surface-variant truncate">Ikan Segar dari Pesisir</p>
      </div>
    </div>
    <a href="https://wa.me/{{ config('app.portal_admin_wa') }}" target="_blank"
       class="hidden sm:flex items-center gap-2 px-4 py-2 bg-secondary-container text-on-secondary-container rounded-full text-sm font-semibold hover:opacity-90 transition">
      <span class="material-symbols-outlined text-base">support_agent</span>
      Tanya Admin
    </a>
  </div>
</header>

{{-- ===== HERO ===== --}}
<section class="px-4 md:px-8 pt-4 md:pt-6 pb-2 max-w-7xl mx-auto">
  <div class="relative rounded-3xl overflow-hidden bg-primary-fixed min-h-[280px] md:min-h-[400px] flex items-center p-6 md:p-12">
    <div class="absolute inset-0 z-0">
      <img class="w-full h-full object-cover opacity-80 mix-blend-overlay"
           src="https://lh3.googleusercontent.com/aida-public/AB6AXuCLCwVjppp8RfKTipBu4Rnc8fRNmLFjYk8vSkougswKnGROH-5t6T-JRtihpZas_UE3dVudfCjpyLY9Z6XHr6GvaSJyF2sUANXyrqnjsakbUhqV5GPQFu8tTHlcsmNMiUr5JGRgx-POc96TIs5dp5qvEfUYMuiv7jYr2u3QGaRh0FYcYHdIGJIhdJ_UNyQsasEif1i2cHjXegvl8NXyIHtsjyN2HgUFCu4rjtm9zKiHSP3QUns_IJcvOzLRNnECwAjqraTdPIUkKCI"
           alt="Pesisir Fresh Fish" />
      <div class="absolute inset-0 bg-gradient-to-r from-primary-fixed/90 via-primary-fixed/40 to-transparent"></div>
    </div>
    <div class="relative z-10 max-w-2xl">
      <span class="inline-block px-3 py-1 bg-secondary-container text-on-secondary-container rounded-full text-xs font-bold mb-4">FRESH FROM THE DOCK</span>
      <h2 class="text-2xl md:text-4xl font-bold text-on-primary-fixed leading-tight mb-3">Ikan Laut Segar, Langsung dari Nelayan</h2>
      <p class="text-on-primary-fixed text-sm md:text-base opacity-80 max-w-md">Pilih produk segar, tambah ke keranjang, lalu booking via WhatsApp.</p>
    </div>
  </div>
</section>

{{-- ===== SEARCH + CATEGORY FILTER ===== --}}
<section class="px-4 md:px-8 max-w-7xl mx-auto pt-3 md:pt-4">
  <div class="bg-white rounded-3xl shadow-sm border border-outline-variant/40 p-4 md:p-6 space-y-4">

    {{-- Search box + counter --}}
    <div class="flex flex-col md:flex-row items-stretch md:items-center gap-3">
      <div class="relative flex-1">
        <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-primary pointer-events-none">search</span>
        <input id="search-input" type="search" placeholder="Cari produk atau kategori..." autocomplete="off"
          class="w-full pl-12 pr-12 py-3.5 bg-surface-container-low border-2 border-transparent rounded-2xl text-on-surface placeholder:text-on-surface-variant focus:outline-none focus:border-primary focus:bg-white transition-all" />
        <button id="search-clear" onclick="clearSearch()" class="hidden absolute right-3 top-1/2 -translate-y-1/2 w-8 h-8 hover:bg-surface-container rounded-full flex items-center justify-center">
          <span class="material-symbols-outlined text-on-surface-variant text-base">close</span>
        </button>
      </div>
      <div class="hidden md:flex items-center gap-2 text-sm text-on-surface-variant whitespace-nowrap px-2">
        <span class="material-symbols-outlined text-base">inventory_2</span>
        <span><span id="product-count" class="font-bold text-primary">0</span> produk tersedia</span>
      </div>
    </div>

    {{-- Category chips header + chips --}}
    <div class="space-y-2">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-2 text-xs font-bold text-on-surface-variant uppercase tracking-wider">
          <span class="material-symbols-outlined text-base">filter_list</span>
          Filter Kategori
        </div>
        <span class="md:hidden text-xs text-on-surface-variant"><span id="product-count-m" class="font-bold text-primary">0</span> produk</span>
      </div>
      <div id="cat-chips" class="flex items-center gap-2 overflow-x-auto no-scrollbar -mx-1 px-1 pb-1"></div>
    </div>

  </div>
</section>

{{-- ===== PRODUCT GRID ===== --}}
<section class="px-4 md:px-8 max-w-7xl mx-auto pt-5 md:pt-6">
  <div id="product-grid" class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3 md:gap-6">
    {{-- Loading skeletons --}}
    <template id="skeleton-tpl">
      <div class="bg-surface-container-low rounded-3xl p-4 animate-pulse">
        <div class="h-40 bg-surface-container rounded-2xl mb-3"></div>
        <div class="h-4 bg-surface-container rounded mb-2"></div>
        <div class="h-3 bg-surface-container rounded w-1/2"></div>
      </div>
    </template>
  </div>
  <div id="empty-state" class="hidden text-center py-20">
    <span class="material-symbols-outlined text-on-surface-variant" style="font-size:64px;">set_meal</span>
    <p class="text-on-surface-variant mt-2">Belum ada produk tersedia saat ini.</p>
  </div>
</section>

{{-- ===== FLOATING CART BUTTON ===== --}}
<button id="cart-fab" onclick="openCart()"
  class="cart-fab fixed bottom-6 right-6 z-50 hidden flex-row items-center gap-3 px-5 py-4 bg-primary text-white rounded-full hover:bg-primary-container active:scale-95 transition-all">
  <span class="material-symbols-outlined">shopping_cart</span>
  <span class="font-bold" id="cart-count">0</span>
  <span class="hidden sm:inline-block text-sm font-semibold border-l border-white/30 pl-3" id="cart-total">Rp 0</span>
</button>

{{-- ===== CART SHEET ===== --}}
<div id="cart-overlay" class="hidden fixed inset-0 bg-black/50 z-50" onclick="closeCart()"></div>
<div id="cart-sheet" class="hidden fixed bottom-0 left-0 right-0 z-50 bg-surface rounded-t-3xl shadow-2xl max-h-[85vh] overflow-hidden flex flex-col">
  <div class="p-4 border-b border-outline-variant flex items-center justify-between">
    <h3 class="text-xl font-bold">Keranjang Belanja</h3>
    <button onclick="closeCart()" class="p-2 hover:bg-surface-container rounded-full">
      <span class="material-symbols-outlined">close</span>
    </button>
  </div>
  <div id="cart-items" class="flex-1 overflow-y-auto p-4 space-y-3"></div>
  <div class="p-4 border-t border-outline-variant bg-surface-container-low">
    <div class="flex items-center justify-between mb-3">
      <span class="text-on-surface-variant">Total</span>
      <span class="text-2xl font-bold text-primary" id="sheet-total">Rp 0</span>
    </div>
    <button onclick="checkoutWA()" id="checkout-btn"
      class="w-full py-4 bg-[#25D366] text-white rounded-2xl font-bold flex items-center justify-center gap-2 hover:bg-[#1ea554] active:scale-[0.98] transition-all">
      <svg class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
      Booking via WhatsApp
    </button>
    <button onclick="clearCart()" class="w-full mt-2 py-2 text-error text-sm font-semibold">Kosongkan Keranjang</button>
  </div>
</div>

{{-- ===== NUTRITION MODAL ===== --}}
<div id="nut-overlay" class="hidden fixed inset-0 bg-black/50 z-[60]" onclick="closeNutritionModal()"></div>
<div id="nut-modal" class="hidden fixed left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 z-[70] w-[92vw] max-w-md bg-surface rounded-3xl shadow-2xl overflow-hidden">
  <div class="bg-gradient-to-br from-primary to-primary-container text-white p-5 pr-16 relative">
    <button type="button" onclick="closeNutritionModal()"
      class="absolute top-3 right-3 z-10 w-11 h-11 rounded-full bg-white/25 hover:bg-white/40 active:scale-90 flex items-center justify-center transition-all cursor-pointer">
      <span class="material-symbols-outlined text-white pointer-events-none" style="font-size:24px;">close</span>
    </button>
    <div class="flex items-center gap-2 text-xs font-bold uppercase opacity-80 mb-1">
      <span class="material-symbols-outlined" style="font-size:16px;">spa</span>
      Kandungan Nutrisi
    </div>
    <h3 id="nut-modal-title" class="text-xl font-bold leading-tight"></h3>
  </div>
  <div id="nut-modal-body" class="p-5 space-y-3 max-h-[55vh] overflow-y-auto"></div>
  <div class="p-4 border-t border-outline-variant/50 bg-surface-container-low">
    <button type="button" onclick="closeNutritionModal()" class="w-full py-3 bg-primary text-white rounded-2xl font-bold hover:bg-primary-container active:scale-[0.98] transition-all">
      Tutup
    </button>
  </div>
</div>

{{-- ===== TOAST ===== --}}
<div id="toast" class="hidden fixed bottom-24 left-1/2 -translate-x-1/2 z-50 px-4 py-3 bg-on-surface text-surface rounded-full shadow-2xl text-sm font-semibold"></div>

<script>
const STORAGE_KEY = 'pesisir_cart_v1';
const PRODUCTS_URL = "{{ route('portal.products') }}";
let ADMIN_WA = "{{ config('app.portal_admin_wa', '') }}";
let ALL_PRODUCTS = [];
let activeCategory = 'all';
let searchTerm = '';

function fmtRp(v) { return 'Rp ' + Math.round(parseFloat(v) || 0).toLocaleString('id-ID'); }
function fmtQty(v) {
  const n = parseFloat(v) || 0;
  return Math.floor(n) === n ? n.toLocaleString('id-ID') : n.toLocaleString('id-ID', { minimumFractionDigits: 3, maximumFractionDigits: 3 });
}
function showToast(msg) {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.classList.remove('hidden');
  setTimeout(() => t.classList.add('hidden'), 2200);
}

// ===== CART (localStorage) =====
function getCart() { try { return JSON.parse(localStorage.getItem(STORAGE_KEY)) || []; } catch (e) { return []; } }
function saveCart(c) {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(c));
  updateCartUI();
  // Sync product cards (qty badge / +/- controls)
  if (ALL_PRODUCTS.length) renderProducts(getFilteredList());
}
function addToCart(p) {
  const cart = getCart();
  const existing = cart.find(i => i.id === p.id);
  if (existing) {
    if (existing.qty + 1 > p.stock) { showToast(`Stok ${p.name} hanya ${fmtQty(p.stock)}`); return; }
    existing.qty += 1;
  } else {
    if (p.stock <= 0) { showToast(`${p.name} stok habis`); return; }
    cart.push({ id: p.id, sku: p.sku, name: p.name, price: p.price, qty: 1, uom: p.uom, stock: p.stock });
  }
  saveCart(cart);
  showToast(`+ ${p.name}`);
}
function changeQty(productId, delta) {
  const cart = getCart();
  const item = cart.find(i => i.id === productId);
  if (! item) return;
  const newQty = item.qty + delta;
  if (newQty <= 0) {
    saveCart(cart.filter(i => i.id !== productId));
    return;
  }
  if (newQty > item.stock) { showToast(`Stok ${item.name} hanya ${fmtQty(item.stock)}`); return; }
  item.qty = newQty;
  saveCart(cart);
}
function removeFromCart(productId) { saveCart(getCart().filter(i => i.id !== productId)); }
function clearCart() {
  if (! confirm('Kosongkan keranjang?')) return;
  saveCart([]);
  closeCart();
}

// ===== UI UPDATE =====
function updateCartUI() {
  const cart = getCart();
  const count = cart.reduce((s, i) => s + i.qty, 0);
  const total = cart.reduce((s, i) => s + (i.qty * i.price), 0);
  document.getElementById('cart-count').textContent = count;
  document.getElementById('cart-total').textContent = fmtRp(total);
  document.getElementById('sheet-total').textContent = fmtRp(total);
  const fab = document.getElementById('cart-fab');
  if (count > 0) fab.classList.remove('hidden'); else fab.classList.add('hidden');
  fab.classList.toggle('flex', count > 0);

  // Render cart items in sheet
  const itemsEl = document.getElementById('cart-items');
  if (! cart.length) {
    itemsEl.innerHTML = '<div class="text-center py-12 text-on-surface-variant"><span class="material-symbols-outlined" style="font-size:48px;">remove_shopping_cart</span><p class="mt-2">Keranjang kosong</p></div>';
    document.getElementById('checkout-btn').disabled = true;
    document.getElementById('checkout-btn').classList.add('opacity-50');
  } else {
    itemsEl.innerHTML = cart.map(it => `
      <div class="flex items-center gap-3 p-3 bg-surface-container-low rounded-2xl">
        <div class="flex-1">
          <div class="font-semibold">${it.name}</div>
          <div class="text-xs text-on-surface-variant">${it.sku}</div>
          <div class="text-sm text-primary font-bold mt-1">${fmtRp(it.price)} / ${it.uom}</div>
        </div>
        <div class="flex items-center gap-2">
          <button onclick="changeQty(${it.id}, -1)" class="w-8 h-8 rounded-full bg-surface-container active:bg-surface-container-high flex items-center justify-center"><span class="material-symbols-outlined text-base">remove</span></button>
          <span class="font-bold w-8 text-center">${it.qty}</span>
          <button onclick="changeQty(${it.id}, 1)" class="w-8 h-8 rounded-full bg-primary text-white active:bg-primary-container flex items-center justify-center"><span class="material-symbols-outlined text-base">add</span></button>
        </div>
        <button onclick="removeFromCart(${it.id})" class="text-error p-1"><span class="material-symbols-outlined">delete</span></button>
      </div>
    `).join('');
    document.getElementById('checkout-btn').disabled = false;
    document.getElementById('checkout-btn').classList.remove('opacity-50');
  }
}

// ===== NUTRITION MODAL =====
function openNutritionModal(p) {
  document.getElementById('nut-modal-title').textContent = p.name;
  const body = document.getElementById('nut-modal-body');
  body.innerHTML = (p.nutrition_info || []).map(n => `
    <div class="flex items-start gap-3 p-3 bg-surface-container-low rounded-2xl">
      <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-secondary-container text-on-secondary-container flex items-center justify-center">
        <span class="material-symbols-outlined">${n.icon || 'spa'}</span>
      </div>
      <div class="flex-1 min-w-0">
        <div class="font-bold text-on-surface">${n.label}</div>
        ${n.detail ? `<div class="text-sm text-on-surface-variant mt-1 leading-relaxed">${n.detail}</div>` : ''}
      </div>
    </div>
  `).join('');
  document.getElementById('nut-overlay').classList.remove('hidden');
  document.getElementById('nut-modal').classList.remove('hidden');
  document.body.style.overflow = 'hidden';
}
function closeNutritionModal() {
  document.getElementById('nut-overlay').classList.add('hidden');
  document.getElementById('nut-modal').classList.add('hidden');
  document.body.style.overflow = '';
}

// ===== CART SHEET =====
function openCart() {
  document.getElementById('cart-overlay').classList.remove('hidden');
  document.getElementById('cart-sheet').classList.remove('hidden');
  document.getElementById('cart-sheet').classList.add('sheet-slide-up');
  document.body.style.overflow = 'hidden';
}
function closeCart() {
  document.getElementById('cart-overlay').classList.add('hidden');
  document.getElementById('cart-sheet').classList.add('hidden');
  document.body.style.overflow = '';
}

// ===== CHECKOUT VIA WA =====
function checkoutWA() {
  const cart = getCart();
  if (! cart.length) return;
  const total = cart.reduce((s, i) => s + i.qty * i.price, 0);
  let msg = `Halo Admin ${"{{ config('app.name', 'Pesisir') }}"} 🙏\n\nSaya mau booking pesanan berikut:\n\n`;
  cart.forEach((it, idx) => {
    msg += `${idx + 1}. *${it.name}*\n   ${it.qty} ${it.uom} × ${fmtRp(it.price)} = ${fmtRp(it.qty * it.price)}\n`;
  });
  msg += `\n*TOTAL: ${fmtRp(total)}*\n\nMohon konfirmasi ketersediaan & cara pembayaran. Terima kasih 🙏`;

  const url = ADMIN_WA
    ? `https://wa.me/${ADMIN_WA}?text=${encodeURIComponent(msg)}`
    : `https://wa.me/?text=${encodeURIComponent(msg)}`;
  window.open(url, '_blank');
}

// ===== PRODUCT CARDS =====
const DEFAULT_PRODUCT_IMG = "{{ asset('assets/media/product/default-produk.jpg') }}";
function getFishIcon(category) {
  const c = (category || '').toLowerCase();
  if (c.includes('udang') || c.includes('shrimp')) return 'set_meal';
  if (c.includes('cumi') || c.includes('squid'))   return 'phishing';
  if (c.includes('kepiting') || c.includes('crab')) return 'pets';
  return 'set_meal';
}
// Badge color map — pakai class hardcoded supaya Tailwind JIT pick up
const BADGE_STYLES = {
  tertiary:  { bg: 'bg-tertiary',  text: 'text-on-tertiary' },
  secondary: { bg: 'bg-secondary', text: 'text-on-secondary' },
  primary:   { bg: 'bg-primary',   text: 'text-on-primary' },
};
function getStockBadge(stock, uom) {
  // Tier indikator stok: low (<5), mid (5-20), good (>20)
  if (stock < 5)  return { cls: 'bg-tertiary/10 text-tertiary border-tertiary/30',     icon: 'priority_high',  label: `Sisa ${fmtQty(stock)} ${uom}` };
  if (stock < 20) return { cls: 'bg-secondary/10 text-secondary border-secondary/30',  icon: 'inventory_2',    label: `Stok ${fmtQty(stock)} ${uom}` };
  return            { cls: 'bg-secondary-container text-on-secondary-container border-secondary/40', icon: 'verified', label: `Tersedia ${fmtQty(stock)} ${uom}` };
}

function renderProducts(list) {
  const grid = document.getElementById('product-grid');
  const empty = document.getElementById('empty-state');
  // Update counter
  const cntEl = document.getElementById('product-count');
  const cntElM = document.getElementById('product-count-m');
  if (cntEl)  cntEl.textContent  = list.length;
  if (cntElM) cntElM.textContent = list.length;
  if (! list.length) {
    grid.innerHTML = '';
    empty.classList.remove('hidden');
    return;
  }
  empty.classList.add('hidden');
  const cart = getCart();
  const inCartMap = {};
  cart.forEach(it => inCartMap[it.id] = it.qty);

  grid.innerHTML = list.map(p => {
    const packParts = [];
    if (p.pack_content) packParts.push(p.pack_content);
    if (p.pack_weight)  packParts.push(p.pack_weight);
    const packStr = packParts.length ? packParts.join(' · ') : '';
    const inCartQty = inCartMap[p.id] || 0;
    const stockBadge = getStockBadge(p.stock, p.uom);

    // Image: prefer admin upload → fallback ke default produk asset → fallback ke icon
    const imgUrl = p.image_url || DEFAULT_PRODUCT_IMG;
    const imgHtml = `
      <img src="${imgUrl}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" alt="${p.name}"
           onerror="this.onerror=null; this.src='${DEFAULT_PRODUCT_IMG}'; this.onerror=function(){this.parentElement.innerHTML='<div class=\\'w-full h-full bg-gradient-to-br from-primary-fixed to-secondary-container flex items-center justify-center\\'><span class=\\'material-symbols-outlined text-primary\\' style=\\'font-size:72px; opacity:0.6;\\'>${getFishIcon(p.parent_cat)}</span></div>';};" />
      <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent pointer-events-none"></div>
    `;

    // Action button:
    //   Mobile  → full-width (lebih nyaman di-tap).
    //   Desktop → compact (icon button / stepper kecil).
    const actionHtml = inCartQty > 0 ? `
      <div class="flex items-center gap-0.5 bg-primary-fixed border border-primary/30 rounded-xl p-1 md:p-0.5 w-full md:w-auto">
        <button onclick="changeQty(${p.id}, -1)" class="h-9 md:h-7 w-9 md:w-7 rounded-lg bg-white text-primary hover:bg-primary hover:text-white active:scale-90 flex items-center justify-center transition-all">
          <span class="material-symbols-outlined" style="font-size:18px;">remove</span>
        </button>
        <span class="font-bold flex-1 md:w-7 text-center text-primary text-base md:text-sm">${inCartQty}</span>
        <button onclick='addToCart(${JSON.stringify(p)})' class="h-9 md:h-7 w-9 md:w-7 rounded-lg bg-primary text-white hover:bg-primary-container active:scale-90 flex items-center justify-center transition-all">
          <span class="material-symbols-outlined" style="font-size:18px;">add</span>
        </button>
      </div>
    ` : `
      <button onclick='addToCart(${JSON.stringify(p)})'
        class="group/btn inline-flex items-center justify-center gap-1.5 w-full md:w-10 h-10 px-3 md:px-0 bg-primary text-white rounded-xl hover:bg-primary-container active:scale-95 transition-all shadow-md hover:shadow-lg hover:-translate-y-0.5"
        title="Tambah ke pesanan">
        <span class="material-symbols-outlined group-hover/btn:rotate-12 transition-transform" style="font-size:20px;">add_shopping_cart</span>
        <span class="text-sm font-semibold md:hidden">Pesan</span>
      </button>
    `;

    return `
      <div class="group fade-in bg-white rounded-3xl overflow-hidden border-2 ${inCartQty > 0 ? 'border-primary shadow-lg shadow-primary/20' : 'border-outline-variant/50'} hover:shadow-xl hover:border-primary/30 hover:-translate-y-1 transition-all duration-300 flex flex-col relative">

        {{-- Top badges (badge admin di kiri + cart counter di kanan) --}}
        <div class="absolute top-3 left-3 right-3 z-10 flex items-start justify-between gap-2 pointer-events-none">
          <div class="flex flex-col items-start gap-1">
            ${p.badge ? (() => {
              const s = BADGE_STYLES[p.badge.color] || BADGE_STYLES.primary;
              return `<span class="px-2.5 py-1 ${s.bg} ${s.text} text-[10px] font-bold uppercase tracking-wide rounded-full shadow-md">${p.badge.label}</span>`;
            })() : ''}
          </div>
          ${inCartQty > 0 ? `<span class="px-2.5 py-1 bg-primary text-white text-[10px] font-bold rounded-full shadow-md flex items-center gap-1"><span class="material-symbols-outlined text-xs">check_circle</span>${inCartQty}</span>` : ''}
        </div>

        {{-- Image with hover zoom --}}
        <div class="relative h-40 md:h-48 overflow-hidden bg-surface-container">
          ${imgHtml}
        </div>

        {{-- Body --}}
        <div class="p-4 md:p-5 flex flex-col flex-1">
          <h3 class="font-bold text-base md:text-lg leading-tight mb-1 line-clamp-2 group-hover:text-primary transition-colors">${p.name}</h3>
          ${packStr ? `<div class="text-xs text-on-surface-variant mb-2 flex items-center gap-1"><span class="material-symbols-outlined text-sm">scale</span>${packStr}</div>` : '<div class="mb-2"></div>'}

          {{-- Nutrition tags (kalau ada).
               Max 2 badge di card + tombol "Lihat Detail Nutrisi" full-width
               kalau ada >2 nutrisi atau ada detail manfaat. --}}
          ${(p.nutrition_info && p.nutrition_info.length) ? `
            <div class="flex flex-wrap items-center gap-1 mb-2">
              ${p.nutrition_info.slice(0, 2).map(n => `
                <span class="inline-flex items-center gap-1 text-[10px] font-semibold px-2 py-0.5 bg-secondary-container text-on-secondary-container rounded-full">
                  ${n.icon ? `<span class="material-symbols-outlined" style="font-size:12px;">${n.icon}</span>` : ''}
                  ${n.label}
                </span>
              `).join('')}
              ${p.nutrition_info.length > 2 ? `<span class="text-[10px] font-semibold text-on-surface-variant">+${p.nutrition_info.length - 2}</span>` : ''}
            </div>
            ${(p.nutrition_info.length > 0) ? `
              <button type="button" onclick='openNutritionModal(${JSON.stringify(p)})'
                class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-primary hover:text-primary-container hover:underline mb-3 transition-colors">
                <span class="material-symbols-outlined" style="font-size:14px;">info</span>
                Lihat Detail Nutrisi
              </button>
            ` : '<div class="mb-3"></div>'}
          ` : '<div class="mb-3"></div>'}

          {{-- Price + action.
               Mobile: stack vertikal (harga full-width di atas, stepper/button di bawah-kanan).
               Desktop (md+): sejajar horizontal supaya hemat tinggi. --}}
          <div class="mt-auto pt-3 border-t border-outline-variant/30">
            <div class="text-[10px] text-on-surface-variant font-bold uppercase tracking-wider mb-0.5">Per ${p.uom}</div>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2 md:gap-3">
              <div class="text-lg md:text-xl font-bold text-primary leading-tight whitespace-nowrap">${fmtRp(p.price)}</div>
              <div class="w-full md:w-auto">${actionHtml}</div>
            </div>
          </div>
        </div>
      </div>
    `;
  }).join('');
}

function renderCategoryChips() {
  // Filter pakai parent_cat (kategori induk), bukan sub-kategori
  const cats = ['all', ...new Set(ALL_PRODUCTS.map(p => p.parent_cat).filter(Boolean))].sort((a, b) => a === 'all' ? -1 : b === 'all' ? 1 : a.localeCompare(b));
  // Count produk per kategori
  const countByCat = {};
  ALL_PRODUCTS.forEach(p => { countByCat[p.parent_cat] = (countByCat[p.parent_cat] || 0) + 1; });
  countByCat.all = ALL_PRODUCTS.length;

  const chips = document.getElementById('cat-chips');
  chips.innerHTML = cats.map(c => {
    const active = c === activeCategory;
    const count = countByCat[c] || 0;
    return `
      <button onclick="filterCategory('${c.replace(/'/g, "\\'")}')"
        class="cat-chip group flex items-center gap-2 px-4 py-2.5 rounded-full text-sm font-semibold whitespace-nowrap transition-all ${active ? 'bg-primary text-white shadow-md shadow-primary/30' : 'bg-surface-container-low text-on-surface-variant hover:bg-primary-fixed hover:text-on-primary-fixed border border-outline-variant/40'}">
        <span>${c === 'all' ? 'Semua' : c}</span>
        <span class="text-xs font-bold px-2 py-0.5 rounded-full ${active ? 'bg-white/20' : 'bg-surface-container'}">${count}</span>
      </button>
    `;
  }).join('');
}

function getFilteredList() {
  let list = ALL_PRODUCTS;
  if (activeCategory !== 'all') list = list.filter(p => p.parent_cat === activeCategory);
  if (searchTerm) {
    const q = searchTerm.toLowerCase();
    list = list.filter(p =>
      p.name.toLowerCase().includes(q) ||
      p.sku.toLowerCase().includes(q) ||
      (p.category || '').toLowerCase().includes(q)
    );
  }
  return list;
}

function filterCategory(c) {
  activeCategory = c;
  renderCategoryChips();
  renderProducts(getFilteredList());
}

function applySearch(v) {
  searchTerm = (v || '').trim();
  document.getElementById('search-clear').classList.toggle('hidden', ! searchTerm);
  renderProducts(getFilteredList());
}
function clearSearch() {
  document.getElementById('search-input').value = '';
  applySearch('');
}

// Show skeletons initially
function showSkeletons() {
  const tpl = document.getElementById('skeleton-tpl');
  const grid = document.getElementById('product-grid');
  grid.innerHTML = '';
  for (let i = 0; i < 8; i++) grid.appendChild(tpl.content.cloneNode(true));
}

// ===== INIT =====
async function loadProducts() {
  showSkeletons();
  try {
    const res = await fetch(PRODUCTS_URL);
    const data = await res.json();
    ALL_PRODUCTS = data.products || [];
    if (data.admin_wa) ADMIN_WA = data.admin_wa;
    renderCategoryChips();
    renderProducts(ALL_PRODUCTS);
  } catch (e) {
    document.getElementById('product-grid').innerHTML = '<div class="col-span-full text-center py-12 text-error">Gagal memuat produk. Refresh halaman.</div>';
  }
}

document.addEventListener('DOMContentLoaded', () => {
  updateCartUI();
  loadProducts();
  // Search input handler dengan debounce ringan
  let searchTimer;
  document.getElementById('search-input').addEventListener('input', (e) => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => applySearch(e.target.value), 150);
  });
});
</script>

</body>
</html>
