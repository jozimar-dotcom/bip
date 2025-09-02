<?php
// public/cadastrar_bip.php — tela de bipagem com "Data de trabalho"
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

// exige login
if (empty($_SESSION['usuario'])) {
  header('Location: index.php');
  exit;
}

// atualiza a data de trabalho quando clicar em "Usar essa data"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['definir_data'])) {
  $d  = (string)($_POST['data_trabalho'] ?? '');
  $dt = DateTime::createFromFormat('Y-m-d', $d);
  if ($dt && $dt->format('Y-m-d') === $d) {
    $_SESSION['data_trabalho'] = $d; // salva na sessão
  }
  header('Location: cadastrar_bip.php');
  exit;
}

// data ativa (padrão = hoje)
$dataAtualTrabalho = $_SESSION['data_trabalho'] ?? date('Y-m-d');

include __DIR__ . '/../includes/header.php';
?>
<style>
  .page-wrap   { max-width: 960px; margin: 16px auto; padding: 0 12px; }
  .back-link   { display:inline-flex; align-items:center; gap:6px; margin:8px 0 20px; text-decoration:none; color:#333; }
  .card        { max-width: 420px; margin: 0 auto; background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,.07); padding: 18px 16px; text-align:center; }
  .brand       { font-weight:700; letter-spacing:.2px; margin-bottom:6px; }
  .muted       { opacity:.75; }
  .date-row    { display:flex; justify-content:center; align-items:center; gap:10px; flex-wrap:wrap; margin:6px 0 12px; }
  .date-row input[type="date"] { padding:6px 8px; }
  .date-row button { padding:8px 10px; cursor:pointer; }
  .scan-input  { width:100%; padding:12px 14px; font-size:1rem; border:1px solid #cfd6de; border-radius:6px; }
  .submit-btn  { display:none; } /* envio é via Enter/leitor */
  #mensagem    { margin-top:10px; min-height: 1.2em; }
</style>

<div class="page-wrap">
  <?php include __DIR__ . '/../includes/voltar_dashboard.php'; ?>

  <div class="card">
    <div class="brand">MULT<span class="muted">CABOS</span></div>
    <p class="muted" style="margin:0 0 12px">Aponte o leitor de código de barras</p>

    <!-- Seletor de Data de Trabalho -->
    <form method="post" class="date-row">
      <label for="data_trabalho"><strong>Data de trabalho</strong></label>
      <input
        type="date"
        id="data_trabalho"
        name="data_trabalho"
        value="<?= htmlspecialchars($dataAtualTrabalho) ?>"
        min="<?= date('Y-m-d') ?>"  <!-- permite hoje e futuro; remova o min para permitir passado -->
      >
      <button type="submit" name="definir_data">Usar essa data</button>
      <span class="muted">(Ativa: <?= htmlspecialchars(date('d/m/Y', strtotime($dataAtualTrabalho))) ?>)</span>
    </form>

    <!-- Campo de leitura -->
    <form id="form-bip" onsubmit="return enviarBip(event)">
      <input
        type="text"
        name="codigo_barras"
        id="codigo_barras"
        class="scan-input"
        inputmode="numeric"
        autocomplete="off"
        autofocus
        placeholder="Insira o código"
        minlength="10"
        maxlength="20"
        required
      >
      <button class="submit-btn" type="submit">Registrar</button>
    </form>

    <div id="mensagem" class="muted"></div>
  </div>
</div>

<script>
  async function enviarBip(e){
    e.preventDefault();
    const inp  = document.getElementById('codigo_barras');
    const msg  = document.getElementById('mensagem');

    const dados = new FormData();
    dados.append('codigo_barras', inp.value);
    // envia também a data ativa (além de estar na sessão)
    dados.append('data_trabalho', '<?= htmlspecialchars($dataAtualTrabalho) ?>');

    msg.textContent = 'Enviando...';

    try {
      const r = await fetch('processa_bip.php', { method: 'POST', body: dados });
      const j = await r.json();
      if (j.status === 'success') {
        msg.textContent = j.mensagem || 'Registrado.';
        inp.value = '';
        inp.focus();
      } else if (j.status === 'redirect' && j.location) {
        window.location.href = j.location;
      } else {
        msg.textContent = j.mensagem || 'Falha ao registrar.';
      }
    } catch (err) {
      msg.textContent = 'Erro de comunicação.';
    }
    return false;
  }
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
