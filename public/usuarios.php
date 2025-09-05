<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../config/config.php';

if (empty($_SESSION['usuario']) || ($_SESSION['perfil'] ?? '') !== 'admin') {
  header('HTTP/1.1 403 Forbidden'); echo 'Acesso negado.'; exit;
}

$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$rows = $conn->query("SELECT id, usuario, perfil, etapa_permitida, criado_em FROM usuarios ORDER BY usuario ASC")
             ->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Usuários</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light py-4">
<div class="container">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 m-0">Usuários</h1>
    <div class="d-flex gap-2">
      <a class="btn btn-primary" href="cadastrar_usuario.php">+ Novo usuário</a>
      <a class="btn btn-outline-secondary" href="dashboard.php">← Voltar</a>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="table-responsive">
      <table class="table align-middle m-0">
        <thead class="table-light">
          <tr>
            <th>#</th><th>Usuário</th><th>Perfil</th><th>Etapa</th><th>Criado em</th><th style="width:220px">Ações</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?= (int)$r['id'] ?></td>
            <td><?= htmlspecialchars($r['usuario']) ?></td>
            <td><span class="badge text-bg-dark"><?= htmlspecialchars($r['perfil']) ?></span></td>
            <td><span class="badge text-bg-secondary"><?= htmlspecialchars($r['etapa_permitida'] ?? '-') ?></span></td>
            <td><?= htmlspecialchars($r['criado_em']) ?></td>
            <td class="d-flex gap-2">
              <a class="btn btn-sm btn-primary" href="editar_usuario.php?id=<?= (int)$r['id'] ?>">Editar</a>
              <a class="btn btn-sm btn-warning" href="trocar_senha.php?id=<?= (int)$r['id'] ?>">Trocar senha</a>
              <a class="btn btn-sm btn-outline-danger" href="excluir_usuario.php?id=<?= (int)$r['id'] ?>" onclick="return confirm('Excluir este usuário?');">Excluir</a>
            </td>
          </tr>
        <?php endforeach; if (!count($rows)): ?>
          <tr><td colspan="6" class="text-muted text-center py-4">Nenhum usuário encontrado.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</body>
</html>
