<?php
session_start();
require_once '../config/config.php';

$dataFiltro = $_GET['data'] ?? date('Y-m-d');
$codigoFiltro = $_GET['codigo'] ?? '';

if (isset($_GET['ajax'])) {
    if (!empty($codigoFiltro)) {
        $query = "SELECT * FROM bipagens WHERE codigo LIKE :codigo ORDER BY horario ASC";
        $params = [':codigo' => "%$codigoFiltro%"];
    } else {
        $query = "SELECT * FROM bipagens WHERE DATE(horario) = :data ORDER BY horario ASC";
        $params = [':data' => $dataFiltro];
    }

    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($codigoFiltro)) {
        echo "<div class='alert alert-info'>";
        if (count($dados) > 0) {
            $primeiro = $dados[0];
            $dataBip = date('d/m/Y', strtotime($primeiro['horario']));
            echo "<strong>Código:</strong> <span class='badge bg-primary'>" . htmlspecialchars($primeiro['codigo']) . "</span><br>";
            echo "<strong>Data:</strong> <span class='badge bg-success'>" . $dataBip . "</span><br>";
            echo "<strong>Usuário:</strong> <span class='badge bg-secondary'>" . htmlspecialchars($primeiro['usuario']) . "</span><br>";
            echo "<strong>Lote:</strong> Encontrado em {$dataBip}";
        } else {
            echo "Nenhum resultado encontrado para o código <strong>" . htmlspecialchars($codigoFiltro) . "</strong>.<br>";
        }
        echo "<div class='mt-2'><a href='relatorio.php' class='btn btn-sm btn-danger'>Limpar pesquisa</a></div>";
        echo "</div>";
    }

    $totalRegistros = count($dados);
    echo "<div class='mb-3 fw-bold'>Total de Registros: {$totalRegistros}</div>";

    $lotes = array_chunk($dados, 10);
    $lotes = array_reverse($lotes);
    $loteNumero = count($lotes);

    foreach ($lotes as $grupo) {
        echo "<div class='card mb-4 shadow-sm'>";
        echo "<div class='card-header bg-danger text-white fw-bold text-center'>Lote {$loteNumero}</div>";
        echo "<div class='table-responsive'>";
        echo "<table class='table table-bordered table-hover align-middle' style='font-size:14px; min-width:500px;'>";
        echo "<thead class='table-light'>";
        echo "<tr>";
        echo "<th class='text-center' style='width:5%;'>#</th>";
        echo "<th class='text-center' style='width:20%;'>Código</th>";
        echo "<th class='text-center' style='width:25%;'>Hora</th>";
        echo "<th class='text-start' style='width:30%;'>Usuário</th>";
        if ($_SESSION['perfil'] === 'admin') {
            echo "<th class='text-center' style='width:20%;'>Ações</th>";
        }
        echo "</tr>";
        echo "</thead><tbody>";

        foreach ($grupo as $index => $linha) {
            $hora = date('H:i:s', strtotime($linha['horario']));
            echo "<tr>";
            echo "<td class='text-center'>".($index + 1)."</td>";
            echo "<td class='text-center text-primary fw-bold'>".htmlspecialchars($linha['codigo'])."</td>";
            echo "<td class='text-center'>{$hora}</td>";
            echo "<td class='text-start'>".htmlspecialchars($linha['usuario'])."</td>";
            if ($_SESSION['perfil'] === 'admin') {
                echo "<td class='text-center'>
                    <a href='confirmar_exclusao.php?id={$linha['id']}' class='btn btn-sm btn-danger'>🗑️</a>
                </td>";
            }
            echo "</tr>";
        }

        echo "</tbody></table></div></div>";
        $loteNumero--;
    }

    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Bipagens</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="bg-light">

<?php include_once '../includes/header.php'; ?>
<?php include_once '../includes/voltar_dashboard.php'; ?>

<div style="display:flex; justify-content:center;">
    <main class="container mt-4 bg-white p-4 shadow rounded" style="max-width: 900px;">
        <h2 class="text-center mb-4">
            <img src="https://cdn-icons-png.flaticon.com/512/1827/1827484.png" alt="ícone" style="width:24px; vertical-align:middle; margin-right:8px;">
            Relatório de Bipagens do Dia <?= date('d/m/Y', strtotime($dataFiltro)) ?>
        </h2>

        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-4">
                <input type="date" name="data" value="<?= htmlspecialchars($dataFiltro) ?>" class="form-control">
            </div>
            <div class="col-md-5">
                <input type="text" name="codigo" placeholder="Buscar código" value="<?= htmlspecialchars($_GET['codigo'] ?? '') ?>" class="form-control">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-success w-100">Filtrar</button>
            </div>
        </form>

        <div id="conteudo-lotes"></div>
    </main>
</div>

<script>
    function carregarRelatorio() {
        const data = document.querySelector('input[name="data"]').value;
        const codigo = document.querySelector('input[name="codigo"]').value;
        fetch(`relatorio.php?ajax=1&data=${data}&codigo=${codigo}`)
            .then(response => response.text())
            .then(html => {
                document.getElementById('conteudo-lotes').innerHTML = html;
            });
    }

    window.onload = () => {
        carregarRelatorio();
        setInterval(carregarRelatorio, 1000);
        document.querySelector('input[name="codigo"]').focus();
    };
</script>

<?php include_once '../includes/footer.php'; ?>
</body>
</html>
