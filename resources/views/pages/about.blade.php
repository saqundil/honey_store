{{-- resources/views/pages/about.blade.php --}}
@extends('layouts.app')

@section('title', __('pages.about.title').' | '.__('home.meta.title'))

@section('content')
<div class="relative" id="aboutPage">

    {{-- ══ Background: subtle maps + bee journey (decorative only) ══ --}}
    <div class="journey-bg" id="journeyBg" aria-hidden="true">
        <svg id="journeySvg" class="journey-bg__svg" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <linearGradient id="kgFill" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#f59e0b" stop-opacity="0.38"/>
                    <stop offset="100%" stop-color="#b45309" stop-opacity="0.18"/>
                </linearGradient>
                <linearGradient id="kgStroke" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0%" stop-color="#fcd34d" stop-opacity="1"/>
                    <stop offset="100%" stop-color="#d97706" stop-opacity="1"/>
                </linearGradient>
                <linearGradient id="joFill" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#c74817" stop-opacity="0.38"/>
                    <stop offset="100%" stop-color="#7c2d12" stop-opacity="0.18"/>
                </linearGradient>
                <linearGradient id="joStroke" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0%" stop-color="#fb923c" stop-opacity="1"/>
                    <stop offset="100%" stop-color="#c74817" stop-opacity="1"/>
                </linearGradient>
            </defs>
            <g id="kgGroup" opacity="0.72"><g id="kgMapLayer"></g></g>
            <g id="joGroup" opacity="0.72"><g id="joMapLayer"></g></g>
            <path id="flightPath" fill="none" stroke="#c74817" stroke-opacity="0.22"
                  stroke-dasharray="5 12" stroke-width="2" stroke-linecap="round"/>
            <g id="cityDots"></g>
        </svg>
        <div class="journey-bee" id="journeyBee">
            <img src="{{ asset('images/bee.png') }}" alt="" width="40" height="40" class="journey-bee__img" draggable="false">
        </div>
    </div>

    {{-- Hero --}}
    <x-page-hero :eyebrow="__('pages.about.hero_eyebrow')" :heading="__('pages.about.hero_heading')" />

    {{-- Breadcrumb --}}
    <div class="mx-auto max-w-[1100px] px-6 pt-7">
        <nav class="flex flex-wrap items-center gap-1.5 text-[13px] text-honey-muted" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="transition-colors duration-200 hover:text-honey-orange">{{ __('pages.breadcrumb_home') }}</a>
            <span class="{{ app()->isLocale('ar') ? 'rotate-180' : '' }} inline-block text-xs opacity-40">/</span>
            <span class="text-honey-dark">{{ __('pages.about.title') }}</span>
        </nav>
    </div>

    {{-- ── 1 · Introduction ───────────────────────────── --}}
    <section class="mx-auto max-w-[1100px] px-6 py-20 lg:py-28">
        <div class="grid gap-14 lg:grid-cols-2 lg:gap-20 lg:items-center">
            <div class="about-reveal" data-reveal>
                <span class="about-eyebrow">{{ app()->isLocale('ar') ? 'تعرّف علينا' : 'About us' }}</span>
                <h2 class="about-h2 mt-2">{{ __('pages.about.intro_heading') }}</h2>
                <p class="about-body mt-5">{{ __('pages.about.intro_text') }}</p>
            </div>
            <div class="about-reveal" data-reveal data-reveal-delay="120">
                <figure class="about-figure m-0">
                    <img src="{{ asset('images/h1-img6.png') }}"
                         alt="{{ __('pages.about.intro_heading') }}"
                         class="h-auto w-full object-cover"
                         loading="lazy" decoding="async">
                </figure>
            </div>
        </div>
    </section>

    {{-- ── 2 · Stats ───────────────────────────────────── --}}
    <section class="about-stats-band" id="aboutStats">
        <div class="mx-auto max-w-[1100px] px-6">
            <div class="grid grid-cols-2 divide-x divide-y divide-white/[0.07] md:grid-cols-4 md:divide-y-0">
                @foreach (__('pages.about.stats') as $i => $stat)
                    <div class="about-reveal px-6 py-10 text-center" data-reveal data-reveal-delay="{{ $i * 70 }}">
                        <p class="stat-number"
                           data-count-to="{{ preg_replace('/[^0-9]/', '', $stat['value']) }}"
                           data-count-suffix="{{ preg_replace('/[0-9]/', '', $stat['value']) }}">
                            0{{ preg_replace('/[0-9]/', '', $stat['value']) }}
                        </p>
                        <p class="mt-2 font-condensed text-[11px] font-bold uppercase tracking-[0.2em] text-white/45">{{ $stat['label'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ── 3 · Origin story ───────────────────────────── --}}
    <section class="mx-auto max-w-[1100px] px-6 py-20 lg:py-28">
        <div class="grid gap-14 lg:grid-cols-2 lg:gap-20 lg:items-center">
            <div class="order-2 lg:order-1 {{ app()->isLocale('ar') ? 'lg:!order-2' : '' }} about-reveal" data-reveal>
                <figure class="about-figure m-0">
                    <img src="{{ asset('images/h1-img8.png') }}"
                         alt="{{ __('pages.about.origin_heading') }}"
                         class="h-auto w-full object-cover"
                         loading="lazy" decoding="async">
                </figure>
            </div>
            <div class="order-1 lg:order-2 {{ app()->isLocale('ar') ? 'lg:!order-1' : '' }} about-reveal" data-reveal data-reveal-delay="120">
                <span class="about-eyebrow">{{ app()->isLocale('ar') ? 'قصتنا' : 'Our origin' }}</span>
                <h2 class="about-h2 mt-2">{{ __('pages.about.origin_heading') }}</h2>
                <p class="about-body mt-5">{{ __('pages.about.origin_text') }}</p>
            </div>
        </div>
    </section>

    {{-- ── 4 · Values ──────────────────────────────────── --}}
    <section class="py-20 lg:py-28">
        <div class="mx-auto max-w-[1100px] px-6">
            <div class="about-reveal mb-12 text-center" data-reveal>
                <span class="about-eyebrow">{{ app()->isLocale('ar') ? 'ما يميزنا' : 'What we stand for' }}</span>
                <h2 class="about-h2 mt-2">{{ __('pages.about.values_heading') }}</h2>
            </div>
            <div class="grid gap-4 sm:grid-cols-2 lg:gap-5">
                @foreach (__('pages.about.values') as $i => $value)
                    <article class="about-reveal about-value-card" data-reveal data-reveal-delay="{{ $i * 80 }}">
                        <div class="about-value-icon" aria-hidden="true">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <h3 class="mt-4 font-condensed text-[1.05rem] font-bold uppercase tracking-[0.04em] text-honey-dark">{{ $value['title'] }}</h3>
                        <p class="mt-2 text-[0.9375rem] leading-[1.75] text-honey-grey">{{ $value['text'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ── 5 · Promise / CTA ───────────────────────────── --}}
    <section class="py-20 lg:py-28">
        <div class="about-reveal mx-auto max-w-[580px] px-6 text-center" data-reveal>
            <span class="about-eyebrow">{{ app()->isLocale('ar') ? 'التزامنا' : 'Our promise' }}</span>
            <h2 class="about-h2 mt-2">{{ __('pages.about.promise_heading') }}</h2>
            <p class="about-body mx-auto mt-5">{{ __('pages.about.promise_text') }}</p>
            <div class="mt-8">
                <a href="{{ route('home') }}#products" class="about-cta">
                    <span>{{ __('home.hero.cta') }}</span>
                    <svg class="{{ app()->isLocale('ar') ? 'rotate-180' : '' }} h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                </a>
            </div>
        </div>
    </section>

</div>{{-- /#aboutPage --}}

    {{-- ══ Background journey script ══ --}}
    <script>
    (function () {
        var bgEl    = document.getElementById('journeyBg');
        var svgEl   = document.getElementById('journeySvg');
        var kgLayer = document.getElementById('kgMapLayer');
        var joLayer = document.getElementById('joMapLayer');
        var flight  = document.getElementById('flightPath');
        var dotsG   = document.getElementById('cityDots');
        var bee     = document.getElementById('journeyBee');

        var BISHKEK = { lon: 74.59, lat: 42.87, label: 'Bishkek' };
        var AMMAN   = { lon: 35.93, lat: 31.95, label: 'Amman'   };

        function loadMap(url) {
            return fetch(url).then(function (r) { return r.json(); }).then(function (geo) {
                var features = geo.features || [], coords = [];
                features.forEach(function (f) {
                    var g = f.geometry; if (!g) return;
                    var add = function (ring) { ring.forEach(function (c) { coords.push(c); }); };
                    if (g.type === 'Polygon') g.coordinates.forEach(add);
                    if (g.type === 'MultiPolygon') g.coordinates.forEach(function (p) { p.forEach(add); });
                });
                return { features: features, coords: coords };
            });
        }

        function makeProj(coords, W, H, ox, oy, pad) {
            var xs = coords.map(function (c) { return c[0]; });
            var ys = coords.map(function (c) { return c[1]; });
            var x0 = Math.min.apply(null, xs), x1 = Math.max.apply(null, xs);
            var y0 = Math.min.apply(null, ys), y1 = Math.max.apply(null, ys);
            var ls = x1 - x0 || 1, la = y1 - y0 || 1;
            var sc = Math.min((W - pad * 2) / ls, (H - pad * 2) / la);
            var mx = ox + (W - ls * sc) / 2, my = oy + (H - la * sc) / 2;
            return function (lon, lat) {
                return [mx + (lon - x0) * sc, oy + H - (my - oy + (lat - y0) * sc)];
            };
        }

        function buildPaths(data, layer, fillId, strokeId, proj) {
            function r2d(ring) {
                return ring.map(function (c, i) {
                    var p = proj(c[0], c[1]);
                    return (i === 0 ? 'M' : 'L') + p[0].toFixed(1) + ',' + p[1].toFixed(1);
                }).join(' ') + ' Z';
            }
            function f2d(f) {
                var g = f.geometry;
                if (g.type === 'Polygon') return g.coordinates.map(r2d).join(' ');
                if (g.type === 'MultiPolygon') return g.coordinates.map(function (p) { return p.map(r2d).join(' '); }).join(' ');
                return '';
            }
            data.features.forEach(function (f) {
                var el = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                el.setAttribute('d', f2d(f));
                el.setAttribute('fill', 'url(#' + fillId + ')');
                el.setAttribute('stroke', 'url(#' + strokeId + ')');
                el.setAttribute('stroke-width', '1.6');
                layer.appendChild(el);
            });
        }

        function positionMaps(kg, jo) {
            var W = bgEl.offsetWidth, H = bgEl.offsetHeight;
            var rtl = document.documentElement.getAttribute('dir') === 'rtl';
            svgEl.setAttribute('viewBox', '0 0 ' + W + ' ' + H);
            /* LTR: KG top-left, JO bottom-right | RTL: KG top-right, JO bottom-left */
            var kgOX = rtl ? W * 0.56 : W * 0.06;
            var joOX = rtl ? W * 0.06 : W * 0.64;
            var kgP = makeProj(kg.coords, W * 0.38, H * 0.22, kgOX, H * 0.12, 10);
            kgLayer.innerHTML = ''; buildPaths(kg, kgLayer, 'kgFill', 'kgStroke', kgP);
            var joP = makeProj(jo.coords, W * 0.30, H * 0.26, joOX, H * 0.76, 10);
            joLayer.innerHTML = ''; buildPaths(jo, joLayer, 'joFill', 'joStroke', joP);

            var bish = kgP(BISHKEK.lon, BISHKEK.lat);
            var amm  = joP(AMMAN.lon,   AMMAN.lat);
            var dx = amm[0] - bish[0], dy = amm[1] - bish[1];
            flight.setAttribute('d',
                'M' + bish[0].toFixed(1) + ',' + bish[1].toFixed(1) +
                ' C' + (bish[0] + dx * 0.15).toFixed(1) + ',' + (bish[1] + dy * 0.35).toFixed(1) +
                ' '  + (amm[0]  - dx * 0.15).toFixed(1) + ',' + (amm[1]  - dy * 0.35).toFixed(1) +
                ' '  + amm[0].toFixed(1) + ',' + amm[1].toFixed(1)
            );

            dotsG.innerHTML = '';
            [[bish, BISHKEK, '#d97706'], [amm, AMMAN, '#c74817']].forEach(function (e) {
                var pos = e[0], city = e[1], col = e[2];
                var dot = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                dot.setAttribute('cx', pos[0]); dot.setAttribute('cy', pos[1]);
                dot.setAttribute('r', '4'); dot.setAttribute('fill', col); dot.setAttribute('fill-opacity', '0.85');
                dotsG.appendChild(dot);
                var lbl = document.createElementNS('http://www.w3.org/2000/svg', 'text');
                lbl.setAttribute('x', pos[0]); lbl.setAttribute('y', pos[1] - 9);
                lbl.setAttribute('text-anchor', 'middle'); lbl.setAttribute('fill', col);
                lbl.setAttribute('fill-opacity', '0.85'); lbl.setAttribute('font-size', '9'); lbl.setAttribute('font-weight', '700');
                lbl.textContent = city.label;
                dotsG.appendChild(lbl);
            });
        }

        function moveBee(p) {
            var len = flight.getTotalLength(); if (!len) return;
            var pt  = flight.getPointAtLength(len * p);
            var pt2 = flight.getPointAtLength(Math.min(len, len * p + 2));
            bee.style.left      = pt.x + 'px';
            bee.style.top       = pt.y + 'px';
            bee.style.transform = 'translate(-50%,-50%) rotate(' + (Math.atan2(pt2.y - pt.y, pt2.x - pt.x) * 180 / Math.PI + 90).toFixed(1) + 'deg)';
            bee.style.opacity   = (p > 0.02 && p < 0.98) ? '0.92' : '0';
        }

        function tick() {
            var r = bgEl.getBoundingClientRect(), total = r.height - window.innerHeight;
            if (total <= 0) { moveBee(0); return; }
            moveBee(Math.max(0, Math.min(1, -r.top / total)));
        }

        Promise.all([loadMap('/data/kg.json'), loadMap('/data/jo.json')]).then(function (res) {
            positionMaps(res[0], res[1]); tick();
            var raf = false;
            window.addEventListener('scroll', function () {
                if (!raf) { requestAnimationFrame(function () { tick(); raf = false; }); raf = true; }
            }, { passive: true });
            var t;
            window.addEventListener('resize', function () {
                clearTimeout(t); t = setTimeout(function () { positionMaps(res[0], res[1]); tick(); }, 200);
            });
        });
    })();
    </script>

    {{-- Scroll-reveal & counter animations --}}
    <script>
    (function () {
        var ro = new IntersectionObserver(function (entries) {
            entries.forEach(function (e) {
                if (!e.isIntersecting) return;
                var d = parseInt(e.target.getAttribute('data-reveal-delay') || '0', 10);
                setTimeout(function () { e.target.classList.add('is-revealed'); }, d);
                ro.unobserve(e.target);
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -28px 0px' });
        document.querySelectorAll('[data-reveal]').forEach(function (el) { ro.observe(el); });

        var so = new IntersectionObserver(function (entries) {
            entries.forEach(function (e) {
                if (!e.isIntersecting) return;
                var el = e.target;
                var target = parseInt(el.getAttribute('data-count-to'), 10);
                var suffix = el.getAttribute('data-count-suffix') || '';
                if (isNaN(target)) return;
                so.unobserve(el);
                var t0 = null, dur = 1500;
                function ease(t) { return t < 0.5 ? 2 * t * t : -1 + (4 - 2 * t) * t; }
                (function step(ts) {
                    if (!t0) t0 = ts;
                    var p = Math.min((ts - t0) / dur, 1);
                    el.textContent = Math.floor(ease(p) * target).toLocaleString() + suffix;
                    if (p < 1) requestAnimationFrame(step);
                    else el.textContent = target.toLocaleString() + suffix;
                })(performance.now());
            });
        }, { threshold: 0.5 });
        document.querySelectorAll('.stat-number[data-count-to]').forEach(function (el) { so.observe(el); });
    })();
    </script>

@endsection
