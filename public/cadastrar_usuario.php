<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../config/config.php';

if (empty($_SESSION['usuario']) || ($_SESSION['perfil'] ?? '') !== 'admin') { header('HTTP/1.1 403'); echo 'Acesso negado.'; exit; }

$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$PERFIS = ['admin','user','conferente'];
$ETAPAS = ['estoque','embalagem','conferencia','admin'];

if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['csrf'];

$msg = '';
if ($_SERVER['REQUEST_METHOD']==='POST') {
  if (!hash_equals($csrf, $_POST['__csrf'] ?? '')) { $msg = 'Falha de segurança.'; }
  else {
    try {
      $usuario = trim($_POST['usuario'] ?? '');
      $perfil  = trim($_POST['perfil']  ?? '');
      $etapa   = trim($_POST['etapa_permitida'] ?? '');
      $senha   = (string)($_POST['senha'] ?? '');

      if ($usuario==='' || $senha==='') throw new Exception('Informe usuário e senha.');
      if (!in_array($perfil, $PERFIS, true)) throw new Exception('Perfil inválido.');
      if (!in_array($etapa, $ETAPAS, true)) throw new Exception('Etapa inválida.');

      $st = $conn->prepare("SELECT COUNT(*) FROM usuarios WHERE usuario=?");
      $st->execute([$usuario]);
      if ((int)$st->fetchColumn() > 0) throw new Exception('Usuário já existe.');

      $hash = password_hash($senha, PASSWORD_DEFAULT);
      $ins = $conn->prepare("INSERT INTO usuarios (usuario, senha, perfil, etapa_permitida, criado_em) VALUES (?,?,?,?,NOW())");
      $ins->execute([$usuario, $hash, $perfil, $etapa]);

      header('Location: usuarios.php'); exit;
    } catch (Throwable $e) { $msg = 'Erro: '.$e->getMessage(); }
  }
}
?>
<!DOCTYPE html><html lang="pt-BR"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Cadastrar usuário</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head><body class="bg-light py-4">
<div class="container" style="max-width:700px">
  <h1 class="h4 mb-3">Cadastrar usuário</h1>
  <?php if ($msg): ?><div class="alert alert-info"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <div class="card shadow-sm p-3">
    <form method="post" class="row g-3">
      <input type="hidden" name="__csrf" value="<?= htmlspecialchars($csrf) ?>">
      <div class="col-12">
        <label class="form-label">Usuário</label>
        <input name="usuario" class="form-control" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">Perfil</label>
        <select name="perfil" class="form-select" required>
          <?php foreach ($PERFIS as $p): ?><option value="<?= $p ?>"><?= $p ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Etapa permitida</label>
        <select name="etapa_permitida" class="form-select" required>
          <?php foreach ($ETAPAS as $e): ?><option value="<?= $e ?>"><?= $e ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-12">
        <label class="form-label">Senha</label>
        <input type="password" name="senha" class="form-control" required>
      </div>
      <div class="col-12 d-flex gap-2">
        <button class="btn btn-primary">Salvar</button>
        <a class="btn btn-outline-secondary" href="usuarios.php">Cancelar</a>
      </div>
    </form>
  </div>
</div>
</body></html>
