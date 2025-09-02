<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit();
}

$perfil = $_SESSION['perfil'] ?? '';
$dataHoje = date('Y-m-d');
$usuarioLogado = $_SESSION['usuario'] ?? '';

// Total de códigos bipados hoje
$stmt1 = $conn->prepare("SELECT COUNT(*) FROM bipagens WHERE DATE(horario) = ?");
$stmt1->execute([$dataHoje]);
$totalHoje = $stmt1->fetchColumn();

// Total de códigos no sistema
$stmt5 = $conn->query("SELECT COUNT(*) FROM bipagens");
$totalCodigosSistema = $stmt5->fetchColumn();

// Recuperar os IDs e usuários dos bips do dia
$stmtIds = $conn->prepare("SELECT id, usuario FROM bipagens WHERE DATE(horario) = ? ORDER BY id ASC");
$stmtIds->execute([$dataHoje]);
$bipsHoje = $stmtIds->fetchAll(PDO::FETCH_ASSOC);

$ids = array_column($bipsHoje, 'id');
$lotesAgrupados = array_chunk($ids, 10);
$totalLotesHoje = count($lotesAgrupados);
$totalLotesCheios = count(array_filter($lotesAgrupados, fn($grupo) => count($grupo) === 10));
$totalLotesAbertos = $totalLotesHoje - $totalLotesCheios;

// Dados do usuário logado (user ou admin)
$userCodigos = 0;
$userIds = [];
foreach ($bipsHoje as $bip) {
    if ($bip['usuario'] === $usuarioLogado) {
        $userCodigos++;
        $userIds[] = $bip['id'];
    }
}
$userLotes = array_chunk($userIds, 10);
$userLotesCheios = count(array_filter($userLotes, fn($grupo) => count($grupo) === 10));
$userLotesAbertos = count($userLotes) - $userLotesCheios;
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - MULTCABOS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .card:hover {
            transform: scale(1.02);
            transition: transform 0.2s ease-in-out;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.15);
            cursor: pointer;
        }
    </style>
</head>
<body class="bg-light">

<?php include_once '../includes/header.php'; ?>

<div class="container py-4">

    <div class="row row-cols-1 row-cols-md-2 g-4 mb-4">
        <div class="col">
            <a href="cadastrar_bip.php" class="text-decoration-none text-dark">
                <div class="card shadow-sm h-100">
                    <div class="card-body d-flex align-items-center">
                        <i class="bi bi-upc-scan fs-1 me-3 text-dark"></i>
                        <div>
                            <h5 class="card-title mb-0">Cadastrar Bips</h5>
                            <p class="card-text text-muted">Ler e registrar códigos por lote.</p>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col">
            <a href="relatorio.php" class="text-decoration-none text-dark">
                <div class="card shadow-sm h-100">
                    <div class="card-body d-flex align-items-center">
                        <i class="bi bi-bar-chart-line fs-1 me-3 text-success"></i>
                        <div>
                            <h5 class="card-title mb-0">Relatório</h5>
                            <p class="card-text text-muted">Visualizar registros bipados por lote.</p>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <h2 class="mb-4">Dashboard</h2>

    <div class="row row-cols-1 row-cols-md-5 g-4 mb-4">
        <div class="col">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <i class="bi bi-upc-scan fs-2 text-primary"></i>
                    <h6 class="mt-2">Códigos Bipados</h6>
                    <h4><?= $totalHoje ?></h4>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <i class="bi bi-stack fs-2 text-secondary"></i>
                    <h6 class="mt-2">Total de Lotes</h6>
                    <h4><?= $totalLotesHoje ?></h4>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <i class="bi bi-check-circle fs-2 text-success"></i>
                    <h6 class="mt-2">Lotes Completos</h6>
                    <h4><?= $totalLotesCheios ?></h4>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <i class="bi bi-hourglass-split fs-2 text-warning"></i>
                    <h6 class="mt-2">Lotes Incompletos</h6>
                    <h4><?= $totalLotesAbertos ?></h4>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <i class="bi bi-database fs-2 text-dark"></i>
                    <h6 class="mt-2">Total no Sistema</h6>
                    <h4><?= $totalCodigosSistema ?></h4>
                </div>
            </div>
        </div>
    </div>

    <h5 class="mb-3">Meus Registros de Hoje</h5>
    <div class="row row-cols-1 row-cols-md-3 g-4 mb-5">
        <div class="col">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <i class="bi bi-person-badge fs-2 text-info"></i>
                    <h6 class="mt-2">Meus Códigos Bipados</h6>
                    <h4><?= $userCodigos ?></h4>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <i class="bi bi-check2-circle fs-2 text-success"></i>
                    <h6 class="mt-2">Meus Lotes Completos</h6>
                    <h4><?= $userLotesCheios ?></h4>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <i class="bi bi-hourglass-bottom fs-2 text-warning"></i>
                    <h6 class="mt-2">Meus Lotes Incompletos</h6>
                    <h4><?= $userLotesAbertos ?></h4>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>

</body>
</html>
