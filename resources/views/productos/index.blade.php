<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Catálogo – Productos</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <style>
        :root{
            --bg:#050816;
            --card:#0f172a;
            --muted:#9ca3af;
            --line:#1f2937;
            --accent:#22c55e;
            --accent2:#38bdf8;
        }

        * { box-sizing: border-box; }
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: radial-gradient(circle at top, #0b122a 0%, var(--bg) 45%, #03040a 100%);
            color:#f9fafb;
            margin:0;
        }

        /* Header */
        .topbar{
            position: sticky;
            top: 0;
            z-index: 50;
            backdrop-filter: blur(10px);
            background: rgba(5,8,22,0.72);
            border-bottom: 1px solid rgba(31,41,55,0.7);
        }

        .topbar-inner{
            max-width: 1240px;
            margin: 0 auto;
            padding: 14px 18px;
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap: 14px;
        }

        .brand{
            display:flex;
            align-items:center;
            gap: 12px;
            min-width: 220px;
        }

        .brand img{
            width: 40px;
            height: 40px;
            object-fit: contain;
            border-radius: 10px;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(31,41,55,0.9);
            padding: 6px;
        }

        .brand h1{
            font-size: 1rem;
            margin: 0;
            line-height: 1.1;
        }

        .brand small{
            display:block;
            color: var(--muted);
            font-size: .78rem;
            margin-top: 2px;
        }

        .right-info{
            display:flex;
            align-items:center;
            gap: 10px;
            color: var(--muted);
            font-size: .85rem;
            white-space: nowrap;
        }

        /* Container */
        .container {
            max-width: 1240px;
            margin: 0 auto;
            padding: 18px;
        }

        /* Search */
        .searchbar{
            display:flex;
            align-items:center;
            gap: 10px;
            margin: 16px 0 18px;
            flex-wrap: wrap;
        }

        .input{
            flex: 1;
            min-width: 240px;
            background: rgba(2,6,23,0.9);
            border: 1px solid rgba(31,41,55,0.9);
            color: #e5e7eb;
            padding: 10px 12px;
            border-radius: 12px;
            outline: none;
        }

        .input::placeholder{ color:#6b7280; }

        .btn{
            background: linear-gradient(135deg, var(--accent2), #6366f1);
            border: 0;
            color: #fff;
            padding: 10px 14px;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
        }

        .btn:hover{
            filter: brightness(1.08);
        }

        .btn-clear{
            background: transparent;
            border: 1px solid rgba(31,41,55,0.9);
            color: #e5e7eb;
            padding: 10px 14px;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
        }

        .btn-clear:hover{
            border-color: rgba(34,197,94,0.9);
            color: #bbf7d0;
        }

        /* Grid */
        .grid {
            display:grid;
            grid-template-columns: repeat(auto-fill,minmax(230px,1fr));
            gap: 18px;
        }

        .card {
            background: radial-gradient(circle at top left, #111827 0, var(--card) 55%);
            border-radius: 16px;
            padding: 12px;
            border: 1px solid rgba(31,41,55,0.9);
            display:flex;
            flex-direction:column;
            height:100%;
            transition: transform .15s ease, border-color .15s ease, box-shadow .15s ease;
        }

        .card:hover{
            transform: translateY(-3px);
            border-color: rgba(34,197,94,0.85);
            box-shadow: 0 18px 55px rgba(0,0,0,0.35);
        }

        .thumb {
            width:100%;
            height: 210px;
            border-radius: 14px;
            background: rgba(2,6,23,0.55);
            border: 1px solid rgba(31,41,55,0.7);
            overflow: hidden;
            display:flex;
            align-items:center;
            justify-content:center;
        }

        .thumb img{
            width:100%;
            height:100%;
            object-fit: contain;
            padding: 10px;
            background: #fff;
        }

        .badge {
            display:inline-flex;
            align-items:center;
            gap: 6px;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: .75rem;
            background: rgba(15,23,42,0.8);
            color: #a5b4fc;
            border: 1px solid rgba(31,41,55,0.9);
            max-width: 100%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .title{
            margin-top: 10px;
            font-size: .95rem;
            line-height: 1.25rem;
        }

        .desc{
            margin-top: 6px;
            font-size: .82rem;
            color: var(--muted);
            min-height: 34px;
        }

        .price {
            margin-top: 10px;
            font-weight: 800;
            color: var(--accent);
            font-size: 1rem;
        }

        .cuota {
            font-size: .85rem;
            color: #e5e7eb;
            margin-top: 4px;
        }

        a { color:inherit; text-decoration:none; }

        /* Pagination */
        .pagination-wrap{
            margin-top: 22px;
            display:flex;
            justify-content:center;
        }

        .pagination{
            display:flex;
            gap: 8px;
            align-items:center;
            padding: 10px;
            border: 1px solid rgba(31,41,55,0.9);
            background: rgba(2,6,23,0.7);
            border-radius: 14px;
        }

        .page{
            min-width: 38px;
            height: 38px;
            display:flex;
            align-items:center;
            justify-content:center;
            border-radius: 12px;
            border: 1px solid rgba(31,41,55,0.9);
            color: #e5e7eb;
            text-decoration:none;
            font-weight: 800;
            user-select: none;
        }

        .page:hover{
            border-color: rgba(34,197,94,0.9);
            color: #bbf7d0;
        }

        .page.active{
            background: rgba(34,197,94,0.15);
            border-color: rgba(34,197,94,0.9);
            color: #bbf7d0;
        }

        .page.disabled{
            opacity: .35;
            cursor: not-allowed;
        }

        /* Small helper text */
        .empty{
            margin-top: 18px;
            padding: 14px;
            border-radius: 14px;
            background: rgba(2,6,23,0.7);
            border: 1px solid rgba(31,41,55,0.9);
            color: #cbd5e1;
        }
    </style>
</head>

<body>
@php
    // Cambiá este path si tu logo está en otro lado
    $logoUrl = asset('img/logo-katuete.png');
@endphp

<div class="topbar">
    <div class="topbar-inner">
        <div class="brand">
            <img src="{{ $logoUrl }}" alt="Katuete Importados">
            <div>
                <h1>✨ Catálogo Inteligente</h1>
                <small>Katuete Importados</small>
            </div>
        </div>

        <div class="right-info">
            <span>
                Mostrando {{ $products->count() }} de {{ $products->total() }}
            </span>
        </div>
    </div>
</div>

<div class="container">

    {{-- Buscador --}}
    <form method="GET" class="searchbar">
        <input
            type="text"
            name="q"
            value="{{ request('q') }}"
            class="input"
            placeholder="🔎 Buscar por nombre, marca o palabra clave..."
        >

        <button class="btn" type="submit">Buscar</button>

        @if(request('q'))
            <a class="btn-clear" href="{{ route('productos.index') }}">Limpiar</a>
        @endif
    </form>

    @if ($products->isEmpty())
        <div class="empty">
            No hay productos activos en el catálogo todavía.
        </div>
    @else
        <div class="grid">
            @foreach ($products as $product)
                <a href="{{ route('productos.show', $product->slug) }}">
                    <div class="card">
                        @php
                            $img = $product->images->firstWhere('es_principal', true)
                                   ?? $product->images->first();
                        @endphp

                        <div class="thumb">
                            @if($img)
                                <img src="{{ $img->url }}" alt="{{ $product->nombre_basico }}">
                            @else
                                <div style="color:#6b7280;font-size:.85rem;">Sin imagen</div>
                            @endif
                        </div>

                        <div style="margin-top:10px;flex:1;">
                            @if($product->categoria)
                                <div class="badge">🏷️ {{ $product->categoria }}</div>
                            @endif

                            <div class="title">
                                {{ $product->presentation->titulo_marketing ?? $product->nombre_basico }}
                            </div>

                            @if($product->presentation?->resumen_corto)
                                <div class="desc">
                                    {{ \Illuminate\Support\Str::limit($product->presentation->resumen_corto, 90) }}
                                </div>
                            @else
                                <div class="desc">
                                    {{ \Illuminate\Support\Str::limit($product->nombre_basico, 90) }}
                                </div>
                            @endif
                        </div>

                        <div>
                            <div class="price">
                                Contado: Gs. {{ number_format($product->precio_contado, 0, ',', '.') }}
                            </div>
                            <div class="cuota">
                                3x de Gs. {{ number_format($product->precio_cuota_3, 0, ',', '.') }}
                            </div>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        {{-- PAGINACIÓN PROPIA (sin Tailwind) --}}
        @php
            $p = $products->withQueryString();
            $start = max(1, $p->currentPage() - 2);
            $end   = min($p->lastPage(), $p->currentPage() + 2);
        @endphp

        @if ($p->hasPages())
            <div class="pagination-wrap">
                <nav class="pagination" aria-label="Paginación">
                    {{-- Prev --}}
                    @if ($p->onFirstPage())
                        <span class="page disabled" aria-disabled="true">‹</span>
                    @else
                        <a class="page" href="{{ $p->previousPageUrl() }}" rel="prev" aria-label="Anterior">‹</a>
                    @endif

                    {{-- First + dots --}}
                    @if ($start > 1)
                        <a class="page" href="{{ $p->url(1) }}">1</a>
                        @if ($start > 2)
                            <span class="page disabled" aria-disabled="true">…</span>
                        @endif
                    @endif

                    {{-- Range --}}
                    @for ($page = $start; $page <= $end; $page++)
                        @if ($page == $p->currentPage())
                            <span class="page active" aria-current="page">{{ $page }}</span>
                        @else
                            <a class="page" href="{{ $p->url($page) }}">{{ $page }}</a>
                        @endif
                    @endfor

                    {{-- dots + Last --}}
                    @if ($end < $p->lastPage())
                        @if ($end < $p->lastPage() - 1)
                            <span class="page disabled" aria-disabled="true">…</span>
                        @endif
                        <a class="page" href="{{ $p->url($p->lastPage()) }}">{{ $p->lastPage() }}</a>
                    @endif

                    {{-- Next --}}
                    @if ($p->hasMorePages())
                        <a class="page" href="{{ $p->nextPageUrl() }}" rel="next" aria-label="Siguiente">›</a>
                    @else
                        <span class="page disabled" aria-disabled="true">›</span>
                    @endif
                </nav>
            </div>
        @endif
    @endif
</div>

</body>
</html>
