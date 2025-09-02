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

require_once '../config/config.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Cadastro de Bips - MULTCABOS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex flex-col min-h-screen">

<?php include_once '../includes/header.php'; ?>
<?php include_once '../includes/voltar_dashboard.php'; ?>

<main class="flex-grow flex items-center justify-center px-4">
    <div class="bg-white shadow-lg rounded-xl p-8 w-full max-w-md text-center">
        <img src="logo.png" alt="Logo Multcabos" class="mx-auto mb-4 h-12">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Aponte o leitor de código de barras</h2>
        <form id="form_codigo">
            <input type="text" id="codigo_barras" name="codigo_barras" placeholder="Insira o código" autocomplete="off" autofocus
                   class="w-full border border-gray-300 rounded-md px-4 py-3 text-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            <div id="mensagem" class="mt-4 text-sm font-medium"></div>
        </form>
    </div>
</main>

<footer class="bg-white text-center text-sm py-4 mt-auto shadow-inner">
    <p class="text-gray-500">© 2025 <strong>MULTCABOS</strong> | Desenvolvido por <strong>Infolondrina</strong></p>
</footer>

<audio id="sucessoSound" src="sucesso.mp3" preload="auto"></audio>
<audio id="erroSound" src="error.mp3" preload="auto"></audio>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const input = document.getElementById('codigo_barras');
        const mensagem = document.getElementById('mensagem');
        const sucessoSound = document.getElementById('sucessoSound');
        const erroSound = document.getElementById('erroSound');

        input.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                let codigo = input.value.trim();

                // Validação de tamanho
                if (codigo.length < 10 || codigo.length > 20) {
                    erroSound.pause();
                    erroSound.currentTime = 0;
                    erroSound.play();

                    alert('❌ O código deve ter entre 10 e 20 caracteres.');
                    input.value = '';
                    input.focus();
                    return;
                }

                fetch('processa_bip.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'codigo_barras=' + encodeURIComponent(codigo)
                })
                    .then(response => response.json())
                    .then(data => {
                        mensagem.textContent = data.mensagem;
                        mensagem.className = 'mt-4 text-sm font-medium ' +
                            (data.status === 'success' ? 'text-green-600' : 'text-red-600');

                        input.value = '';
                        input.focus();

                        if (data.status === 'success') {
                            sucessoSound.pause();
                            sucessoSound.currentTime = 0;
                            sucessoSound.play();
                        } else if (data.status === 'error') {
                            erroSound.pause();
                            erroSound.currentTime = 0;
                            erroSound.play();
                        }

                        if (data.status === 'redirect') {
                            erroSound.pause();
                            erroSound.currentTime = 0;
                            erroSound.play();

                            setTimeout(() => {
                                window.location.href = data.location;
                            }, 300);
                        }
                    })
                    .catch(() => {
                        erroSound.pause();
                        erroSound.currentTime = 0;
                        erroSound.play();
                        alert('Erro ao tentar registrar. Tente novamente.');
                        input.value = '';
                        input.focus();
                    });
            }
        });
    });
</script>

</body>
</html>
