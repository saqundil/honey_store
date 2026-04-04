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
            ->merge($fallbackGallery)
            ->unique('src')
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
        $formattedUnitPrice = $formatPrice($unitPrice);
        $formattedTotal = $formatPrice($unitPrice * $startingQuantity);
        $sku = $product['sku'] ?? '13';
        $category = $product['category'] ?? __('home.product_page.default_category');
        $tags = implode(', ', $product['tags'] ?? [__('home.product_page.default_tag')]);
        $shareUrl = urlencode(url()->current());
        $shareText = urlencode($product['name'].' | '.__('home.meta.title'));
        $titleBackground = asset('images/product-figma/title-bg.png');
    @endphp

    <section class="figma-product-title">
        <img src="{{ $titleBackground }}"
             alt=""
             aria-hidden="true"
             class="pointer-events-none absolute inset-0 h-full w-full object-contain object-center select-none"
             decoding="async"
             fetchpriority="high">
        <div class="absolute inset-0 bg-[linear-gradient(rgba(52,30,12,0.34),rgba(52,30,12,0.18))]"></div>
        <div class="figma-product-title__inner mx-auto max-w-[1130px] px-6">
            <div>
                <p class="figma-product-title__eyebrow">{{ __('home.product_page.eyebrow') }}</p>
                <h1 class="figma-product-title__heading">{{ __('home.product_page.banner_title') }}</h1>
            </div>
            <img src="{{ asset('images/product-figma/header-logo.png') }}"
                 alt="{{ __('home.brand.logo_alt') }}"
                 width="176"
                 height="176"
                 decoding="async"
                 fetchpriority="high"
                 class="hidden w-[9rem] opacity-90 lg:block xl:w-[11rem]">
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
                            <ul class="mt-6 space-y-4 text-honey-grey">
                                @foreach ($product['highlights'] as $highlight)
                                    <li class="flex items-start gap-3 leading-8">
                                        <span class="mt-3 inline-block h-2 w-2 rounded-full bg-honey-orange"></span>
                                        <span>{{ $highlight }}</span>
                                    </li>
                                @endforeach
                            </ul>
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
                    <div class="figma-summary-card">
                        <p class="figma-kicker">{{ __('home.product_page.quality_chip') }}</p>
                        <h2 class="figma-product-name">{{ $product['name'] }}</h2>
                        <p class="figma-product-price">{{ $product['price'] }}</p>
                        <p class="figma-copy mt-5">{{ $product['excerpt'] }}</p>

                        <form action="{{ route('products.order', ['slug' => $product['slug']]) }}" method="POST" class="mt-8 space-y-6 product-form-grid">
                            @csrf

                            <div class="figma-purchase-row">
                                <div class="figma-quantity-box">
                                    <label for="quantity" class="figma-quantity-box__label">{{ __('home.product_page.form.quantity') }}</label>
                                    <div class="figma-quantity-box__controls">
                                        <button type="button" class="figma-quantity-box__button" data-quantity-action="decrease" aria-label="{{ __('home.product_page.stepper_decrease') }}">-</button>
                                        <input id="quantity" name="quantity" type="number" min="1" max="100" value="{{ $startingQuantity }}" class="figma-quantity-box__input" data-quantity-input required>
                                        <button type="button" class="figma-quantity-box__button" data-quantity-action="increase" aria-label="{{ __('home.product_page.stepper_increase') }}">+</button>
                                    </div>
                                </div>

                                <button type="submit" class="figma-cart-button">{{ __('home.products.add_to_cart') }}</button>
                            </div>
                            @error('quantity')<p class="text-sm text-red-600">{{ $message }}</p>@enderror

                            <div class="figma-order-card">
                                <div class="figma-order-card__header">
                                    <h3>{{ __('home.product_page.summary_heading') }}</h3>
                                    <span>{{ __('home.product_page.starting_from') }} {{ $product['price'] }}</span>
                                </div>

                                <div class="mt-5 space-y-3">
                                    <div class="product-summary-row">
                                        <span>{{ __('home.product_page.summary_unit_price') }}</span>
                                        <strong data-summary-unit>{{ $formattedUnitPrice }}</strong>
                                    </div>
                                    <div class="product-summary-row">
                                        <span>{{ __('home.product_page.summary_quantity') }}</span>
                                        <strong data-summary-quantity aria-live="polite">{{ $startingQuantity }}</strong>
                                    </div>
                                    <div class="product-summary-row border-t border-black/10 pt-3">
                                        <span>{{ __('home.product_page.summary_total') }}</span>
                                        <strong class="text-honey-orange" data-summary-total aria-live="polite">{{ $formattedTotal }}</strong>
                                    </div>
                                </div>
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

                            <div>
                                <label for="email" class="figma-form-label">{{ __('home.product_page.form.email') }}</label>
                                <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" class="figma-form-input" required>
                                @error('email')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <p class="figma-form-heading">{{ __('home.product_page.request_details_heading') }}</p>
                                <p class="mt-2 text-sm leading-7 text-honey-muted">{{ __('home.product_page.request_details_text') }}</p>
                            </div>

                            <div>
                                <label for="notes" class="figma-form-label">{{ __('home.product_page.form.notes') }}</label>
                                <textarea id="notes" name="notes" rows="4" class="figma-form-textarea" data-order-notes>{{ old('notes') }}</textarea>
                                @error('notes')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div class="figma-meta-stack">
                                <div class="figma-meta-row"><span>{{ __('home.product_page.meta.sku') }}</span><span>{{ $sku }}</span></div>
                                <div class="figma-meta-row"><span>{{ __('home.product_page.meta.category') }}</span><span>{{ $category }}</span></div>
                                <div class="figma-meta-row"><span>{{ __('home.product_page.meta.tags') }}</span><span>{{ $tags }}</span></div>
                            </div>

                            <div class="figma-share-row">
                                <span>{{ __('home.product_page.share_product') }}</span>
                                <div class="figma-share-icons">
                                    <a href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrl }}" target="_blank" rel="noopener" aria-label="Facebook">Fb</a>
                                    <a href="https://twitter.com/intent/tweet?url={{ $shareUrl }}&text={{ $shareText }}" target="_blank" rel="noopener" aria-label="X">X</a>
                                    <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ $shareUrl }}" target="_blank" rel="noopener" aria-label="LinkedIn">In</a>
                                </div>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-2">
                                <button type="submit" class="figma-submit-button">{{ __('home.product_page.form.submit') }}</button>
                                <a href="https://api.whatsapp.com/send?text=" target="_blank" rel="noopener" class="figma-whatsapp-button" data-whatsapp-link>{{ __('home.product_page.whatsapp_button') }}</a>
                            </div>

                            <p class="text-sm leading-7 text-honey-muted">{{ __('home.product_page.whatsapp_hint') }}</p>
                            <p class="text-sm leading-7 text-honey-muted">{{ __('home.product_page.form.disclaimer') }}</p>
                        </form>
                    </div>
                </aside>
            </div>
        </div>
    </section>

    <section class="figma-related-section">
        <div class="mx-auto max-w-[1130px] px-6 py-16 lg:py-20">
            <div class="mx-auto max-w-3xl text-center">
                <h2 class="font-condensed text-5xl font-bold uppercase text-honey-dark">{{ __('home.products.heading') }}</h2>
                <p class="mt-4 text-lg leading-8 text-honey-grey">{{ __('home.product_page.related_description') }}</p>
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