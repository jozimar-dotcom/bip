<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['usuario']) || $_SESSION['perfil'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$dataHoje = date('Y-m-d');
$usuarioLogado = $_SESSION['usuario'] ?? '';

// Totais gerais
$totalHoje = $conn->query("SELECT COUNT(*) FROM bipagens WHERE DATE(horario) = CURDATE()")->fetchColumn();
$totalSemana = $conn->query("SELECT COUNT(*) FROM bipagens WHERE YEARWEEK(horario, 1) = YEARWEEK(CURDATE(), 1)")->fetchColumn();
$totalMes = $conn->query("SELECT COUNT(*) FROM bipagens WHERE MONTH(horario) = MONTH(CURDATE()) AND YEAR(horario) = YEAR(CURDATE())")->fetchColumn();
$totalAno = $conn->query("SELECT COUNT(*) FROM bipagens WHERE YEAR(horario) = YEAR(CURDATE())")->fetchColumn();

// Tempo médio para fechar lotes (10 em 10 bips)
$stmtTempo = $conn->prepare("SELECT horario FROM bipagens WHERE DATE(horario) = CURDATE() ORDER BY horario ASC");
$stmtTempo->execute();
$bips = $stmtTempo->fetchAll(PDO::FETCH_COLUMN);
$tempos = [];
for ($i = 9; $i < count($bips); $i += 10) {
    $tempoInicial = strtotime($bips[$i - 9]);
    $tempoFinal = strtotime($bips[$i]);
    $tempos[] = $tempoFinal - $tempoInicial;
}
$mediaTempo = count($tempos) ? array_sum($tempos) / count($tempos) : 0;
$mediaTempoMin = floor($mediaTempo / 60);
$mediaTempoSeg = $mediaTempo % 60;
$tempoFormatado = $mediaTempoMin < 60
    ? sprintf("0:%02d min", $mediaTempoMin)
    : sprintf("%d:%02d", floor($mediaTempoMin / 60), $mediaTempoMin % 60);

// Médias de bipagens
$totalBips = $conn->query("SELECT COUNT(*) FROM bipagens")->fetchColumn();
$totalDias = $conn->query("SELECT COUNT(DISTINCT DATE(horario)) FROM bipagens")->fetchColumn();
$totalSemanas = $conn->query("SELECT COUNT(DISTINCT YEARWEEK(horario, 1)) FROM bipagens")->fetchColumn();
$totalMeses = $conn->query("SELECT COUNT(DISTINCT CONCAT(YEAR(horario), '-', MONTH(horario))) FROM bipagens")->fetchColumn();
$totalAnos = $conn->query("SELECT COUNT(DISTINCT YEAR(horario)) FROM bipagens")->fetchColumn();

$mediaDia = $totalDias ? floor($totalBips / $totalDias) : 0;
$mediaSemana = $totalSemanas ? floor($totalBips / $totalSemanas) : 0;
$mediaMes = $totalMeses ? floor($totalBips / $totalMeses) : 0;
$mediaAno = $totalAnos ? floor($totalBips / $totalAnos) : 0;

// Bipagens por usuário
$stmtPorUsuario = $conn->query("SELECT usuario, COUNT(*) as total FROM bipagens WHERE DATE(horario) = CURDATE() GROUP BY usuario ORDER BY total DESC");
$ranking = $stmtPorUsuario->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Desempenho Admin - MULTCABOS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include_once '../includes/header.php'; ?>
<?php include_once '../includes/voltar_dashboard.php'; ?>
<div class="container py-5">
    <h2 class="mb-5 text-center fw-bold fs-3 text-dark border-bottom pb-3">📊 <span class="text-primary">Desempenho Geral - Administrador</span></h2>

    <div class="row row-cols-1 row-cols-md-4 g-4 mb-4">
        <div class="col">
            <div class="card text-center shadow-sm border-primary">
                <div class="card-body">
                    <i class="bi bi-calendar-day display-6 text-primary mb-2"></i>
                    <h6 class="text-muted">Códigos Bipados (Hoje)</h6>
                    <h4 class="text-primary fw-bold"><?= $totalHoje ?></h4>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card text-center shadow-sm border-success">
                <div class="card-body">
                    <i class="bi bi-calendar-week display-6 text-success mb-2"></i>
                    <h6 class="text-muted">Bips da Semana</h6>
                    <h4 class="text-success fw-bold"><?= $totalSemana ?></h4>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card text-center shadow-sm border-warning">
                <div class="card-body">
                    <i class="bi bi-calendar-month display-6 text-warning mb-2"></i>
                    <h6 class="text-muted">Bips do Mês</h6>
                    <h4 class="text-warning fw-bold"><?= $totalMes ?></h4>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card text-center shadow-sm border-danger">
                <div class="card-body">
                    <i class="bi bi-calendar3 display-6 text-danger mb-2"></i>
                    <h6 class="text-muted">Bips do Ano</h6>
                    <h4 class="text-danger fw-bold"><?= $totalAno ?></h4>
                </div>
            </div>
        </div>
    </div>

    <div class="row row-cols-1 row-cols-md-4 g-4 mb-4">
        <div class="col">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <i class="bi bi-bar-chart-line display-6 text-dark mb-2"></i>
                    <h6 class="text-muted">Média diária</h6>
                    <h4 class="fw-bold"><?= $mediaDia ?></h4>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <i class="bi bi-bar-chart-steps display-6 text-dark mb-2"></i>
                    <h6 class="text-muted">Média semanal</h6>
                    <h4 class="fw-bold"><?= $mediaSemana ?></h4>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <i class="bi bi-bar-chart-fill display-6 text-dark mb-2"></i>
                    <h6 class="text-muted">Média mensal</h6>
                    <h4 class="fw-bold"><?= $mediaMes ?></h4>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <i class="bi bi-bar-chart display-6 text-dark mb-2"></i>
                    <h6 class="text-muted">Média anual</h6>
                    <h4 class="fw-bold"><?= $mediaAno ?></h4>
                </div>
            </div>
        </div>
    </div>

    <div class="row justify-content-center mb-5">
        <div class="col-md-6">
            <div class="card shadow-sm border border-info">
                <div class="card-body text-center">
                    <i class="bi bi-stopwatch display-6 text-info mb-2"></i>
                    <h6 class="text-muted">Média de tempo entre lotes (Hoje)</h6>
                    <h4 class="fw-bold text-info"><?= $tempoFormatado ?></h4>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-5">
        <h4 class="mb-3 fw-semibold text-secondary"><i class="bi bi-trophy-fill text-warning"></i> Ranking de Bips por Usuário (Hoje)</h4>
        <div class="table-responsive" style="max-width: 600px;">
            <table class="table table-sm table-bordered table-hover align-middle text-center">
                <thead class="table-light">
                <tr>
                    <th class="text-nowrap">#</th>
                    <th class="text-nowrap">Usuário</th>
                    <th class="text-nowrap">Total de Bips</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($ranking as $i => $linha): ?>
                    <tr>
                        <td class="text-nowrap"><?= $i + 1 ?></td>
                        <td class="text-nowrap"><?= htmlspecialchars($linha['usuario']) ?></td>
                        <td class="text-nowrap"><?= $linha['total'] ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include_once '../includes/footer.php'; ?>
</body>
</html>
