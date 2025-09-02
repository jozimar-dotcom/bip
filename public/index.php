<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../config/config.php';

if (isset($_SESSION['usuario'])) {
    switch ($_SESSION['perfil']) {
        case 'admin':
            header("Location: dashboard.php");
            break;
        case 'user':
            header("Location: dashboard_user.php");
            break;
        case 'conferente':
            header("Location: dashboard_conferente.php");
            break;
        default:
            header("Location: index.php");
    }
    exit;
}

$msg = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = $_POST['usuario'];
    $senha = $_POST['senha'];

    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE usuario = ?");
    $stmt->execute([$usuario]);
    $usuario_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario_data && password_verify($senha, $usuario_data['senha'])) {
        session_regenerate_id(true);
        $_SESSION['usuario'] = $usuario_data['usuario'];
        $_SESSION['perfil'] = $usuario_data['perfil'];
        $_SESSION['id'] = $usuario_data['id'];

        switch ($_SESSION['perfil']) {
            case 'admin':
                header("Location: dashboard.php");
                break;
            case 'user':
                header("Location: dashboard_user.php");
                break;
            case 'conferente':
                header("Location: dashboard_conferente.php");
                break;
            default:
                header("Location: index.php");
        }
        exit;
    } else {
        $msg = "Usuário ou senha inválidos!";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Login - MULTCABOS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="margin: 0; background-color: #2c3e50; font-family: 'Segoe UI', sans-serif; min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center;">

<div class="bg-white p-4 rounded shadow text-center" style="max-width: 400px; width: 100%;">
    <div style="font-size: 26px; font-weight: bold; margin-bottom: 20px;">
        <span style="color: #c0392b;">MULT</span><span style="color: #2c3e50;">CABOS</span>
    </div>
    <h4 class="mb-4">Login</h4>

    <?php if ($msg): ?>
        <div class="alert alert-danger"><?= $msg ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3 text-start">
            <input type="text" name="usuario" class="form-control" placeholder="Usuário" required>
        </div>
        <div class="mb-3 text-start">
            <input type="password" name="senha" class="form-control" placeholder="Senha" required>
        </div>
        <button type="submit" class="btn btn-danger w-100">Entrar</button>
    </form>
</div>

<footer class="text-light py-2 mt-4" style="font-size: 0.85rem;">
    © 2025 MULTCABOS | Desenvolvido por Infolondrina
</footer>

</body>
</html>
