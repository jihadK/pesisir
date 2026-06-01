<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    <title>QRIS — {{ $method->name }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #f5f7fa;
            color: #222;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }
        .card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
            max-width: 420px;
            width: 100%;
            padding: 24px 20px 28px;
            text-align: center;
        }
        h1 {
            font-size: 18px;
            margin: 0 0 4px;
            color: #1a1a1a;
        }
        .subtitle {
            font-size: 13px;
            color: #6b7280;
            margin: 0 0 18px;
        }
        .qris-img {
            display: block;
            width: 100%;
            max-width: 340px;
            margin: 0 auto 18px;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            background: #fff;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 14px 16px;
            font-size: 15px;
            font-weight: 600;
            border: none;
            border-radius: 10px;
            background: #16a34a;
            color: #fff;
            text-decoration: none;
            cursor: pointer;
            transition: background 0.15s;
        }
        .btn:hover { background: #15803d; }
        .btn:active { background: #166534; }
        .hint {
            font-size: 12px;
            color: #6b7280;
            margin-top: 12px;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>{{ $method->name }}</h1>
        <p class="subtitle">Scan QRIS berikut untuk membayar</p>

        <img class="qris-img"
             src="{{ $method->qris_image_display_url }}"
             alt="QRIS {{ $method->name }}" />

        <a class="btn"
           href="{{ $method->qris_image_display_url }}"
           download="qris-{{ \Illuminate\Support\Str::slug($method->code ?? $method->name) }}.png">
            <span style="font-size:18px;">⬇️</span> Download QRIS
        </a>

        <p class="hint">Setelah download, buka aplikasi e-wallet/m-banking lalu scan dari galeri.</p>
    </div>
</body>
</html>
