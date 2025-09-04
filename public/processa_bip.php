<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json; charset=utf-8');

function jexit(array $p){ echo json_encode($p); exit; }

if (empty($_SESSION['usuario'])) {
  jexit(['ok'=>false,'mensagem'=>'Sessão expirada. Faça login novamente.']);
}

function etapaSessao(): string {
  $raw = strtolower($_SESSION['etapa'] ?? $_SESSION['perfil'] ?? 'estoque');
  if ($raw === 'user') $raw = 'estoque';
  return in_array($raw, ['estoque','embalagem','conferencia'], true) ? $raw : 'estoque';
}
function dataAtiva(): string {
  return $_SESSION['data_trabalho'] ?? date('Y-m-d');
}
function usuarioId(PDO $pdo, string $usuario): ?int {
  $st = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = ? LIMIT 1");
  $st->execute([$usuario]);
  $r = $st->fetch(PDO::FETCH_ASSOC);
  return $r ? (int)$r['id'] : null;
}
function codigoId(PDO $pdo, string $codigo, string $dataTrabalho): int {
  $st = $pdo->prepare("SELECT id FROM codigos WHERE codigo = ? LIMIT 1");
  $st->execute([$codigo]);
  $r = $st->fetch(PDO::FETCH_ASSOC);
  if ($r) return (int)$r['id'];

  $ins = $pdo->prepare("INSERT INTO codigos (codigo, data_trabalho) VALUES (?, ?)");
  $ins->execute([$codigo, $dataTrabalho]);
  return (int)$pdo->lastInsertId();
}
function etapasFeitas(PDO $pdo, int $codigoId, string $dataTrabalho): array {
  $st = $pdo->prepare("
    SELECT m.etapa, u.usuario, m.horario
    FROM movimentos m
    LEFT JOIN usuarios u ON u.id = m.usuario_id
    WHERE m.codigo_id = ? AND m.data_trabalho = ?
    ORDER BY m.horario ASC
  ");
  $st->execute([$codigoId, $dataTrabalho]);
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);

  $timeline = [
    'estoque'     => ['usuario'=>null,'hora'=>null],
    'embalagem'   => ['usuario'=>null,'hora'=>null],
    'conferencia' => ['usuario'=>null,'hora'=>null],
  ];
  $ordem = [];
  foreach ($rows as $r) {
    $e = $r['etapa'];
    if (isset($timeline[$e]) && $timeline[$e]['hora'] === null) {
      $timeline[$e] = [
        'usuario' => $r['usuario'] ?: null,
        'hora'    => $r['horario'] ? date('H:i', strtotime($r['horario'])) : null,
      ];
      $ordem[] = $e;
    }
  }
  return [$timeline, array_values(array_unique($ordem))];
}
function proximaEDepois(array $ordemFeita): array {
  $seq = ['estoque','embalagem','conferencia'];
  if (empty($ordemFeita)) return ['estoque','embalagem'];
  $last = end($ordemFeita);
  $idx  = array_search($last, $seq, true);
  if ($idx === false) return ['estoque','embalagem'];

  $next = $seq[$idx+1] ?? null;
  $after= $seq[$idx+2] ?? null;
  return [$next, $after];
}

$payload = json_decode(file_get_contents('php://input'), true);
$codigo  = isset($payload['codigo']) ? trim((string)$payload['codigo']) : '';

if ($codigo === '' || strlen($codigo) < 12 || strlen($codigo) > 20) {
  jexit(['ok'=>false,'mensagem'=>'O código deve ter entre 12 e 20 caracteres.']);
}

$etapaAtual   = etapaSessao();
$dataTrabalho = dataAtiva();
$usuarioNome  = (string)$_SESSION['usuario'];

try {
  $uid = usuarioId($conn, $usuarioNome);
  if (!$uid) jexit(['ok'=>false,'mensagem'=>'Usuário não encontrado.']);

  $codigoId = codigoId($conn, $codigo, $dataTrabalho);

  [$timeline, $ordemFeita] = etapasFeitas($conn, $codigoId, $dataTrabalho);
  [$filaAtual, $proxDepois] = proximaEDepois($ordemFeita);

  // já existe mesma etapa hoje?
  $stDup = $conn->prepare("SELECT COUNT(*) c FROM movimentos WHERE codigo_id = ? AND data_trabalho = ? AND etapa = ?");
  $stDup->execute([$codigoId, $dataTrabalho, $etapaAtual]);
  $jaTemEtapa = (int)$stDup->fetchColumn();

  if ($jaTemEtapa > 0) {
    jexit([
      'ok'=>false,
      'mensagem'=>'Esta etapa já foi registrada para este código.',
      'showModal'=>true,
      'modal'=>[
        'codigo' => $codigo,
        'timeline' => $timeline,
        'fila_atual' => $filaAtual,
        'proxima_etapa' => $proxDepois
      ]
    ]);
  }

  // fora de ordem
  if ($filaAtual !== null && $etapaAtual !== $filaAtual) {
    $ultima = $ordemFeita ? end($ordemFeita) : 'início';
    jexit([
      'ok'=>false,
      'mensagem'=>"Fluxo inválido: após ".ucfirst($ultima).", a próxima etapa é ".ucfirst($filaAtual).".",
      'showModal'=>true,
      'modal'=>[
        'codigo' => $codigo,
        'timeline' => $timeline,
        'fila_atual' => $filaAtual,
        'proxima_etapa' => $proxDepois
      ]
    ]);
  }

  // insere movimento válido
  $ins = $conn->prepare("INSERT INTO movimentos (codigo_id, etapa, usuario_id, data_trabalho) VALUES (?, ?, ?, ?)");
  $ins->execute([$codigoId, $etapaAtual, $uid, $dataTrabalho]);

  jexit([
    'ok'=>true,
    'mensagem'=>'Registrado com sucesso.',
    'loteFechado'=>false
  ]);

} catch (Throwable $e) {
  jexit(['ok'=>false,'mensagem'=>'Erro no servidor.']);
}
