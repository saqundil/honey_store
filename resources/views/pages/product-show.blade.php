@extends('layouts.app')

@section('title', $product['name'].' | '.__('home.meta.title'))

@section('content')
    @php
        $gallery = $product['gallery'] ?? [['src' => $product['image'], 'alt' => $product['name']]];
        $fallbackGallery = [
            ['src' => 'images/product-figma/thumb-1.png', 'alt' => $product['name'].' 1'],
            ['src' => 'images/product-figma/thumb-2.png', 'alt' => $product['name'].' 2'],
            ['src' => 'images/product-figma/thumb-3.png', 'alt' => $product['name'].' 3'],
            ['src' => 'images/product-figma/thumb-4.png', 'alt' => $product['name'].' 4'],
            ['src' => 'images/product-figma/thumb-5.png', 'alt' => $product['name'].' 5'],
        ];
        $displayGallery = collect($gallery)
            ->filter(fn (array $item) => filled($item['src'] ?? null))
            ->unique('src')
            ->values();

        if ($displayGallery->isEmpty()) {
            $displayGallery = collect($fallbackGallery);
        }

        $displayGallery = $displayGallery
            ->take(5)
            ->values()
            ->all();
        $relatedProducts = collect($relatedProducts ?? [])->values();
        $relatedVisuals = [
            'images/product-figma/related-1.png',
            'images/product-figma/related-2.png',
            'images/product-figma/related-3.png',
            'images/product-figma/related-4.png',
        ];
        $startingQuantity = max(1, (int) old('quantity', 1));
        $unitPrice = (float) ($product['price_value'] ?? preg_replace('/[^\d.]/', '', $product['price']));
        $priceCurrency = $product['currency'] ?? '$';
        $priceCurrencyPosition = $product['currency_position'] ?? 'prefix';
        $priceDecimals = (int) ($product['price_decimals'] ?? 2);
        $formatPrice = static function (float $value) use ($priceCurrency, $priceCurrencyPosition, $priceDecimals): string {
            $formattedValue = number_format($value, $priceDecimals);

            return $priceCurrencyPosition === 'suffix'
                ? $formattedValue.' '.$priceCurrency
                : $priceCurrency.$formattedValue;
        };
        $compareAtRaw = $product['compare_at_price_value'] ?? null;

        if ($compareAtRaw === null && filled($product['compare_at_price'] ?? null)) {
            $compareAtRaw = preg_replace('/[^\d.]/', '', $product['compare_at_price']);
        }

        $compareAtPrice = filled($compareAtRaw) ? (float) $compareAtRaw : null;
        $formattedUnitPrice = $formatPrice($unitPrice);
        $formattedTotal = $formatPrice($unitPrice * $startingQuantity);
        $formattedCompareAtPrice = $compareAtPrice && $compareAtPrice > $unitPrice ? $formatPrice($compareAtPrice) : null;
        $category = $product['category'] ?? __('home.product_page.default_category');
        $heroFacts = collect([
            $category,
            $product['origin'] ?? null,
            $product['size'] ?? null,
        ])->filter()->unique()->take(3)->values();
        $titleBackground = asset('images/product-figma/title-bg.png');
    @endphp

    <section class="figma-product-title">
        <img src="{{ $titleBackground }}"
             alt=""
             aria-hidden="true"
             class="pointer-events-none absolute inset-0 h-full w-full object-cover object-center opacity-20 select-none"
             decoding="async"
             fetchpriority="high">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(246,193,90,0.14),transparent_28%),linear-gradient(180deg,rgba(251,247,241,0.92),rgba(251,247,241,0.98))]"></div>
        <div class="figma-product-title__inner mx-auto max-w-[1130px] px-6">
            <div class="relative z-10">
                <p class="figma-product-title__eyebrow">{{ __('home.product_page.eyebrow') }}</p>
                <h1 class="figma-product-title__heading">{{ $product['name'] }}</h1>
                <p class="figma-product-title__summary">{{ $product['excerpt'] }}</p>

                <div class="figma-product-title__meta">
                    <div class="figma-product-title__price-stack">
                        <div class="figma-product-title__price-row">
                            <span class="figma-product-title__price">{{ $product['price'] }}</span>
                            @if ($formattedCompareAtPrice)
                                <span class="figma-product-title__price-compare">{{ $formattedCompareAtPrice }}</span>
                            @endif
                        </div>
                        @if ($formattedCompareAtPrice)
                            <span class="figma-product-title__offer">{{ __('home.product_page.opening_offer') }}</span>
                        @endif
                        @if ($formattedCompareAtPrice)
                            <p class="figma-product-title__price-note">
                                {{ __('home.product_page.opening_offer_note') }}
                            </p>
                        @endif
                    </div>
                    @if (!empty($product['badge']))
                        <span class="figma-product-title__badge">{{ $product['badge'] }}</span>
                    @endif
                </div>

                @if ($heroFacts->isNotEmpty())
                    <div class="figma-product-title__facts">
                        @foreach ($heroFacts as $fact)
                            <span class="figma-product-title__fact">{{ $fact }}</span>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="figma-product-title__visual">
                <div class="figma-product-title__visual-card">
                    <img src="{{ asset($displayGallery[0]['src']) }}"
                         alt="{{ $displayGallery[0]['alt'] }}"
                         width="700"
                         height="700"
                         decoding="async"
                         fetchpriority="high"
                         class="h-full w-full object-cover">
                </div>
            </div>
        </div>
    </section>

    <section class="figma-product-page"
             data-product-page
             data-unit-price="{{ $unitPrice }}"
             data-price-currency="{{ $priceCurrency }}"
             data-price-currency-position="{{ $priceCurrencyPosition }}"
             data-price-decimals="{{ $priceDecimals }}"
             data-product-name="{{ $product['name'] }}"
             data-whatsapp-phone="{{ __('home.product_page.whatsapp_phone') }}"
             data-whatsapp-intro="{{ __('home.product_page.whatsapp_message_intro') }}"
             data-whatsapp-name-label="{{ __('home.product_page.whatsapp_message_name') }}"
             data-whatsapp-phone-label="{{ __('home.product_page.whatsapp_message_phone') }}"
             data-whatsapp-quantity-label="{{ __('home.product_page.whatsapp_message_quantity') }}"
             data-whatsapp-notes-label="{{ __('home.product_page.whatsapp_message_notes') }}">
        <div class="mx-auto max-w-[1130px] px-6 py-14 lg:py-20">
            <div class="mb-8 flex flex-wrap items-center gap-3 text-sm text-honey-muted md:mb-10">
                <a href="{{ route('home') }}" class="transition-colors hover:text-honey-orange">{{ __('home.nav.home') }}</a>
                <span>/</span>
                <a href="{{ route('home') }}#products" class="transition-colors hover:text-honey-orange">{{ __('home.products.heading') }}</a>
                <span>/</span>
                <span class="text-honey-dark">{{ $product['name'] }}</span>
            </div>

            @if (session('order_success'))
                <div class="mb-8 rounded-3xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-emerald-800 shadow-[0_18px_45px_rgba(16,185,129,0.08)] md:mb-10">
                    {{ session('order_success') }}
                </div>
            @endif

            <div class="figma-product-grid">
                <div>
                    <div class="figma-gallery-shell">
                        <div class="figma-gallery-strip">
                            @foreach ($displayGallery as $index => $item)
                                <button type="button"
                                        class="figma-gallery-thumb {{ $index === 0 ? 'is-active' : '' }}"
                                        data-product-thumbnail
                                        data-image-src="{{ asset($item['src']) }}"
                                        data-image-alt="{{ $item['alt'] }}"
                                        aria-label="{{ __('home.product_page.eyebrow') }} {{ $index + 1 }}"
                                        aria-pressed="{{ $index === 0 ? 'true' : 'false' }}">
                                    <img src="{{ asset($item['src']) }}"
                                         alt="{{ $item['alt'] }}"
                                         loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
                                         decoding="async"
                                         width="160"
                                         height="160"
                                         class="h-full w-full object-cover">
                                </button>
                            @endforeach
                        </div>

                        <div class="figma-gallery-frame">
                            <img src="{{ asset($displayGallery[0]['src']) }}"
                                 alt="{{ $displayGallery[0]['alt'] }}"
                                 width="760"
                                 height="760"
                                 decoding="async"
                                 fetchpriority="high"
                                 sizes="(max-width: 1023px) 100vw, 60vw"
                                 class="h-full w-full object-cover"
                                 data-product-main-image>

                            @if (!empty($product['badge']))
                                <span class="figma-status-chip">{{ $product['badge'] }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="figma-tab-shell mt-10" data-product-tabs>
                        <div class="figma-tab-list" role="tablist">
                            <button type="button" class="figma-tab-button is-active" data-tab-trigger="description" role="tab" aria-selected="true">{{ __('home.product_page.tabs.description') }}</button>
                            <button type="button" class="figma-tab-button" data-tab-trigger="additional" role="tab" aria-selected="false">{{ __('home.product_page.tabs.additional_information') }}</button>
                            <button type="button" class="figma-tab-button" data-tab-trigger="reviews" role="tab" aria-selected="false">{{ __('home.product_page.tabs.reviews') }}</button>
                        </div>

                        <div class="figma-tab-panel" data-tab-panel="description">
                            <p class="figma-copy">{{ $product['description'] }}</p>

                            <div class="figma-highlight-grid mt-8">
                                @foreach ($product['highlights'] as $index => $highlight)
                                    <article class="figma-highlight-card">
                                        <span class="figma-highlight-card__index">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                                        <p class="figma-highlight-card__text">{{ $highlight }}</p>
                                    </article>
                                @endforeach
                            </div>
                        </div>

                        <div class="figma-tab-panel hidden" data-tab-panel="additional">
                            <div class="grid gap-4 md:grid-cols-3">
                                <article class="figma-fact-card">
                                    <p class="figma-fact-card__label">{{ __('home.product_page.facts.origin') }}</p>
                                    <p class="figma-fact-card__value">{{ $product['origin'] }}</p>
                                </article>
                                <article class="figma-fact-card">
                                    <p class="figma-fact-card__label">{{ __('home.product_page.facts.texture') }}</p>
                                    <p class="figma-fact-card__value">{{ $product['texture'] }}</p>
                                </article>
                                <article class="figma-fact-card">
                                    <p class="figma-fact-card__label">{{ __('home.product_page.facts.size') }}</p>
                                    <p class="figma-fact-card__value">{{ $product['size'] }}</p>
                                </article>
                            </div>

                            <div class="mt-6 grid gap-3">
                                @foreach (__('home.product_page.assurance_items') as $item)
                                    <div class="figma-note-card">{{ $item }}</div>
                                @endforeach
                            </div>
                        </div>

                        <div class="figma-tab-panel hidden" data-tab-panel="reviews">
                            <div class="figma-review-card">
                                <p class="figma-review-card__quote">“{{ __('home.testimonial.quote') }}”</p>
                                <div class="mt-6 flex items-center justify-between gap-4 border-t border-black/10 pt-5">
                                    <div>
                                        <p class="font-condensed text-2xl font-bold uppercase text-honey-dark">{{ __('home.testimonial.name') }}</p>
                                        <p class="text-sm uppercase tracking-[0.22em] text-honey-orange">{{ __('home.testimonial.role') }}</p>
                                    </div>
                                    <img src="{{ asset('images/product-figma/footer-icon.png') }}"
                                         alt="{{ __('home.testimonial.avatar_alt') }}"
                                         loading="lazy"
                                         decoding="async"
                                         width="40"
                                         height="40"
                                         class="h-10 w-10 object-contain opacity-75">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <aside class="figma-summary-shell">
                    <div class="figma-summary-card figma-order-sheet">
                        <div class="figma-order-sheet__masthead">
                            <h2 class="figma-order-sheet__title">{{ $product['name'] }}</h2>
                            <p class="figma-order-sheet__summary">{{ $product['excerpt'] }}</p>

                            <div class="figma-order-sheet__chips">
                                <span class="figma-order-sheet__chip">{{ $product['size'] }}</span>
                                <span class="figma-order-sheet__chip">{{ $category }}</span>
                                @if (!empty($product['badge']))
                                    <span class="figma-order-sheet__chip figma-order-sheet__chip--accent">{{ $product['badge'] }}</span>
                                @endif
                            </div>

                            <div class="figma-order-sheet__price">
                                <span class="figma-order-sheet__price-label">{{ $formattedCompareAtPrice ? __('home.product_page.opening_offer') : __('home.product_page.starting_from') }}</span>
                                <div class="figma-order-sheet__price-line">
                                    <strong class="figma-order-sheet__price-value">{{ $product['price'] }}</strong>
                                    @if ($formattedCompareAtPrice)
                                        <span class="figma-order-sheet__price-compare">{{ $formattedCompareAtPrice }}</span>
                                    @endif
                                </div>
                                @if ($formattedCompareAtPrice)
                                    <p class="figma-order-sheet__price-note">
                                        {{ __('home.product_page.opening_offer_note') }}
                                    </p>
                                @endif
                            </div>
                        </div>

                        <form action="{{ route('products.order', ['slug' => $product['slug']]) }}" method="POST" class="mt-7 space-y-6 product-form-grid figma-order-sheet__form">
                            @csrf

                            <section class="figma-order-step">
                                <div class="figma-order-step__panel">
                                    <div class="figma-order-step__header">
                                        <p class="figma-order-step__title">{{ app()->isLocale('ar') ? 'اختيار الكمية' : 'Choose quantity' }}</p>
                                        <p class="figma-order-step__hint">{{ app()->isLocale('ar') ? 'اختر عدد العبوات ثم أضفها مباشرة إلى الطلب.' : 'Pick the number of jars, then add them straight into the order.' }}</p>
                                    </div>

                                    <div class="figma-order-builder">
                                        <div class="figma-quantity-box">
                                            <label for="quantity" class="figma-quantity-box__label">{{ __('home.product_page.form.quantity') }}</label>
                                            <div class="figma-quantity-box__controls">
                                                <button type="button" class="figma-quantity-box__button" data-quantity-action="decrease" aria-label="{{ __('home.product_page.stepper_decrease') }}">-</button>
                                                <input id="quantity" name="quantity" type="number" min="1" max="100" value="{{ $startingQuantity }}" class="figma-quantity-box__input" data-quantity-input required>
                                                <button type="button" class="figma-quantity-box__button" data-quantity-action="increase" aria-label="{{ __('home.product_page.stepper_increase') }}">+</button>
                                            </div>
                                        </div>
                                    </div>

                                    <button type="submit" class="figma-cart-button figma-cart-button--wide">{{ __('home.products.add_to_cart') }}</button>
                                </div>
                            </section>
                            @error('quantity')<p class="text-sm text-red-600">{{ $message }}</p>@enderror

                            <section class="figma-order-step figma-order-step--receipt">
                                <div class="figma-order-step__panel">
                                    <div class="figma-order-step__header">
                                        <p class="figma-order-step__title">{{ __('home.product_page.summary_heading') }}</p>
                                        <p class="figma-order-step__hint">{{ app()->isLocale('ar') ? 'ملخص مباشر يتغيّر مع كل تعديل على الكمية.' : 'A live summary that updates whenever the quantity changes.' }}</p>
                                    </div>

                                    <div class="figma-order-ledger">
                                        <div class="figma-order-ledger__row">
                                            <span>{{ __('home.product_page.summary_unit_price') }}</span>
                                            <strong data-summary-unit>{{ $formattedUnitPrice }}</strong>
                                        </div>
                                        <div class="figma-order-ledger__row">
                                            <span>{{ __('home.product_page.summary_quantity') }}</span>
                                            <strong data-summary-quantity aria-live="polite">{{ $startingQuantity }}</strong>
                                        </div>
                                        <div class="figma-order-ledger__row figma-order-ledger__row--total">
                                            <span>{{ __('home.product_page.summary_total') }}</span>
                                            <strong data-summary-total aria-live="polite">{{ $formattedTotal }}</strong>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <section class="figma-order-step">
                                <div class="figma-order-step__panel">
                                    <div class="figma-order-step__header">
                                        <p class="figma-form-heading">{{ __('home.product_page.request_details_heading') }}</p>
                                        <p class="figma-form-section-copy">{{ __('home.product_page.request_details_text') }}</p>
                                    </div>

                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <div>
                                            <label for="customer_name" class="figma-form-label">{{ __('home.product_page.form.name') }}</label>
                                            <input id="customer_name" name="customer_name" type="text" value="{{ old('customer_name') }}" autocomplete="name" class="figma-form-input" data-customer-name required>
                                            @error('customer_name')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                                        </div>
                                        <div>
                                            <label for="phone" class="figma-form-label">{{ __('home.product_page.form.phone') }}</label>
                                            <input id="phone" name="phone" type="text" value="{{ old('phone') }}" autocomplete="tel" class="figma-form-input" data-customer-phone required>
                                            @error('phone')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                                        </div>
                                    </div>

                                    <div class="mt-4">
                                        <label for="email" class="figma-form-label">{{ __('home.product_page.form.email') }}</label>
                                        <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" class="figma-form-input" required>
                                        @error('email')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                                    </div>

                                    <div class="mt-4">
                                        <label for="notes" class="figma-form-label">{{ __('home.product_page.form.notes') }}</label>
                                        <textarea id="notes" name="notes" rows="4" class="figma-form-textarea" data-order-notes>{{ old('notes') }}</textarea>
                                        @error('notes')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                                    </div>
                                </div>
                            </section>

                            <div class="figma-order-sheet__foot">
                                <div class="figma-order-sheet__actions">
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <button type="submit" class="figma-submit-button">{{ __('home.product_page.form.submit') }}</button>
                                        <a href="https://api.whatsapp.com/send?text=" target="_blank" rel="noopener" class="figma-whatsapp-button" data-whatsapp-link>{{ __('home.product_page.whatsapp_button') }}</a>
                                    </div>
                                </div>

                                <div class="figma-order-sheet__support">
                                    <p class="text-sm leading-7 text-honey-muted">{{ __('home.product_page.whatsapp_hint') }}</p>
                                    <p class="mt-2 text-sm leading-7 text-honey-muted">{{ __('home.product_page.form.disclaimer') }}</p>
                                </div>
                            </div>
                        </form>
                    </div>
                </aside>
            </div>
        </div>
    </section>

    @if ($relatedProducts->isNotEmpty())
        <section class="figma-related-section">
            <div class="mx-auto max-w-[1130px] px-6 py-16 lg:py-20">
                <div class="mx-auto max-w-3xl text-center">
                    <span class="home-eyebrow mx-auto">{{ app()->isLocale('ar') ? 'اقتراحات قريبة' : 'Selected suggestions' }}</span>
                    <h2 class="home-title mx-auto mt-3 max-w-[10ch]">{{ app()->isLocale('ar') ? 'قد يعجبك أيضًا' : 'You May Also Like' }}</h2>
                    <p class="home-copy mx-auto mt-4">{{ __('home.product_page.related_description') }}</p>
                </div>

                <div class="mt-12 grid gap-8 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach ($relatedProducts as $index => $relatedProduct)
                        <article class="figma-related-card">
                            <div class="figma-related-card__media">
                                <img src="{{ asset($relatedVisuals[$index] ?? $relatedProduct['image']) }}"
                                     alt="{{ $relatedProduct['name'] }}"
                                       loading="lazy"
                                       decoding="async"
                                       width="420"
                                       height="420"
                                       sizes="(max-width: 639px) 100vw, (max-width: 1279px) 50vw, 25vw"
                                     class="h-full w-full object-cover">

                                @if (!empty($relatedProduct['badge']))
                                    <span class="figma-status-chip figma-status-chip--small">{{ $relatedProduct['badge'] }}</span>
                                @endif
                            </div>
                            <div class="figma-related-card__content">
                                <h3>{{ $relatedProduct['name'] }}</h3>
                                <p>{{ $relatedProduct['price'] }}</p>
                                <a href="{{ route('products.show', ['slug' => $relatedProduct['slug']]) }}">{{ __('home.products.view_and_order') }}</a>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const page = document.querySelector('[data-product-page]');

            if (!page) {
                return;
            }

            const unitPrice = Number(page.dataset.unitPrice || 0);
            const priceCurrency = page.dataset.priceCurrency || '$';
            const priceCurrencyPosition = page.dataset.priceCurrencyPosition || 'prefix';
            const priceDecimals = Number(page.dataset.priceDecimals || 2);
            const productName = page.dataset.productName || '';
            const normalizeWhatsAppPhone = (value) => value
                .replace(/[^\d+]/g, '')
                .replace(/^\+/, '')
                .replace(/^00/, '');

            const whatsappPhone = normalizeWhatsAppPhone(page.dataset.whatsappPhone || '');
            const quantityInput = page.querySelector('[data-quantity-input]');
            const nameInput = page.querySelector('[data-customer-name]');
            const phoneInput = page.querySelector('[data-customer-phone]');
            const notesInput = page.querySelector('[data-order-notes]');
            const summaryQuantity = page.querySelector('[data-summary-quantity]');
            const summaryTotal = page.querySelector('[data-summary-total]');
            const whatsappLink = page.querySelector('[data-whatsapp-link]');
            const mainImage = page.querySelector('[data-product-main-image]');
            const thumbnails = page.querySelectorAll('[data-product-thumbnail]');
            const tabsRoot = document.querySelector('[data-product-tabs]');

            const formatMoney = (value) => {
                const formattedValue = value.toFixed(priceDecimals);

                return priceCurrencyPosition === 'suffix'
                    ? `${formattedValue} ${priceCurrency}`
                    : `${priceCurrency}${formattedValue}`;
            };

            const sanitizeQuantity = () => {
                const raw = Number(quantityInput?.value || 1);
                const next = Math.max(1, Math.min(100, Number.isFinite(raw) ? raw : 1));

                if (quantityInput) {
                    quantityInput.value = next;
                }

                return next;
            };

            const updateSummary = () => {
                const quantity = sanitizeQuantity();

                if (summaryQuantity) {
                    summaryQuantity.textContent = String(quantity);
                }

                if (summaryTotal) {
                    summaryTotal.textContent = formatMoney(unitPrice * quantity);
                }
            };

            const updateWhatsAppLink = () => {
                if (!whatsappLink) {
                    return;
                }

                const quantity = sanitizeQuantity();
                const intro = page.dataset.whatsappIntro || 'Hello, I would like to order';
                const nameLabel = page.dataset.whatsappNameLabel || 'Name';
                const phoneLabel = page.dataset.whatsappPhoneLabel || 'Phone';
                const quantityLabel = page.dataset.whatsappQuantityLabel || 'Quantity';
                const notesLabel = page.dataset.whatsappNotesLabel || 'Notes';
                const message = [
                    `${intro} ${productName}`,
                    `${quantityLabel}: ${quantity}`,
                    `${nameLabel}: ${nameInput?.value || '-'}`,
                    `${phoneLabel}: ${phoneInput?.value || '-'}`,
                    `${notesLabel}: ${notesInput?.value || '-'}`,
                ].join('\n');

                const whatsappBase = whatsappPhone
                    ? `https://api.whatsapp.com/send?phone=${whatsappPhone}&text=`
                    : 'https://api.whatsapp.com/send?text=';

                whatsappLink.href = `${whatsappBase}${encodeURIComponent(message)}`;
            };

            page.querySelectorAll('[data-quantity-action]').forEach((button) => {
                button.addEventListener('click', () => {
                    const current = sanitizeQuantity();
                    const delta = button.dataset.quantityAction === 'increase' ? 1 : -1;

                    if (quantityInput) {
                        quantityInput.value = Math.max(1, Math.min(100, current + delta));
                    }

                    updateSummary();
                    updateWhatsAppLink();
                });
            });

            [quantityInput, nameInput, phoneInput, notesInput].forEach((field) => {
                if (!field) {
                    return;
                }

                field.addEventListener('input', () => {
                    updateSummary();
                    updateWhatsAppLink();
                });
            });

            thumbnails.forEach((thumbnail) => {
                thumbnail.addEventListener('click', () => {
                    if (!mainImage) {
                        return;
                    }

                    mainImage.src = thumbnail.dataset.imageSrc || mainImage.src;
                    mainImage.alt = thumbnail.dataset.imageAlt || mainImage.alt;

                    thumbnails.forEach((item) => {
                        item.classList.remove('is-active');
                        item.setAttribute('aria-pressed', 'false');
                    });

                    thumbnail.classList.add('is-active');
                    thumbnail.setAttribute('aria-pressed', 'true');
                });
            });

            if (tabsRoot) {
                const triggers = tabsRoot.querySelectorAll('[data-tab-trigger]');
                const panels = tabsRoot.querySelectorAll('[data-tab-panel]');

                triggers.forEach((trigger) => {
                    trigger.addEventListener('click', () => {
                        const target = trigger.dataset.tabTrigger;

                        triggers.forEach((item) => {
                            item.classList.remove('is-active');
                            item.setAttribute('aria-selected', item === trigger ? 'true' : 'false');
                        });

                        panels.forEach((panel) => {
                            panel.classList.toggle('hidden', panel.dataset.tabPanel !== target);
                        });

                        trigger.classList.add('is-active');
                    });
                });
            }

            updateSummary();
            updateWhatsAppLink();
        });
    </script>
@endsection