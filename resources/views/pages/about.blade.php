{{-- resources/views/pages/about.blade.php --}}
@extends('layouts.app')

@section('title', __('pages.about.title').' | '.__('home.meta.title'))

@section('content')
@php
    $isRtl = app()->isLocale('ar');
    $translatedStats = __('pages.about.stats');
    $translatedValues = __('pages.about.values');
    $stats = is_array($translatedStats) ? $translatedStats : [];
    $values = is_array($translatedValues) ? $translatedValues : [];

    $heroHighlights = [
        [
            'label' => $isRtl ? 'المنشأ' : 'Origin',
            'value' => $isRtl ? 'مروج قرغيزستان الجبلية' : 'Kyrgyz mountain meadows',
        ],
        [
            'label' => $isRtl ? 'التركيز' : 'Focus',
            'value' => $isRtl ? 'عسل خام ومصدر واضح' : 'Raw honey and clear provenance',
        ],
        [
            'label' => $isRtl ? 'الوعد' : 'Promise',
            'value' => $isRtl ? 'جودة تحترم الأصل' : 'Quality that respects the source',
        ],
    ];

    $journeySteps = [
        [
            'id' => '01',
            'eyebrow' => $isRtl ? 'البداية' : 'The beginning',
            'title' => __('pages.about.intro_heading'),
            'text' => __('pages.about.intro_text'),
        ],
        [
            'id' => '02',
            'eyebrow' => $isRtl ? 'الحكاية' : 'The story',
            'title' => __('pages.about.origin_heading'),
            'text' => __('pages.about.origin_text'),
        ],
        [
            'id' => '03',
            'eyebrow' => $isRtl ? 'الوعد' : 'The promise',
            'title' => __('pages.about.promise_heading'),
            'text' => __('pages.about.promise_text'),
        ],
    ];

    $qualityPillars = [
        [
            'title' => $isRtl ? 'معرفة أقرب بالمصدر' : 'Closer knowledge of the source',
            'text' => $isRtl ? 'نختار المراعي التي نعرف مواسمها ونباتاتها جيدًا حتى يبدأ الطعم من أصل واضح وموثوق.' : 'We choose meadows whose seasons and blossoms we know well so that flavor begins with a source we trust.',
        ],
        [
            'title' => $isRtl ? 'عناية من المنحل إلى العبوة' : 'Care from hive to jar',
            'text' => $isRtl ? 'نهتم بطريقة الجمع والحفظ والتعبئة حتى يصل العسل بهدوئه الطبيعي وطابعه الحقيقي.' : 'We handle harvesting, storage, and packing with care so the honey arrives with its natural character intact.',
        ],
        [
            'title' => $isRtl ? 'ثقة تدعو للعودة' : 'Trust worth returning to',
            'text' => $isRtl ? 'حين يكون المصدر واضحًا والجودة ثابتة، يعود العميل لأنه يعرف ما الذي ينتظره في كل مرة.' : 'When the source is clear and the quality stays steady, customers return because they know what to expect each time.',
        ],
    ];

    $statDescriptions = [
        $isRtl ? 'الارتفاع يمنح المراعي مناخًا أنقى وطابعًا زهريًا أوضح.' : 'High altitude gives the meadows a cleaner climate and a clearer floral profile.',
        $isRtl ? 'العسل يصل كما هو، من دون تسخين أو خلط أو معالجة تُفقده شخصيته.' : 'The honey reaches you without heating, blending, or processing that dulls its character.',
        $isRtl ? 'خلف كل موسم شبكة من مربي النحل المحليين الذين يحفظون الحرفة ويعتنون بالخلايا.' : 'Behind each harvest is a network of local beekeepers who protect the craft and care for the hives.',
        $isRtl ? 'لا شيء مضاف إلى العسل، فقط نقاؤه الطبيعي كما خرج من المنحل.' : 'Nothing is added to the honey, only its natural purity as it leaves the hive.',
    ];
@endphp

<div class="relative isolate overflow-hidden bg-[#fbf7f1]" id="aboutPage">
    <div class="pointer-events-none absolute inset-x-0 top-0 h-[38rem] bg-[radial-gradient(circle_at_top_left,_rgba(245,158,11,0.22),_transparent_45%),radial-gradient(circle_at_top_right,_rgba(199,72,23,0.16),_transparent_34%),linear-gradient(180deg,_rgba(255,255,255,0.96),_rgba(251,247,241,0.78)_56%,_rgba(251,247,241,1)_100%)]"></div>
    <div class="pointer-events-none absolute -left-16 top-24 h-48 w-48 rounded-full bg-[#f6c15a]/30 blur-3xl"></div>
    <div class="pointer-events-none absolute -right-14 top-32 h-56 w-56 rounded-full bg-[#cf5a2a]/15 blur-3xl"></div>

    <section class="relative mx-auto max-w-[1180px] px-6 pb-14 pt-10 sm:pb-16 lg:pb-20 lg:pt-14">
        <nav class="flex flex-wrap items-center gap-2 text-[13px] text-honey-muted" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="transition-colors duration-200 hover:text-honey-orange">{{ __('pages.breadcrumb_home') }}</a>
            <span class="{{ $isRtl ? 'rotate-180' : '' }} inline-block text-xs opacity-40">/</span>
            <span class="text-honey-dark">{{ __('pages.about.title') }}</span>
        </nav>

        <div class="mt-8 grid gap-10 lg:grid-cols-[minmax(0,1.08fr)_minmax(20rem,0.92fr)] lg:items-start lg:gap-12">
            <div class="about-reveal is-revealed">
                <span class="inline-flex rounded-full border border-[#c74817]/12 bg-white/70 px-4 py-2 font-condensed text-[0.72rem] font-bold uppercase tracking-[0.24em] text-[#c74817] shadow-[0_12px_32px_rgba(199,72,23,0.08)] backdrop-blur-sm">
                    {{ __('pages.about.hero_eyebrow') }}
                </span>
                <h1 class="mt-5 max-w-[12ch] font-condensed text-[clamp(2.8rem,8vw,5.7rem)] font-bold uppercase leading-[0.92] tracking-[-0.04em] text-[#2c1b0b] {{ $isRtl ? 'tracking-normal leading-[1.02]' : '' }}">
                    {{ __('pages.about.hero_heading') }}
                </h1>
                <p class="mt-6 max-w-[62ch] text-[1.02rem] leading-8 text-honey-grey sm:text-[1.08rem] sm:leading-9">
                    {{ __('pages.about.intro_text') }}
                </p>

                <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                    <a href="{{ route('home') }}#products" class="inline-flex items-center justify-center gap-2 rounded-full bg-[#c74817] px-6 py-3.5 font-condensed text-[0.84rem] font-bold uppercase tracking-[0.18em] text-white shadow-[0_18px_40px_rgba(199,72,23,0.24)] transition duration-300 hover:-translate-y-0.5 hover:bg-[#b63f12]">
                        <span>{{ __('home.hero.cta') }}</span>
                        <svg class="{{ $isRtl ? 'rotate-180' : '' }} h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                    </a>
                    <a href="{{ route('contact') }}" class="inline-flex items-center justify-center rounded-full border border-[#2c1b0b]/10 bg-white/78 px-6 py-3.5 font-condensed text-[0.84rem] font-bold uppercase tracking-[0.18em] text-[#2c1b0b] shadow-[0_14px_36px_rgba(44,27,11,0.08)] transition duration-300 hover:-translate-y-0.5 hover:border-[#c74817]/22 hover:text-[#c74817]">
                        {{ $isRtl ? 'تواصل معنا' : 'Contact us' }}
                    </a>
                </div>

                <div class="mt-9 grid gap-3 sm:grid-cols-3">
                    @foreach ($heroHighlights as $highlight)
                        <article class="rounded-[1.35rem] border border-[#2c1b0b]/8 bg-white/76 px-4 py-4 shadow-[0_16px_40px_rgba(44,27,11,0.06)] backdrop-blur-sm">
                            <p class="font-condensed text-[0.72rem] font-bold uppercase tracking-[0.22em] text-[#c74817]">{{ $highlight['label'] }}</p>
                            <p class="mt-2 text-sm font-semibold leading-6 text-[#2c1b0b]">{{ $highlight['value'] }}</p>
                        </article>
                    @endforeach
                </div>

                <div class="mt-4 rounded-[1.8rem] bg-[#2c1b0b] p-5 text-white shadow-[0_20px_46px_rgba(44,27,11,0.18)] sm:p-6 lg:p-7">
                    <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                        <div class="max-w-[30rem]">
                            <p class="font-condensed text-[0.72rem] font-bold uppercase tracking-[0.24em] text-[#f6c15a]">{{ $isRtl ? 'ما الذي تتوقعه' : 'What to expect' }}</p>
                            <p class="mt-3 text-base leading-8 text-white/82">{{ __('pages.about.promise_text') }}</p>
                        </div>
                        <div class="grid gap-3 sm:min-w-[10rem]">
                            @foreach (array_slice($stats, 0, 2) as $stat)
                                <div class="rounded-[1.2rem] border border-white/10 bg-white/8 px-4 py-3">
                                    <p class="font-condensed text-[0.68rem] font-bold uppercase tracking-[0.22em] text-white/55">{{ $stat['label'] }}</p>
                                    <p class="mt-1 text-lg font-semibold text-white">{{ $stat['value'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="about-reveal is-revealed relative">
                <div class="relative overflow-hidden rounded-[2.2rem] border border-[#2c1b0b]/8 bg-white/82 shadow-[0_30px_80px_rgba(44,27,11,0.12)] backdrop-blur-sm">
                    <div class="absolute inset-x-0 top-0 h-28 bg-[linear-gradient(180deg,rgba(246,193,90,0.16),transparent)]"></div>
                    <div class="relative grid gap-5 p-5 sm:p-6">
                        <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_15rem] lg:items-stretch">
                            <figure class="relative overflow-hidden rounded-[1.7rem] border border-[#2c1b0b]/8 bg-[#f3eeea] shadow-[0_14px_34px_rgba(44,27,11,0.07)]">
                                <div class="pointer-events-none absolute inset-x-0 top-0 z-[1] h-28 bg-[linear-gradient(180deg,rgba(246,193,90,0.18),transparent)]"></div>
                                <div class="absolute left-4 top-4 z-[2] rounded-full border border-white/70 bg-white/84 px-3 py-2 font-condensed text-[0.68rem] font-bold uppercase tracking-[0.2em] text-[#c74817] shadow-[0_10px_24px_rgba(44,27,11,0.08)] backdrop-blur-sm">
                                    {{ $isRtl ? 'من الجبال إلى الطاولة' : 'From mountain to table' }}
                                </div>
                                <img src="{{ asset('images/h1-img6.png') }}"
                                     alt="{{ __('pages.about.intro_heading') }}"
                                     class="h-full min-h-[22rem] w-full object-cover"
                                     loading="lazy"
                                     decoding="async">
                                <div class="absolute inset-x-4 bottom-4 z-[2] rounded-[1rem] border border-white/75 bg-white/82 px-4 py-3 shadow-[0_10px_24px_rgba(44,27,11,0.08)] backdrop-blur-sm">
                                    <p class="font-condensed text-[0.62rem] font-bold uppercase tracking-[0.22em] text-[#c74817]">{{ $isRtl ? 'موطن العسل' : 'Land of origin' }}</p>
                                    <p class="mt-1 text-xs font-semibold text-[#2c1b0b]">{{ $isRtl ? 'خريطة سريعة للمشهد القرغيزي الذي تبدأ منه حكاية العسل.' : 'A quick map of the Kyrgyz landscape where our honey story begins.' }}</p>
                                </div>
                            </figure>

                            <div class="flex flex-col gap-4">
                                <div class="rounded-[1.4rem] border border-[#2c1b0b]/8 bg-[#fff8ee] p-5 sm:p-6">
                                    <p class="font-condensed text-[0.72rem] font-bold uppercase tracking-[0.24em] text-[#c74817]">{{ $isRtl ? 'من أين نبدأ' : 'Where we begin' }}</p>
                                    <h2 class="mt-2 font-condensed text-[1.45rem] font-bold uppercase leading-tight text-[#2c1b0b] sm:text-[1.6rem]">{{ __('pages.about.origin_heading') }}</h2>
                                    <p class="mt-3 text-[0.98rem] leading-8 text-honey-grey">{{ __('pages.about.origin_text') }}</p>
                                </div>

                                <div class="flex items-start gap-3 rounded-[1.4rem] border border-[#c74817]/10 bg-white p-5 shadow-[0_12px_24px_rgba(44,27,11,0.05)] sm:p-6">
                                    <span class="inline-flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-[#c74817]/10">
                                        <img src="{{ asset('images/bee.png') }}" alt="" class="h-7 w-7 object-contain" loading="lazy" decoding="async">
                                    </span>
                                    <div>
                                        <p class="font-condensed text-[0.72rem] font-bold uppercase tracking-[0.22em] text-[#c74817]">{{ $isRtl ? 'لماذا يهم' : 'Why it matters' }}</p>
                                        <p class="mt-2 text-[0.96rem] font-semibold leading-7 text-[#2c1b0b]">{{ $isRtl ? 'الأرض التي يبدأ منها العسل تصنع فرقًا في الرائحة والطعم والملمس داخل كل عبوة.' : 'The land where the honey begins shapes the aroma, flavor, and texture carried in every jar.' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="relative mx-auto max-w-[1180px] px-6 pb-16 lg:pb-20">
        <div class="about-reveal overflow-hidden rounded-[2rem] border border-[#2c1b0b]/8 bg-[#fffdf9] shadow-[0_24px_60px_rgba(44,27,11,0.08)]" data-reveal>
            <div class="grid gap-5 border-b border-[#2c1b0b]/8 px-6 py-7 lg:grid-cols-[0.86fr_1.14fr] lg:px-8 lg:py-8">
                <div>
                    <p class="font-condensed text-[0.74rem] font-bold uppercase tracking-[0.24em] text-[#c74817]">{{ $isRtl ? 'رحلتنا' : 'Our journey' }}</p>
                    <h2 class="mt-3 max-w-[15ch] font-condensed text-[clamp(2rem,4vw,3.4rem)] font-bold uppercase leading-[0.96] tracking-[-0.03em] text-[#2c1b0b] {{ $isRtl ? 'tracking-normal leading-[1.02]' : '' }}">
                        {{ $isRtl ? 'كيف يتحول الأصل إلى ثقة في كل عبوة.' : 'How origin becomes trust in every jar.' }}
                    </h2>
                </div>
                <p class="max-w-[62ch] self-end text-[1rem] leading-8 text-honey-grey">
                    {{ $isRtl ? 'نشارك هنا ما يهم فعلًا: الأرض التي يأتي منها العسل، والعناية التي ترافقه، والقيم التي تقود كل عبوة.' : 'Here we share what matters most: the land the honey comes from, the care behind it, and the values guiding every jar.' }}
                </p>
            </div>

            <div class="grid gap-px bg-[#2c1b0b]/8 lg:grid-cols-3">
                @foreach ($journeySteps as $index => $step)
                    <article class="about-reveal bg-white px-6 py-7 lg:px-7 lg:py-8" data-reveal data-reveal-delay="{{ $index * 90 }}">
                        <div class="flex items-center gap-4">
                            <span class="inline-flex h-11 w-11 items-center justify-center rounded-full bg-[#2c1b0b] font-condensed text-sm font-bold tracking-[0.18em] text-[#f6c15a]">{{ $step['id'] }}</span>
                            <p class="font-condensed text-[0.74rem] font-bold uppercase tracking-[0.24em] text-[#c74817]">{{ $step['eyebrow'] }}</p>
                        </div>
                        <h3 class="mt-5 max-w-[18ch] font-condensed text-[1.55rem] font-bold uppercase leading-tight text-[#2c1b0b]">{{ $step['title'] }}</h3>
                        <p class="mt-4 text-[0.98rem] leading-8 text-honey-grey">{{ $step['text'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
</div>

<section class="relative overflow-hidden bg-[#2c1b0b] py-16 sm:py-18 lg:py-20" id="aboutStats">
    <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top,_rgba(246,193,90,0.18),_transparent_42%),linear-gradient(180deg,rgba(255,255,255,0.02),rgba(255,255,255,0))]"></div>
    <div class="relative mx-auto max-w-[1180px] px-6">
        <div class="about-reveal flex flex-col gap-4 text-white lg:flex-row lg:items-end lg:justify-between" data-reveal>
            <div>
                <p class="font-condensed text-[0.74rem] font-bold uppercase tracking-[0.24em] text-[#f6c15a]">{{ $isRtl ? 'لمحة سريعة' : 'At a glance' }}</p>
                <h2 class="mt-3 max-w-[15ch] font-condensed text-[clamp(2rem,4vw,3.1rem)] font-bold uppercase leading-[0.96] tracking-[-0.03em] text-white {{ $isRtl ? 'tracking-normal leading-[1.02]' : '' }}">
                    {{ $isRtl ? 'أرقام تعطي القصة وزنًا حقيقيًا.' : 'Numbers that give the story real weight.' }}
                </h2>
            </div>
            <p class="max-w-[34rem] text-[0.98rem] leading-8 text-white/72">
                {{ $isRtl ? 'هذه الأرقام تمنحك لمحة سريعة عن الارتفاع والنقاء والناس الذين يقفون وراء كل موسم.' : 'These numbers offer a quick view of altitude, purity, and the people behind each harvest.' }}
            </p>
        </div>

        <div class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($stats as $index => $stat)
                <article class="about-reveal rounded-[1.7rem] border border-white/10 bg-white/6 px-6 py-7 text-white shadow-[0_22px_50px_rgba(0,0,0,0.18)] backdrop-blur-sm" data-reveal data-reveal-delay="{{ $index * 70 }}">
                    <p class="stat-number text-[#f6c15a]"
                       data-count-to="{{ preg_replace('/[^0-9]/', '', $stat['value']) }}"
                       data-count-suffix="{{ preg_replace('/[0-9]/', '', $stat['value']) }}">
                        0{{ preg_replace('/[0-9]/', '', $stat['value']) }}
                    </p>
                    <p class="mt-4 font-condensed text-[0.76rem] font-bold uppercase tracking-[0.24em] text-white/55">{{ $stat['label'] }}</p>
                    <div class="mt-4 h-px w-full bg-white/10"></div>
                    <p class="mt-4 text-sm leading-7 text-white/72">{{ $statDescriptions[$index] ?? ($isRtl ? 'رقم يوضح جانبًا من حكاية هذا العسل.' : 'A number that highlights one part of this honey story.') }}</p>
                </article>
            @endforeach
        </div>
    </div>
</section>

<section class="mx-auto max-w-[1180px] px-6 py-16 lg:py-24">
    <div class="grid gap-10 lg:grid-cols-[minmax(0,0.92fr)_minmax(0,1.08fr)] lg:items-start">
        <div class="about-reveal lg:sticky lg:top-28" data-reveal>
            <p class="font-condensed text-[0.74rem] font-bold uppercase tracking-[0.24em] text-[#c74817]">{{ $isRtl ? 'ما يهمنا' : 'What matters to us' }}</p>
            <h2 class="mt-3 max-w-[15ch] font-condensed text-[clamp(2rem,4vw,3.2rem)] font-bold uppercase leading-[0.96] tracking-[-0.03em] text-[#2c1b0b] {{ $isRtl ? 'tracking-normal leading-[1.02]' : '' }}">
                {{ __('pages.about.values_heading') }}
            </h2>
            <p class="mt-5 max-w-[34rem] text-[1rem] leading-8 text-honey-grey">
                {{ $isRtl ? 'هذه المبادئ لا تبقى في الكلام فقط، بل تظهر في طريقة الاختيار والجمع والتقديم داخل كل عبوة.' : 'These principles do not live in words alone; they shape how we source, handle, and present every jar.' }}
            </p>

            <div class="mt-8 space-y-4">
                @foreach ($qualityPillars as $index => $pillar)
                    <article class="about-reveal rounded-[1.5rem] border border-[#2c1b0b]/8 bg-white p-5 shadow-[0_18px_42px_rgba(44,27,11,0.06)]" data-reveal data-reveal-delay="{{ $index * 70 }}">
                        <p class="font-condensed text-[0.74rem] font-bold uppercase tracking-[0.24em] text-[#c74817]">{{ sprintf('%02d', $index + 1) }}</p>
                        <h3 class="mt-2 font-condensed text-[1.3rem] font-bold uppercase text-[#2c1b0b]">{{ $pillar['title'] }}</h3>
                        <p class="mt-3 text-sm leading-7 text-honey-grey">{{ $pillar['text'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:gap-5">
            @foreach ($values as $index => $value)
                <article class="about-reveal about-value-card relative overflow-hidden" data-reveal data-reveal-delay="{{ $index * 80 }}">
                    <div class="absolute inset-x-0 top-0 h-1 bg-[linear-gradient(90deg,#f6c15a,#c74817)] opacity-70"></div>
                    <div class="about-value-icon" aria-hidden="true">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <h3 class="mt-4 font-condensed text-[1.12rem] font-bold uppercase tracking-[0.04em] text-honey-dark">{{ $value['title'] }}</h3>
                    <p class="mt-3 text-[0.97rem] leading-8 text-honey-grey">{{ $value['text'] }}</p>
                </article>
            @endforeach

            <figure class="about-reveal overflow-hidden rounded-[1.8rem] border border-[#2c1b0b]/8 bg-[#f7f1ea] shadow-[0_18px_48px_rgba(44,27,11,0.08)] sm:col-span-2" data-reveal data-reveal-delay="160">
                <div class="grid gap-0 lg:grid-cols-[0.9fr_1.1fr] lg:items-center">
                    <img src="{{ asset('images/h1-img8.png') }}"
                         alt="{{ __('pages.about.origin_heading') }}"
                         class="h-full min-h-[18rem] w-full object-cover"
                         loading="lazy"
                         decoding="async">
                    <figcaption class="p-6 sm:p-8 lg:p-10">
                        <p class="font-condensed text-[0.74rem] font-bold uppercase tracking-[0.24em] text-[#c74817]">{{ $isRtl ? 'من القصة إلى القرار' : 'From story to decision' }}</p>
                        <h3 class="mt-3 max-w-[16ch] font-condensed text-[1.9rem] font-bold uppercase leading-tight text-[#2c1b0b]">{{ $isRtl ? 'حين تكون الجودة واضحة، يصبح الاختيار أسهل.' : 'When quality is clear, choosing becomes easier.' }}</h3>
                        <p class="mt-4 text-[0.98rem] leading-8 text-honey-grey">{{ $isRtl ? 'نجمع هنا بين الأصل والعناية والقيم حتى تصل حكاية العسل بصورة أبسط وأكثر صدقًا.' : 'Here, origin, care, and values come together so the story of the honey feels clearer and more honest.' }}</p>
                    </figcaption>
                </div>
            </figure>
        </div>
    </div>
</section>

<section class="px-6 pb-20 lg:pb-28">
    <div class="mx-auto max-w-[1180px]">
        <div class="about-reveal relative overflow-hidden rounded-[2.4rem] border border-[#2c1b0b]/8 bg-[linear-gradient(135deg,#fff7ea_0%,#fff 42%,#fff5f2_100%)] p-8 shadow-[0_28px_70px_rgba(44,27,11,0.1)] sm:p-10 lg:p-12" data-reveal>
            <div class="pointer-events-none absolute -left-10 top-0 h-36 w-36 rounded-full bg-[#f6c15a]/20 blur-3xl"></div>
            <div class="pointer-events-none absolute -right-10 bottom-0 h-40 w-40 rounded-full bg-[#c74817]/12 blur-3xl"></div>

            <div class="relative grid gap-8 lg:grid-cols-[minmax(0,1.05fr)_minmax(18rem,0.95fr)] lg:items-center">
                <div>
                    <p class="font-condensed text-[0.74rem] font-bold uppercase tracking-[0.24em] text-[#c74817]">{{ $isRtl ? 'التزامنا' : 'Our promise' }}</p>
                    <h2 class="mt-3 max-w-[14ch] font-condensed text-[clamp(2rem,4.2vw,3.5rem)] font-bold uppercase leading-[0.96] tracking-[-0.03em] text-[#2c1b0b] {{ $isRtl ? 'tracking-normal leading-[1.02]' : '' }}">
                        {{ __('pages.about.promise_heading') }}
                    </h2>
                    <p class="mt-5 max-w-[60ch] text-[1rem] leading-8 text-honey-grey">
                        {{ __('pages.about.promise_text') }}
                    </p>

                    <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                        <a href="{{ route('home') }}#products" class="inline-flex items-center justify-center gap-2 rounded-full bg-[#2c1b0b] px-6 py-3.5 font-condensed text-[0.84rem] font-bold uppercase tracking-[0.18em] text-white shadow-[0_18px_42px_rgba(44,27,11,0.2)] transition duration-300 hover:-translate-y-0.5 hover:bg-[#1f1207]">
                            <span>{{ __('home.hero.cta') }}</span>
                            <svg class="{{ $isRtl ? 'rotate-180' : '' }} h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                        </a>
                        <a href="{{ route('contact') }}" class="inline-flex items-center justify-center rounded-full border border-[#2c1b0b]/10 bg-white px-6 py-3.5 font-condensed text-[0.84rem] font-bold uppercase tracking-[0.18em] text-[#2c1b0b] transition duration-300 hover:-translate-y-0.5 hover:border-[#c74817]/22 hover:text-[#c74817]">
                            {{ $isRtl ? 'اسأل عن منتجاتنا' : 'Ask about our products' }}
                        </a>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <article class="rounded-[1.5rem] border border-[#2c1b0b]/8 bg-white/88 p-5 shadow-[0_18px_38px_rgba(44,27,11,0.07)]">
                        <p class="font-condensed text-[0.74rem] font-bold uppercase tracking-[0.24em] text-[#c74817]">{{ $isRtl ? 'ما الذي يصلك' : 'What reaches you' }}</p>
                        <p class="mt-3 text-base font-semibold leading-7 text-[#2c1b0b]">{{ $isRtl ? 'عسل يحمل طابع مراعي قرغيزستان ونقاؤها في كل عبوة.' : 'Honey that carries the character and purity of Kyrgyz meadows in every jar.' }}</p>
                    </article>
                    <article class="rounded-[1.5rem] border border-[#2c1b0b]/8 bg-white/88 p-5 shadow-[0_18px_38px_rgba(44,27,11,0.07)]">
                        <p class="font-condensed text-[0.74rem] font-bold uppercase tracking-[0.24em] text-[#c74817]">{{ $isRtl ? 'لماذا يعود العملاء' : 'Why customers return' }}</p>
                        <p class="mt-3 text-base font-semibold leading-7 text-[#2c1b0b]">{{ $isRtl ? 'لأن المصدر واضح، والجودة ثابتة، والتجربة تبقى مطمئنة من أول طلب حتى ما بعده.' : 'Because the source is clear, the quality stays steady, and the experience remains dependable from the first order onward.' }}</p>
                    </article>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
(function () {
    var mapContainer = document.getElementById('aboutJourneyMap');
    var mapSvg = document.getElementById('aboutJourneySvg');
    var kgLayer = document.getElementById('aboutKgLayer');
    var cityDots = document.getElementById('aboutCityDots');

    if (!mapContainer || !mapSvg || !kgLayer || !cityDots) {
        return;
    }

    var BISHKEK = { lon: 74.59, lat: 42.87, label: mapContainer.dataset.bishkekLabel || 'Bishkek' };

    function loadMap(url) {
        return fetch(url)
            .then(function (response) { return response.json(); })
            .then(function (geo) {
                var features = geo.features || [];
                var coords = [];

                features.forEach(function (feature) {
                    var geometry = feature.geometry;
                    if (!geometry) return;

                    var addRing = function (ring) {
                        ring.forEach(function (coord) { coords.push(coord); });
                    };

                    if (geometry.type === 'Polygon') geometry.coordinates.forEach(addRing);
                    if (geometry.type === 'MultiPolygon') geometry.coordinates.forEach(function (polygon) { polygon.forEach(addRing); });
                });

                return { features: features, coords: coords };
            });
    }

    function makeProjection(coords, width, height, offsetX, offsetY, padding) {
        var xs = coords.map(function (coord) { return coord[0]; });
        var ys = coords.map(function (coord) { return coord[1]; });
        var x0 = Math.min.apply(null, xs);
        var x1 = Math.max.apply(null, xs);
        var y0 = Math.min.apply(null, ys);
        var y1 = Math.max.apply(null, ys);
        var lonSpan = x1 - x0 || 1;
        var latSpan = y1 - y0 || 1;
        var scale = Math.min((width - padding * 2) / lonSpan, (height - padding * 2) / latSpan);
        var marginX = offsetX + (width - lonSpan * scale) / 2;
        var marginY = offsetY + (height - latSpan * scale) / 2;

        return function (lon, lat) {
            return [
                marginX + (lon - x0) * scale,
                offsetY + height - (marginY - offsetY + (lat - y0) * scale),
            ];
        };
    }

    function buildPaths(data, layer, fillId, strokeId, projection) {
        layer.innerHTML = '';

        function ringToPath(ring) {
            return ring.map(function (coord, index) {
                var point = projection(coord[0], coord[1]);
                return (index === 0 ? 'M' : 'L') + point[0].toFixed(1) + ',' + point[1].toFixed(1);
            }).join(' ') + ' Z';
        }

        function featureToPath(feature) {
            var geometry = feature.geometry;
            if (!geometry) return '';
            if (geometry.type === 'Polygon') return geometry.coordinates.map(ringToPath).join(' ');
            if (geometry.type === 'MultiPolygon') return geometry.coordinates.map(function (polygon) { return polygon.map(ringToPath).join(' '); }).join(' ');
            return '';
        }

        data.features.forEach(function (feature) {
            var path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            path.setAttribute('d', featureToPath(feature));
            path.setAttribute('fill', 'url(#' + fillId + ')');
            path.setAttribute('stroke', 'url(#' + strokeId + ')');
            path.setAttribute('stroke-width', '1.6');
            layer.appendChild(path);
        });
    }

    function renderJourney(kgData) {
        var width = mapContainer.clientWidth;
        var height = mapContainer.clientHeight;

        mapSvg.setAttribute('viewBox', '0 0 ' + width + ' ' + height);

        var kgProjection = makeProjection(kgData.coords, width * 0.84, height * 0.62, width * 0.08, height * 0.2, 8);

        buildPaths(kgData, kgLayer, 'aboutKgFill', 'aboutKgStroke', kgProjection);

        var bishkek = kgProjection(BISHKEK.lon, BISHKEK.lat);

        cityDots.innerHTML = '';

        var ring = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        ring.setAttribute('cx', bishkek[0]);
        ring.setAttribute('cy', bishkek[1]);
        ring.setAttribute('r', '10');
        ring.setAttribute('fill', '#f6c15a');
        ring.setAttribute('fill-opacity', '0.14');
        cityDots.appendChild(ring);

        var dot = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        dot.setAttribute('cx', bishkek[0]);
        dot.setAttribute('cy', bishkek[1]);
        dot.setAttribute('r', '4.5');
        dot.setAttribute('fill', '#c74817');
        dot.setAttribute('fill-opacity', '0.92');
        cityDots.appendChild(dot);

        var text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        text.setAttribute('x', bishkek[0] + 14);
        text.setAttribute('y', bishkek[1] - 7);
        text.setAttribute('text-anchor', 'start');
        text.setAttribute('fill', '#8c4f22');
        text.setAttribute('fill-opacity', '0.82');
        text.setAttribute('font-size', '12');
        text.setAttribute('font-weight', '700');
        text.textContent = BISHKEK.label;
        cityDots.appendChild(text);
    }

    loadMap('/data/kg.json')
        .then(function (kgData) {
            var resizeTimer;

            renderJourney(kgData);

            window.addEventListener('resize', function () {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(function () { renderJourney(kgData); }, 120);
            });
        })
        .catch(function () {
            mapContainer.style.display = 'none';
        });
})();
</script>

<script>
(function () {
    var revealNodes = document.querySelectorAll('[data-reveal]');
    var countNodes = document.querySelectorAll('.stat-number[data-count-to]');

    if (!('IntersectionObserver' in window)) {
        revealNodes.forEach(function (node) { node.classList.add('is-revealed'); });
        countNodes.forEach(function (node) {
            var targetText = (node.getAttribute('data-count-to') || '0') + (node.getAttribute('data-count-suffix') || '');
            node.textContent = targetText;
        });
        return;
    }

    var revealObserver = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (!entry.isIntersecting) return;
            var delay = parseInt(entry.target.getAttribute('data-reveal-delay') || '0', 10);
            setTimeout(function () { entry.target.classList.add('is-revealed'); }, delay);
            revealObserver.unobserve(entry.target);
        });
    }, { threshold: 0.12, rootMargin: '0px 0px -28px 0px' });

    revealNodes.forEach(function (node) { revealObserver.observe(node); });

    var countObserver = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (!entry.isIntersecting) return;

            var element = entry.target;
            var target = parseInt(element.getAttribute('data-count-to'), 10);
            var suffix = element.getAttribute('data-count-suffix') || '';

            if (isNaN(target)) {
                countObserver.unobserve(element);
                return;
            }

            var startTime = null;
            var duration = 1500;

            function ease(progress) {
                return progress < 0.5 ? 2 * progress * progress : -1 + (4 - 2 * progress) * progress;
            }

            function step(timestamp) {
                if (!startTime) startTime = timestamp;

                var progress = Math.min((timestamp - startTime) / duration, 1);
                element.textContent = Math.floor(ease(progress) * target).toLocaleString() + suffix;

                if (progress < 1) {
                    requestAnimationFrame(step);
                    return;
                }

                element.textContent = target.toLocaleString() + suffix;
            }

            requestAnimationFrame(step);
            countObserver.unobserve(element);
        });
    }, { threshold: 0.45 });

    countNodes.forEach(function (node) { countObserver.observe(node); });
})();
</script>
@endsection
