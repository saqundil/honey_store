@php
    $products = collect(trans('home.products.items'))->take(6);
    $articles = collect(trans('home.news.articles'))->take(3);
    $footerBackground = asset('images/Footer [qodef-page-footer].webp');
    $phoneValue = __('home.footer.phone_value');
    $emailValue = __('home.footer.email');
    $phoneHref = preg_replace('/[^\d+]/', '', $phoneValue);
    $followLabel = app()->isLocale('ar') ? 'تابعنا' : 'Follow us';
    $footerCardClass = 'rounded-[1.6rem] border border-white/10 bg-white/7 p-5 backdrop-blur-sm';
    $footerLinkClass = 'inline-flex rounded-lg py-1 text-white/72 transition duration-200 hover:text-[#f6c15a] focus:outline-none focus:text-[#f6c15a]';
    $footerIconClass = 'mt-1 inline-flex h-10 w-10 items-center justify-center rounded-full bg-[#f6c15a]/12 text-[#f6c15a]';
    $hiveLinks = [
        ['label' => __('home.footer.hive_links.0'), 'route' => route('about')],
        ['label' => __('home.footer.hive_links.1'), 'route' => route('contact')],
        ['label' => __('home.footer.hive_links.2'), 'route' => route('faq')],
        ['label' => __('home.footer.hive_links.3'), 'route' => route('shipping')],
        ['label' => __('home.footer.hive_links.4'), 'route' => route('privacy')],
    ];
@endphp

<footer class="relative overflow-hidden border-t border-white/8 bg-[#2c1b0b] text-[#f7efe4]" aria-labelledby="site-footer-heading">
    <img src="{{ $footerBackground }}"
         alt=""
         aria-hidden="true"
         class="pointer-events-none absolute inset-0 h-full w-full object-cover object-bottom opacity-16 select-none"
         loading="lazy"
         decoding="async">
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(246,193,90,0.08),transparent_34%),linear-gradient(180deg,rgba(19,13,9,0.1),rgba(19,13,9,0.34))]"></div>

    <div class="relative px-6 pb-8 pt-12 sm:px-8 sm:pb-10 sm:pt-14 lg:px-10 lg:pt-18">
        <h2 id="site-footer-heading" class="sr-only">{{ __('home.brand.name') }}</h2>

        <div class="relative mx-auto max-w-[1260px]">
            <div class="relative grid gap-8 border-b border-white/10 pb-10 md:grid-cols-2 xl:grid-cols-[1.18fr_0.88fr_1fr_0.9fr] xl:gap-6">
                <section aria-labelledby="footer-brand-heading" class="space-y-6">
                    <div class="space-y-5">
                        <a href="{{ url('/') }}" class="inline-flex items-center gap-4 rounded-[1.7rem] border border-white/10 bg-white/7 px-4 py-3 backdrop-blur-sm transition duration-300 hover:border-[#f6c15a]/35 hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-[#f6c15a]/20">
                            <img src="{{ asset('images/honey_logo.png') }}"
                             alt="{{ __('home.brand.logo_alt') }}"
                             class="h-14 w-auto object-contain"
                             width="74"
                             height="74"
                             loading="lazy"
                             decoding="async">
                            <div>
                                <p id="footer-brand-heading" class="font-condensed text-[1.8rem] font-bold uppercase leading-none text-white sm:text-[2rem]">
                                {{ __('home.brand.name') }}
                                </p>
                                <p class="mt-2 text-xs font-semibold uppercase tracking-[0.24em] text-[#f6c15a]">
                                {{ __('home.hero.eyebrow') }}
                                </p>
                            </div>
                        </a>

                        <p class="max-w-md text-[15px] leading-7 text-white/72 sm:text-base">
                            {{ __('home.hero.description') }}
                        </p>
                    </div>

                    <div class="{{ $footerCardClass }}">
                        <h3 class="font-condensed text-[1.15rem] font-semibold uppercase tracking-[0.18em] text-white">
                            {{ __('home.footer.contact_info_title') }}
                        </h3>

                        <ul class="mt-5 space-y-4 text-[15px] leading-7 text-white/72" role="list">
                            <li class="flex items-start gap-3">
                                <span class="{{ $footerIconClass }}" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" class="h-4 w-4 fill-current"><path d="M6.62 10.79a15.05 15.05 0 0 0 6.59 6.59l2.2-2.2a1 1 0 0 1 1.02-.24c1.12.37 2.32.56 3.57.56a1 1 0 0 1 1 1V20a1 1 0 0 1-1 1C10.3 21 3 13.7 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1c0 1.25.19 2.45.56 3.57a1 1 0 0 1-.24 1.02l-2.2 2.2Z"/></svg>
                                </span>
                                <a href="tel:{{ $phoneHref }}" class="transition-colors duration-200 hover:text-[#f6c15a] focus:outline-none focus:text-[#f6c15a]">
                                    {{ __('home.footer.phone_label') }} {{ $phoneValue }}
                                </a>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="{{ $footerIconClass }}" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" class="h-4 w-4 fill-current"><path d="M20 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2Zm0 4.24-7.37 4.92a1 1 0 0 1-1.11 0L4 8.24V6l8 5.33L20 6v2.24Z"/></svg>
                                </span>
                                <a href="mailto:{{ $emailValue }}" class="transition-colors duration-200 hover:text-[#f6c15a] focus:outline-none focus:text-[#f6c15a]">
                                    {{ $emailValue }}
                                </a>
                            </li>
                        </ul>
                    </div>

                    <div class="space-y-3">
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-white/52">
                            {{ $followLabel }}
                        </p>
                        <div class="flex flex-wrap items-center gap-3">
                            <a href="#"
                               aria-label="Facebook"
                               class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-white/10 bg-white/7 text-white/70 transition duration-300 hover:-translate-y-0.5 hover:border-[#f6c15a]/35 hover:bg-[#f6c15a]/14 hover:text-[#f6c15a] focus:outline-none focus:ring-2 focus:ring-[#f6c15a]/20">
                                <svg viewBox="0 0 24 24" class="h-4 w-4 fill-current" aria-hidden="true"><path d="M13.5 21v-8h2.7l.4-3h-3.1V8.1c0-.87.24-1.46 1.49-1.46H17V3.96c-.36-.05-1.18-.16-2.25-.16-2.23 0-3.75 1.36-3.75 3.86V10H8.5v3H11v8h2.5Z"/></svg>
                            </a>
                            <a href="#"
                               aria-label="Instagram"
                               class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-white/10 bg-white/7 text-white/70 transition duration-300 hover:-translate-y-0.5 hover:border-[#f6c15a]/35 hover:bg-[#f6c15a]/14 hover:text-[#f6c15a] focus:outline-none focus:ring-2 focus:ring-[#f6c15a]/20">
                                <svg viewBox="0 0 24 24" class="h-4 w-4 fill-current" aria-hidden="true"><path d="M7 2h10a5 5 0 0 1 5 5v10a5 5 0 0 1-5 5H7a5 5 0 0 1-5-5V7a5 5 0 0 1 5-5Zm0 2.2A2.8 2.8 0 0 0 4.2 7v10A2.8 2.8 0 0 0 7 19.8h10a2.8 2.8 0 0 0 2.8-2.8V7A2.8 2.8 0 0 0 17 4.2H7Zm10.25 1.65a.95.95 0 1 1 0 1.9.95.95 0 0 1 0-1.9ZM12 7a5 5 0 1 1 0 10 5 5 0 0 1 0-10Zm0 2.2A2.8 2.8 0 1 0 12 14.8 2.8 2.8 0 0 0 12 9.2Z"/></svg>
                            </a>
                            <a href="#"
                               aria-label="Twitter / X"
                               class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-white/10 bg-white/7 text-white/70 transition duration-300 hover:-translate-y-0.5 hover:border-[#f6c15a]/35 hover:bg-[#f6c15a]/14 hover:text-[#f6c15a] focus:outline-none focus:ring-2 focus:ring-[#f6c15a]/20">
                                <svg viewBox="0 0 24 24" class="h-4 w-4 fill-current" aria-hidden="true"><path d="M18.9 2H22l-6.77 7.73L23 22h-6.08l-4.76-6.21L6.72 22H3.6l7.24-8.28L1 2h6.23l4.3 5.67L18.9 2Zm-1.07 18h1.69L6.3 3.9H4.49L17.83 20Z"/></svg>
                            </a>
                        </div>
                    </div>
                </section>

                <nav aria-labelledby="footer-products-heading" class="{{ $footerCardClass }} space-y-5 h-full">
                    <h3 id="footer-products-heading" class="font-condensed text-[1.15rem] font-semibold uppercase tracking-[0.18em] text-white">
                        {{ __('home.footer.products_title') }}
                    </h3>

                    <ul class="space-y-1.5 text-[15px] leading-7" role="list">
                        @foreach ($products as $footerProduct)
                            <li>
                                <a href="{{ route('products.show', ['slug' => $footerProduct['slug']]) }}"
                                   class="{{ $footerLinkClass }}">
                                    {{ $footerProduct['name'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>

                <section aria-labelledby="footer-news-heading" class="{{ $footerCardClass }} space-y-5 h-full">
                    <h3 id="footer-news-heading" class="font-condensed text-[1.15rem] font-semibold uppercase tracking-[0.18em] text-white">
                        {{ __('home.news.heading') }}
                    </h3>

                    <div class="space-y-4">
                        @foreach ($articles as $article)
                            <article class="rounded-[1.35rem] border border-white/10 bg-white/6 p-4 backdrop-blur-sm transition duration-300 hover:border-[#f6c15a]/24 hover:bg-white/9">
                                <p class="font-condensed text-[12px] font-bold uppercase tracking-[0.2em] text-[#f6c15a]">
                                    {{ $article['date'] }}
                                </p>
                                <p class="mt-3 font-condensed text-[1.35rem] font-bold uppercase leading-[1.15] text-white">
                                    {{ $article['title'] }}
                                </p>
                            </article>
                        @endforeach
                    </div>
                </section>

                <nav aria-labelledby="footer-hive-heading" class="{{ $footerCardClass }} space-y-5 h-full">
                    <h3 id="footer-hive-heading" class="font-condensed text-[1.15rem] font-semibold uppercase tracking-[0.18em] text-white">
                        {{ __('home.footer.hive_title') }}
                    </h3>

                    <ul class="space-y-2 text-[15px] leading-7" role="list">
                        @foreach ($hiveLinks as $item)
                            <li>
                                <a href="{{ $item['route'] }}"
                                   class="{{ $footerLinkClass }}">
                                    {{ $item['label'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
            </div>

            <div class="relative flex flex-col gap-4 pt-6 md:flex-row md:items-center md:justify-between">
                <div class="flex items-center gap-3 text-white/56">
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/10 bg-white/7 text-[#f6c15a]" aria-hidden="true">
                        <svg viewBox="0 0 24 24" class="h-5 w-5 fill-current"><path d="M8.5 4A2.5 2.5 0 0 0 6 6.5c0 .77.35 1.45.9 1.91A3.98 3.98 0 0 0 5 12c0 1.04.4 1.98 1.05 2.69A3.48 3.48 0 0 0 5 17a3.5 3.5 0 0 0 6 2.45A3.48 3.48 0 0 0 13.5 20 3.5 3.5 0 0 0 17 16.5c0-.87-.32-1.67-.85-2.28A3.98 3.98 0 0 0 19 12a4 4 0 0 0-2.1-3.51c.55-.46.9-1.14.9-1.91A2.5 2.5 0 0 0 15.5 4c-.97 0-1.81.55-2.23 1.35A2.98 2.98 0 0 0 11 4.5c-.66 0-1.27.21-1.77.56A2.5 2.5 0 0 0 8.5 4Zm0 2c.28 0 .5.22.5.5S8.78 7 8.5 7 8 6.78 8 6.5 8.22 6 8.5 6Zm7 0c.28 0 .5.22.5.5S15.78 7 15.5 7s-.5-.22-.5-.5.22-.5.5-.5ZM11 6.5c.83 0 1.5.67 1.5 1.5v1h-3V8c0-.83.67-1.5 1.5-1.5ZM9 11h6a2 2 0 1 1-2 2H11a2 2 0 1 1-2-2Zm-.5 5c.6 0 1.13.27 1.5.69V18a1 1 0 1 1-2 0v-.5c0-.83.67-1.5 1.5-1.5Zm7 0c.83 0 1.5.67 1.5 1.5v.5a1 1 0 1 1-2 0v-1.31c.37-.42.9-.69 1.5-.69Z"/></svg>
                    </span>
                    <span class="text-xs font-semibold uppercase tracking-[0.22em] text-white/56">
                        {{ __('home.brand.name') }}
                    </span>
                </div>

                <div class="flex flex-wrap items-center gap-4 text-[12px] text-white/56">
                    <a href="{{ route('privacy') }}" class="transition-colors hover:text-[#f6c15a]">{{ __('pages.privacy.title') }}</a>
                    <span class="text-white/18">·</span>
                    <a href="{{ route('terms') }}" class="transition-colors hover:text-[#f6c15a]">{{ __('pages.terms.title') }}</a>
                    <span class="text-white/18">·</span>
                    <a href="{{ route('shipping') }}" class="transition-colors hover:text-[#f6c15a]">{{ __('pages.shipping.title') }}</a>
                </div>

                <p class="text-center text-[12px] leading-6 text-white/56 {{ app()->isLocale('ar') ? 'md:text-left' : 'md:text-right' }}">
                    &copy; {{ date('Y') }} {{ __('home.footer.copyright_owner') }} {{ __('home.footer.all_rights_reserved') }}
                </p>
            </div>
        </div>
    </div>
</footer>
