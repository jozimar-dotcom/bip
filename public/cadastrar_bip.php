<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../config/config.php';

if (empty($_SESSION['usuario'])) {
  header('Location: index.php'); exit;
}

$usuario = $_SESSION['usuario'];
// etapa permitida do usuário (estoque / embalagem / conferencia)
$etapa   = strtoupper($_SESSION['etapa_permitida'] ?? $_SESSION['perfil'] ?? 'ESTOQUE');

/* ========= AJAX: salvar data de trabalho na sessão ========= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['__setDate'])) {
  $d = $_POST['date'] ?? '';
  $ok = false; $msg = 'Data inválida.';
  $dt = DateTime::createFromFormat('Y-m-d', $d);
  if ($dt && $dt->format('Y-m-d') === $d) {
    $_SESSION['data_trabalho'] = $d;
    $ok = true; $msg = 'Data atualizada.';
  }
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['ok'=>$ok,'mensagem'=>$msg,'data'=>$_SESSION['data_trabalho'] ?? date('Y-m-d')]);
  exit;
}
/* ========================================================== */

$dataAtiva = $_SESSION['data_trabalho'] ?? date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Cadastrar Bips — MULTCABOS</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root{
      --bg:#f3f5f7;--card:#fff;--text:#111827;--muted:#6b7280;
      --brand:#d32f2f;--ok:#16a34a;--err:#dc2626;--chip:#e5e7eb;
      --focus:#111827;--shadow:0 8px 24px rgba(0,0,0,.08);
    }
    *{box-sizing:border-box}
    body{margin:0;background:var(--bg);font-family:Inter,system-ui,Segoe UI,Roboto,Arial,sans-serif;color:var(--text)}
    .wrap{max-width:920px;margin:28px auto;padding:0 16px}
    .back{display:inline-block;margin-bottom:12px;color:#374151;text-decoration:none}

    .card{background:var(--card);border-radius:16px;box-shadow:var(--shadow);padding:24px}
    .brand{font-weight:800;letter-spacing:.4px}
    .brand .r{color:var(--brand)} .brand .k{color:#111827}
    .head{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px}
    .etapa{display:inline-flex;gap:8px;align-items:center;background:var(--chip);padding:6px 10px;border-radius:999px;font-size:12px}
    .muted{color:var(--muted)}
    .row{display:flex;gap:12px;flex-wrap:wrap;align-items:end;margin:12px 0 8px}
    .col{flex:1 1 240px}
    .btn{display:inline-flex;align-items:center;gap:8px;background:#111827;color:#fff;border:none;border-radius:10px;padding:10px 14px;font-weight:600;cursor:pointer}
    .btn.light{background:#e5e7eb;color:#111827}
    input[type="date"]{width:100%;padding:12px 14px;border:1.5px solid #e5e7eb;border-radius:12px;font:inherit}
    input[type="date"]:focus{outline:none;border-color:var(--focus)}
    .input-giant{width:100%;padding:18px 20px;border:2px solid #111827;border-radius:14px;font-size:20px}
    .input-giant:focus{outline:none;box-shadow:0 0 0 4px rgba(17,24,39,.1)}
    .msg{margin-top:12px;padding:10px 12px;border-radius:12px;font-weight:600}
    .msg.ok{background:#ecfdf5;color:#065f46}
    .msg.err{background:#fef2f2;color:#991b1b}
    .chips{display:flex;gap:8px;flex-wrap:wrap;margin:8px 0 16px}
    .chip{background:var(--chip);border-radius:999px;padding:6px 10px;font-size:12px}
    .list{margin-top:12px;border-top:1px dashed #e5e7eb;padding-top:12px}
    .item{display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid #f1f2f4}
    .badge{padding:4px 10px;border-radius:999px;font-size:12px}
    .badge.ok{background:#dcfce7;color:#166534}

    #toasts { position: fixed; right: 20px; bottom: 20px; display: flex; flex-direction: column; gap: 10px; z-index: 9999; }
    .toast { min-width: 260px; max-width: 360px; padding: 12px 14px; border-radius: 10px; color: #111827; background: #fff; box-shadow: 0 8px 24px rgba(0,0,0,.18); font-weight: 600; transition: .2s ease; }
    .toast.ok  { border-left: 6px solid #16a34a; }
    .toast.err { border-left: 6px solid #dc2626; }

    .shake { animation: shake .25s linear 2; border-color: #dc2626 !important; box-shadow: 0 0 0 4px rgba(220,38,38,.10) !important; }
    @keyframes shake { 0% { transform: translateX(0) } 25% { transform: translateX(-4px) } 50% { transform: translateX(4px) } 75% { transform: translateX(-4px) } 100% { transform: translateX(0) } }

    /* ==== MODAL ==== */
    .flux-modal-backdrop{ position:fixed; inset:0; background:rgba(0,0,0,.35); z-index:9999; display:flex; align-items:center; justify-content:center; }
    .flux-modal{ width:min(920px, calc(100vw - 32px)); background:#fff; border-radius:12px; padding:16px; box-shadow:0 10px 30px rgba(0,0,0,.2); }
    .flux-modal-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:8px}
    .flux-modal-header .chip{background:#eef2ff;border-radius:999px;padding:6px 10px}
    .flux-modal-header .x{border:none;background:transparent;font-size:22px;cursor:pointer}
    .alert-err{background:#fdecec;color:#991b1b;padding:10px;border-radius:8px;margin:10px 0}
    .grid{display:grid;gap:12px;grid-template-columns:1fr 1fr}
    .box{border:1px solid #e5e7eb;border-radius:10px;padding:12px}
    .box-title{font-weight:700;margin-bottom:8px}
    .linha{display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px dashed #e5e7eb}
    .linha:last-child{border-bottom:none}
    .left{display:flex;align-items:center;gap:8px}
    .dot{display:inline-block;width:10px;height:10px;border-radius:50%}
    .dot.green{background:#16a34a}.dot.blue{background:#2563eb}.dot.gray{background:#9ca3af}
    .muted{color:#6b7280}
    .status-row{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px dashed #e5e7eb}
    .status-row:last-child{border-bottom:none}
    .print-actions{margin-top:12px;text-align:right}
    .modal-footer{margin-top:16px;text-align:right}
  </style>
</head>
<body>
  <div class="wrap">
    <a class="back" href="dashboard_user.php">‹ Voltar ao Dashboard</a>

    <div class="card">
      <div class="head">
        <div class="brand"><span class="r">MULT</span><span class="k">CABOS</span></div>
        <div class="etapa">
          <strong>Etapa:</strong> <span id="etapa"><?= htmlspecialchars($etapa) ?></span>
          • <span class="muted">Usuário: <?= htmlspecialchars($usuario) ?></span>
        </div>
      </div>

      <p class="muted" style="margin:6px 0 14px">
        Aponte o leitor de código no campo abaixo e pressione <span class="kbd">Enter</span>.
      </p>

      <div class="row">
        <div class="col">
          <label class="muted" style="display:block;margin-bottom:6px">Data de trabalho</label>
          <input type="date" id="data" value="<?= htmlspecialchars($dataAtiva) ?>">
        </div>
        <div class="col" style="flex:0 0 auto;display:flex;gap:8px">
          <button class="btn light" id="ontem"  type="button">Ontem</button>
          <button class="btn light" id="hoje"   type="button">Hoje</button>
          <button class="btn light" id="amanha" type="button">Amanhã</button>
        </div>
      </div>

      <input id="codigo" class="input-giant" placeholder="Insira o código" autofocus>

      <div class="chips">
        <div class="chip">Ativa: <strong id="ativa"><?= date('d/m/Y', strtotime($dataAtiva)) ?></strong></div>
        <div class="chip">Atalhos: <span class="kbd">Enter</span> enviar · <span class="kbd">.</span> focar · <span class="kbd">Esc</span> limpar</div>
      </div>

      <div id="msg" class="msg" style="display:none"></div>

      <div class="list">
        <div class="muted" style="margin-bottom:6px">Últimos bipes</div>
        <div id="ultimos"></div>
      </div>
    </div>
  </div>

  <!-- toasts -->
  <div id="toasts"></div>

  <!-- sons -->
  <audio id="snd-ok"   src="../assets/sounds/sucesso.mp3" preload="auto"></audio>
  <audio id="snd-err"  src="../assets/sounds/error.mp3"   preload="auto"></audio>
  <audio id="snd-lote" src="../assets/sounds/lote_cheio.mp3" preload="auto"></audio>

<script>
/* ===== CONFIG ===== */
const MIN_LEN = 12;
const MAX_LEN = 20;

const input = document.getElementById('codigo');
const msg   = document.getElementById('msg');
const ult   = document.getElementById('ultimos');
const data  = document.getElementById('data');
const ativa = document.getElementById('ativa');

const okS   = document.getElementById('snd-ok');
const errS  = document.getElementById('snd-err');
const lotS  = document.getElementById('snd-lote');

const toasts = document.getElementById('toasts');

/* ===== helpers ===== */
function showToast(text, type='ok', ms=2500){
  const el = document.createElement('div');
  el.className = `toast ${type}`;
  el.textContent = text;
  toasts.appendChild(el);
  setTimeout(()=> {
    el.style.opacity = '0';
    el.style.transform = 'translateY(4px)';
    setTimeout(()=> el.remove(), 220);
  }, ms);
}
function bumpInputError(){
  input.classList.remove('shake'); void input.offsetWidth; input.classList.add('shake');
  if (navigator.vibrate) { navigator.vibrate(80); }
}
const fmtBR = s => { const [y,m,d] = s.split('-'); return `${d}/${m}/${y}`; };

async function salvarDataAtiva(isoDate){
  try{
    const form = new FormData();
    form.append('__setDate','1'); form.append('date', isoDate);
    const r = await fetch(location.href, { method:'POST', body: form });
    const j = await r.json();
    if (j.ok){
      ativa.textContent = fmtBR(j.data);
      localStorage.setItem('data_trabalho', j.data);
      carregarRecentes();
    }
  }catch(_){}
}

async function carregarRecentes(){
  try{
    const r = await fetch('recentes_bips.php', {cache:'no-store'}); // precisa existir
    const j = await r.json();
    if (!j.ok) return;
    ult.innerHTML = '';
    (j.itens || []).slice(0,4).forEach(row=>{
      const item = document.createElement('div');
      item.className = 'item';
      item.innerHTML = `<span>${row.hora.slice(0,5)} · <strong>${row.codigo}</strong></span>
                        <span class="badge ok">OK</span>`;
      ult.appendChild(item);
    });
  }catch(_){}
}

/* ==== Data local hoje/ontem/amanhã (sem UTC) ==== */
function isoLocalComDeslocamento(dias=0){
  const t = new Date(); t.setHours(0,0,0,0); t.setDate(t.getDate()+dias);
  const y = t.getFullYear(); const m = String(t.getMonth()+1).padStart(2,'0'); const d = String(t.getDate()).padStart(2,'0');
  return `${y}-${m}-${d}`;
}

document.getElementById('hoje').onclick   = ()=> { const iso = isoLocalComDeslocamento(0);  data.value = iso; salvarDataAtiva(iso); };
document.getElementById('ontem').onclick  = ()=> { const iso = isoLocalComDeslocamento(-1); data.value = iso; salvarDataAtiva(iso); };
document.getElementById('amanha').onclick = ()=> { const iso = isoLocalComDeslocamento(1);  data.value = iso; salvarDataAtiva(iso); };
data.addEventListener('change', ()=> { salvarDataAtiva(data.value); });

document.addEventListener('keydown', (e)=>{ if (e.key === '.') { input.focus(); e.preventDefault(); } if (e.key === 'Escape') { input.value=''; } });

/* ======= Proteção contra duplo Enter ======= */
let busy = false;

/* ===== Lógica de próxima etapa (não repete a fila) ===== */
function proximaEtapa(filaAtual) {
  switch ((filaAtual || '').toLowerCase()) {
    case 'estoque':     return 'Embalagem';
    case 'embalagem':   return 'Conferência';
    case 'conferência': return 'Conferência'; // pronto p/ coleta
    default:            return 'Embalagem';
  }
}

/* ===== Modal: renderizar / imprimir ===== */
function renderLinha(etapa, dados) {
  const tem = !!dados && (dados.usuario || dados.hora);
  const cor = etapa === 'Estoque' ? 'green' : etapa === 'Embalagem' ? 'blue' : 'gray';
  const icone = tem ? '●' : '○';
  return `
    <div class="linha">
      <div class="left">
        <span class="dot ${cor}">${icone}</span>
        <strong>${etapa}</strong>
      </div>
      <div class="right muted">
        Usuário: ${dados?.usuario || '—'} · Hora: ${dados?.hora || '—'}
      </div>
    </div>
  `;
}
function abrirModalFluxo(m) {
  const filaAtual = m?.status?.fila || '';
  const prox = proximaEtapa(filaAtual);
  const html = `
  <div id="fluxo-modal" class="flux-modal-backdrop">
    <div class="flux-modal">
      <div class="flux-modal-header">
        <div class="chip">#CÓDIGO <strong>${m.codigo || ''}</strong></div>
        <button class="x" type="button" aria-label="Fechar" onclick="fecharFluxoModal()">×</button>
      </div>

      ${m.erro ? `<div class="alert-err">${m.erro}</div>` : ''}

      <div class="grid">
        <div class="box">
          <div class="box-title">Linha do tempo</div>
          ${renderLinha('Estoque',      m.timeline?.estoque)}
          ${renderLinha('Embalagem',    m.timeline?.embalagem)}
          ${renderLinha('Conferência',  m.timeline?.conferencia)}
        </div>

        <div class="box">
          <div class="box-title">Status</div>
          <div class="status-row"><span>Fila atual</span><strong>${filaAtual || '—'}</strong></div>
          <div class="status-row"><span>Próxima etapa</span><strong>${prox}</strong></div>

          <div class="print-actions">
            <button class="btn" type="button" onclick="imprimirFluxo()">Imprimir detalhes</button>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn light" type="button" onclick="fecharFluxoModal()">Ok, entendi</button>
      </div>
    </div>
  </div>`;
  document.body.insertAdjacentHTML('beforeend', html);
}
function fecharFluxoModal() {
  const el = document.getElementById('fluxo-modal'); if (el) el.remove();
}
function imprimirFluxo() {
  const el = document.querySelector('#fluxo-modal .flux-modal'); if (!el) return;
  const w = window.open('', '_blank', 'width=920,height=700');
  w.document.write(`
    <html><head><meta charset="utf-8"><title>Fluxo do código</title>
    <style>
      body{font-family:Inter,system-ui,Arial,sans-serif;margin:24px;color:#111827}
      .chip{display:inline-block;background:#eef2ff;color:#111827;padding:6px 10px;border-radius:999px;font-weight:700}
      .alert-err{background:#fef2f2;color:#991b1b;padding:10px;border-radius:8px;margin:12px 0}
      .box{border:1px solid #e5e7eb;border-radius:10px;padding:12px;margin-top:10px}
      .box-title{font-weight:700;margin-bottom:8px}
      .linha{display:flex;justify-content:space-between;border-bottom:1px dashed #e5e7eb;padding:8px 0}
      .dot{display:inline-block;width:10px;height:10px;border-radius:50%}
      .dot.green{background:#16a34a}.dot.blue{background:#2563eb}.dot.gray{background:#9ca3af}
      .muted{color:#6b7280}
      .status-row{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px dashed #e5e7eb}
    </style>
    </head><body>${el.innerHTML}</body></html>
  `);
  w.document.close(); w.focus(); w.print();
}

/* ===== envio do código ===== */
input.addEventListener('keydown', async (e)=>{
  if (e.key !== 'Enter' || busy) return;
  e.preventDefault();
  busy = true;

  const codigo = input.value.trim();
  if (!codigo) { busy = false; return; }

  if (codigo.length < MIN_LEN || codigo.length > MAX_LEN) {
    msg.style.display='block';
    msg.className='msg err';
    msg.textContent=`O código deve ter entre ${MIN_LEN} e ${MAX_LEN} caracteres.`;
    errS.currentTime = 0; errS.play().catch(()=>{});
    bumpInputError();
    input.value=''; input.focus(); busy=false; return;
  }

  msg.style.display='block';
  msg.className='msg';
  msg.textContent='Processando…';

  try{
    const r = await fetch('processa_bip.php', {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({ codigo })
    });
    const j = await r.json();

    if (j.ok){
      msg.className='msg ok';
      msg.textContent = j.mensagem || 'Registrado com sucesso.';
      okS.currentTime = 0; okS.play().catch(()=>{});
      if (j.loteFechado){ lotS.currentTime = 0; lotS.play().catch(()=>{}); }

      // topo dos últimos (apenas sucesso)
      const row = document.createElement('div');
      row.className='item';
      row.innerHTML = `<span>${new Date().toLocaleTimeString('pt-BR').slice(0,5)} · <strong>${codigo}</strong></span>
                       <span class="badge ok">OK</span>`;
      ult.prepend(row); while (ult.children.length>4) ult.lastChild.remove();

      input.value = ''; input.focus();
      carregarRecentes();
    } else {
      msg.className='msg err';
      msg.textContent = j.mensagem || 'Erro no registro.';
      errS.currentTime = 0; errS.play().catch(()=>{});
      bumpInputError();
      showToast(j.mensagem || 'Erro no registro.', 'err');
      input.value = ''; input.focus();

      if (j.modal) abrirModalFluxo(j.modal);
    }
  }catch(_){
    msg.className='msg err';
    msg.textContent = 'Falha de comunicação com o servidor.';
    errS.currentTime = 0; errS.play().catch(()=>{});
    bumpInputError();
    showToast('Falha de comunicação com o servidor.', 'err');
    input.value = ''; input.focus();
  }
  busy = false;
});

(function init(){
  const dSessao = '<?= htmlspecialchars($dataAtiva) ?>';
  data.value = dSessao; ativa.textContent = fmtBR(dSessao);
  carregarRecentes();
})();
</script>
</body>
</html>
