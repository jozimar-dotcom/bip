<?php
// public/processa_bip.php — grava o bip usando a "data de trabalho"
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

require __DIR__ . '/../config/config.php'; // expõe $conn (PDO)

// 1) Autenticação
if (empty($_SESSION['usuario'])) {
  echo json_encode(['status' => 'error', 'mensagem' => 'Usuário não autenticado.']);
  exit;
}

$usuario = (string)($_SESSION['usuario'] ?? '');

// 2) Entrada
$codigo = trim((string)($_POST['codigo_barras'] ?? ''));

// 3) Data de trabalho (POST > sessão > hoje)
$dataTrabalho = (string)($_POST['data_trabalho'] ?? ($_SESSION['data_trabalho'] ?? date('Y-m-d')));

// Valida formato YYYY-MM-DD; se inválido, volta para hoje
$dt = DateTime::createFromFormat('Y-m-d', $dataTrabalho);
$okFormato = $dt && $dt->format('Y-m-d') === $dataTrabalho;
if (!$okFormato) {
  $dataTrabalho = date('Y-m-d');
}

// Horário final a gravar: usa a HORA atual com a DATA de trabalho escolhida
$horarioParaInsert = $dataTrabalho . ' ' . date('H:i:s');

// 4) Validação do código
$len = strlen($codigo);
if ($len < 10 || $len > 20) {
  echo json_encode(['status' => 'error', 'mensagem' => 'O código deve ter entre 10 e 20 caracteres.']);
  exit;
}

try {
  // 5) Verifica duplicidade (único no sistema todo)
  $stmt = $conn->prepare("SELECT id, codigo, usuario, horario FROM bipagens WHERE codigo = :codigo LIMIT 1");
  $stmt->execute([':codigo' => $codigo]);
  $duplicado = $stmt->fetch();

  if ($duplicado) {
    // Calcula o lote do DUPLICADO usando a data do próprio registro duplicado
    $stmtLote = $conn->prepare(
      "SELECT COUNT(*) AS posicao
         FROM bipagens
        WHERE DATE(horario) = DATE(:horario_dup)
          AND id <= :id_dup"
    );
    $stmtLote->execute([
      ':horario_dup' => $duplicado['horario'],
      ':id_dup'      => $duplicado['id'],
    ]);
    $posicao = (int)($stmtLote->fetch()['posicao'] ?? 0);
    $lote    = max(1, (int)ceil($posicao / 10));

    $_SESSION['erro_duplicado'] = [
      'codigo' => $codigo,
      'lote'   => $lote,
    ];

    echo json_encode(['status' => 'redirect', 'location' => 'lista_erro_lotes.php']);
    exit;
  }

  // 6) Insere novo bip (com a data de trabalho no campo `horario`)
  $stmtInsert = $conn->prepare(
    "INSERT INTO bipagens (codigo, usuario, horario)
     VALUES (:codigo, :usuario, :horario)"
  );

  $ok = $stmtInsert->execute([
    ':codigo'  => $codigo,
    ':usuario' => $usuario,
    ':horario' => $horarioParaInsert,
  ]);

  if ($ok) {
    echo json_encode(['status' => 'success', 'mensagem' => '✅ Código registrado com sucesso!']);
  } else {
    echo json_encode(['status' => 'error', 'mensagem' => 'Erro ao tentar registrar o código.']);
  }
} catch (Throwable $e) {
  echo json_encode(['status' => 'error', 'mensagem' => 'Falha inesperada ao registrar o código.']);
}
