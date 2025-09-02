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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['id'])) {
        $id = $_POST['id'];

        try {
            $stmt = $conn->prepare("DELETE FROM usuarios WHERE usuario = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            header('Location: usuarios.php?sucesso=1');
            exit();
        } catch (PDOException $e) {
            die("Erro ao excluir usuário: " . $e->getMessage());
        }
    } else {
        header('Location: usuarios.php');
        exit();
    }
} elseif (isset($_GET['id'])) {
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
} else {
    header('Location: usuarios.php');
    exit();
}
?>

<?php include '../includes/header.php'; ?>

<main class="flex-grow p-6 bg-gray-100 pb-32">
    <div class="max-w-2xl mx-auto">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-semibold text-gray-800">Excluir Usuário</h1>
            <a href="usuarios.php" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Cancelar</a>
        </div>

        <div class="bg-white shadow-md rounded-lg p-6">
            <p class="text-lg text-gray-800 mb-4">Tem certeza que deseja excluir o usuário <strong><?php echo htmlspecialchars($usuario['usuario']); ?></strong>?</p>
            <form action="excluir_usuario.php" method="POST">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($usuario['usuario']); ?>">
                <button type="submit" class="px-6 py-2 bg-red-600 text-white font-semibold rounded hover:bg-red-700">Sim, excluir</button>
            </form>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
