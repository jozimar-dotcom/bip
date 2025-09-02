<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['usuario']) || $_SESSION['perfil'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Buscar dados gerenciais
try {
    $dataHoje = date('Y-m-d');

    $totalBipsHoje = $conn->query("SELECT COUNT(*) FROM bipagens WHERE DATE(horario) = CURDATE()")->fetchColumn();

    // Novo cálculo baseado apenas na tabela bipagens
    $totalLotesHoje = ceil($totalBipsHoje / 10);
    $totalLotesConcluidos = floor($totalBipsHoje / 10);
    $totalLotesIncompletos = $totalBipsHoje % 10 > 0 ? 1 : 0;

    // Cards placeholders para futura conferência
    $totalAConferir = '---';
    $totalConferidos = '---';
    $totalColeta = '---';

} catch (PDOException $e) {
    echo "Erro ao buscar dados: " . $e->getMessage();
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - MULTCABOS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .card-hover:hover {
            transform: scale(1.02);
            transition: all 0.2s ease-in-out;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
            cursor: pointer;
        }
    </style>
</head>
<body class="bg-light">
<?php include_once '../includes/header.php'; ?>

<div class="container mt-4">
    <h2 class="text-center fw-bold fs-4 text-dark border-bottom pb-2 mb-4">
        🛠️ <span class="text-primary">Dashboard - Administrador</span>
    </h2>

    <div class="row row-cols-1 row-cols-md-3 g-4 mb-4">
        <div class="col">
            <a href="cadastrar_bip.php" class="text-decoration-none">
                <div class="card border-danger text-center shadow-sm card-hover">
                    <div class="card-body">
                        <i class="bi bi-upc-scan display-6 text-danger mb-2"></i>
                        <h5 class="card-title text-danger fw-bold">Cadastrar Código</h5>
                        <p class="card-text text-muted">Registrar novos códigos bipados.</p>
                    </div>
                </div>
            </a>
        </div>

        <div class="col">
            <a href="relatorio.php" class="text-decoration-none">
                <div class="card border-success text-center shadow-sm card-hover">
                    <div class="card-body">
                        <i class="bi bi-bar-chart-line-fill display-6 text-success mb-2"></i>
                        <h5 class="card-title text-success fw-bold">Relatório</h5>
                        <p class="card-text text-muted">Visualizar bipagens por lote.</p>
                    </div>
                </div>
            </a>
        </div>

        <div class="col">
            <a href="desempenho_admin.php" class="text-decoration-none">
                <div class="card border-primary text-center shadow-sm card-hover">
                    <div class="card-body">
                        <i class="bi bi-graph-up-arrow display-6 text-primary mb-2"></i>
                        <h5 class="card-title text-primary fw-bold">Desempenho</h5>
                        <p class="card-text text-muted">Estatísticas e performance dos usuários.</p>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <h4 class="fw-bold text-center mt-4 text-secondary">📌 Status Gerencial</h4>
    <div class="row row-cols-1 row-cols-md-4 g-4 mt-3 mb-5">
        <div class="col">
            <div class="card text-center border-primary shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted">Códigos Bipados (Hoje)</h6>
                    <h5 class="fw-bold text-primary"><?php echo $totalBipsHoje; ?></h5>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card text-center border-secondary shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted">Total de Lotes (Hoje)</h6>
                    <h5 class="fw-bold text-secondary"><?php echo $totalLotesHoje; ?></h5>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card text-center border-success shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted">Lotes Concluídos</h6>
                    <h5 class="fw-bold text-success"><?php echo $totalLotesConcluidos; ?></h5>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card text-center border-warning shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted">Lotes Incompletos</h6>
                    <h5 class="fw-bold text-warning"><?php echo $totalLotesIncompletos; ?></h5>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card text-center border-info shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted">Itens a Conferir</h6>
                    <h5 class="fw-bold text-info"><?php echo $totalAConferir; ?></h5>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card text-center border-dark shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted">Itens Conferidos</h6>
                    <h5 class="fw-bold text-dark"><?php echo $totalConferidos; ?></h5>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card text-center border-danger shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted">Itens para Coleta</h6>
                    <h5 class="fw-bold text-danger"><?php echo $totalColeta; ?></h5>
                </div>
            </div>
        </div>
    </div>
</div>

<footer class="text-center text-muted py-3 mt-4" style="padding-bottom: 40px;">
    © 2025 <strong>MULTCABOS</strong> | Desenvolvido por <strong>Infolondrina</strong>
</footer>

</body>
</html>
