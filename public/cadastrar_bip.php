<?php
// cadastrar_bip.php — tela com seletor de "Data de trabalho" (hoje ou futura)
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

// 1) Atualiza a data de trabalho (POST do seletor)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['definir_data'])) {
  $d  = (string)($_POST['data_trabalho'] ?? '');
  $dt = DateTime::createFromFormat('Y-m-d', $d);
  if ($dt && $dt->format('Y-m-d') === $d) {
    // Se desejar impedir datas passadas, valide aqui antes de salvar
    $_SESSION['data_trabalho'] = $d;
  }
  header('Location: cadastrar_bip.php');
  exit;
}

// Data ativa (default = hoje)
$dataAtualTrabalho = $_SESSION['data_trabalho'] ?? date('Y-m-d');

// Segurança mínima: exige login
if (empty($_SESSION['usuario'])) {
  header('Location: index.php');
  exit;
}

include __DIR__ . '/../includes/header.php';
?>
<div style="max-width:760px;margin:16px auto;padding:0 12px;">
  <div style="display:flex;align-items:center;gap:12px;margin:8px 0 4px;">
    <img src="../logo/logo.png" alt="Logo" style="height:40px;">
    <h1 style="margin:0;font-size:1.4rem;">Cadastrar Bips</h1>
  </div>

  <!-- Seletor de Data de Trabalho -->
  <form method="post" style="margin:12px 0;display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
    <label for="data_trabalho"><strong>Data de trabalho:</strong></label>
    <input
      type="date"
      id="data_trabalho"
      name="data_trabalho"
      value="<?= htmlspecialchars($dataAtualTrabalho) ?>"
      min="<?= date('Y-m-d') ?>"  <!-- permite hoje e futuro; remova o min para permitir passado -->
      style="padding:6px 8px"
    >
    <button type="submit" name="definir_data" style="padding:6px 10px;">Usar essa data</button>
    <span style="opacity:.75;">(Ativa: <?= htmlspecialchars(date('d/m/Y', strtotime($dataAtualTrabalho))) ?>)</span>
  </form>

  <hr style="margin:12px 0;">

  <p style="margin:8px 0 12px;">Aponte o leitor de código de barras para o campo abaixo (ou digite e pressione Enter).</p>

  <!-- Formulário simples de bip (funciona com leitores que simulam teclado) -->
  <form id="form-bip" style="display:flex;gap:8px;flex-wrap:wrap;" onsubmit="return enviarBip(event);">
    <input
      type="text"
      name="codigo_barras"
      id="codigo_barras"
      inputmode="numeric"
      autocomplete="off"
      autofocus
      placeholder="Leia o código de barras"
      minlength="10"
      maxlength="20"
      required
      style="flex:1;min-width:260px;padding:10px 12px;font-size:1rem;"
    >
    <button type="submit" style="padding:10px 12px;">Registrar</button>
  </form>

  <div id="mensagem" style="margin-top:10px;"></div>

  <script>
    async function enviarBip(e){
      e.preventDefault();
      const form = document.getElementById('form-bip');
      const inp  = document.getElementById('codigo_barras');
      const msg  = document.getElementById('mensagem');

      const dados = new FormData();
      dados.append('codigo_barras', inp.value);
      // data_trabalho vai por sessão; mas enviamos também por redundância:
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
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
