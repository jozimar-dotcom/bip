<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['usuario']) || $_SESSION['perfil'] !== 'admin') {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario']);
    $senha = $_POST['senha'];
    $confirmarSenha = $_POST['confirmar_senha'];

    if ($senha !== $confirmarSenha) {
        $_SESSION['msg'] = 'As senhas não coincidem!';
        header('Location: cadastrar_usuario.php');
        exit;
    }

    if (strlen($senha) < 4) {
        $_SESSION['msg'] = 'A senha deve ter no mínimo 4 caracteres.';
        header('Location: cadastrar_usuario.php');
        exit;
    }

    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
    $perfil = 'user'; // perfil padrão

    $stmt = $conn->prepare("INSERT INTO usuarios (usuario, senha, perfil) VALUES (?, ?, ?)");
    if ($stmt->execute([$usuario, $senhaHash, $perfil])) {
        header('Location: usuarios.php');
        exit;
    } else {
        $_SESSION['msg'] = 'Erro ao cadastrar o usuário.';
        header('Location: cadastrar_usuario.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Cadastrar Usuário</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex justify-content-center align-items-center vh-100">
<div class="card shadow p-4" style="width: 100%; max-width: 400px;">
    <h4 class="mb-4 text-center text-primary">Cadastro de Novo Usuário</h4>

    <?php if (isset($_SESSION['msg'])): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($_SESSION['msg']); unset($_SESSION['msg']); ?>
        </div>
    <?php endif; ?>

    <form method="POST" id="formCadastro">
        <div class="mb-3">
            <label for="usuario" class="form-label">Usuário</label>
            <input type="text" name="usuario" id="usuario" class="form-control" required>
        </div>

        <div class="mb-3">
            <label for="senha" class="form-label">Senha</label>
            <input type="password" name="senha" id="senha" class="form-control" required minlength="4">
        </div>

        <div class="mb-3">
            <label for="confirmar_senha" class="form-label">Confirmar Senha</label>
            <input type="password" name="confirmar_senha" id="confirmar_senha" class="form-control" required>
            <div id="erroSenha" class="form-text text-danger d-none">As senhas não coincidem.</div>
        </div>

        <div class="d-flex justify-content-between">
            <a href="usuarios.php" class="btn btn-danger">Cancelar</a>
            <button type="submit" class="btn btn-primary">Cadastrar</button>
        </div>
    </form>
</div>

<script>
    const senha = document.getElementById('senha');
    const confirmar = document.getElementById('confirmar_senha');
    const erro = document.getElementById('erroSenha');

    confirmar.addEventListener('input', () => {
        if (confirmar.value !== senha.value) {
            confirmar.classList.add('is-invalid');
            erro.classList.remove('d-none');
        } else {
            confirmar.classList.remove('is-invalid');
            erro.classList.add('d-none');
        }
    });
</script>
</body>
</html>
