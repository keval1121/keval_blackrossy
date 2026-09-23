const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content;

const toast = (message, type = 'ok') => {
    const wrap = document.getElementById('toast-wrap');
    if (!wrap || !message) return;
    const el = document.createElement('div');
    el.className = `mb-2 rounded-full px-4 py-3 text-sm font-medium shadow-lg ${type === 'error' ? 'bg-rose-700 text-white' : 'bg-stone-900 text-white'}`;
    el.textContent = message;
    wrap.appendChild(el);
    setTimeout(() => el.remove(), 2800);
};

const jsonFetch = async (url, options = {}) => {
    const response = await fetch(url, {
        headers: {
            'X-CSRF-TOKEN': csrf(),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            ...(options.body instanceof FormData ? {} : { 'Content-Type': 'application/json' }),
        },
        ...options,
    });
    const data = await response.json().catch(() => ({}));
    if (!response.ok) {
        throw new Error(data.message || 'Something went wrong. Please try again.');
    }
    return data;
};

const setCartCount = (count) => {
    document.querySelectorAll('[data-cart-count]').forEach((el) => {
        el.textContent = count;
        el.classList.toggle('hidden', Number(count) === 0);
    });
};

const setBusy = (el, busy) => {
    if (!el) return;
    el.disabled = busy;
    el.dataset.original = el.dataset.original || el.innerHTML;
    el.innerHTML = busy ? 'Please wait…' : el.dataset.original;
};

document.addEventListener('submit', async (event) => {
    const form = event.target.closest('[data-ajax-cart]');
    if (!form) return;
    event.preventDefault();
    const button = form.querySelector('[type="submit"]');
    setBusy(button, true);
    try {
        const data = await jsonFetch(form.action, { method: 'POST', body: new FormData(form) });
        if (data.redirect) {
            window.location = data.redirect;
            return;
        }
        if (typeof data.count !== 'undefined') setCartCount(data.count);
        const box = document.getElementById('cart-contents');
        if (box && data.html) box.innerHTML = data.html;
        toast(data.message || 'Updated');
    } catch (error) {
        toast(error.message, 'error');
    } finally {
        setBusy(button, false);
    }
});

document.addEventListener('click', (event) => {
    const toggle = event.target.closest('[data-toggle]');
    if (toggle) {
        document.getElementById(toggle.dataset.toggle)?.classList.toggle('hidden');
    }
});

const searchInput = document.getElementById('live-search');
const searchBox = document.getElementById('search-suggest');
let searchTimer;
if (searchInput && searchBox) {
    searchInput.addEventListener('input', () => {
        clearTimeout(searchTimer);
        const q = searchInput.value.trim();
        if (q.length < 2) {
            searchBox.classList.add('hidden');
            searchBox.innerHTML = '';
            return;
        }
        searchTimer = setTimeout(async () => {
            const data = await jsonFetch(`/search?q=${encodeURIComponent(q)}&suggest=1`);
            const products = (data.products || []).map((item) => `
                <a class="flex gap-3 px-3 py-2 hover:bg-stone-50" href="${item.url}">
                    <img src="${item.image}" alt="" class="h-12 w-12 rounded-lg object-cover" width="48" height="48">
                    <span><strong class="block text-sm">${item.name}</strong><span class="text-gold text-sm">${item.price}</span></span>
                </a>`).join('');
            const cats = (data.categories || []).map((item) => `<a class="block px-3 py-2 text-sm text-stone-500" href="${item.url}">${item.name}</a>`).join('');
            searchBox.innerHTML = products || cats ? products + cats : '<p class="p-4 text-sm text-stone-500">No matches found</p>';
            searchBox.classList.remove('hidden');
        }, 180);
    });
}

const filterForm = document.getElementById('filter-form');
const productGrid = document.getElementById('product-grid');
const filterPanel = document.getElementById('filter-panel');
const filterBackdrop = document.getElementById('filter-backdrop');
const openFiltersBtn = document.getElementById('open-filters');
const closeFiltersBtn = document.getElementById('close-filters');

const closeFilters = () => {
    filterPanel?.classList.remove('is-open');
    filterBackdrop?.classList.remove('is-open');
    filterPanel?.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('filter-open');
};

const openFilters = () => {
    filterPanel?.classList.add('is-open');
    filterBackdrop?.classList.add('is-open');
    filterPanel?.setAttribute('aria-hidden', 'false');
    document.body.classList.add('filter-open');
};

openFiltersBtn?.addEventListener('click', openFilters);
closeFiltersBtn?.addEventListener('click', closeFilters);
filterBackdrop?.addEventListener('click', closeFilters);

filterForm?.querySelectorAll('.filter-chip.has-check input').forEach((input) => {
    input.addEventListener('change', () => {
        input.closest('.filter-chip')?.classList.toggle('is-active', input.checked);
    });
});

if (filterForm && productGrid) {
    const applyFilters = async () => {
        const params = new URLSearchParams(new FormData(filterForm));
        // Keep mobile sort if desktop sort field is hidden / empty conflict
        const url = `${window.location.pathname}?${params.toString()}`;
        productGrid.classList.add('opacity-50');
        try {
            const data = await jsonFetch(url, { method: 'GET' });
            productGrid.innerHTML = data.html;
            history.pushState({}, '', url);
            const count = document.getElementById('result-count');
            if (count && data.count !== undefined) count.textContent = `${data.count} items`;
        } catch (error) {
            toast(error.message, 'error');
        } finally {
            productGrid.classList.remove('opacity-50');
            if (window.matchMedia('(max-width: 767px)').matches) {
                closeFilters();
            }
        }
    };

    filterForm.addEventListener('submit', (event) => {
        event.preventDefault();
        applyFilters();
    });

    // Desktop: apply on change for quick filtering
    filterForm.addEventListener('change', (event) => {
        if (window.matchMedia('(min-width: 768px)').matches) {
            // Don't auto-submit category chip links
            if (event.target.closest('a')) return;
            applyFilters();
        }
    });
}

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') closeFilters();
});

const heroSlider = document.querySelector('[data-hero-slider]');
if (heroSlider) {
    const track = heroSlider.querySelector('[data-hero-track]');
    const slides = [...heroSlider.querySelectorAll('[data-hero-slide]')];
    const dots = [...heroSlider.querySelectorAll('[data-hero-dot]')];
    const prev = heroSlider.querySelector('[data-hero-prev]');
    const next = heroSlider.querySelector('[data-hero-next]');
    let index = 0;
    let timer = null;

    const goTo = (nextIndex) => {
        if (!slides.length || !track) {
            return;
        }

        // Keep page scroll stable: focused links inside a moving slide can force the browser to jump up.
        const scrollY = window.scrollY;
        const scrollX = window.scrollX;
        const focused = document.activeElement;
        if (focused instanceof HTMLElement && heroSlider.contains(focused)) {
            focused.blur();
        }

        index = (nextIndex + slides.length) % slides.length;
        track.style.transform = `translate3d(-${index * 100}%, 0, 0)`;

        slides.forEach((slide, i) => {
            const isActive = i === index;
            slide.setAttribute('aria-hidden', isActive ? 'false' : 'true');
            if ('inert' in slide) {
                slide.inert = !isActive;
            }
            slide.querySelectorAll('a, button').forEach((el) => {
                if (isActive) {
                    el.removeAttribute('tabindex');
                } else {
                    el.setAttribute('tabindex', '-1');
                }
            });
        });

        dots.forEach((dot, i) => dot.classList.toggle('is-active', i === index));

        window.scrollTo(scrollX, scrollY);
        requestAnimationFrame(() => window.scrollTo(scrollX, scrollY));
    };

    const start = () => {
        stop();
        if (slides.length < 2) {
            return;
        }
        timer = setInterval(() => {
            if (document.hidden) {
                return;
            }
            goTo(index + 1);
        }, 5200);
    };

    const stop = () => {
        if (timer) {
            clearInterval(timer);
        }
        timer = null;
    };

    prev?.addEventListener('click', () => {
        goTo(index - 1);
        start();
    });
    next?.addEventListener('click', () => {
        goTo(index + 1);
        start();
    });
    dots.forEach((dot) => {
        dot.addEventListener('click', () => {
            goTo(Number(dot.dataset.heroDot || 0));
            start();
        });
    });

    // Initialize inactive slides without scrolling the page.
    goTo(0);
    heroSlider.addEventListener('mouseenter', stop);
    heroSlider.addEventListener('mouseleave', start);
    start();
}

window.BlackRossy = { toast, jsonFetch, setCartCount, csrf };
