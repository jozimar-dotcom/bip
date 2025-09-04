<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../config/config.php';

/**
 * Se já estiver logado, só redireciona conforme o perfil atual.
 * Agora também garantimos que a etapa esteja na sessão.
 */
if (!empty($_SESSION['usuario'])) {
    if (empty($_SESSION['etapa'])) {
        // garante etapa na sessão se ficou faltando em logins antigos
        try {
            $stmt = $conn->prepare("SELECT etapa_permitida FROM usuarios WHERE id = ?");
            $stmt->execute([$_SESSION['id'] ?? 0]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $_SESSION['etapa'] = $row['etapa_permitida'] ?? 'estoque';
        } catch (\Throwable $e) {
            $_SESSION['etapa'] = 'estoque';
        }
    }

    // Data de trabalho padrão (se ainda não houver)
    if (empty($_SESSION['data_trabalho'])) {
        $_SESSION['data_trabalho'] = date('Y-m-d');
    }

    switch ($_SESSION['perfil'] ?? '') {
        case 'admin':
            header("Location: dashboard.php"); exit;
        case 'user':
            header("Location: dashboard_user.php"); exit;
        case 'conferente':
            header("Location: dashboard_conferente.php"); exit;
        default:
            header("Location: index.php"); exit;
    }
}

$msg = '';
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $usuario = trim($_POST['usuario'] ?? '');
    $senha   = (string)($_POST['senha'] ?? '');

    // Busca o usuário
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE usuario = ?");
    $stmt->execute([$usuario]);
    $usuario_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario_data && password_verify($senha, $usuario_data['senha'])) {
        // Login OK
        session_regenerate_id(true);
        $_SESSION['id']      = $usuario_data['id'];
        $_SESSION['usuario'] = $usuario_data['usuario'];
        $_SESSION['perfil']  = $usuario_data['perfil'];
        // >>> NOVO: etapa do fluxo (estoque/embalagem/conferencia/admin)
        $_SESSION['etapa']   = $usuario_data['etapa_permitida'] ?? 'estoque';

        // Data de trabalho padrão (usada nas telas de bip/relatórios)
        if (empty($_SESSION['data_trabalho'])) {
            $_SESSION['data_trabalho'] = date('Y-m-d');
        }

        switch ($_SESSION['perfil']) {
            case 'admin':
                header("Location: dashboard.php"); exit;
            case 'user':
                header("Location: dashboard_user.php"); exit;
            case 'conferente':
                header("Location: dashboard_conferente.php"); exit;
            default:
                header("Location: index.php"); exit;
        }
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

    <?php if (!empty($msg)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">
        <div class="mb-3 text-start">
            <input type="text" name="usuario" class="form-control" placeholder="Usuário" required autofocus>
        </div>
        <div class="mb-3 text-start">
            <input type="password" name="senha" class="form-control" placeholder="Senha" required>
        </div>
        <button type="submit" class="btn btn-danger w-100">Entrar</button>
    </form>
</div>

<footer class="text-light py-2 mt-4" style="font-size: 0.85rem;">
    © <?= date('Y') ?> MULTCABOS | Desenvolvido por Infolondrina
</footer>

</body>
</html>
