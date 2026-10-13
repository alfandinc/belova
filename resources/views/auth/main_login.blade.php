@php
    $clinics = [
        'premiere' => [
            'name' => 'Premiere Belova',
            'logo' => asset('img/logo-premiere.png'),
            'bg' => asset('img/bg-premiere.jpg'),
            'accent' => '#2563eb',
            'accent2' => '#1e3a8a',
        ],
        'skin' => [
            'name' => 'Belova Skin',
            'logo' => asset('img/logo-belovaskin.png'),
            'bg' => asset('img/bg-belovaskin.jpg'),
            'accent' => '#db2777',
            'accent2' => '#831843',
        ],
        'dental' => [
            'name' => 'Belova Dental Care',
            'logo' => asset('img/logo-dental.png'),
            'bg' => asset('img/bg-dental.jpg'),
            'accent' => '#16a34a',
            'accent2' => '#14532d',
        ],
    ];
    $emotions = $emotionOptions ?? \App\Http\Controllers\AuthController::emotionCatalog();
    $oldClinic = old('clinic_choice');
    $oldEmotion = old('emotion');
    $heroClinic = $oldClinic ?: 'premiere';

    $hour = (int) now()->format('H');
    $greeting = $hour < 11 ? 'Selamat pagi' : ($hour < 15 ? 'Selamat siang' : ($hour < 18 ? 'Selamat sore' : 'Selamat malam'));
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <title>Login - SIM Klinik Belova</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="{{ asset('img/logo-favicon-belova.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --accent: {{ $clinics[$heroClinic]['accent'] }};
            --accent-2: {{ $clinics[$heroClinic]['accent2'] }};
            --text: #0f172a;
            --muted: #64748b;
            --border: #e2e8f0;
            --danger: #e11d48;
        }
        * { box-sizing: border-box; }
        html, body { margin: 0; min-height: 100%; }
        body {
            font-family: 'Plus Jakarta Sans', 'Segoe UI', system-ui, -apple-system, sans-serif;
            color: var(--text);
            background: #0b1220;
            -webkit-font-smoothing: antialiased;
        }
        button { font: inherit; }

        /* ---------- Full-screen company photo ---------- */
        .page-bg { position: fixed; inset: 0; z-index: -1; overflow: hidden; background: #0b1220; }
        .hero-bg {
            position: absolute; inset: -20px;
            background-size: cover;
            background-position: center;
            opacity: 0;
            transform: scale(1.06);
            transition: opacity .9s ease, transform 8s ease-out;
        }
        .hero-bg.active { opacity: 1; transform: scale(1); }
        .page-bg::after {
            content: '';
            position: absolute; inset: 0;
            background:
                linear-gradient(90deg, rgba(8,13,28,.88) 0%, rgba(8,13,28,.7) 45%, rgba(8,13,28,.5) 100%),
                radial-gradient(70% 90% at 0% 100%, color-mix(in srgb, var(--accent-2) 55%, transparent) 0%, transparent 70%);
        }

        /* ---------- Page grid: top bar / content / footer, all on one centred container ---------- */
        .shell {
            min-height: 100vh;
            max-width: 1200px;
            margin: 0 auto;
            padding: 32px 48px;
            display: grid;
            grid-template-rows: auto 1fr auto;
            gap: 32px;
        }
        .topbar { display: flex; align-items: center; justify-content: space-between; }
        .topbar img { height: 36px; display: block; }
        .clinic-tag {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 7px 14px;
            border-radius: 999px;
            background: rgba(255,255,255,.1);
            border: 1px solid rgba(255,255,255,.18);
            color: #fff;
            font-size: 13px; font-weight: 600;
        }
        .clinic-tag .dot { width: 7px; height: 7px; border-radius: 50%; background: #4ade80; box-shadow: 0 0 0 3px rgba(74,222,128,.25); }

        .layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 440px;
            gap: 80px;
            align-items: center;
        }

        /* ---------- Left: welcome ---------- */
        .intro { color: #fff; max-width: 560px; }
        .eyebrow {
            margin: 0 0 16px;
            font-size: 12px; font-weight: 700; letter-spacing: .16em; text-transform: uppercase;
            color: rgba(255,255,255,.65);
        }
        .intro h1 {
            margin: 0 0 20px;
            font-size: 52px; line-height: 1.08; font-weight: 800; letter-spacing: -.025em;
        }
        .intro h1 span { color: color-mix(in srgb, var(--accent) 35%, #fff); transition: color .6s; }
        .slogan {
            display: flex; align-items: center; gap: 12px;
            margin: 0;
            font-size: 17px; font-weight: 500;
            color: rgba(255,255,255,.85);
        }
        .slogan::before { content: ''; width: 32px; height: 2px; border-radius: 2px; background: var(--accent); flex: none; transition: background .6s; }
        .hero-dots { display: flex; gap: 6px; margin-top: 36px; }
        .hero-dots span { height: 4px; width: 18px; border-radius: 4px; background: rgba(255,255,255,.3); transition: width .4s, background .4s; }
        .hero-dots span.active { width: 36px; background: #fff; }

        /* ---------- Right: form card ---------- */
        .login-box {
            width: 100%;
            padding: 36px;
            border-radius: 24px;
            background: #fff;
            box-shadow: 0 30px 70px -25px rgba(0,0,0,.7);
        }
        .login-box h2 { margin: 0 0 6px; font-size: 26px; font-weight: 800; letter-spacing: -.02em; }
        .login-box .subtitle { margin: 0 0 28px; color: var(--muted); font-size: 14px; }

        .field { margin-bottom: 18px; }
        .field-label {
            display: flex; align-items: center; gap: 6px;
            font-size: 13px; font-weight: 600; color: #334155;
            margin-bottom: 8px;
        }
        .input-wrap { position: relative; }
        .input-wrap .icon {
            position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
            width: 18px; height: 18px; color: #94a3b8;
            pointer-events: none; transition: color .2s;
        }
        .input {
            width: 100%; height: 48px;
            padding: 0 14px 0 42px;
            font: inherit; font-size: 14px;
            border: 1px solid var(--border); border-radius: 12px;
            background: #f8fafc; color: var(--text);
            outline: none;
            transition: border-color .2s, box-shadow .2s, background .2s;
        }
        .input::placeholder { color: #a3aec0; }
        .input:focus { background: #fff; border-color: var(--accent); box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent) 15%, transparent); }
        .input-wrap:focus-within .icon { color: var(--accent); }
        .password-wrap .input { padding-right: 46px; }
        .toggle-password {
            position: absolute; right: 5px; top: 50%; transform: translateY(-50%);
            width: 38px; height: 38px;
            display: grid; place-items: center;
            border: 0; border-radius: 10px; background: none; color: #94a3b8;
            cursor: pointer; transition: color .2s;
        }
        .toggle-password:hover { color: var(--text); }
        .toggle-password svg { width: 18px; height: 18px; }
        .toggle-password .eye-off { display: none; }
        .toggle-password.showing .eye { display: none; }
        .toggle-password.showing .eye-off { display: block; }

        .clinic-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
        .emotion-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 8px; }
        .clinic-choice, .emotion-choice {
            display: flex; align-items: center; justify-content: center;
            border: 1px solid var(--border); border-radius: 12px;
            background: #fff; cursor: pointer;
            transition: border-color .2s, box-shadow .2s, background .2s;
        }
        .clinic-choice { height: 60px; padding: 8px 10px; }
        .clinic-choice img { max-width: 100%; height: 34px; object-fit: contain; transition: filter .2s, opacity .2s; }
        .emotion-choice { height: 44px; }
        .emotion-choice .emoji { font-size: 20px; line-height: 1; transition: filter .2s, opacity .2s, transform .2s; }
        .clinic-choice:hover, .emotion-choice:hover { border-color: #cbd5e1; }
        .clinic-choice.active, .emotion-choice.active {
            border-color: var(--accent);
            box-shadow: 0 0 0 1px var(--accent);
            background: color-mix(in srgb, var(--accent) 5%, #fff);
        }
        .emotion-choice.active .emoji { transform: scale(1.15); }
        /* once one is picked, the others step back */
        .clinic-grid:has(.active) .clinic-choice:not(.active):not(:hover) img,
        .emotion-grid:has(.active) .emotion-choice:not(.active):not(:hover) .emoji { filter: grayscale(1); opacity: .5; }
        .mood-picked { color: var(--accent); font-weight: 700; }
        .mood-picked:not(:empty)::before { content: '\00b7'; color: #94a3b8; margin-right: 6px; }

        .alert {
            display: flex; align-items: flex-start; gap: 10px;
            padding: 11px 14px; margin-bottom: 18px;
            border-radius: 12px;
            background: #fff1f2; color: #be123c;
            font-size: 13px; font-weight: 500;
            animation: fade-in .25s ease;
        }
        .alert[hidden] { display: none; }
        .alert svg { width: 18px; height: 18px; flex: none; margin-top: 1px; }
        .field-error { display: none; color: var(--danger); font-size: 12px; font-weight: 500; margin-top: 6px; }
        .field.invalid .field-error { display: block; animation: fade-in .2s ease; }
        .field.invalid .input, .field.invalid .clinic-choice, .field.invalid .emotion-choice { border-color: #fda4af; }

        .btn-login {
            width: 100%; height: 50px;
            margin-top: 10px;
            display: flex; align-items: center; justify-content: center; gap: 10px;
            border: 0; border-radius: 12px;
            background: var(--accent); color: #fff;
            font-weight: 700; font-size: 15px;
            cursor: pointer;
            transition: background .3s, filter .2s;
        }
        .btn-login:hover { filter: brightness(1.08); }
        .btn-login .arrow { width: 18px; height: 18px; transition: transform .2s; }
        .btn-login:hover .arrow { transform: translateX(3px); }
        .btn-login:disabled { cursor: wait; filter: none; }
        .btn-login .spinner {
            display: none; width: 18px; height: 18px;
            border: 2.5px solid rgba(255,255,255,.35); border-top-color: #fff; border-radius: 50%;
            animation: spin .7s linear infinite;
        }
        .btn-login.loading .spinner { display: inline-block; }
        .btn-login.loading .arrow { display: none; }

        /* while the request runs: fields stay visible but can't be changed */
        form.busy .field { pointer-events: none; opacity: .6; transition: opacity .2s; }

        .page-footer { display: flex; justify-content: space-between; gap: 16px; font-size: 12px; color: rgba(255,255,255,.55); }

        @keyframes spin { to { transform: rotate(360deg); } }
        @keyframes fade-in { from { opacity: 0; transform: translateY(-4px); } to { opacity: 1; transform: none; } }
        @keyframes rise { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }
        .shake { animation: shake .4s ease; }
        @keyframes shake { 20%, 60% { transform: translateX(-7px); } 40%, 80% { transform: translateX(7px); } }
        .reveal { animation: rise .6s cubic-bezier(.22,1,.36,1) both; animation-delay: calc(var(--i, 0) * 70ms + 100ms); }

        /* Large monitors: same layout, everything one step bigger */
        @media (min-width: 1600px) {
            .shell { max-width: 1360px; padding: 40px 56px; }
            .layout { grid-template-columns: minmax(0, 1fr) 480px; gap: 112px; }
            .intro { max-width: 640px; }
            .intro h1 { font-size: 64px; }
            .slogan { font-size: 19px; }
            .login-box { padding: 44px; }
            .login-box h2 { font-size: 30px; }
            .input { height: 52px; font-size: 15px; }
            .clinic-choice { height: 66px; }
            .clinic-choice img { height: 38px; }
            .emotion-choice { height: 48px; }
            .btn-login { height: 54px; }
        }
        @media (max-width: 1100px) {
            .shell { padding: 28px 32px; }
            .layout { grid-template-columns: minmax(0, 1fr) 420px; gap: 48px; }
            .intro h1 { font-size: 42px; }
        }
        /* Tablet/phone: one column, welcome above the card */
        @media (max-width: 860px) {
            .page-bg::after {
                background:
                    linear-gradient(180deg, rgba(8,13,28,.75) 0%, rgba(8,13,28,.6) 40%, rgba(8,13,28,.85) 100%),
                    radial-gradient(120% 50% at 0% 20%, color-mix(in srgb, var(--accent-2) 50%, transparent) 0%, transparent 70%);
            }
            .shell { padding: 18px 16px; gap: 20px; }
            .topbar img { height: 28px; }
            .clinic-tag { padding: 5px 11px; font-size: 12px; }
            .layout { grid-template-columns: 1fr; gap: 20px; align-items: start; align-content: start; justify-items: center; }
            .intro, .login-box { width: 100%; max-width: 480px; }
            .eyebrow { font-size: 10px; margin-bottom: 8px; }
            .intro h1 { font-size: 28px; margin-bottom: 10px; }
            .intro h1 br { display: none; }
            .slogan { font-size: 14px; }
            .slogan::before { width: 20px; }
            .hero-dots { display: none; }
            .login-box { padding: 24px 20px; border-radius: 20px; }
            .login-box h2 { font-size: 22px; }
            .login-box .subtitle { font-size: 13px; margin-bottom: 20px; }
            .field { margin-bottom: 16px; }
            .page-footer { justify-content: center; }
            .page-footer .footer-slogan { display: none; }
        }
        @media (max-width: 400px) {
            .intro h1 { font-size: 24px; }
            .login-box { padding: 22px 16px; }
            .clinic-choice img { height: 28px; }
        }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: .01ms !important; animation-delay: 0ms !important; transition-duration: .01ms !important; }
        }
    </style>
</head>
<body>
<div class="page-bg" aria-hidden="true">
    @foreach($clinics as $key => $clinic)
        <div class="hero-bg {{ $heroClinic === $key ? 'active' : '' }}"
             data-clinic="{{ $key }}"
             style="background-image:url('{{ $clinic['bg'] }}')"></div>
    @endforeach
</div>

<div class="shell">
    <header class="topbar">
        <img src="{{ asset('img/logo-belovacorp-bw.png') }}" alt="Belova Corp">
        <span class="clinic-tag"><span class="dot"></span><span id="hero-clinic-name">{{ $clinics[$heroClinic]['name'] }}</span></span>
    </header>

    <div class="layout">
        <section class="intro reveal" style="--i:0">
            <p class="eyebrow">Sistem Informasi Manajemen</p>
            <h1>Welcome to <br><span>SIM Klinik Belova</span></h1>
            <p class="slogan">Bertumbuh Bersama dengan Niat Baik</p>
            <div class="hero-dots">
                @foreach($clinics as $key => $clinic)
                    <span data-clinic="{{ $key }}" class="{{ $heroClinic === $key ? 'active' : '' }}"></span>
                @endforeach
            </div>
        </section>

        <main class="login-box reveal" style="--i:1">
            <h2>{{ $greeting }} 👋</h2>
            <p class="subtitle">Masuk untuk melanjutkan ke SIM Klinik Belova.</p>

            <div class="alert" id="login-alert" role="alert" @if (!$errors->any()) hidden @endif>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
                <span id="login-alert-text">{{ $errors->first() }}</span>
            </div>

            <form method="POST" action="{{ route('login') }}" id="login-form" novalidate>
                @csrf

                <div class="field" id="field-clinic">
                    <span class="field-label">Klinik</span>
                    <div class="clinic-grid">
                        @foreach($clinics as $key => $clinic)
                            <button type="button" class="clinic-choice {{ $oldClinic === $key ? 'active' : '' }}"
                                    data-clinic="{{ $key }}" data-name="{{ $clinic['name'] }}"
                                    data-accent="{{ $clinic['accent'] }}" data-accent2="{{ $clinic['accent2'] }}"
                                    title="{{ $clinic['name'] }}" aria-pressed="{{ $oldClinic === $key ? 'true' : 'false' }}">
                                <img src="{{ $clinic['logo'] }}" alt="{{ $clinic['name'] }}">
                            </button>
                        @endforeach
                    </div>
                    <input type="hidden" name="clinic_choice" id="clinic_choice_input" value="{{ $oldClinic }}">
                    <div class="field-error">Pilih klinik terlebih dahulu.</div>
                </div>

                <div class="field" id="field-email">
                    <label class="field-label" for="email">Email</label>
                    <div class="input-wrap">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/></svg>
                        <input type="email" class="input" id="email" name="email" value="{{ old('email') }}"
                               placeholder="nama@belova.id" autocomplete="username" required autofocus>
                    </div>
                    <div class="field-error">Masukkan email yang valid.</div>
                </div>

                <div class="field" id="field-password">
                    <label class="field-label" for="password">Password</label>
                    <div class="input-wrap password-wrap">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 018 0v4"/></svg>
                        <input type="password" class="input" id="password" name="password" placeholder="Masukkan password" autocomplete="current-password" required>
                        <button type="button" class="toggle-password" id="toggle-password" aria-label="Tampilkan password">
                            <svg class="eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg class="eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.6 5.1A10.8 10.8 0 0112 5c6.5 0 10 7 10 7a17 17 0 01-3.2 4.2M6.6 6.6A17 17 0 002 12s3.5 7 10 7a10 10 0 005.4-1.6M9.9 9.9a3 3 0 004.2 4.2M3 3l18 18"/></svg>
                        </button>
                    </div>
                    <div class="field-error">Masukkan password.</div>
                </div>

                <div class="field" id="field-emotion">
                    <span class="field-label">Mood hari ini <span class="mood-picked" id="mood-picked">{{ $oldEmotion && isset($emotions[$oldEmotion]) ? $emotions[$oldEmotion]['label'] : '' }}</span></span>
                    <div class="emotion-grid">
                        @foreach($emotions as $key => $emotion)
                            <button type="button" class="emotion-choice {{ $oldEmotion === $key ? 'active' : '' }}"
                                    data-emotion="{{ $key }}" data-label="{{ $emotion['label'] }}" title="{{ $emotion['label'] }}" aria-label="{{ $emotion['label'] }}" aria-pressed="{{ $oldEmotion === $key ? 'true' : 'false' }}">
                                <span class="emoji">{{ $emotion['emoji'] }}</span>
                            </button>
                        @endforeach
                    </div>
                    <input type="hidden" name="emotion" id="emotion_input" value="{{ $oldEmotion }}">
                    <div class="field-error">Pilih mood Anda.</div>
                </div>

                <button type="submit" class="btn-login" id="login-btn">
                    <span class="spinner" aria-hidden="true"></span>
                    <span class="btn-text">Login</span>
                    <svg class="arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                </button>
            </form>
        </main>
    </div>

    <footer class="page-footer">
        <span>&copy; {{ date('Y') }} Belova Corp</span>
        <span class="footer-slogan">Premiere Belova &middot; Belova Skin &middot; Belova Dental Care</span>
    </footer>
</div>

<script>
(function () {
    var form = document.getElementById('login-form');
    var clinicInput = document.getElementById('clinic_choice_input');
    var emotionInput = document.getElementById('emotion_input');
    var STORAGE_KEY = 'belova_last_clinic';

    function selectClinic(key) {
        var btn = document.querySelector('.clinic-choice[data-clinic="' + key + '"]');
        if (!btn) return;
        clinicInput.value = key;
        document.querySelectorAll('.clinic-choice').forEach(function (b) {
            b.classList.toggle('active', b === btn);
            b.setAttribute('aria-pressed', b === btn ? 'true' : 'false');
        });
        document.querySelectorAll('.hero-bg, .hero-dots span').forEach(function (el) {
            el.classList.toggle('active', el.dataset.clinic === key);
        });
        document.getElementById('hero-clinic-name').textContent = btn.dataset.name;
        document.documentElement.style.setProperty('--accent', btn.dataset.accent);
        document.documentElement.style.setProperty('--accent-2', btn.dataset.accent2);
        document.getElementById('field-clinic').classList.remove('invalid');
        try { localStorage.setItem(STORAGE_KEY, key); } catch (e) {}
    }

    document.querySelectorAll('.clinic-choice').forEach(function (btn) {
        btn.addEventListener('click', function () { selectClinic(btn.dataset.clinic); });
    });

    document.querySelectorAll('.emotion-choice').forEach(function (btn) {
        btn.addEventListener('click', function () {
            emotionInput.value = btn.dataset.emotion;
            document.getElementById('mood-picked').textContent = btn.dataset.label;
            document.querySelectorAll('.emotion-choice').forEach(function (b) {
                b.classList.toggle('active', b === btn);
                b.setAttribute('aria-pressed', b === btn ? 'true' : 'false');
            });
            document.getElementById('field-emotion').classList.remove('invalid');
        });
    });

    document.getElementById('toggle-password').addEventListener('click', function () {
        var pwd = document.getElementById('password');
        var show = pwd.type === 'password';
        pwd.type = show ? 'text' : 'password';
        this.classList.toggle('showing', show);
        this.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');
    });

    ['email', 'password'].forEach(function (id) {
        document.getElementById(id).addEventListener('input', function () {
            document.getElementById('field-' + id).classList.remove('invalid');
        });
    });

    var btn = document.getElementById('login-btn');
    var btnText = btn.querySelector('.btn-text');
    var alertBox = document.getElementById('login-alert');
    var alertText = document.getElementById('login-alert-text');
    var box = document.querySelector('.login-box');

    function setLoading(on, text) {
        btn.disabled = on;
        btn.classList.toggle('loading', on);
        form.classList.toggle('busy', on);
        btnText.textContent = text || (on ? 'Memeriksa...' : 'Login');
    }

    function shake() {
        box.classList.remove('shake');
        void box.offsetWidth; // restart the animation on repeated failures
        box.classList.add('shake');
    }

    function showError(message) {
        alertText.textContent = message;
        alertBox.hidden = false;
        shake();
    }

    function send(retried) {
        return fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        }).then(function (res) {
            return res.json().catch(function () { return {}; }).then(function (data) {
                // Expired CSRF token: take the fresh one and retry once, invisibly
                if (res.status === 419 && data.token && !retried) {
                    form.querySelector('input[name="_token"]').value = data.token;
                    return send(true);
                }
                if (res.ok && data.redirect) {
                    setLoading(true, 'Berhasil, mengalihkan...');
                    window.location.href = data.redirect;
                    return;
                }
                var message = data.message || 'Login gagal, silakan coba lagi.';
                if (data.errors) {
                    var first = Object.keys(data.errors)[0];
                    message = data.errors[first][0];
                }
                var err = new Error(message);
                err.fromServer = true;
                throw err;
            });
        });
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var email = document.getElementById('email');
        var password = document.getElementById('password');
        var checks = {
            'field-clinic': !!clinicInput.value,
            'field-email': email.value.trim() !== '' && email.checkValidity(),
            'field-password': password.value !== '',
            'field-emotion': !!emotionInput.value
        };
        var valid = true;
        Object.keys(checks).forEach(function (id) {
            document.getElementById(id).classList.toggle('invalid', !checks[id]);
            if (!checks[id]) valid = false;
        });
        if (!valid) {
            shake();
            return;
        }

        alertBox.hidden = true;
        setLoading(true);
        send(false).catch(function (err) {
            setLoading(false);
            showError(err && err.fromServer
                ? err.message
                : 'Tidak dapat terhubung ke server. Periksa koneksi Anda.');
            password.value = '';
            password.focus();
        });
    });

    // Page restored from the back/forward cache: reset the loading state
    window.addEventListener('pageshow', function (e) {
        if (e.persisted) setLoading(false);
    });

    // Restore the clinic from the failed attempt, otherwise the one used last time on this device
    var initial = clinicInput.value;
    if (!initial) {
        try { initial = localStorage.getItem(STORAGE_KEY); } catch (e) {}
    }
    if (initial) selectClinic(initial);
})();
</script>
</body>
</html>
