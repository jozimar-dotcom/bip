<?php
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');
session_start();

if (!isset($_SESSION['usuario']) || !isset($_SESSION['id'])) {
    header("Location: ../index.php");
    exit();
}

require_once '../config/config.php';

if (!isset($_SESSION['lote_pendente'])) {
    header("Location: cadastrar_bip.php");
    exit();
}

$lote_id = $_SESSION['lote_pendente'];
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $senha = trim($_POST['senha'] ?? '');

    $stmt = $conn->prepare("SELECT id, senha FROM usuarios WHERE usuario = :usuario");
    $stmt->execute(['usuario' => $usuario]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($senha, $user['senha'])) {
        $stmt = $conn->prepare("UPDATE lotes SET responsavel_id = :responsavel_id WHERE id = :lote_id");
        $stmt->execute([
            'responsavel_id' => $user['id'],
            'lote_id' => $lote_id
        ]);

        unset($_SESSION['lote_pendente']);

        header("Location: cadastrar_bip.php");
        exit();
    } else {
        $erro = 'Usuário ou senha inválidos';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmar Responsável - MULTCABOS</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex flex-col min-h-screen">

<?php include_once '../includes/header.php'; ?>
<?php include_once '../includes/voltar_dashboard.php'; ?>

<audio id="somResponsavel" src="../assets/sond/lote_cheio.mp3" autoplay></audio>

<main class="flex-grow flex items-center justify-center px-4">
    <div class="bg-white shadow-lg rounded-xl p-8 w-full max-w-md text-center">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Confirmar Responsável pelo Lote #<?= htmlspecialchars($lote_id) ?></h2>

        <?php if (!empty($erro)): ?>
            <div class="bg-red-100 text-red-700 p-2 mb-4 rounded"> <?= htmlspecialchars($erro) ?> </div>
        <?php endif; ?>

        <form method="POST">
            <input type="text" name="usuario" placeholder="Usuário" required class="w-full mb-4 px-4 py-2 border border-gray-300 rounded">
            <input type="password" name="senha" placeholder="Senha" required class="w-full mb-4 px-4 py-2 border border-gray-300 rounded">
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 rounded">Confirmar Responsável</button>
        </form>
    </div>
</main>

<footer class="bg-white text-center text-sm py-4 mt-auto shadow-inner">
    <p class="text-gray-500">© 2025 <strong>MULTCABOS</strong> | Desenvolvido por <strong>Infolondrina</strong></p>
</footer>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const som = document.getElementById('somResponsavel');
        if (som) {
            som.play().catch(() => {
                console.warn('Áudio de lote cheio não pode ser reproduzido automaticamente.');
            });
        }
    });
</script>

</body>
</html>
