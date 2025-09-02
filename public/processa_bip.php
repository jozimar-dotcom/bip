<?php
session_start();
header('Content-Type: application/json');
require_once '../config/config.php';

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['status' => 'error', 'mensagem' => 'Usuário não autenticado.']);
    exit;
}

$codigo = $_POST['codigo_barras'] ?? '';
$usuario = $_SESSION['usuario'] ?? '';

// Validação de tamanho
if (strlen($codigo) < 10 || strlen($codigo) > 20) {
    echo json_encode(['status' => 'error', 'mensagem' => 'O código deve ter entre 10 e 20 caracteres.']);
    exit;
}

// Verifica duplicidade
$stmt = $conn->prepare("SELECT * FROM bipagens WHERE codigo = :codigo LIMIT 1");
$stmt->execute(['codigo' => $codigo]);
$duplicado = $stmt->fetch(PDO::FETCH_ASSOC);

if ($duplicado) {
    // Conta quantos códigos existem antes do código duplicado para calcular o lote
    $stmtLote = $conn->prepare("SELECT COUNT(*) as posicao FROM bipagens WHERE id <= :id AND DATE(horario) = CURDATE()");
    $stmtLote->execute(['id' => $duplicado['id']]);
    $posicao = $stmtLote->fetch(PDO::FETCH_ASSOC)['posicao'];

    $lote = ceil($posicao / 10);

    $_SESSION['erro_duplicado'] = ['codigo' => $codigo, 'lote' => $lote];

    echo json_encode(['status' => 'redirect', 'location' => 'lista_erro_lotes.php']);
    exit;
}

// Inserindo novo código
$sqlInsert = "INSERT INTO bipagens (codigo, usuario) VALUES (:codigo, :usuario)";
$stmtInsert = $conn->prepare($sqlInsert);
if ($stmtInsert->execute(['codigo' => $codigo, 'usuario' => $usuario])) {
    echo json_encode(['status' => 'success', 'mensagem' => '✅ Código registrado com sucesso!']);
} else {
    echo json_encode(['status' => 'error', 'mensagem' => 'Erro ao tentar registrar o código.']);
}
