<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $product->presentation->titulo_marketing ?? $product->nombre_basico }} – Catálogo</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">


    <style>
        :root{
            --bg:#020617;
            --card:#0b122a;
            --line: rgba(31,41,55,.85);
            --muted:#9ca3af;
            --text:#f9fafb;
            --accent:#22c55e;
            --blue:#2563eb;
        }

        *{ box-sizing:border-box; }
        body{
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: radial-gradient(circle at top, #0b122a 0%, var(--bg) 55%, #01030a 100%);
            color:var(--text);
            margin:0;
        }

        .container{ max-width:1100px; margin:0 auto; padding:24px 18px; }
        .back{ color:var(--muted); text-decoration:none; font-size:.9rem; }
        .back:hover{ color:#bbf7d0; }

        .layout{
            margin-top:18px;
            display:grid;
            grid-template-columns: minmax(0,1.05fr) minmax(0,1.25fr);
            gap: 22px;
        }
        @media (max-width: 920px){
            .layout{ grid-template-columns: 1fr; }
        }

        .card{
            background: rgba(2,6,23,.35);
            border:1px solid var(--line);
            border-radius:18px;
            padding:18px;
            box-shadow: 0 18px 55px rgba(0,0,0,.25);
        }

        .badge{
            display:inline-flex;
            align-items:center;
            gap:6px;
            padding:4px 10px;
            border-radius:999px;
            font-size:.78rem;
            background: rgba(15,23,42,.7);
            border:1px solid var(--line);
            color:#a5b4fc;
            max-width:100%;
            white-space:nowrap;
            overflow:hidden;
            text-overflow:ellipsis;
        }

        .title{
            margin:10px 0 0 0;
            font-size:1.55rem;
            line-height:1.2;
            font-weight:900;
            letter-spacing: -0.2px;
        }
        .subtitle{
            margin-top:6px;
            color:#a5b4fc;
            font-weight:700;
            font-size:1rem;
        }
        .desc{
            margin-top:10px;
            color:#e5e7eb;
            font-size:.98rem;
            line-height:1.45rem;
        }

        .price{
            margin-top:16px;
            font-weight:900;
            color: var(--accent);
            font-size:1.25rem;
        }
        .price-cuota{
            margin-top:6px;
            color:#e5e7eb;
            font-size:.95rem;
        }
        .cuotas-text{
            margin-top:10px;
            color:#bbf7d0;
            font-size:.92rem;
        }

        .section{ margin-top:16px; }
        .section h3{ margin:0 0 8px 0; font-size:1.02rem; }
        .section ul{ margin:0; padding-left:18px; color:#e5e7eb; }
        .section li{ margin-bottom:6px; }

        /* Botones */
        .btn{
            flex:1;
            padding:12px 14px;
            border-radius:12px;
            border:0;
            font-weight:900;
            cursor:pointer;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            gap:8px;
            text-decoration:none;
            min-width: 180px;
        }
        .btn-whatsapp{ background: var(--accent); color:#041018; }
        .btn-buy{ background: var(--blue); color:#fff; }
        .btn:hover{ filter: brightness(1.07); }

        /* IMAGEN */
        .img-wrap{
            display:flex;
            align-items:center;
            justify-content:center;
            height: 420px;
            border-radius:16px;
            border:1px solid rgba(31,41,55,.7);
            background: rgba(2,6,23,.35);
            overflow:hidden;
        }
        .product-img{
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            background:#fff;
            border-radius: 14px;
            cursor: zoom-in;
            transition: transform .15s ease;
        }
        .product-img:hover{ transform: scale(1.01); }
        .sku{
            margin-top:10px;
            font-size:.82rem;
            color:#6b7280;
            text-align:center;
        }

        /* ZOOM MODAL */
        .zoom-modal{
            position:fixed; inset:0;
            background: rgba(0,0,0,.86);
            display:none;
            align-items:center; justify-content:center;
            z-index:150;
            padding: 18px;
        }
        .zoom-box{
            width:100%;
            max-width: 1200px;
            border-radius: 18px;
            overflow:hidden;
            border:1px solid rgba(31,41,55,.85);
            background: rgba(2,6,23,.75);
            box-shadow: 0 30px 90px rgba(0,0,0,.6);
        }
        .zoom-toolbar{
            display:flex;
            justify-content:flex-end;
            gap:10px;
            padding:12px;
            border-bottom: 1px solid rgba(31,41,55,.65);
        }
        .zoom-btn{
            width:42px; height:42px;
            border-radius:14px;
            border:1px solid rgba(31,41,55,.9);
            background: rgba(2,6,23,.7);
            color:#e5e7eb;
            cursor:pointer;
            font-weight:900;
            font-size:1.05rem;
        }
        .zoom-btn:hover{ border-color: rgba(34,197,94,.75); color:#bbf7d0; }
        .zoom-stage{
            width:100%;
            height: calc(90vh - 64px);
            display:flex;
            align-items:center;
            justify-content:center;
            overflow:hidden;
            cursor: grab;
        }
        .zoom-stage:active{ cursor: grabbing; }
        #zoomImage{
            max-width: 92vw;
            max-height: 85vh;
            object-fit: contain;
            border-radius: 14px;
            background:#fff;
            transform: translate(0px,0px) scale(1);
            transition: transform .02s linear;
            user-select:none;
            -webkit-user-drag:none;
        }

        /* MODAL COMPRA */
        .modal{
            display:none;
            position:fixed;
            inset:0;
            background: rgba(0,0,0,.65);
            align-items:center;
            justify-content:center;
            z-index:140;
            padding: 18px;
        }
        .modal-box{
            width:100%;
            max-width:560px;
            background: radial-gradient(circle at top, #0b122a 0%, #020617 55%, #01030a 100%);
            border:1px solid rgba(31,41,55,.9);
            border-radius:18px;
            box-shadow: 0 25px 80px rgba(0,0,0,.55);
            overflow:hidden;
        }
        .modal-head{
            display:flex;
            align-items:center;
            justify-content:space-between;
            padding:14px 16px;
            border-bottom:1px solid rgba(31,41,55,.7);
        }
        .modal-title{ margin:0; font-size:1rem; font-weight:900; }
        .modal-close{
            width:36px; height:36px; border-radius:12px;
            border:1px solid rgba(31,41,55,.9);
            background: rgba(2,6,23,.7);
            color:#e5e7eb; cursor:pointer;
        }
        .modal-close:hover{ border-color: rgba(34,197,94,.8); color:#bbf7d0; }

        .form{
            padding:16px;
            display:grid;
            gap:12px;
        }
        .field label{
            display:block;
            font-size:.85rem;
            color: var(--muted);
            margin-bottom:6px;
        }
        .control{
            width:100%;
            padding:12px 12px;
            border-radius:12px;
            border:1px solid rgba(31,41,55,.9);
            background: rgba(2,6,23,.7);
            color: #e5e7eb;
            outline:none;
        }
        .control:focus{
            border-color: rgba(34,197,94,.85);
            box-shadow: 0 0 0 3px rgba(34,197,94,.12);
        }
        textarea.control{ min-height: 90px; resize: vertical; }

        .modal-actions{
            padding: 0 16px 16px 16px;
        }
        .modal-help{
            padding: 0 16px 16px 16px;
            font-size:.82rem;
            color: var(--muted);
        }

        /* ===== Modal Confirmación (bonito) ===== */
        .confirm-modal{
            position:fixed; inset:0;
            background:rgba(0,0,0,.65);
            display:none; align-items:center; justify-content:center;
            z-index:160;
            padding: 18px;
        }
        .confirm-box{
            width:100%;
            max-width:520px;
            background: radial-gradient(circle at top, #0b122a 0%, #020617 55%, #01030a 100%);
            border:1px solid rgba(31,41,55,.9);
            border-radius:18px;
            box-shadow: 0 25px 80px rgba(0,0,0,.55);
            overflow:hidden;
        }
        .confirm-head{
            display:flex; align-items:center; justify-content:space-between;
            padding:14px 16px;
            border-bottom:1px solid rgba(31,41,55,.7);
        }
        .confirm-title{
            display:flex; align-items:center; gap:10px;
            font-weight:900; font-size:1rem;
        }
        .confirm-title .ok{
            width:28px; height:28px; border-radius:10px;
            display:inline-flex; align-items:center; justify-content:center;
            background: rgba(34,197,94,.18);
            border:1px solid rgba(34,197,94,.35);
            color:#22c55e;
        }
        .confirm-close{
            width:36px; height:36px; border-radius:12px;
            display:flex; align-items:center; justify-content:center;
            cursor:pointer;
            border:1px solid rgba(31,41,55,.9);
            background: rgba(2,6,23,.7);
            color:#e5e7eb;
        }
        .confirm-close:hover{ border-color: rgba(34,197,94,.8); color:#bbf7d0; }

        .confirm-body{ padding:16px; }
        .confirm-sub{ color:#9ca3af; font-size:.9rem; margin: 0 0 12px 0; }
        .kv{
            background: rgba(2,6,23,.65);
            border:1px solid rgba(31,41,55,.85);
            border-radius:14px;
            padding:12px;
            font-size:.92rem;
            line-height: 1.6rem;
            color:#e5e7eb;
        }
        .kv b{ color:#fff; }
        .kv .muted{ color:#9ca3af; }

        .confirm-actions{
            display:flex; gap:10px;
            padding:14px 16px 16px 16px;
        }
        .btn2{
            flex:1;
            padding:12px 14px;
            border-radius:12px;
            border:0;
            font-weight:900;
            cursor:pointer;
        }
        .btn2-primary{
            background: linear-gradient(135deg, #22c55e, #0ea5e9);
            color:#020617;
        }
        .btn2-secondary{
            background: rgba(2,6,23,.6);
            border: 1px solid rgba(31,41,55,.9);
            color:#e5e7eb;
        }
        .btn2-secondary:hover{
            border-color: rgba(34,197,94,.85);
            color:#bbf7d0;
        }
        .toast{
            margin-top:10px;
            font-size:.85rem;
            color:#bbf7d0;
            display:none;
        }
    </style>
</head>
<body>

@php
    $img = $product->images->firstWhere('es_principal', true) ?? $product->images->first();
    $imgUrl = $img ? ($img->url_hd ?? $img->url_original ?? $img->url ?? null) : null;
@endphp

<div class="container">
    <a href="{{ route('productos.index') }}" class="back">← Volver al catálogo</a>

    <div class="layout">

        {{-- IMAGEN --}}
        <div class="card" style="text-align:center;">
            <div class="img-wrap">
                @if($imgUrl)
                    <img
                        src="{{ $imgUrl }}"
                        alt="{{ $product->nombre_basico }}"
                        class="product-img"
                        onclick="openZoom(@js($imgUrl))"
                    >
                @else
                    <div style="color:#6b7280;">Sin imagen</div>
                @endif
            </div>
            <div class="sku">SKU: {{ $product->sku }}</div>
        </div>

        {{-- DETALLES --}}
        <div class="card">
            @if($product->categoria)
                <div class="badge">🏷️ {{ $product->categoria }}</div>
            @endif

            <h1 class="title">
                {{ $product->presentation->titulo_marketing ?? $product->nombre_basico }}
            </h1>

            @if(!empty($product->presentation?->nombre_premium))
                <div class="subtitle">{{ $product->presentation->nombre_premium }}</div>
            @endif

            @if(!empty($product->presentation?->resumen_corto))
                <div class="desc">{{ $product->presentation->resumen_corto }}</div>
            @endif

            <div class="price">
                Contado: Gs. {{ number_format($product->precio_contado, 0, ',', '.') }}
            </div>
            <div class="price-cuota">
                3 cuotas de Gs. {{ number_format($product->precio_cuota_3, 0, ',', '.') }}
            </div>

            @if(!empty($product->presentation?->texto_cuotas))
                <div class="cuotas-text">{{ $product->presentation->texto_cuotas }}</div>
            @endif

            <div style="margin-top:14px; display:flex; gap:12px; flex-wrap:wrap;">
                <a
                    class="btn btn-whatsapp"
                    target="_blank"
                    href="https://wa.me/595984784509?text={{ urlencode(
                        'Hola, quiero consultar por el producto: ' .
                        ($product->presentation->titulo_marketing ?? $product->nombre_basico) .
                        ' (SKU ' . $product->sku . ')'
                    ) }}"
                >
                    💬 Contactar
                </a>

                <button class="btn btn-buy" type="button" onclick="openBuyModal()">
                    🛒 Comprar
                </button>
            </div>

            @if(is_array($product->presentation?->bullets_sensoriales) && count($product->presentation->bullets_sensoriales) > 0)
                <div class="section">
                    <h3>✨ Experiencia sensorial</h3>
                    <ul>
                        @foreach($product->presentation->bullets_sensoriales as $bullet)
                            <li>• {{ $bullet }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if(!empty($product->presentation?->historia))
                <div class="section">
                    <h3>📖 Historia</h3>
                    <div class="desc" style="margin-top:0;">{{ $product->presentation->historia }}</div>
                </div>
            @endif

            @if(!empty($product->presentation?->notas_aroma) || !empty($product->presentation?->genero))
                <div class="section">
                    <h3>🧾 Detalles</h3>
                    <div style="font-size:.92rem;color:var(--muted);line-height:1.5rem;">
                        @if(!empty($product->presentation?->notas_aroma))
                            <div><strong style="color:#e5e7eb;">Notas de aroma:</strong> {{ $product->presentation->notas_aroma }}</div>
                        @endif
                        @if(!empty($product->presentation?->genero))
                            <div style="margin-top:.25rem;"><strong style="color:#e5e7eb;">Orientado a:</strong> {{ ucfirst($product->presentation->genero) }}</div>
                        @endif
                    </div>
                </div>
            @endif

            @if(empty($product->presentation))
                <div class="section">
                    <div style="font-size:.9rem;color:#fca5a5;">
                        ⚠️ Este producto todavía no tiene presentación generada.
                    </div>
                </div>
            @endif
        </div>

    </div>
</div>

{{-- ZOOM MODAL --}}
<div id="zoomModal" class="zoom-modal" aria-hidden="true" onclick="closeZoom()">
    <div class="zoom-box" role="dialog" aria-modal="true" onclick="event.stopPropagation()">
        <div class="zoom-toolbar">
            <button class="zoom-btn" type="button" onclick="zoomOut()" title="Alejar">−</button>
            <button class="zoom-btn" type="button" onclick="zoomReset()" title="Reset">↺</button>
            <button class="zoom-btn" type="button" onclick="zoomIn()" title="Acercar">+</button>
            <button class="zoom-btn" type="button" onclick="closeZoom()" title="Cerrar (Esc)">✕</button>
        </div>
        <div class="zoom-stage" id="zoomStage">
            <img id="zoomImage" src="" alt="Zoom imagen">
        </div>
    </div>
</div>

{{-- COMPRA MODAL --}}
<div id="buyModal" class="modal" onclick="closeBuyModal()">
    <div class="modal-box" onclick="event.stopPropagation()">
        <div class="modal-head">
            <h3 class="modal-title">Finalizar pedido</h3>
            <button class="modal-close" type="button" onclick="closeBuyModal()" title="Cerrar (Esc)">✕</button>
        </div>

        <div class="form">
        <div class="field">
            <label for="buyName">Nombre y apellido *</label>
            <input id="buyName" class="control">
        </div>

  <div class="field">
    <label for="buyPhone">WhatsApp *</label>
    <input id="buyPhone" class="control">
  </div>

  <div class="field">
    <label for="buyDoc">CPF / Cédula *</label>
    <input id="buyDoc" class="control" placeholder="Ej: 1234567 o 123.456.789-00">
  </div>

  <div class="field">
    <label for="buyEmail">Email *</label>
    <input id="buyEmail" class="control">
  </div>

  <div class="field">
    <label for="buyAddress">Dirección</label>
    <input id="buyAddress" class="control">
  </div>

  <div class="field">
    <label>Forma de pago *</label>
    <select id="buyPayMode" class="control" onchange="recalcPayment()">
      <option value="contado">Contado</option>
      <option value="3cuotas">3 cuotas</option>
    </select>
  </div>

  <div class="field" id="initialWrap" style="display:none;">
    <label>Entrega inicial</label>
    <input id="buyInitial" class="control" oninput="recalcPayment()">
  </div>

  <div class="field">
    <label>Resumen</label>
    <div id="paySummary" class="control"></div>
  </div>
</div>


        <div class="modal-actions">
            <button class="btn btn-buy" style="width:100%;" type="button" onclick="sendOrder()">
                Enviar pedido
            </button>
        </div>

        <div class="modal-help">
            * Necesitamos estos datos para registrar el cliente y enviar el pedido al ERP.
        </div>
    </div>
</div>

{{-- CONFIRM MODAL --}}
<div id="confirmModal" class="confirm-modal" onclick="closeConfirmIfBackdrop(event)">
    <div class="confirm-box" role="dialog" aria-modal="true" aria-labelledby="confirmTitle" onclick="event.stopPropagation()">
        <div class="confirm-head">
            <div class="confirm-title" id="confirmTitle">
                <span class="ok">✅</span>
                Pedido capturado
            </div>
            <div class="confirm-close" onclick="closeConfirm()">✕</div>
        </div>

        <div class="confirm-body">
            <p class="confirm-sub">Pendiente conectar backend (ERP). Datos capturados:</p>

            <div class="kv" id="confirmKv"></div>

            <div class="toast" id="copyToast">Copiado ✅</div>
        </div>

        <div class="confirm-actions">
            <button class="btn2 btn2-secondary" onclick="copyConfirm()">📋 Copiar</button>
            <button class="btn2 btn2-primary" onclick="closeConfirm()">Cerrar</button>
        </div>
    </div>
</div>

<script>
  // ===== Zoom =====
  let zoomScale = 1;
  let panX = 0, panY = 0;
  let isPanning = false;
  let startX = 0, startY = 0;

  const zoomModalEl = () => document.getElementById('zoomModal');
  const zoomImgEl   = () => document.getElementById('zoomImage');
  const zoomStageEl = () => document.getElementById('zoomStage');

  function applyTransform(){
    zoomImgEl().style.transform = `translate(${panX}px, ${panY}px) scale(${zoomScale})`;
  }

  function openZoom(src){
    zoomImgEl().src = src;
    zoomScale = 1; panX = 0; panY = 0;
    applyTransform();
    zoomModalEl().style.display = 'flex';
    zoomModalEl().setAttribute('aria-hidden','false');
    document.body.style.overflow = 'hidden';
  }

  function closeZoom(){
    zoomModalEl().style.display = 'none';
    zoomModalEl().setAttribute('aria-hidden','true');
    document.body.style.overflow = '';
    isPanning = false;
  }

  function zoomIn(){ zoomScale = Math.min(zoomScale + 0.2, 4); applyTransform(); }
  function zoomOut(){
    zoomScale = Math.max(zoomScale - 0.2, 1);
    if (zoomScale === 1){ panX = 0; panY = 0; }
    applyTransform();
  }
  function zoomReset(){ zoomScale = 1; panX = 0; panY = 0; applyTransform(); }

  // (si el elemento no existe todavía, no rompe)
  zoomStageEl()?.addEventListener('wheel', (e) => {
    e.preventDefault();
    const delta = Math.sign(e.deltaY);
    if (delta > 0) zoomOut();
    else zoomIn();
  }, { passive:false });

  zoomStageEl()?.addEventListener('mousedown', (e) => {
    if (zoomScale <= 1) return;
    isPanning = true;
    startX = e.clientX - panX;
    startY = e.clientY - panY;
  });

  window.addEventListener('mousemove', (e) => {
    if (!isPanning) return;
    panX = e.clientX - startX;
    panY = e.clientY - startY;
    applyTransform();
  });

  window.addEventListener('mouseup', () => { isPanning = false; });

  // ===== Compra =====
  function openBuyModal(){
    const m = document.getElementById('buyModal');
    if (!m) return;
    m.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    setTimeout(() => document.getElementById('buyName')?.focus(), 60);
    recalcPayment(); // refresca resumen al abrir
  }

  function closeBuyModal(){
    const m = document.getElementById('buyModal');
    if (!m) return;
    m.style.display = 'none';
    document.body.style.overflow = '';
  }

  function isValidEmail(email){
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  }

  // ===== Confirm Modal =====
  function openConfirm(htmlKv){
    const kv = document.getElementById('confirmKv');
    kv.innerHTML = htmlKv;

    const toast = document.getElementById('copyToast');
    if (toast) toast.style.display = 'none';

    document.getElementById('confirmModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
  }

  function closeConfirm(){
    document.getElementById('confirmModal').style.display = 'none';
    document.body.style.overflow = '';
  }

  function closeConfirmIfBackdrop(e){
    if (e.target && e.target.id === 'confirmModal') closeConfirm();
  }

  function copyConfirm(){
    const text = document.getElementById('confirmKv').innerText;
    navigator.clipboard.writeText(text).then(() => {
      const t = document.getElementById('copyToast');
      if (!t) return;
      t.style.display = 'block';
      setTimeout(()=> t.style.display = 'none', 1600);
    });
  }

  // ===== Pago (contado / 3 cuotas + entrega inicial) =====
  // precios del producto (Blade -> JS)
  const PRICE_CONTADO = Number(@js($product->precio_contado));
  const PRICE_CUOTA_3 = Number(@js($product->precio_cuota_3));

  function formatGs(n){
    const v = Math.round(Number(n) || 0);
    return v.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
  }

  // si estos IDs no existen, no rompe (solo no muestra resumen)
  function recalcPayment(){
    const modeEl = document.getElementById('buyPayMode');
    const wrap   = document.getElementById('initialWrap');
    const initEl = document.getElementById('buyInitial');
    const sumEl  = document.getElementById('paySummary');

    if (!modeEl || !wrap || !initEl || !sumEl) return;

    const mode = modeEl.value;

    if (mode === 'contado'){
      wrap.style.display = 'none';
      sumEl.innerHTML = `<b>Contado:</b> Gs. ${formatGs(PRICE_CONTADO)}`;
      return;
    }

    // 3 cuotas
    wrap.style.display = 'block';
    const entrega = Math.max(0, Number(String(initEl.value || '').replace(/\./g,'').replace(/,/g,'.')) || 0);
    const totalCredito = PRICE_CUOTA_3 * 3;

    const saldo = Math.max(0, totalCredito - entrega);
    const cuota = Math.ceil(saldo / 3);

    sumEl.innerHTML = `
      <div><b>Total crédito:</b> <span class="muted">Gs. ${formatGs(totalCredito)}</span></div>
      <div><b>Entrega:</b> <span class="muted">Gs. ${formatGs(entrega)}</span></div>
      <div><b>3 cuotas de:</b> <span class="muted">Gs. ${formatGs(cuota)}</span></div>
    `;
  }

  // 🔧 Cambiá esto según tu ERP (local o prod)
  // (para este endpoint del catálogo NO se usa, pero lo dejo porque vos lo tenías)
  const ERP_BASE_URL = "http://127.0.0.1:8000"; // EJ: https://erp.tudominio.com

  function normalizeDoc(v){
    return String(v || '').trim().replace(/\s+/g,'').replace(/[.\-]/g,'');
  }

  async function sendOrder(){
    const name    = (document.getElementById('buyName')?.value || '').trim();
    const phone   = (document.getElementById('buyPhone')?.value || '').trim();
    const docRaw  = (document.getElementById('buyDoc')?.value || '').trim();
    const doc     = normalizeDoc(docRaw);
    const email   = (document.getElementById('buyEmail')?.value || '').trim();
    const address = (document.getElementById('buyAddress')?.value || '').trim();
    const notes   = (document.getElementById('buyNotes')?.value || '').trim();

    const payMode = (document.getElementById('buyPayMode')?.value || 'contado');
    const entregaRaw = (document.getElementById('buyInitial')?.value || '');
    const entrega = Math.max(0, Number(String(entregaRaw).replace(/\./g,'').replace(/,/g,'.')) || 0);

    if (!name || !phone || !email || !doc){
      alert('Completá Nombre, WhatsApp, CPF/Cédula y Email.');
      return;
    }
    if (!isValidEmail(email)){
      alert('Ingresá un email válido.');
      return;
    }

    // ✅ Definimos item y precios (catálogo guarda total real por seguridad)
    const qty  = 1;
    const unit = PRICE_CONTADO;              // para tu OrderItem.precio_unitario
    const itemTotal = qty * unit;

    // ✅ Total del pedido:
    // - si contado: contado
    // - si 3 cuotas: total crédito = cuota*3 (para que quede registrado como crédito)
    const totalPedido = (payMode === '3cuotas')
      ? (PRICE_CUOTA_3 * 3)
      : itemTotal;

    // ✅ Payload QUE ESPERA TU CatalogOrderController
    const payload = {
      cliente: {
        nombre: name,
        email: email,
        telefono: phone,
        direccion: address || null,
        ciudad: null,
        documento: doc
      },

      // método para tu columna `metodo` (ajustá estos strings si querés)
      metodo: (payMode === '3cuotas') ? 'credito_3' : 'contado',

      // tu controller lee $data['total']
      total: Math.round(totalPedido),

      // (extra opcional) si querés guardar entrega y nota (no rompe validación si tu FormRequest lo permite)
      entrega_inicial: (payMode === '3cuotas') ? Math.round(entrega) : 0,
      nota: notes || null,

      // tu controller lee $data['productos']
      productos: [{
        scraper_id: Number(@js($product->scraper_id ?? 1)),
        sku: @js($product->sku),
        cantidad: qty,
        precio_unitario: Math.round(unit),
        precio_total: Math.round(itemTotal) // ✅ ESTO TE FALTABA
      }]
    };

    const endpoint = "{{ url('/api/catalogo/pedido') }}"; // ✅ MISMO DOMINIO (catálogo)

    closeBuyModal();

    openConfirm(`
      <div><b>Enviando pedido...</b> <span class="muted">⏳</span></div>
      <div style="margin-top:8px;"><b>Método:</b> <span class="muted">${escapeHtml(payload.metodo)}</span></div>
      <div><b>Documento:</b> <span class="muted">${escapeHtml(doc)}</span></div>
      <div><b>Total:</b> <span class="muted">Gs. ${escapeHtml(formatGs(payload.total))}</span></div>
    `);

    try {
      const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

      const res = await fetch(endpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          ...(csrf ? {'X-CSRF-TOKEN': csrf} : {}),
        },
        body: JSON.stringify(payload)
      });

      const data = await res.json().catch(() => ({}));

      if (!res.ok || data.success === false){
        openConfirm(`
          <div><b>❌ Error</b></div>
          <pre style="white-space:pre-wrap;color:#e5e7eb;">${escapeHtml(JSON.stringify(data, null, 2))}</pre>
        `);
        return;
      }

      // ✅ Mostrar OK con IDs
      openConfirm(`
        <div><b>✅ Pedido procesado</b></div>
        <div style="margin-top:8px;"><b>Pedido ID (catálogo):</b> <span class="muted">${escapeHtml(String(data?.data?.pedido_id ?? ''))}</span></div>
        <div><b>Estado:</b> <span class="muted">${escapeHtml(String(data?.data?.estado ?? ''))}</span></div>
        <div><b>Total:</b> <span class="muted">Gs. ${escapeHtml(formatGs(data?.data?.total ?? payload.total))}</span></div>
        <div style="margin-top:8px;"><b>Enviado al ERP:</b> <span class="muted">${escapeHtml(String(data?.data?.enviado_a_erp ?? 'false'))}</span></div>
        <div><b>ERP Pedido ID:</b> <span class="muted">${escapeHtml(String(data?.data?.erp_pedido_id ?? ''))}</span></div>
      `);

    } catch (e){
      openConfirm(`
        <div><b>❌ Error de conexión</b></div>
        <div class="muted">${escapeHtml(e?.message || String(e))}</div>
      `);
    }
  }

  function escapeHtml(str){
    return String(str).replace(/[&<>"']/g, (m) => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
    }[m]));
  }

  // ESC (una sola vez)
  window.addEventListener('keydown', (e) => {
    if (e.key === 'Escape'){
      closeZoom();
      closeBuyModal();
      closeConfirm();
    }
  });
</script>


</body>
</html>
