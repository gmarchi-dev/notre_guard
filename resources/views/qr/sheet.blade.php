<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} - Notre Guard</title>
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
            background: #f1f5f9;
            color: #0f172a;
        }
        .toolbar {
            padding: 16px 24px;
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            gap: 16px;
            align-items: center;
        }
        .toolbar h1 { font-size: 16px; margin: 0; font-weight: 600; }
        .toolbar button {
            margin-left: auto;
            padding: 8px 16px;
            font-size: 14px;
            border: 0;
            border-radius: 6px;
            background: #2563eb;
            color: #fff;
            cursor: pointer;
        }
        .tag {
            width: 148mm;
            margin: 24px auto;
            padding: 16mm 12mm;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            text-align: center;
            page-break-after: always;
        }
        .tag:last-child { page-break-after: auto; }
        .heading {
            font-size: 12px;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 6mm;
        }
        .code { font-size: 30px; font-weight: 700; margin: 0 0 2mm; }
        .name { font-size: 15px; color: #475569; margin: 0 0 8mm; }
        .qr svg { width: 62mm; height: 62mm; }
        .footer {
            margin-top: 8mm;
            font-size: 10px;
            color: #94a3b8;
            font-family: ui-monospace, monospace;
        }
        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .tag { margin: 0; border: 0; border-radius: 0; width: auto; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <h1>{{ $title }} - {{ count($tags) }} etiqueta(s)</h1>
        <button onclick="window.print()">Imprimir</button>
    </div>

    @foreach ($tags as $tag)
        <div class="tag">
            <div class="heading">{{ $tag['heading'] }}</div>
            <p class="code">{{ $tag['title'] }}</p>
            <p class="name">{{ $tag['subtitle'] }}</p>
            <div class="qr">{!! QrCode::size(240)->margin(1)->errorCorrection('Q')->generate($tag['payload']) !!}</div>
            <div class="footer">{{ $tag['payload'] }}</div>
        </div>
    @endforeach
</body>
</html>
