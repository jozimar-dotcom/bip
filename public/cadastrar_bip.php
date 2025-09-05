<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../config/config.php';

if (empty($_SESSION['usuario'])) {
  header('Location: index.php'); exit;
}

$usuario   = $_SESSION['usuario'];
$perfilRaw = strtolower($_SESSION['perfil'] ?? $_SESSION['etapa'] ?? 'estoque');
$etapaRaw  = strtolower($_SESSION['etapa']  ?? $_SESSION['perfil'] ?? 'estoque');
$etapa     = ($etapaRaw === 'user') ? 'ESTOQUE' : strtoupper($etapaRaw);

// rota do “voltar”
switch ($perfilRaw) {
  case 'admin':      $backUrl = 'dashboard.php'; break;
  case 'conferente': $backUrl = 'dashboard_conferente.php'; break;
  default:           $backUrl = 'dashboard_user.php';
}

$dataAtiva = $_SESSION['data_trabalho'] ?? date('Y-m-d');

/* ========= AJAX: salvar data ========= */
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
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Cadastrar Bips — MULTCABOS</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root{
      --bg:#f5f7fa; --card:#fff; --text:#111827; --muted:#6b7280;
      --brand:#d32f2f; --ok:#16a34a; --err:#dc2626; --chip:#eef1f4;
      --primary:#111827; --shadow:0 8px 24px rgba(0,0,0,.08);
      --border:#e5e7eb; --blue:#2563eb;
    }
    *{box-sizing:border-box}
    body{margin:0;background:var(--bg);font-family:Inter,system-ui,Segoe UI,Roboto,Arial,sans-serif;color:var(--text)}
    .wrap{max-width:900px;margin:28px auto;padding:0 16px}
    .back{display:inline-block;margin-bottom:10px;text-decoration:none;color:#374151}
    .card{background:var(--card);border-radius:16px;box-shadow:var(--shadow);padding:20px}
    .head{display:flex;align-items:center;justify-content:space-between}
    .brand{font-weight:800;font-size:18px}
    .brand .r{color:var(--brand)} .brand .k{color:#111827}
    .etapa{font-size:13px;background:var(--chip);border-radius:999px;padding:6px 10px}
    .muted{color:var(--muted)}
    .row{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin:12px 0}
    .btn{display:inline-flex;align-items:center;gap:8px;background:#e9eef6;color:#111827;border:none;border-radius:10px;padding:9px 14px;font-weight:600;cursor:pointer}
    input[type="date"]{height:42px;min-width:280px;padding:0 12px;border:1.5px solid var(--border);border-radius:10px;font:inherit}
    input[type="date"]:focus{outline:none;border-color:#111827}
    .input-giant{width:100%;padding:16px 18px;border:2px solid #111827;border-radius:12px;font-size:18px}
    .input-giant:focus{outline:none;box-shadow:0 0 0 4px rgba(17,24,39,.1)}
    .msg{margin-top:10px;padding:12px;border-radius:12px;font-weight:600;display:none}
    .msg.ok{background:#ecfdf5;color:#065f46;display:block}
    .msg.err{background:#fef2f2;color:#991b1b;display:block}
    .chips{display:flex;gap:8px;flex-wrap:wrap;margin:10px 0 12px}
    .chip{background:var(--chip);border-radius:999px;padding:6px 10px;font-size:12px}
    .list{margin-top:8px;border-top:1px dashed var(--border);padding-top:8px}
    .item{display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid #f1f2f4}
    .badge{padding:4px 10px;border-radius:999px;font-size:12px}
    .badge.ok{background:#dcfce7;color:#166534}

    /* Modal */
    .modal-backdrop{position:fixed;inset:0;background:rgba(0,0,0,.35);display:none;align-items:center;justify-content:center;z-index:9998}
    .modal{width:min(900px,92vw);background:#fff;border-radius:14px;box-shadow:0 18px 50px rgba(0,0,0,.18);overflow:hidden}
    .modal-header{padding:14px 16px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center}
    .modal-title{font-weight:800}
    .modal-body{padding:16px}
    .alert-err{background:#fdecec;color:#9b1c1c;padding:10px 12px;border-radius:10px;margin-bottom:14px;font-weight:600}
    .code-chip{display:inline-block;background:#eef1f4;border-radius:999px;padding:6px 10px;font-weight:700;margin:6px 0 10px}
    .cols{display:flex;gap:16px;flex-wrap:wrap}
    .col{flex:1 1 320px;background:#fafbfc;border:1px solid #f0f2f4;border-radius:12px;padding:12px}
    .line{display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px dashed #eaecef}
    .dot{width:10px;height:10px;border-radius:999px;background:#d1d5db}
    .dot.green{background:#16a34a}
    .dot.blue{background:#2563eb}
    .label{font-weight:700}
    .sub{font-size:12px;color:#6b7280;margin-left:6px}
    .modal-footer{padding:12px 16px;border-top:1px solid var(--border);display:flex;justify-content:flex-end;gap:8px}
    .btn-primary{background:#111827;color:#fff;border:none;border-radius:10px;padding:10px 14px;font-weight:700;cursor:pointer}
    .btn-ghost{background:#eef1f4;border:none;border-radius:10px;padding:10px 14px;font-weight:700;cursor:pointer;margin-right:auto}

    #previewWrap{display:none;margin-top:14px;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;background:#fff}
    #previewFrame{width:100%;height:60vh;border:0}

    .shake{animation:shake .25s linear 2;border-color:#dc2626 !important;box-shadow:0 0 0 4px rgba(220,38,38,.08) !important}
    @keyframes shake{0%{transform:translateX(0)}25%{transform:translateX(-4px)}50%{transform:translateX(4px)}75%{transform:translateX(-4px)}100%{transform:translateX(0)}}
  </style>
</head>
<body>
  <div class="wrap">
    <a class="back" href="<?= htmlspecialchars($backUrl) ?>">‹ Voltar ao Dashboard</a>

    <div class="card">
      <div class="head">
        <div class="brand"><span class="r">MULT</span><span class="k">CABOS</span></div>
        <div class="etapa">Etapa: <strong><?= htmlspecialchars($etapa) ?></strong> • Usuário: <strong><?= htmlspecialchars($usuario) ?></strong></div>
      </div>

      <p class="muted" style="margin:8px 0">Aponte o leitor de código no campo abaixo e pressione <strong>Enter</strong>.</p>

      <div class="row">
        <input type="date" id="data" value="<?= htmlspecialchars($dataAtiva) ?>">
        <button class="btn" id="ontem"  type="button">Ontem</button>
        <button class="btn" id="hoje"   type="button">Hoje</button>
        <button class="btn" id="amanha" type="button">Amanhã</button>
      </div>

      <input id="codigo" class="input-giant" placeholder="Insira o código" autofocus>

      <div class="chips">
        <div class="chip">Ativa: <strong id="ativa"><?= date('d/m/Y', strtotime($dataAtiva)) ?></strong></div>
        <div class="chip">Atalhos: <span>Enter</span> enviar · <span>.</span> focar · <span>Esc</span> limpar</div>
      </div>

      <div id="msg" class="msg" style="display:none"></div>

      <div class="list">
        <div class="muted" style="margin-bottom:6px">Últimos bipes</div>
        <div id="ultimos"></div>
      </div>
    </div>
  </div>

  <!-- Modal -->
  <div id="backdrop" class="modal-backdrop" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="mTitle">
      <div class="modal-header">
        <div class="modal-title" id="mTitle">Fluxo do código</div>
        <button id="mClose" class="btn-ghost" title="Fechar">✕</button>
      </div>
      <div class="modal-body">
        <div id="mAlert" class="alert-err" style="display:none"></div>
        <div id="mCode" class="code-chip" style="display:none"></div>

        <div class="cols">
          <div class="col">
            <div class="line"><span id="d1" class="dot"></span><div class="label">Estoque<span id="s1" class="sub"></span></div></div>
            <div class="line"><span id="d2" class="dot"></span><div class="label">Embalagem<span id="s2" class="sub"></span></div></div>
            <div class="line"><span id="d3" class="dot"></span><div class="label">Conferência<span id="s3" class="sub"></span></div></div>
          </div>
          <div class="col">
            <div class="line" style="border-bottom:0">
              <div class="label" style="min-width:140px">Fila atual</div>
              <div id="fila" style="font-weight:800"></div>
            </div>
            <div class="line" style="border-bottom:0">
              <div class="label" style="min-width:140px">Próxima etapa</div>
              <div id="prox" style="font-weight:800"></div>
            </div>
          </div>
        </div>

        <div id="previewWrap">
          <iframe id="previewFrame" title="Pré-visualização do código"></iframe>
        </div>
      </div>
      <div class="modal-footer">
        <button id="mPrint" class="btn-ghost" style="display:none">Ver detalhes do código</button>
        <button id="mOk" class="btn-primary">Ok, entendi</button>
      </div>
    </div>
  </div>

  <audio id="snd-ok"   src="./sucesso.mp3" preload="auto"></audio>
  <audio id="snd-err"  src="./error.mp3"   preload="auto"></audio>
  <audio id="snd-lote" src="../assets/sounds/lote_cheio.mp3" preload="auto"></audio>

<script>
const input = document.getElementById('codigo');
const msg   = document.getElementById('msg');
const ult   = document.getElementById('ultimos');
const data  = document.getElementById('data');
const ativa = document.getElementById('ativa');

const okS   = document.getElementById('snd-ok');
const errS  = document.getElementById('snd-err');
const lotS  = document.getElementById('snd-lote');

// ===== Modal
const bd   = document.getElementById('backdrop');
const mClose = document.getElementById('mClose');
const mOk    = document.getElementById('mOk');
const mAlert = document.getElementById('mAlert');
const mCode  = document.getElementById('mCode');
const dot1 = document.getElementById('d1'), dot2 = document.getElementById('d2'), dot3 = document.getElementById('d3');
const s1 = document.getElementById('s1'), s2 = document.getElementById('s2'), s3 = document.getElementById('s3');
const fila = document.getElementById('fila'), prox = document.getElementById('prox');
const mPrint = document.getElementById('mPrint');

const previewWrap  = document.getElementById('previewWrap');
const previewFrame = document.getElementById('previewFrame');

let lastPrintUrl = null;
let modalOpen = false;
function lockScan(lock){ modalOpen = !!lock; input.disabled = !!lock; if(!lock) input.focus(); }

function openModal(payload){
  mAlert.style.display = 'block';
  mAlert.textContent = payload.mensagem || 'Fluxo inválido.';
  mCode.style.display = 'inline-block';
  mCode.textContent = 'Código: ' + (payload.codigo || '');

  [dot1,dot2,dot3].forEach(d=>{ d.className='dot'; });
  [s1,s2,s3].forEach(s=> s.textContent = ' — Hora: —');

  const tl  = payload.timeline || {};
  const est = tl.estoque || {}, emb = tl.embalagem || {}, conf = tl.conferencia || {};

  if (est.hora){ dot1.classList.add('green'); s1.textContent = ` — Usuário: ${est.usuario||'—'} · Hora: ${est.hora||'—'}`; }
  if (emb.hora){ dot2.classList.add('green'); s2.textContent = ` — Usuário: ${emb.usuario||'—'} · Hora: ${emb.hora||'—'}`; }
  if (conf.hora){ dot3.classList.add('green'); s3.textContent = ` — Usuário: ${conf.usuario||'—'} · Hora: ${conf.hora||'—'}`; }

  const label = { estoque:'Estoque', embalagem:'Embalagem', conferencia:'Conferência', coleta:'Disponível para coleta' };

  const filaAtual = payload.fila_atual || null;
  if (filaAtual === 'estoque'     && !est.hora)  dot1.classList.add('blue');
  if (filaAtual === 'embalagem'   && !emb.hora)  dot2.classList.add('blue');
  if (filaAtual === 'conferencia' && !conf.hora) dot3.classList.add('blue');

  // se o back não mandar “depois”, usa a própria fila (inclui 'coleta')
  const proxima = (payload.proxima_etapa ?? filaAtual) || null;

  fila.textContent = filaAtual ? (label[filaAtual] || '—') : '—';
  prox.textContent = proxima  ? (label[proxima]  || '—') : '—';

  lastPrintUrl = 'preview_codigo.php?codigo=' + encodeURIComponent(payload.codigo || '') +
                 '&date=' + encodeURIComponent(document.getElementById('data').value);
  mPrint.style.display = 'inline-block';

  previewWrap.style.display = 'none';
  previewFrame.removeAttribute('src');

  bd.style.display = 'flex';
  lockScan(true);
}
function closeModal(){
  bd.style.display = 'none';
  previewFrame.removeAttribute('src');
  previewWrap.style.display = 'none';
  lockScan(false);
}
mClose.onclick = mOk.onclick = closeModal;
bd.addEventListener('click', (e)=>{ if (e.target === bd) closeModal(); });

mPrint.addEventListener('click', (e)=>{
  e.preventDefault();
  if (!lastPrintUrl) return;
  previewFrame.src = lastPrintUrl;
  previewWrap.style.display = 'block';
});

const fmtBR = s => { const [y,m,d] = s.split('-'); return `${d}/${m}/${y}`; };
function isoLocalComDeslocamento(dias=0){ const t=new Date(); t.setHours(0,0,0,0); t.setDate(t.getDate()+dias); return `${t.getFullYear()}-${String(t.getMonth()+1).padStart(2,'0')}-${String(t.getDate()).padStart(2,'0')}`; }
async function salvarDataAtiva(iso){
  try{ const fd=new FormData(); fd.append('__setDate','1'); fd.append('date', iso);
    const r=await fetch(location.href,{method:'POST',body:fd}); const j=await r.json();
    if(j.ok){ ativa.textContent=fmtBR(j.data); localStorage.setItem('data_trabalho', j.data); }
  }catch(_){}
}

// trocar data mantém foco e recarrega lista
document.getElementById('hoje').onclick   = ()=>{ const iso=isoLocalComDeslocamento(0);  data.value=iso; salvarDataAtiva(iso).then(()=>{carregarRecentes(); input.focus();}); };
document.getElementById('ontem').onclick  = ()=>{ const iso=isoLocalComDeslocamento(-1); data.value=iso; salvarDataAtiva(iso).then(()=>{carregarRecentes(); input.focus();}); };
document.getElementById('amanha').onclick = ()=>{ const iso=isoLocalComDeslocamento(1);  data.value=iso; salvarDataAtiva(iso).then(()=>{carregarRecentes(); input.focus();}); };
data.addEventListener('change', ()=> salvarDataAtiva(data.value).then(()=>{carregarRecentes(); input.focus();}));

document.addEventListener('keydown',(e)=>{ if(e.key==='.') { input.focus(); e.preventDefault(); } if(e.key==='Escape'){ input.value=''; input.focus(); } });

// últimos sucessos do dia ativo (?date=)
async function carregarRecentes(){
  try{
    const url = 'recentes_bips.php?date=' + encodeURIComponent(data.value);
    const r = await fetch(url, {cache:'no-store'}); const j = await r.json();
    if (!j.ok) return;
    ult.innerHTML='';
    (j.itens||[]).slice(0,4).forEach(row=>{
      const el=document.createElement('div');
      el.className='item';
      el.innerHTML=`<span>${row.hora.slice(0,5)} · <strong>${row.codigo}</strong></span><span class="badge ok">OK</span>`;
      ult.appendChild(el);
    });
  }catch(_){}
}

function showMsg(type,text){
  msg.className='msg ' + type; msg.textContent=text; msg.style.display='block';
  if(type==='ok'){ okS.currentTime=0; okS.play().catch(()=>{}); }
  else if(type==='err'){ errS.currentTime=0; errS.play().catch(()=>{}); }
}
function bump(){ input.classList.remove('shake'); void input.offsetWidth; input.classList.add('shake'); if(navigator.vibrate) navigator.vibrate(80); }

let busy=false;
input.addEventListener('keydown', async (e)=>{
  if (e.key!=='Enter' || busy || modalOpen) return;
  e.preventDefault();
  const codigo = input.value.trim();
  if (!codigo) { input.focus(); return; }

  if (codigo.length < 10 || codigo.length > 20){
    showMsg('err','O código deve ter entre 10 e 20 caracteres.');
    bump(); input.value=''; input.focus(); return;
  }

  busy=true; msg.style.display='none';

  try{
    const r = await fetch('processa_bip.php', {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({codigo, workDate: data.value})
    });

    let j=null;
    try{ j = await r.json(); }catch(_){
      showMsg('err','Falha de comunicação com o servidor.'); bump(); busy=false; input.focus(); return;
    }

    if (j && j.showModal){
      openModal({
        mensagem: j.mensagem || 'Fluxo inválido.',
        codigo:   (j.modal && j.modal.codigo) || codigo,
        timeline: (j.modal && j.modal.timeline) || {},
        fila_atual: (j.modal && j.modal.fila_atual) || null,
        proxima_etapa: (j.modal && j.modal.proxima_etapa) || null
      });
      showMsg('err', j.mensagem || 'Fluxo inválido.');
      bump();
      input.value=''; busy=false;
      return;
    }

    if (j && j.ok){
      showMsg('ok', j.mensagem || 'Registrado com sucesso.');
      const el=document.createElement('div');
      el.className='item';
      el.innerHTML=`<span>${new Date().toLocaleTimeString('pt-BR').slice(0,5)} · <strong>${codigo}</strong></span><span class="badge ok">OK</span>`;
      ult.prepend(el); while(ult.children.length>4) ult.lastChild.remove();
      input.value='';
      if (j.loteFechado){ lotS.currentTime=0; lotS.play().catch(()=>{}); }
      carregarRecentes();
      busy=false; input.focus(); return;
    }

    showMsg('err', (j && j.mensagem) ? j.mensagem : 'Erro no servidor.');
    bump(); input.value=''; busy=false; input.focus();

  }catch(_){
    showMsg('err','Falha de comunicação com o servidor.');
    bump(); input.value=''; busy=false; input.focus();
  }
});

// init
(function init(){
  const dSessao = '<?= htmlspecialchars($dataAtiva) ?>';
  data.value = dSessao; ativa.textContent = fmtBR(dSessao);
  carregarRecentes();
  input.focus();
})();
</script>
</body>
</html>
