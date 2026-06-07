<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="csrf-token" content="{{ csrf_token() }}" />
<title>Masuk · Ganxie</title>
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet" />
<style>
  :root{
    --bg-0:#05080e;
    --bg-1:#0a1019;
    --ink:#eaf1fb;
    --ink-dim:#8fa0b6;
    --ink-faint:#5d6c82;
    --navy:#1f6ba8;
    --navy-deep:#0f4d7d;
    --gold:#f2b11e;
    --gold-soft:#f5c64a;
    --gold-deep:#d9971a;
    --field:rgba(255,255,255,.035);
    --field-focus:rgba(242,177,30,.06);
  }
  *{box-sizing:border-box;margin:0;padding:0}
  html,body{height:100%}
  body{font-family:'Manrope',system-ui,sans-serif;background:var(--bg-0);color:var(--ink);-webkit-font-smoothing:antialiased;overflow:hidden}

  .split{height:100vh;display:grid;grid-template-columns:1.04fr .96fr}

  /* ============ LEFT — BRAND ============ */
  .brand-side{position:relative;overflow:hidden;background:linear-gradient(150deg,#0d1726,#091018)}
  .hero-bg{position:absolute;inset:-4% -4% -4% -4%;width:108%;height:108%;
    background:url('{{ asset('images/ganxie/booth-bg.jpg') }}') center/cover no-repeat;
    animation:kenburns 26s ease-in-out infinite alternate}
  @keyframes kenburns{from{transform:scale(1) translate(0,0)}to{transform:scale(1.08) translate(-2%,-1.5%)}}
  .brand-overlay{position:absolute;inset:0;pointer-events:none;
    background:
      linear-gradient(105deg,rgba(7,11,18,.80) 0%,rgba(8,13,22,.46) 44%,rgba(8,13,22,.14) 74%,rgba(8,13,22,.34) 100%),
      radial-gradient(70% 55% at 28% 28%,rgba(8,12,20,.42),transparent 62%),
      linear-gradient(0deg,rgba(5,8,14,.72),transparent 42%);
  }
  .arc{position:absolute;border-radius:50%;border:1px solid rgba(242,177,30,.16);pointer-events:none}
  .arc.a1{width:680px;height:680px;right:-260px;top:-200px;border-color:rgba(242,177,30,.12)}
  .arc.a2{width:480px;height:480px;right:-160px;top:-110px;border-color:rgba(31,107,168,.22)}
  .arc.a3{width:300px;height:300px;left:-130px;bottom:-120px;border-color:rgba(242,177,30,.10)}
  .grain{position:absolute;inset:0;opacity:.05;pointer-events:none;mix-blend-mode:overlay;
    background-image:url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='160' height='160'><filter id='n'><feTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='2'/></filter><rect width='100%25' height='100%25' filter='url(%23n)'/></svg>");}

  .brand-content{position:absolute;inset:0;z-index:3;display:flex;flex-direction:column;justify-content:center;padding:48px 54px;pointer-events:none}
  .eyebrow{display:flex;align-items:center;gap:10px;font-size:11px;letter-spacing:3px;text-transform:uppercase;color:var(--gold-soft);font-weight:600}
  .eyebrow::after{content:"";height:1px;width:46px;background:linear-gradient(90deg,var(--gold-soft),transparent)}

  .logo-wrap{position:relative;width:164px;height:132px;flex:none;margin:24px 0 6px -6px;animation:floatY 7s ease-in-out infinite}
  @keyframes floatY{0%,100%{transform:translateY(0)}50%{transform:translateY(-7px)}}
  .logo-wrap::before{content:"";position:absolute;left:50%;top:46%;width:210px;height:210px;transform:translate(-50%,-50%);border-radius:50%;
    background:radial-gradient(circle,rgba(242,177,30,.16),rgba(31,107,168,.08) 45%,transparent 70%);filter:blur(10px);animation:breath 5.5s ease-in-out infinite;z-index:0}
  @keyframes breath{0%,100%{opacity:.5;transform:translate(-50%,-50%) scale(.92)}50%{opacity:1;transform:translate(-50%,-50%) scale(1.06)}}
  .logo-wrap::after{content:"";position:absolute;left:50%;top:46%;width:200px;height:200px;transform:translate(-50%,-50%);border-radius:50%;z-index:0;
    background:conic-gradient(from 0deg,transparent 0deg,rgba(242,177,30,0) 200deg,rgba(245,198,74,.5) 320deg,transparent 360deg);
    -webkit-mask:radial-gradient(closest-side,transparent 82%,#000 84%,#000 97%,transparent 100%);mask:radial-gradient(closest-side,transparent 82%,#000 84%,#000 97%,transparent 100%);
    animation:spin 9s linear infinite;opacity:.7}
  @keyframes spin{to{transform:translate(-50%,-50%) rotate(360deg)}}
  .logo-img,.logo-shine{position:absolute;inset:0;width:100%;height:100%;object-fit:contain;object-position:left center;z-index:1;animation:logoIn 1.1s cubic-bezier(.2,.85,.25,1) both .15s}
  @keyframes logoIn{from{transform:scale(.9);filter:blur(3px)}to{transform:none;filter:none}}
  .logo-img{opacity:1;filter:drop-shadow(0 8px 24px rgba(0,0,0,.7))}
  .logo-shine{z-index:2;pointer-events:none;
    -webkit-mask-image:url('{{ asset('images/ganxie/ganxie-logo.png') }}');mask-image:url('{{ asset('images/ganxie/ganxie-logo.png') }}');
    -webkit-mask-size:contain;mask-size:contain;-webkit-mask-repeat:no-repeat;mask-repeat:no-repeat;
    -webkit-mask-position:left center;mask-position:left center;
    background:linear-gradient(108deg,transparent 42%,rgba(255,249,228,.92) 50%,transparent 58%);
    background-size:260% 100%;background-repeat:no-repeat;mix-blend-mode:screen;animation:glint 6s ease-in-out infinite 1.1s}
  @keyframes glint{0%,62%{background-position:185% 0}100%{background-position:-130% 0}}

  .brand-copy{margin-top:26px}
  .brand-copy h2{font-family:'Cormorant Garamond',serif;font-weight:600;font-size:42px;line-height:1.08;letter-spacing:.2px;max-width:13ch}
  .brand-copy h2 .accent{color:var(--gold-soft);font-style:italic}
  .brand-copy p{color:var(--ink-dim);font-size:14.5px;line-height:1.6;margin-top:14px;max-width:34ch}

  .features{display:flex;flex-direction:column;gap:13px;margin-top:26px}
  .feat{display:flex;align-items:center;gap:13px;color:var(--ink);font-size:13.5px;font-weight:500}
  .feat .fic{width:38px;height:38px;border-radius:11px;flex:none;display:grid;place-items:center;color:var(--gold-soft);
    background:rgba(242,177,30,.08);border:1px solid rgba(242,177,30,.2)}
  .feat small{display:block;color:var(--ink-faint);font-size:11.5px;font-weight:400;margin-top:2px}

  .cats{display:flex;flex-wrap:wrap;gap:9px;margin-top:24px;pointer-events:none}
  .cat{display:inline-flex;align-items:center;gap:7px;font-size:12px;font-weight:600;letter-spacing:.3px;color:var(--ink);
    padding:7px 13px;border-radius:999px;background:rgba(255,255,255,.05);border:1px solid rgba(242,177,30,.22);backdrop-filter:blur(4px)}
  .cat svg{color:var(--gold-soft)}

  .brand-side::after{content:"";position:absolute;top:0;right:0;width:1px;height:100%;z-index:5;
    background:linear-gradient(180deg,transparent,rgba(242,177,30,.45),rgba(31,107,168,.3),transparent)}

  /* ============ RIGHT — FORM ============ */
  .form-side{position:relative;display:grid;place-items:center;padding:40px 28px;overflow-y:auto;
    background:radial-gradient(120% 80% at 80% -10%,rgba(31,107,168,.12),transparent 55%),linear-gradient(180deg,var(--bg-1),var(--bg-0) 70%)}
  .form-inner{width:100%;max-width:372px;animation:panelIn .9s cubic-bezier(.2,.8,.2,1) both .1s}
  @keyframes panelIn{from{transform:translateY(16px)}to{transform:none}}

  .welcome{font-family:'Cormorant Garamond',serif;font-weight:600;font-size:33px;letter-spacing:.3px}
  .welcome .accent{color:var(--gold-soft)}
  .sub{color:var(--ink-dim);font-size:13.5px;margin-top:7px;letter-spacing:.2px}

  form{margin-top:28px;display:flex;flex-direction:column;gap:16px}
  .field{position:relative}
  .field .lbl{display:block;font-size:11.5px;letter-spacing:.6px;text-transform:uppercase;color:var(--ink-faint);margin:0 0 7px 2px;font-weight:600}
  .input{width:100%;height:50px;border-radius:13px;background:var(--field);border:1px solid rgba(255,255,255,.08);color:var(--ink);font:inherit;font-size:15px;padding:0 44px;outline:none;transition:border-color .2s,background .2s,box-shadow .2s}
  .input::placeholder{color:var(--ink-faint)}
  .input:focus{border-color:rgba(242,177,30,.55);background:var(--field-focus);box-shadow:0 0 0 4px rgba(242,177,30,.10)}
  .field.invalid .input{border-color:rgba(232,90,76,.6);box-shadow:0 0 0 4px rgba(232,90,76,.10)}
  .ic{position:absolute;left:15px;top:38px;color:var(--ink-faint);display:flex;pointer-events:none;transition:color .2s}
  .field:focus-within .ic{color:var(--gold-soft)}
  .toggle{position:absolute;right:8px;top:30px;width:34px;height:34px;border:none;background:transparent;color:var(--ink-faint);cursor:pointer;border-radius:9px;display:grid;place-items:center;transition:color .2s,background .2s}
  .toggle:hover{color:var(--ink);background:rgba(255,255,255,.05)}
  .err{color:#e7766a;font-size:12px;margin:6px 2px 0;opacity:0;transform:translateY(-3px);transition:.2s}
  .field.invalid .err{opacity:1;transform:none}

  .row{display:flex;align-items:center;justify-content:space-between;margin-top:-2px}
  .remember{display:flex;align-items:center;gap:9px;cursor:pointer;color:var(--ink-dim);font-size:13px;user-select:none}
  .box{width:18px;height:18px;border-radius:6px;border:1.5px solid rgba(255,255,255,.22);display:grid;place-items:center;transition:.2s;background:rgba(255,255,255,.03)}
  .remember input{display:none}
  .remember input:checked+.box{background:linear-gradient(180deg,var(--gold-soft),var(--gold-deep));border-color:transparent}
  .box svg{opacity:0;transform:scale(.5);transition:.2s;color:#1a1205}
  .remember input:checked+.box svg{opacity:1;transform:none}
  .link{color:var(--gold-soft);font-size:13px;text-decoration:none;font-weight:600;transition:.2s}
  .link:hover{color:#ffd873}

  .btn{margin-top:6px;height:52px;border:none;border-radius:13px;cursor:pointer;font:inherit;font-weight:700;font-size:15.5px;letter-spacing:.3px;color:#1c1303;
    background:linear-gradient(180deg,#f8cf5f,#e8a417);position:relative;overflow:hidden;
    box-shadow:0 12px 26px -10px rgba(242,177,30,.6),0 1px 0 rgba(255,255,255,.4) inset;transition:transform .12s,box-shadow .2s,filter .2s;display:grid;place-items:center}
  .btn:hover{transform:translateY(-1px);box-shadow:0 18px 34px -10px rgba(242,177,30,.7)}
  .btn:active{transform:translateY(0)}
  .btn:disabled{cursor:default;filter:saturate(.7) brightness(.95)}
  .btn::after{content:"";position:absolute;top:0;left:-60%;width:40%;height:100%;background:linear-gradient(100deg,transparent,rgba(255,255,255,.55),transparent);transform:skewX(-18deg);animation:btnshine 4.5s ease-in-out infinite}
  @keyframes btnshine{0%,70%{left:-60%}100%{left:130%}}
  .btn .spinner{display:none}
  .btn.loading .label{visibility:hidden}
  .btn.loading .spinner{display:block;position:absolute}
  .spinner{width:24px;height:24px;border-radius:50%;
    background:conic-gradient(from 0deg,rgba(28,19,3,0) 0deg,rgba(28,19,3,.16) 170deg,#1c1303 330deg,rgba(28,19,3,0) 360deg);
    -webkit-mask:radial-gradient(closest-side,transparent 62%,#000 66%,#000 100%);
    mask:radial-gradient(closest-side,transparent 62%,#000 66%,#000 100%);
    animation:buttonSpin .9s linear infinite}
  @keyframes buttonSpin{to{transform:rotate(360deg)}}

  .divider{display:flex;align-items:center;gap:14px;color:var(--ink-faint);font-size:12px;letter-spacing:.5px;margin:20px 0 4px}
  .divider::before,.divider::after{content:"";height:1px;flex:1;background:linear-gradient(90deg,transparent,rgba(255,255,255,.12),transparent)}
  .social{width:100%;height:50px;border-radius:13px;border:1px solid rgba(255,255,255,.1);background:rgba(255,255,255,.025);color:var(--ink);font:inherit;font-weight:600;font-size:14px;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:11px;transition:.2s;text-decoration:none}
  .social:hover{background:rgba(255,255,255,.06);border-color:rgba(255,255,255,.18)}
  .foot{text-align:center;color:var(--ink-dim);font-size:13.5px;margin-top:22px}
  .foot a{color:var(--gold-soft);font-weight:700;text-decoration:none}
  .foot a:hover{color:#ffd873}

  .toast{position:fixed;left:50%;top:26px;transform:translateX(-50%) translateY(-20px);z-index:9;background:linear-gradient(180deg,rgba(22,30,44,.96),rgba(13,19,29,.96));border:1px solid rgba(242,177,30,.35);color:var(--ink);padding:13px 20px;border-radius:14px;font-size:14px;font-weight:600;box-shadow:0 20px 50px -18px rgba(0,0,0,.8);display:flex;align-items:center;gap:10px;opacity:0;transition:.35s cubic-bezier(.2,.8,.2,1);pointer-events:none}
  .toast.show{opacity:1;transform:translateX(-50%) translateY(0)}
  .toast .dot{width:22px;height:22px;border-radius:50%;background:linear-gradient(180deg,var(--gold-soft),var(--gold-deep));display:grid;place-items:center;color:#1a1205}

  /* ============ RESPONSIVE ============ */
  @media (min-width:861px) and (max-width:1180px){
    .brand-content{padding:40px 42px}
    .brand-copy h2{font-size:36px}
    .form-side{padding:36px 24px}
  }
  @media (min-width:861px) and (max-height:720px){ .features{display:none} }
  @media (min-width:861px) and (max-height:600px){
    .cats{margin-top:18px}
    .brand-copy h2{font-size:32px}
    .logo-wrap{width:140px;height:112px;margin-top:8px}
  }
  @media (max-width:860px){
    html,body{height:auto}
    body{overflow-y:auto}
    .split{grid-template-columns:1fr;height:auto;min-height:100dvh}
    .brand-side{min-height:auto}
    .brand-side::after{display:none}
    .brand-content{position:relative;inset:auto;justify-content:flex-start;padding:46px 40px 40px}
    .hero-bg{position:absolute}
    .arc.a3{display:none}
    .logo-wrap{margin:18px 0 4px -6px}
    .brand-copy{margin-top:20px}
    .brand-copy h2{font-size:34px;max-width:18ch}
    .brand-copy p{max-width:46ch}
    .features{display:none}
    .cats{margin-top:22px}
    .form-side{min-height:auto;padding:44px 28px 60px}
  }
  @media (max-width:560px){
    .brand-content{padding:38px 26px 34px}
    .eyebrow{font-size:10px;letter-spacing:2px}
    .logo-wrap{width:148px;height:118px}
    .brand-copy h2{font-size:29px;line-height:1.12}
    .brand-copy p{font-size:13.5px}
    .cat{font-size:11.5px;padding:6px 11px}
    .form-side{padding:38px 22px 56px}
    .welcome{font-size:29px}
    .input{height:52px}
    .btn,.social{height:54px}
  }
  @media (max-width:360px){
    .brand-content{padding:32px 20px 30px}
    .brand-copy h2{font-size:26px}
    .form-side{padding:34px 18px 50px}
  }
  @media (prefers-reduced-motion:reduce){*{animation-duration:.001s!important;animation-iteration-count:1!important}}
</style>
</head>
<body>

  {{-- success/status toast (e.g. after password reset) --}}
  @if (session('status'))
    <div class="toast show" id="statusToast">
      <span class="dot"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2"><path d="m5 12.5 4.5 4.5L19 7"/></svg></span>
      {{ session('status') }}
    </div>
  @endif

  <div class="split">

    {{-- ---------- LEFT: BRAND ---------- --}}
    <section class="brand-side">
      <div class="hero-bg"></div>
      <div class="brand-overlay"></div>
      <div class="arc a1"></div><div class="arc a2"></div><div class="arc a3"></div>
      <div class="grain"></div>

      <div class="brand-content">
        <div class="eyebrow">Dimsum · Mentai · Boba · Kopi</div>
        <div class="logo-wrap">
          <img class="logo-img" src="{{ asset('images/ganxie/ganxie-logo.png') }}" alt="Ganxie" onerror="this.onerror=null;this.src='{{ asset('images/ganxie-logo.png') }}';" />
          <div class="logo-shine"></div>
        </div>

        <div class="brand-copy">
          <h2>Satu booth, <span class="accent">banyak rasa</span> — atur tanpa ribet.</h2>
          <p>Dari dimsum &amp; mentai sampai boba dan kopi, kelola menu, pesanan, dan kasir dalam satu tempat.</p>
        </div>

        <div class="cats">
          <span class="cat"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M4 13h16M5 13a7 7 0 0 1 14 0"/><path d="M3.5 13v1.5a2 2 0 0 0 2 2h13a2 2 0 0 0 2-2V13"/><path d="M12 6v-.5M9.5 6.4 9.2 6M14.5 6.4l.3-.4"/></svg> Dimsum</span>
          <span class="cat"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M5 14c0-4 3-8 7-8s7 4 7 8a3 3 0 0 1-3 3H8a3 3 0 0 1-3-3Z"/><path d="M9 12.5c.6-.6 1.2-.6 1.8 0M13 12.5c.6-.6 1.2-.6 1.8 0"/></svg> Mentai</span>
          <span class="cat"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M7 8h10l-1 11a2 2 0 0 1-2 2h-4a2 2 0 0 1-2-2L7 8Z"/><path d="M9 5l1-2h4l1 2"/><circle cx="10" cy="17.5" r="1"/><circle cx="13.5" cy="18" r="1"/></svg> Boba</span>
          <span class="cat"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M5 9h12v5a4 4 0 0 1-4 4H9a4 4 0 0 1-4-4V9Z"/><path d="M17 10h1.5a2.5 2.5 0 0 1 0 5H17"/><path d="M9 5.5c-.4.5-.4 1 0 1.5M12.5 5c-.4.5-.4 1 0 1.5"/></svg> Kopi</span>
        </div>

        <div class="features">
          <div class="feat"><span class="fic"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M5 4h11a2 2 0 0 1 2 2v14l-4-2.2L10 20l-4-2.2L4 19V6"/><path d="M8 9h7M8 13h5"/></svg></span><div>Menu &amp; Stok Digital<small>Perbarui harga dan ketersediaan seketika</small></div></div>
          <div class="feat"><span class="fic"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/></svg></span><div>Pesanan Real-time<small>Dapur &amp; kasir selalu sinkron</small></div></div>
          <div class="feat"><span class="fic"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4 20V4M20 20H4"/><path d="M8 16v-3M12 16V8M16 16v-5"/></svg></span><div>Laporan Penjualan<small>Pantau performa harian dengan jelas</small></div></div>
        </div>
      </div>
    </section>

    {{-- ---------- RIGHT: FORM ---------- --}}
    <section class="form-side">
      <div class="form-inner">
        <h1 class="welcome">Selamat Datang <span class="accent">Kembali</span></h1>
        <p class="sub">Masuk untuk mengelola booth Ganxie Anda</p>

        <form method="POST" action="{{ route('login') }}">
          @csrf

          <div class="field @error('email') invalid @enderror">
            <label class="lbl" for="email">Email</label>
            <span class="ic"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3" y="5" width="18" height="14" rx="2.5"/><path d="m3.5 7 8.5 6 8.5-6"/></svg></span>
            <input class="input" id="email" type="email" name="email" value="{{ old('email') }}"
                   placeholder="nama@ganxie.id" required autofocus autocomplete="username" />
            <p class="err">@error('email'){{ $message }}@enderror</p>
          </div>

          <div class="field @error('password') invalid @enderror">
            <label class="lbl" for="password">Kata Sandi</label>
            <span class="ic"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="4.5" y="10.5" width="15" height="10" rx="2.5"/><path d="M8 10.5V8a4 4 0 0 1 8 0v2.5"/></svg></span>
            <input class="input" id="password" type="password" name="password"
                   placeholder="••••••••" required autocomplete="current-password" />
            <button type="button" class="toggle" id="togglePwd" aria-label="Tampilkan sandi">
              <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M3 12c1.5-3 5-7 9-7s7.5 4 9 7c-1.5 3-5 7-9 7s-7.5-4-9-7Z"/><circle cx="12" cy="12" r="2.6"/></svg>
            </button>
            <p class="err">@error('password'){{ $message }}@enderror</p>
          </div>

          <div class="row">
            <label class="remember">
              <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }} />
              <span class="box"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2"><path d="m5 12.5 4.5 4.5L19 7"/></svg></span>
              Ingat saya
            </label>
            @if (Route::has('password.request'))
              <a class="link" href="{{ route('password.request') }}">Lupa sandi?</a>
            @endif
          </div>

          <button class="btn" type="submit">
            <span class="label">Masuk</span>
            <span class="spinner"></span>
          </button>
        </form>

        @if (Route::has('register'))
          <p class="foot">Belum punya akun? <a href="{{ route('register') }}">Daftar sekarang</a></p>
        @endif
      </div>
    </section>
  </div>

  <script>
    // show / hide password
    const toggle = document.getElementById('togglePwd');
    const pwd = document.getElementById('password');
    const eyeOpen  = '<svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M3 12c1.5-3 5-7 9-7s7.5 4 9 7c-1.5 3-5 7-9 7s-7.5-4-9-7Z"/><circle cx="12" cy="12" r="2.6"/></svg>';
    const eyeOff   = '<svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M3 3l18 18M10.6 10.7a2 2 0 0 0 2.8 2.8M9.4 5.3A9.5 9.5 0 0 1 12 5c5 0 9 4.5 9 7 0 .9-.9 2.4-2.4 3.8M6.2 6.9C3.9 8.4 3 10.2 3 11c0 1.4 2.7 5.5 7.5 6.6"/></svg>';
    toggle.addEventListener('click', () => {
      const showing = pwd.type === 'text';
      pwd.type = showing ? 'password' : 'text';
      toggle.innerHTML = showing ? eyeOpen : eyeOff;
    });

    // loading state on submit (server handles the real auth)
    document.querySelector('form').addEventListener('submit', (e) => {
      const btn = e.target.querySelector('.btn');
      if (e.target.checkValidity()) btn.classList.add('loading');
    });

    // auto-hide status toast
    const st = document.getElementById('statusToast');
    if (st) setTimeout(() => st.classList.remove('show'), 3500);
  </script>
</body>
</html>
