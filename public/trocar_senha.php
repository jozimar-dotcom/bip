<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit();
}

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senhaAtual = $_POST['senha_atual'] ?? '';
    $novaSenha = $_POST['nova_senha'] ?? '';
    $confirmarSenha = $_POST['confirmar_senha'] ?? '';
    $usuario = $_SESSION['usuario'];

    $stmt = $conn->prepare("SELECT senha FROM usuarios WHERE usuario = ?");
    $stmt->execute([$usuario]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($senhaAtual, $user['senha'])) {
        $msg = "<div class='alert alert-danger'>Senha atual incorreta.</div>";
    } elseif ($novaSenha !== $confirmarSenha) {
        $msg = "<div class='alert alert-warning'>As senhas não coincidem.</div>";
    } else {
        $novaSenhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);
        $update = $conn->prepare("UPDATE usuarios SET senha = ? WHERE usuario = ?");
        $update->execute([$novaSenhaHash, $usuario]);
        $_SESSION['msg_sucesso'] = "Senha alterada com sucesso.";
        header("Location: usuarios.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Trocar Senha - MULTCABOS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

<?php include_once '../includes/header.php'; ?>
<?php include_once '../includes/voltar_dashboard.php'; ?>

<div class="container py-5">
    <div class="card mx-auto shadow-sm" style="max-width: 500px;">
        <div class="card-header text-center bg-primary text-white fw-bold">Trocar Senha</div>
        <div class="card-body">
            <?= $msg ?>
            <form method="POST">
                <div class="mb-3">
                    <label for="usuario" class="form-label">Usuário</label>
                    <input type="text" id="usuario" class="form-control bg-light text-muted" value="<?= htmlspecialchars($_SESSION['usuario']) ?>" readonly>
                </div>
                <div class="mb-3">
                    <label for="senha_atual" class="form-label">Senha Atual</label>
                    <input type="password" name="senha_atual" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="nova_senha" class="form-label">Nova Senha</label>
                    <input type="password" name="nova_senha" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="confirmar_senha" class="form-label">Confirmar Nova Senha</label>
                    <input type="password" name="confirmar_senha" class="form-control" required>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-success">Atualizar Senha</button>
                </div>
            </form>
        </div>
    </div>
</div>

<footer class="bg-white text-center text-muted py-3 mt-auto border-top">
    © 2025 <strong>MULTCABOS</strong> | Desenvolvido por <strong>Infolondrina</strong>
</footer>

</body>
</html>