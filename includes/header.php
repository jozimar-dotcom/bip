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
$paginaAtual = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Dashboard - MULTCABOS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        #saudacao {
            transition: opacity 2s ease-in-out;
        }
    </style>
</head>
<body class="bg-gray-100 flex flex-col min-h-screen">

<header class="bg-white shadow fixed top-0 left-0 right-0 z-50 flex items-center justify-between px-6 py-3">
    <div class="flex items-center space-x-4">
        <span class="text-2xl font-bold"><span class="text-red-600">MULT</span>CABOS</span>
        <?php if ($perfil === 'admin'): ?>
            <div class="relative">
                <button onclick="toggleDropdown('cadastroMenu')" class="ml-4 text-sm font-medium text-gray-700 hover:text-blue-600">Cadastro ▾</button>
                <div id="cadastroMenu" class="hidden absolute bg-white shadow-md rounded-md mt-2 py-2 w-48 z-50">
                    <a href="usuarios.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Usuários</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="flex items-center space-x-4">
        <div id="saudacao" class="text-gray-700 font-medium mr-2 hidden sm:inline">
            <?php echo "Olá, " . htmlspecialchars($nome, ENT_QUOTES, 'UTF-8'); ?>
        </div>
        <div class="relative">
            <button onclick="toggleMenu()" class="flex items-center justify-center w-10 h-10 bg-blue-600 text-white rounded-full focus:outline-none">
                <?php echo strtoupper(mb_substr($nome, 0, 1, 'UTF-8')); ?>
            </button>
            <div id="menuDropdown" class="hidden absolute right-0 mt-2 w-64 bg-white border rounded-md shadow-lg z-50">
                <div class="p-4 border-b">
                    <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($nome, ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
                <a href="trocar_senha.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Trocar Senha</a>
                <div class="p-2 text-right border-t">
                    <a href="logout.php" class="text-red-600 font-semibold hover:underline">Sair</a>
                </div>
            </div>
        </div>
    </div>
</header>

<div class="h-24"></div>

<?php if ($paginaAtual === 'dashboard.php'): ?>
    <main class="flex-1 p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if ($perfil === 'user'): ?>
            <a href="cadastrar_bip.php" class="p-6 bg-white rounded-lg shadow hover:shadow-lg transition transform hover:scale-105 cursor-pointer">
                <div class="flex items-center space-x-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-black" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path d="M3 4h1v16H3zM6 4h1v16H6zM9 4h2v16H9zM13 4h1v16h-1zM16 4h2v16h-2zM20 4h1v16h-1z"/>
                    </svg>
                    <div>
                        <h2 class="text-xl font-semibold mb-1">Cadastrar Bips</h2>
                        <p class="text-gray-600 text-sm">Ler e registrar códigos por lote.</p>
                    </div>
                </div>
            </a>
            <a href="relatorio.php" class="p-6 bg-white rounded-lg shadow hover:shadow-lg transition transform hover:scale-105 cursor-pointer">
                <div class="flex items-center space-x-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3v18h18M9 17V9m4 8V5m4 12v-6" />
                    </svg>
                    <div>
                        <h2 class="text-xl font-semibold mb-1">Relatório</h2>
                        <p class="text-gray-600 text-sm">Visualizar registros bipados por lote.</p>
                    </div>
                </div>
            </a>
        <?php elseif ($perfil === 'conferencia'): ?>
            <a href="relatorio.php" class="p-6 bg-white rounded-lg shadow hover:shadow-lg transition transform hover:scale-105 cursor-pointer">
                <div class="flex items-center space-x-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3v18h18M9 17V9m4 8V5m4 12v-6" />
                    </svg>
                    <div>
                        <h2 class="text-xl font-semibold mb-1">Relatório</h2>
                        <p class="text-gray-600 text-sm">Conferência de lotes bipados.</p>
                    </div>
                </div>
            </a>
        <?php endif; ?>
    </main>
<?php endif; ?>



<script>
    function toggleMenu() {
        document.getElementById('menuDropdown').classList.toggle('hidden');
    }

    function toggleDropdown(id) {
        const el = document.getElementById(id);
        if (el.classList.contains('hidden')) {
            el.classList.remove('hidden');
        } else {
            el.classList.add('hidden');
        }
    }

    document.addEventListener('click', function (e) {
        const menu = document.getElementById('menuDropdown');
        const cadastro = document.getElementById('cadastroMenu');
        if (!e.target.closest('#menuDropdown') && !e.target.closest('button')) {
            menu.classList.add('hidden');
        }
        if (!e.target.closest('#cadastroMenu') && !e.target.closest('button')) {
            cadastro.classList.add('hidden');
        }
    });

    setTimeout(() => {
        const saudacao = document.getElementById('saudacao');
        if (saudacao) {
            saudacao.style.opacity = '0';
            setTimeout(() => saudacao.remove(), 2000);
        }
    }, 4000);
</script>

</body>
</html>
