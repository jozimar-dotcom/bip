<?php
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}
$nome = $_SESSION['usuario'] ?? 'Usuário';
$perfil = $_SESSION['perfil'] ?? 'user';

if ($perfil !== 'admin') {
    header('Location: dashboard.php');
    exit();
}

require_once '../config/config.php';

if (!isset($_GET['id'])) {
    header('Location: usuarios.php');
    exit();
}

$id = $_GET['id'];

try {
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE usuario = :id LIMIT 1");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        header('Location: usuarios.php');
        exit();
    }
} catch (PDOException $e) {
    die("Erro ao buscar usuário: " . $e->getMessage());
}
?>

<?php include '../includes/header.php'; ?>

<main class="flex-grow p-6 bg-gray-100 pb-32">
    <div class="max-w-2xl mx-auto">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-semibold text-gray-800">Editar Usuário</h1>
            <a href="usuarios.php" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Voltar</a>
        </div>

        <?php if (isset($_GET['sucesso']) && $_GET['sucesso'] == 1): ?>
            <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">
                Usuário atualizado com sucesso! Redirecionando...
            </div>
            <script>
                setTimeout(() => window.location.href = 'usuarios.php', 3000);
            </script>
        <?php endif; ?>

        <form action="atualizar_usuario.php" method="POST" onsubmit="return validarSenha()" class="bg-white shadow-md rounded-lg p-6 space-y-4">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($usuario['usuario']); ?>">

            <div>
                <label for="usuario" class="block text-sm font-medium text-gray-700">Usuário</label>
                <input type="text" name="usuario" id="usuario" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500" value="<?php echo htmlspecialchars($usuario['usuario']); ?>">
            </div>

            <div>
                <label for="perfil" class="block text-sm font-medium text-gray-700">Perfil</label>
                <select name="perfil" id="perfil" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <option value="admin" <?php echo $usuario['perfil'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                    <option value="user" <?php echo $usuario['perfil'] === 'user' ? 'selected' : ''; ?>>User</option>
                    <option value="conferente" <?php echo $usuario['perfil'] === 'conferente' ? 'selected' : ''; ?>>Conferente</option>
                </select>
            </div>

            <div>
                <label for="senha" class="block text-sm font-medium text-gray-700">Nova Senha</label>
                <input type="password" name="senha" id="senha" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div>
                <label for="confirmar_senha" class="block text-sm font-medium text-gray-700">Confirmar Senha</label>
                <input type="password" name="confirmar_senha" id="confirmar_senha" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div class="text-right">
                <button type="submit" class="px-6 py-2 bg-green-600 text-white font-semibold rounded hover:bg-green-700">Salvar</button>
            </div>
        </form>
    </div>
</main>

<script>
    function validarSenha() {
        const senha = document.getElementById('senha');
        const confirmar = document.getElementById('confirmar_senha');

        senha.classList.remove('border-red-500');
        confirmar.classList.remove('border-red-500');

        if (senha.value !== confirmar.value) {
            senha.classList.add('border-red-500');
            confirmar.classList.add('border-red-500');
            alert('As senhas não coincidem.');
            return false;
        }
        return true;
    }
</script>

<?php include '../includes/footer.php'; ?>
