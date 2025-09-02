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

try {
    $stmt = $conn->query("SELECT usuario, perfil FROM usuarios ORDER BY id DESC");
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro ao buscar usuários: " . $e->getMessage());
}
?>

<?php include '../includes/header.php'; ?>

<main class="flex-grow p-6 bg-gray-100 pb-24">
    <div class="max-w-6xl mx-auto">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center space-x-4">
                <a href="dashboard.php" title="Voltar ao Dashboard" class="flex items-center text-blue-600 hover:text-blue-800">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    <span class="hidden sm:inline">Voltar</span>
                </a>
                <h1 class="text-3xl font-bold text-gray-800">Usuários</h1>
            </div>
            <a href="cadastrar_usuario.php" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Novo Usuário</a>
        </div>

        <div class="mb-4">
            <input type="text" id="searchInput" placeholder="Pesquisar usuário..." class="w-full sm:w-1/2 px-4 py-2 border rounded shadow-sm" />
        </div>

        <div class="overflow-x-auto rounded shadow">
            <table class="min-w-full bg-white">
                <thead class="bg-gray-200 text-gray-700 text-left">
                <tr>
                    <th class="py-3 px-6">Usuário</th>
                    <th class="py-3 px-6 text-center">Perfil</th>
                    <th class="py-3 px-6 text-right">Ações</th>
                </tr>
                </thead>
                <tbody id="usuariosTabela">
                <?php foreach ($usuarios as $usuario): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-6"><?php echo htmlspecialchars($usuario['usuario']); ?></td>
                        <td class="py-3 px-6 text-center"><?php echo ucfirst($usuario['perfil']); ?></td>
                        <td class="py-3 px-6 text-right space-x-2">
                            <a href="editar_usuario.php?id=<?php echo $usuario['usuario']; ?>" class="bg-yellow-400 hover:bg-yellow-500 text-white font-bold py-1 px-3 rounded">Editar</a>
                            <a href="excluir_usuario.php?id=<?php echo $usuario['usuario']; ?>" class="bg-red-600 hover:bg-red-700 text-white font-bold py-1 px-3 rounded">Excluir</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>

<script>
    document.getElementById('searchInput').addEventListener('keyup', function () {
        let filter = this.value.toLowerCase();
        let rows = document.querySelectorAll('#usuariosTabela tr');
        rows.forEach(row => {
            let user = row.cells[0].textContent.toLowerCase();
            row.style.display = user.includes(filter) ? '' : 'none';
        });
    });
</script>
