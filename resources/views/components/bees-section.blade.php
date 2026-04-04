{{-- resources/views/components/bees-section.blade.php --}}
<section class="relative w-full overflow-hidden bg-honey-cream">

    <img src="{{ asset('images/Rectangle.png') }}"
         alt=""
         aria-hidden="true"
         class="pointer-events-none absolute inset-0 h-full w-full object-contain object-center select-none">

    <style>
        /* ── Bee float animations ───────────────────────────────────── */
        @keyframes bee-float-1 {
            0%   { transform: translate(0,    0)    rotate(0deg);   }
            25%  { transform: translate(18px, -22px) rotate(8deg);  }
            50%  { transform: translate(8px,  -40px) rotate(-5deg); }
            75%  { transform: translate(-12px,-18px) rotate(10deg); }
            100% { transform: translate(0,    0)    rotate(0deg);   }
        }
        @keyframes bee-float-2 {
            0%   { transform: translate(0,    0)    rotate(0deg);   }
            30%  { transform: translate(-20px, 14px) rotate(-12deg);}
            60%  { transform: translate(10px,  28px) rotate(6deg);  }
            100% { transform: translate(0,    0)    rotate(0deg);   }
        }
        @keyframes bee-float-3 {
            0%   { transform: translate(0, 0)       rotate(0deg);   }
            20%  { transform: translate(22px, 12px)  rotate(15deg); }
            55%  { transform: translate(-8px, 30px)  rotate(-8deg); }
            80%  { transform: translate(14px, -10px) rotate(5deg);  }
            100% { transform: translate(0, 0)       rotate(0deg);   }
        }
        @keyframes bee-float-4 {
            0%   { transform: translate(0,    0)     rotate(0deg);  }
            40%  { transform: translate(-15px,-25px)  rotate(-10deg);}
            70%  { transform: translate(20px, -15px)  rotate(12deg);}
            100% { transform: translate(0,    0)     rotate(0deg);  }
        }
        @keyframes bee-float-5 {
            0%   { transform: translate(0,0)         rotate(0deg);  }
            35%  { transform: translate(10px, 20px)  rotate(-15deg);}
            65%  { transform: translate(-18px, 8px)  rotate(8deg);  }
            100% { transform: translate(0,0)         rotate(0deg);  }
        }

        /* ── Individual bee rules ───────────────────────────────────── */
        .bee { position: absolute; pointer-events: none; user-select: none; will-change: transform; }

        .bee-1  { animation: bee-float-1 10s ease-in-out infinite;        }
        .bee-2  { animation: bee-float-2 13s ease-in-out -3s infinite;    }
        .bee-3  { animation: bee-float-3  9s ease-in-out -1s infinite;    }
        .bee-4  { animation: bee-float-4 15s ease-in-out -6s infinite;    }
        .bee-5  { animation: bee-float-5 11s ease-in-out -2s infinite;    }
        .bee-6  { animation: bee-float-2 17s ease-in-out -8s infinite;    }
        .bee-7  { animation: bee-float-3 12s ease-in-out -4s infinite;    }
        .bee-8  { animation: bee-float-1 14s ease-in-out -5s infinite;    }
    </style>

    {{-- ── Bee swarm ──────────────────────────────────────────────── --}}
    <div class="absolute inset-0" aria-hidden="true">

        {{-- left edge, upper third --}}
        <img src="{{ asset('images/bee.png') }}" alt="" width="57" height="46"
             class="bee bee-1" style="left: 3%;  top: 18%;">

        {{-- left edge, lower third --}}
        <img src="{{ asset('images/bee.png') }}" alt="" width="57" height="46"
             class="bee bee-2" style="left: 5%;  top: 62%;">

        {{-- top-centre, slightly left --}}
        <img src="{{ asset('images/bee.png') }}" alt="" width="57" height="46"
             class="bee bee-3" style="left: 38%; top:  8%;">

        {{-- right edge, upper third --}}
        <img src="{{ asset('images/bee.png') }}" alt="" width="57" height="46"
             class="bee bee-4" style="right: 6%; top: 22%;">

        {{-- right edge, mid --}}
        <img src="{{ asset('images/bee.png') }}" alt="" width="57" height="46"
             class="bee bee-5" style="right: 4%; top: 55%;">

        {{-- bottom left --}}
        <img src="{{ asset('images/bee.png') }}" alt="" width="57" height="46"
             class="bee bee-6" style="left: 14%; bottom: 10%;">

        {{-- bottom right --}}
        <img src="{{ asset('images/bee.png') }}" alt="" width="57" height="46"
             class="bee bee-7" style="right: 18%; bottom: 12%;">

        {{-- centre right, mid-height --}}
        <img src="{{ asset('images/bee.png') }}" alt="" width="57" height="46"
             class="bee bee-8" style="right: 30%; top: 40%;">
    </div>

    {{-- ── Content slot (z-index above bees) ─────────────────────── --}}
    <div class="relative z-10 max-w-[1300px] mx-auto px-6 py-24 text-center">
        {{ $slot ?? '' }}
    </div>

</section>
