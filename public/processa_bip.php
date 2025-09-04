<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['usuario']) || empty($_SESSION['id'])) {
  echo json_encode(['ok'=>false,'mensagem'=>'Sessão expirada. Faça login novamente.']); exit;
}

$usuarioLogin = $_SESSION['usuario'];
$usuarioId    = (int)$_SESSION['id'];

// etapa real usada na regra de fluxo (normaliza)
$rawEtapa = strtolower($_SESSION['etapa_permitida'] ?? $_SESSION['perfil'] ?? 'estoque');
$map      = ['user' => 'estoque', 'admin' => 'estoque'];
$etapaPermitida = $map[$rawEtapa] ?? $rawEtapa;
if (!in_array($etapaPermitida, ['estoque','embalagem','conferencia'], true)) {
  $etapaPermitida = 'estoque';
}

$dataTrabalho = $_SESSION['data_trabalho'] ?? date('Y-m-d');

// lê JSON
$payload = json_decode(file_get_contents('php://input'), true);
$codigo  = trim((string)($payload['codigo'] ?? ''));

// validação
if ($codigo === '' || strlen($codigo) < 12 || strlen($codigo) > 20) {
  echo json_encode(['ok'=>false,'mensagem'=>'O código deve ter entre 12 e 20 caracteres.']); exit;
}

try {
  $conn->beginTransaction();

  // 1) garante existência do codigo na tabela codigos (dia ativo)
  // tenta inserir; se já existir (unique), pega o id
  $stmt = $conn->prepare("INSERT INTO codigos (codigo, data_trabalho) VALUES (?, ?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)");
  $stmt->execute([$codigo, $dataTrabalho]);
  $codigoId = (int)$conn->lastInsertId();

  if ($codigoId === 0) {
    // fallback: busca id
    $stmt = $conn->prepare("SELECT id FROM codigos WHERE codigo=? AND data_trabalho=?");
    $stmt->execute([$codigo, $dataTrabalho]);
    $codigoId = (int)($stmt->fetchColumn() ?: 0);
  }
  if ($codigoId === 0) {
    $conn->rollBack();
    echo json_encode(['ok'=>false,'mensagem'=>'Falha ao registrar código do dia.']); exit;
  }

  // 2) carrega histórico de movimentos do código (no dia)
  $stmt = $conn->prepare("
    SELECT m.etapa, m.horario, u.usuario AS usuario_nome
      FROM movimentos m
      JOIN usuarios u ON u.id = m.usuario_id
     WHERE m.codigo_id = ?
     ORDER BY m.horario ASC
  ");
  $stmt->execute([$codigoId]);
  $movs = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // helper para saber quais etapas já passaram
  $tem = ['estoque'=>null,'embalagem'=>null,'conferencia'=>null];
  foreach ($movs as $m) {
    if (isset($tem[$m['etapa']]) && $tem[$m['etapa']] === null) {
      $tem[$m['etapa']] = $m; // guarda 1ª ocorrência
    }
  }

  // ordem válida
  $ordem = ['estoque','embalagem','conferencia'];
  // próxima etapa esperada
  $prox = null;
  if (!$tem['estoque'])          $prox = 'estoque';
  elseif (!$tem['embalagem'])    $prox = 'embalagem';
  elseif (!$tem['conferencia'])  $prox = 'conferencia';
  else                           $prox = null; // já conferido

  // Se já conferido, qualquer tentativa gera modal “já concluído”
  if ($prox === null) {
    $modal = montarModalFluxo($codigo, $tem, 'O código já foi conferido (processo concluído).', 'Conferência', '—');
    $conn->commit();
    echo json_encode(['ok'=>false,'mensagem'=>'Código já está concluído (Conferência).','modal'=>$modal]);
    exit;
  }

  // Se etapa tentada é igual à próxima esperada → registra
  if ($etapaPermitida === $prox) {
    // evita duplo registro na mesma etapa
    $stmt = $conn->prepare("SELECT 1 FROM movimentos WHERE codigo_id=? AND etapa=? LIMIT 1");
    $stmt->execute([$codigoId, $etapaPermitida]);
    if ($stmt->fetch()) {
      $modal = montarModalFluxo($codigo, $tem, 'Esta etapa já foi registrada para este código.', etapaLabel($etapaPermitida), etapaLabel($prox));
      $conn->commit();
      echo json_encode(['ok'=>false,'mensagem'=>'Etapa já registrada para este código.','modal'=>$modal]); exit;
    }

    $stmt = $conn->prepare("INSERT INTO movimentos (codigo_id, etapa, usuario_id) VALUES (?,?,?)");
    $stmt->execute([$codigoId, $etapaPermitida, $usuarioId]);

    $conn->commit();
    echo json_encode(['ok'=>true,'mensagem'=>'Registrado com sucesso.','loteFechado'=>false]);
    exit;
  }

  // Caso contrário, fluxo inválido → monta modal
  $mensagem = "Fluxo inválido: após " . etapaLabel($proxAnterior = etapaAnterior($prox)) . ", a próxima etapa é " . etapaLabel($prox) . ".";
  $modal    = montarModalFluxo($codigo, $tem, $mensagem, etapaLabel($prox), etapaLabel($prox));
  $conn->commit();
  echo json_encode(['ok'=>false,'mensagem'=>$mensagem,'modal'=>$modal]);
  exit;

} catch (Throwable $e) {
  if ($conn->inTransaction()) $conn->rollBack();
  echo json_encode(['ok'=>false,'mensagem'=>'Erro interno: '.$e->getMessage()]);
  exit;
}

/* ================= Helpers ================= */

function etapaLabel(string $e): string {
  return [
    'estoque'     => 'Estoque',
    'embalagem'   => 'Embalagem',
    'conferencia' => 'Conferência',
  ][$e] ?? ucfirst($e);
}
function etapaAnterior(?string $prox): ?string {
  if ($prox === 'embalagem') return 'estoque';
  if ($prox === 'conferencia') return 'embalagem';
  return null;
}
function montarModalFluxo(string $codigo, array $tem, string $mensagem, string $filaAtual, string $proximaEtapa): array {
  $timeline = [];
  // Estoque
  $timeline[] = [
    'etapa'=>'estoque','etapaLabel'=>'Estoque',
    'status' => $tem['estoque'] ? 'ok' : 'pendente',
    'usuario'=> $tem['estoque']['usuario_nome'] ?? '—',
    'hora'   => isset($tem['estoque']['horario']) ? substr($tem['estoque']['horario'],11,5) : '—'
  ];
  // Embalagem
  $timeline[] = [
    'etapa'=>'embalagem','etapaLabel'=>'Embalagem',
    'status' => $tem['embalagem'] ? 'ok' : 'pendente',
    'usuario'=> $tem['embalagem']['usuario_nome'] ?? '—',
    'hora'   => isset($tem['embalagem']['horario']) ? substr($tem['embalagem']['horario'],11,5) : '—'
  ];
  // Conferência
  $timeline[] = [
    'etapa'=>'conferencia','etapaLabel'=>'Conferência',
    'status' => $tem['conferencia'] ? 'ok' : 'pendente',
    'usuario'=> $tem['conferencia']['usuario_nome'] ?? '—',
    'hora'   => isset($tem['conferencia']['horario']) ? substr($tem['conferencia']['horario'],11,5) : '—'
  ];

  return [
    'mensagem'     => $mensagem,
    'codigo'       => $codigo,
    'timeline'     => $timeline,
    'filaAtual'    => $filaAtual,
    'proximaEtapa' => $proximaEtapa,
  ];
}
