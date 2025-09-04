<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../config/config.php';

if (empty($_SESSION['usuario'])) { header('Location: index.php'); exit; }

$usuario = $_SESSION['usuario'] ?? '';
// etapa exibida na UI (normaliza user/admin => estoque)
$rawEtapa = strtolower($_SESSION['etapa_permitida'] ?? $_SESSION['perfil'] ?? 'estoque');
$mapEtapa = ['user' => 'estoque', 'admin' => 'estoque'];
$etapa = strtoupper($mapEtapa[$rawEtapa] ?? $rawEtapa);

// AJAX: salvar data ativa na sessão
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['__setDate'])) {
  $d = $_POST['date'] ?? '';
  $ok=false; $msg='Data inválida.'; $out=$d;
  $dt = DateTime::createFromFormat('Y-m-d', $d);
  if ($dt && $dt->format('Y-m-d') === $d) {
    $_SESSION['data_trabalho'] = $d;
    $ok=true; $msg='Data atualizada.'; $out=$d;
  }
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['ok'=>$ok,'mensagem'=>$msg,'data'=>$out]);
  exit;
}

$dataAtiva = $_SESSION['data_trabalho'] ?? date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Cadastrar Bips — MULTCABOS</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
:root{
  --bg:#f5f7fa;--card:#fff;--text:#111827;--muted:#6b7280;--chip:#eef1f4;
  --brand:#d32f2f;--ok:#16a34a;--err:#dc2626;--focus:#111827;--shadow:0 8px 24px rgba(0,0,0,.08);
}
*{box-sizing:border-box} body{margin:0;background:var(--bg);color:var(--text);font-family:Inter,system-ui,Segoe UI,Roboto,Arial,sans-serif}
.wrap{max-width:960px;margin:28px auto;padding:0 16px}
.back{display:inline-block;margin-bottom:10px;color:#374151;text-decoration:none}
.card{background:var(--card);border-radius:16px;box-shadow:var(--shadow);padding:20px}
.head{display:flex;justify-content:space-between;align-items:center;gap:8px}
.brand{font-weight:800;font-size:18px}.brand .r{color:var(--brand)}.brand .k{color:#111827}
.etapa{display:inline-flex;gap:8px;align-items:center;background:var(--chip);padding:6px 10px;border-radius:999px;font-size:12px;color:#111}
.muted{color:var(--muted)}
.row{display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;margin:10px 0}
.col{flex:1 1 220px}
input[type="date"]{width:100%;padding:12px 14px;border:1.5px solid #e5e7eb;border-radius:12px;font:inherit}
input[type="date"]:focus{outline:none;border-color:var(--focus)}
.btn{display:inline-flex;align-items:center;gap:8px;background:#eceff2;border:none;color:#111;border-radius:10px;padding:10px 14px;font-weight:600;cursor:pointer}
.input-giant{width:100%;padding:18px 20px;border:2px solid #111827;border-radius:14px;font-size:20px}
.input-giant:focus{outline:none;box-shadow:0 0 0 4px rgba(17,24,39,.1)}
.chips{display:flex;gap:8px;flex-wrap:wrap;margin:8px 0 12px}
.chip{background:var(--chip);border-radius:999px;padding:6px 10px;font-size:12px}
.msg{margin-top:10px;padding:10px 12px;border-radius:12px;font-weight:600}
.msg.ok{background:#ecfdf5;color:#065f46}.msg.err{background:#fef2f2;color:#991b1b}
.list{margin-top:12px;border-top:1px dashed #e5e7eb;padding-top:12px}
.item{display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid #f1f2f4}
.badge{padding:4px 10px;border-radius:999px;font-size:12px}
.badge.ok{background:#dcfce7;color:#166534}
.shake{animation:shake .25s linear 2;border-color:#dc2626 !important;box-shadow:0 0 0 4px rgba(220,38,38,.10) !important}
@keyframes shake{0%{transform:translateX(0)}25%{transform:translateX(-4px)}50%{transform:translateX(4px)}75%{transform:translateX(-4px)}100%{transform:translateX(0)}}
#toasts{position:fixed;right:20px;bottom:20px;display:flex;flex-direction:column;gap:10px;z-index:9999}
.toast{min-width:260px;max-width:360px;padding:12px 14px;border-radius:10px;background:#fff;color:#111;box-shadow:0 8px 24px rgba(0,0,0,.18);font-weight:600}
.toast.ok{border-left:6px solid #16a34a}.toast.err{border-left:6px solid #dc2626}

/* Modal */
#modalMask{position:fixed;inset:0;background:rgba(17,24,39,.45);display:none;align-items:center;justify-content:center;z-index:9998}
#modal{width:min(760px,92vw);background:#fff;border-radius:14px;box-shadow:0 20px 40px rgba(0,0,0,.25);overflow:hidden}
.m-header{display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-bottom:1px solid #eef}
.m-title{font-weight:800}
.m-body{padding:16px}
.alert{background:#fee2e2;color:#991b1b;border-left:6px solid #dc2626;padding:10px 12px;border-radius:8px;margin-bottom:12px}
.grid{display:grid;grid-template-columns:1fr 320px;gap:16px}
.tl{background:#f8fafc;border:1px solid #eef;padding:10px;border-radius:10px}
.trow{display:flex;align-items:center;gap:10px;padding:8px 6px;border-bottom:1px dashed #e7eaf0}
.tdot{width:9px;height:9px;border-radius:50%}
.tdot.g{background:#16a34a}.tdot.b{background:#60a5fa}.tdot.k{background:#9ca3af}
.m-chip{display:inline-flex;gap:8px;align-items:center;background:#eef1f4;border-radius:999px;padding:5px 10px;font-size:12px;color:#111}
.m-chip code{background:#fff;border:1px solid #e7eaf0;border-radius:6px;padding:2px 6px}
.m-actions{display:flex;justify-content:flex-end;padding:12px 16px;border-top:1px solid #eef}
.m-button{background:#111;color:#fff;border:none;border-radius:10px;padding:10px 14px;font-weight:700;cursor:pointer}
.m-close{background:#eef;border:none;border-radius:8px;padding:8px 10px;cursor:pointer}
</style>
</head>
<body>
<div class="wrap">
  <a class="back" href="dashboard_user.php">‹ Voltar ao Dashboard</a>

  <div class="card">
    <div class="head">
      <div class="brand"><span class="r">MULT</span><span class="k">CABOS</span></div>
      <div class="etapa"><strong>Etapa:</strong> <span><?= htmlspecialchars($etapa) ?></span> • <span class="muted">Usuário: <?= htmlspecialchars($usuario) ?></span></div>
    </div>

    <p class="muted" style="margin:6px 0 14px">Aponte o leitor de código no campo abaixo e pressione <strong>Enter</strong>.</p>

    <div class="row">
      <div class="col">
        <label class="muted" style="display:block;margin-bottom:6px">Data de trabalho</label>
        <input type="date" id="data" value="<?= htmlspecialchars($dataAtiva) ?>">
      </div>
      <div class="col" style="flex:0 0 auto;display:flex;gap:8px">
        <button class="btn" id="ontem"  type="button">Ontem</button>
        <button class="btn" id="hoje"   type="button">Hoje</button>
        <button class="btn" id="amanha" type="button">Amanhã</button>
      </div>
    </div>

    <input id="codigo" class="input-giant" placeholder="Insira o código" autofocus>

    <div class="chips">
      <div class="chip">Ativa: <strong id="ativa"><?= date('d/m/Y', strtotime($dataAtiva)) ?></strong></div>
      <div class="chip">Atalhos: <strong>Enter</strong> enviar · <strong>.</strong> focar · <strong>Esc</strong> limpar</div>
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

<!-- Modal Fluxo -->
<div id="modalMask">
  <div id="modal">
    <div class="m-header">
      <div class="m-title">Fluxo do código</div>
      <button class="m-close" id="mClose">✕</button>
    </div>
    <div class="m-body">
      <div id="mAlert" class="alert" style="display:none"></div>
      <div class="m-chip" id="mChip" style="margin-bottom:8px;display:none">Código: <code id="mCodigo"></code></div>
      <div class="grid">
        <div class="tl" id="mTimeline"><!-- linhas timeline --></div>
        <div class="tl">
          <div style="font-weight:700;margin-bottom:6px">Status</div>
          <div style="display:flex;justify-content:space-between;margin:10px 0">
            <div class="muted">Fila atual</div><div id="mFila" style="font-weight:700"></div>
          </div>
          <div style="display:flex;justify-content:space-between;margin:10px 0">
            <div class="muted">Próxima etapa</div><div id="mProx" style="font-weight:700"></div>
          </div>
        </div>
      </div>
    </div>
    <div class="m-actions">
      <button class="m-button" id="mOk">Ok, entendi</button>
    </div>
  </div>
</div>

<script>
const input  = document.getElementById('codigo');
const msg    = document.getElementById('msg');
const ult    = document.getElementById('ultimos');
const data   = document.getElementById('data');
const ativa  = document.getElementById('ativa');
const okS    = document.getElementById('snd-ok');
const errS   = document.getElementById('snd-err');
const lotS   = document.getElementById('snd-lote');
const toasts = document.getElementById('toasts');

// modal
const modalMask = document.getElementById('modalMask');
const mClose    = document.getElementById('mClose');
const mOk       = document.getElementById('mOk');
const mAlert    = document.getElementById('mAlert');
const mTimeline = document.getElementById('mTimeline');
const mFila     = document.getElementById('mFila');
const mProx     = document.getElementById('mProx');
const mChip     = document.getElementById('mChip');
const mCodigo   = document.getElementById('mCodigo');
function abrirModalFluxo(payload){
  // mensagem
  mAlert.style.display = payload.mensagem ? 'block' : 'none';
  mAlert.textContent   = payload.mensagem || '';
  // chip
  mCodigo.textContent = payload.codigo || '';
  mChip.style.display = payload.codigo ? 'inline-flex' : 'none';
  // timeline
  mTimeline.innerHTML = '';
  (payload.timeline || []).forEach(l => {
    const d = document.createElement('div');
    d.className = 'trow';
    const dot = document.createElement('span');
    dot.className = 'tdot ' + (l.status === 'ok' ? 'g' : l.status === 'pendente' ? 'b' : 'k');
    const txt = document.createElement('div');
    txt.innerHTML = `<strong>${l.etapaLabel}</strong><br><span class="muted">Usuário: ${l.usuario || '—'} · Hora: ${l.hora || '—'}</span>`;
    d.appendChild(dot); d.appendChild(txt);
    mTimeline.appendChild(d);
  });
  // status
  mFila.textContent = payload.filaAtual || '—';
  mProx.textContent = payload.proximaEtapa || '—';
  // abre
  modalMask.style.display = 'flex';
}
function fecharModal(){ modalMask.style.display='none'; }
mClose.onclick = fecharModal; mOk.onclick = fecharModal; modalMask.addEventListener('click',e=>{ if(e.target===modalMask)fecharModal(); });

// toasts/bump
function showToast(text,type='ok',ms=2500){
  const el=document.createElement('div'); el.className=`toast ${type}`; el.textContent=text;
  toasts.appendChild(el); setTimeout(()=>{ el.style.opacity='0'; el.style.transform='translateY(4px)'; setTimeout(()=>el.remove(),220); }, ms);
}
function bump(){ input.classList.remove('shake'); void input.offsetWidth; input.classList.add('shake'); if(navigator.vibrate)navigator.vibrate(80); }

// helpers
const fmtBR=s=>{const [y,m,d]=s.split('-');return `${d}/${m}/${y}`;}
function isoLocal(dias=0){ const t=new Date(); t.setHours(0,0,0,0); t.setDate(t.getDate()+dias);
  return `${t.getFullYear()}-${String(t.getMonth()+1).padStart(2,'0')}-${String(t.getDate()).padStart(2,'0')}`; }

async function salvarDataAtiva(iso){
  try{ const f=new FormData(); f.append('__setDate','1'); f.append('date',iso);
    const r=await fetch(location.href,{method:'POST',body:f}); const j=await r.json();
    if(j.ok){ ativa.textContent=fmtBR(j.data); localStorage.setItem('data_trabalho',j.data); } }catch(_){}
}
async function carregarRecentes(){
  try{ const r=await fetch('recentes_bips.php',{cache:'no-store'}); const j=await r.json(); if(!j.ok)return;
    ult.innerHTML=''; (j.itens||[]).forEach(row=>{
      const it=document.createElement('div'); it.className='item';
      it.innerHTML=`<span>${row.hora.slice(0,5)} · <strong>${row.codigo}</strong></span><span class="badge ok">OK</span>`;
      ult.appendChild(it);
    }); }catch(_){}
}

// botões rápidos
document.getElementById('hoje').onclick   = ()=>{ const iso=isoLocal(0);  data.value=iso; salvarDataAtiva(iso).then(carregarRecentes); };
document.getElementById('ontem').onclick  = ()=>{ const iso=isoLocal(-1); data.value=iso; salvarDataAtiva(iso).then(carregarRecentes); };
document.getElementById('amanha').onclick = ()=>{ const iso=isoLocal(1);  data.value=iso; salvarDataAtiva(iso).then(carregarRecentes); };
data.addEventListener('change',()=> salvarDataAtiva(data.value).then(carregarRecentes));

// atalhos
document.addEventListener('keydown',(e)=>{ if(e.key==='.') {input.focus(); e.preventDefault();} if(e.key==='Escape'){input.value='';}});

// proteção duplo enter
let busy=false;
input.addEventListener('keydown', async (e)=>{
  if(e.key!=='Enter' || busy) return; e.preventDefault(); busy=true;

  const codigo=input.value.trim();
  if(!codigo){ busy=false; return; }

  // valida 12–20 no front (server confirma também)
  if(codigo.length<12 || codigo.length>20){
    msg.style.display='block'; msg.className='msg err';
    msg.textContent='O código deve ter entre 12 e 20 caracteres.'; errS.currentTime=0; errS.play().catch(()=>{});
    bump(); input.value=''; input.focus(); busy=false; return;
  }

  msg.style.display='block'; msg.className='msg'; msg.textContent='Processando…';
  try{
    const r=await fetch('processa_bip.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({codigo})});
    const j=await r.json();

    if(j.ok){
      msg.className='msg ok'; msg.textContent=j.mensagem||'Registrado com sucesso.'; okS.currentTime=0; okS.play().catch(()=>{});
      if(j.loteFechado){ lotS.currentTime=0; lotS.play().catch(()=>{});}
      // adiciona sucesso local + recarrega lista (mantém só 4)
      const row=document.createElement('div'); row.className='item';
      row.innerHTML=`<span>${new Date().toLocaleTimeString('pt-BR').slice(0,5)} · <strong>${codigo}</strong></span><span class="badge ok">OK</span>`;
      ult.prepend(row); while(ult.children.length>4) ult.lastChild.remove();
      carregarRecentes();
      input.value=''; input.focus();
    }else{
      // erro: pode vir modal
      msg.className='msg err'; msg.textContent=j.mensagem||'Erro no registro.'; errS.currentTime=0; errS.play().catch(()=>{});
      if(j.modal){ abrirModalFluxo(j.modal); }
      showToast(j.mensagem||'Erro no registro.','err');
      input.value=''; input.focus();
    }
  }catch(_){
    msg.className='msg err'; msg.textContent='Falha de comunicação com o servidor.'; errS.currentTime=0; errS.play().catch(()=>{});
    showToast('Falha de comunicação com o servidor.','err'); input.value=''; input.focus();
  }
  busy=false;
});

// init
(function init(){ const dSessao='<?= htmlspecialchars($dataAtiva) ?>'; data.value=dSessao; ativa.textContent=fmtBR(dSessao); carregarRecentes(); })();
</script>
</body>
</html>
