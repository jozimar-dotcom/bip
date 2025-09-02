<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['perfil'] !== 'admin') {
    header("Location: relatorio.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Excluído com Sucesso</title>
    <meta http-equiv="refresh" content="3;URL=relatorio.php">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="alert alert-success text-center shadow p-5 rounded" style="font-size:1.3rem;">
        <img src="https://cdn-icons-png.flaticon.com/512/845/845646.png" alt="Sucesso" width="80" class="mb-3">
        <p><strong>Registro excluído com sucesso!</strong></p>
        <p>Redirecionando para o relatório em instantes...</p>
    </div>
</div>

</body>
</html>
