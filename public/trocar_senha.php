<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../config/config.php';

if (empty($_SESSION['usuario']) || ($_SESSION['perfil'] ?? '') !== 'admin') { header('HTTP/1.1 403'); echo 'Acesso negado.'; exit; }

$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$id = (int)($_GET['id'] ?? 0);
$st = $conn->prepare("SELECT id, usuario FROM usuarios WHERE id=?");
$st->execute([$id]);
$u = $st->fetch(PDO::FETCH_ASSOC);
if (!$u) { echo 'Usuário não encontrado.'; exit; }

if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['csrf'];

$msg = '';
if ($_SERVER['REQUEST_METHOD']==='POST') {
  if (!hash_equals($csrf, $_POST['__csrf'] ?? '')) { $msg = 'Falha de segurança.'; }
  else {
    try {
      $senha = (string)($_POST['senha'] ?? '');
      if ($senha === '') throw new Exception('Informe a nova senha.');

      $hash = password_hash($senha, PASSWORD_DEFAULT);
      $up = $conn->prepare("UPDATE usuarios SET senha=? WHERE id=?");
      $up->execute([$hash, $id]);
      $msg = 'Senha atualizada.';
    } catch (Throwable $e) { $msg = 'Erro: '.$e->getMessage(); }
  }
}
?>
<!DOCTYPE html><html lang="pt-BR"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Trocar senha</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head><body class="bg-light py-4">
<div class="container" style="max-width:700px">
  <h1 class="h4 mb-3">Trocar senha — <?= htmlspecialchars($u['usuario']) ?></h1>
  <?php if ($msg): ?><div class="alert alert-info"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <div class="card shadow-sm p-3">
    <form method="post" class="row g-3">
      <input type="hidden" name="__csrf" value="<?= htmlspecialchars($csrf) ?>">
      <div class="col-12">
        <label class="form-label">Nova senha</label>
        <input type="password" name="senha" class="form-control" required>
      </div>
      <div class="col-12 d-flex gap-2">
        <button class="btn btn-primary">Salvar</button>
        <a class="btn btn-outline-secondary" href="usuarios.php">Voltar</a>
      </div>
    </form>
  </div>
</div>
</body></html>
