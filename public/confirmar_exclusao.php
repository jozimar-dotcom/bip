<?php
session_start();
require_once '../config/config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: relatorio.php");
    exit();
}

$id = intval($_GET['id']);
$stmt = $conn->prepare("SELECT * FROM bipagens WHERE id = :id");
$stmt->execute([':id' => $id]);
$registro = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$registro) {
    echo "<div style='padding:20px; font-family:sans-serif;'>Registro não encontrado. <a href='relatorio.php'>Voltar</a></div>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Confirmar Exclusão</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .icon-alert {
            width: 80px;
            height: 80px;
            display: block;
            margin: 0 auto 20px auto;
        }
    </style>
</head>
<body>

<div class="container d-flex justify-content-center align-items-center" style="min-height: 100vh;">
    <div class="card shadow-lg p-4" style="max-width: 500px; width: 100%;">
        <div class="text-center">
            <img src="https://cdn-icons-png.flaticon.com/512/463/463612.png" alt="Alerta" class="icon-alert">
            <h4 class="text-danger mb-3">Confirmar Exclusão</h4>
            <p class="mb-4">Tem certeza que deseja excluir o seguinte registro?</p>
        </div>

        <ul class="list-group mb-4">
            <li class="list-group-item"><strong>Código:</strong> <?= htmlspecialchars($registro['codigo']) ?></li>
            <li class="list-group-item"><strong>Usuário:</strong> <?= htmlspecialchars($registro['usuario']) ?></li>
            <li class="list-group-item"><strong>Data e Hora:</strong> <?= date('d/m/Y H:i:s', strtotime($registro['horario'])) ?></li>
        </ul>

        <form method="POST" action="excluir_registro.php" class="d-flex justify-content-between">
            <input type="hidden" name="id" value="<?= $registro['id'] ?>">
            <a href="relatorio.php" class="btn btn-outline-secondary">Cancelar</a>
            <button type="submit" class="btn btn-danger">✅ Confirmar Exclusão</button>
        </form>
    </div>
</div>

</body>
</html>
