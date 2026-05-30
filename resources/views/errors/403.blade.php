<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0b0f1a">
    <title>Akses Ditolak (403) - Ganxie</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #0b0f1a;
            color: #e2e8f0;
            padding: 24px;
        }
        .card {
            width: 100%;
            max-width: 420px;
            text-align: center;
            background: #111827;
            border: 1px solid #1f2937;
            border-radius: 20px;
            padding: 36px 28px;
            box-shadow: 0 24px 48px -24px rgba(0,0,0,.6);
        }
        .logo {
            width: 84px; height: 84px;
            margin: 0 auto 18px;
            object-fit: contain;
        }
        .code {
            font-size: 13px;
            font-weight: 800;
            letter-spacing: .18em;
            text-transform: uppercase;
            color: #f59e0b;
            margin-bottom: 8px;
        }
        h1 { font-size: 22px; font-weight: 800; color: #fff; margin-bottom: 10px; }
        p { font-size: 14px; line-height: 1.6; color: #94a3b8; margin-bottom: 24px; }
        .btn {
            display: block;
            width: 100%;
            padding: 13px 16px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            border: none;
            cursor: pointer;
            margin-top: 12px;
            transition: opacity .15s ease;
        }
        .btn:hover { opacity: .9; }
        .btn-primary { background: linear-gradient(135deg, #2563eb, #1d4ed8); color: #fff; }
        .btn-ghost { background: transparent; border: 1px solid #334155; color: #cbd5e1; }
    </style>
</head>

<body>
    <div class="card">
        <img src="{{ asset('images/ganxie-mark.png') }}" alt="Ganxie" class="logo">
        <div class="code">Error 403</div>
        <h1>Akses Ditolak</h1>
        <p>
            Akun kamu tidak punya izin membuka halaman ini.
            Biasanya ini terjadi kalau aplikasi yang dibuka tidak sesuai dengan role akun
            (misal akun kasir membuka aplikasi Admin).
            Tekan tombol di bawah untuk kembali ke halaman yang benar.
        </p>

        <a href="{{ auth()->check() ? route('home') : route('login') }}" class="btn btn-primary">Ke Halaman Saya</a>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-ghost">Login dengan Akun Lain</button>
        </form>
    </div>
</body>

</html>
