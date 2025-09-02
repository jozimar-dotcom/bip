<?php
session_start();

if (!isset($_SESSION['erro_duplicado'])) {
    header('Location: cadastrar_bip.php');
    exit();
}

$erro = $_SESSION['erro_duplicado'];
unset($_SESSION['erro_duplicado']);

require_once '../config/config.php';

// Consulta completa para buscar mais detalhes do código
$stmt = $conn->prepare("SELECT codigo, horario, usuario FROM bipagens WHERE codigo = :codigo LIMIT 1");
$stmt->execute([':codigo' => $erro['codigo']]);
$info = $stmt->fetch(PDO::FETCH_ASSOC);

// Se por algum motivo não encontrar o código novamente
if (!$info) {
    $info = ['codigo' => $erro['codigo'], 'horario' => null, 'usuario' => 'Desconhecido'];
}

// Separar data e hora
$data = $info['horario'] ? date('d/m/Y', strtotime($info['horario'])) : '—';
$hora = $info['horario'] ? date('H:i:s', strtotime($info['horario'])) : '—';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Erro - Código Duplicado</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light min-vh-100 d-flex flex-column">

<?php include_once '../includes/header.php'; ?>

<main class="flex-grow-1 d-flex align-items-center justify-content-center p-4">
    <div class="card shadow-lg" style="max-width: 600px; width: 100%;">
        <div class="card-body text-center">
            <div class="text-warning mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" class="bi bi-exclamation-triangle-fill" viewBox="0 0 16 16">
                    <path d="M8 0c-.69 0-1.37.176-1.97.512L.29 5.012A3.52 3.52 0 0 0 0 8c0 1.227.66 2.35 1.71 2.988l5.74 3.5c.6.366 1.28.512 1.97.512.69 0 1.37-.146 1.97-.512l5.74-3.5A3.52 3.52 0 0 0 16 8a3.52 3.52 0 0 0-1.71-2.988l-5.74-3.5A3.962 3.962 0 0 0 8 0Zm.93 11.412a.933.933 0 1 1-1.86 0 .933.933 0 0 1 1.86 0Zm-.93-2.912a.9.9 0 0 1-.9-.9V4.9a.9.9 0 0 1 1.8 0v2.7a.9.9 0 0 1-.9.9Z"/>
                </svg>
            </div>
            <h4 class="text-danger">Código já registrado!</h4>
            <p class="text-secondary">O código informado já foi registrado anteriormente.</p>

            <table class="table table-bordered mt-4">
                <thead class="table-light">
                <tr>
                    <th scope="col">Código</th>
                    <th scope="col">Lote</th>
                    <th scope="col">Data</th>
                    <th scope="col">Hora</th>
                    <th scope="col">Usuário</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td class="fw-bold text-primary"><?= htmlspecialchars($info['codigo']) ?></td>
                    <td><?= htmlspecialchars($erro['lote']) ?></td>
                    <td><?= $data ?></td>
                    <td><?= $hora ?></td>
                    <td><?= htmlspecialchars($info['usuario']) ?></td>
                </tr>
                </tbody>
            </table>

            <div class="mt-4 d-flex justify-content-center gap-3">
                <a href="cadastrar_bip.php" class="btn btn-primary">Tentar novamente</a>
                <a href="relatorio.php" class="btn btn-secondary">Ver Relatório</a>
            </div>
        </div>
    </div>
</main>

<?php include_once '../includes/footer.php'; ?>

</body>
</html>
