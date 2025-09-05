<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json; charset=utf-8');

function jexit(array $p){ echo json_encode($p, JSON_UNESCAPED_UNICODE); exit; }

if (empty($_SESSION['usuario'])) {
  jexit(['ok'=>false,'mensagem'=>'Sessão expirada. Faça login novamente.']);
}

function etapaSessao(): string {
  $raw = strtolower($_SESSION['etapa'] ?? $_SESSION['perfil'] ?? 'estoque');
  if ($raw === 'user') $raw = 'estoque';
  return in_array($raw, ['estoque','embalagem','conferencia'], true) ? $raw : 'estoque';
}
function usuarioId(PDO $pdo, string $usuario): ?int {
  $st = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = ? LIMIT 1");
  $st->execute([$usuario]);
  $r = $st->fetch(PDO::FETCH_ASSOC);
  return $r ? (int)$r['id'] : null;
}
function codigoId(PDO $pdo, string $codigo, string $dataTrabalho): int {
  // chave lógica por (codigo, data_trabalho)
  $st = $pdo->prepare("SELECT id FROM codigos WHERE codigo = ? AND data_trabalho = ? LIMIT 1");
  $st->execute([$codigo, $dataTrabalho]);
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

/**
 * Retorna [filaAtual, proximaDepois].
 * - Se nada feito:  estoque, depois embalagem
 * - Se terminou conferência: fila = 'coleta' (Disponível para coleta), proximaDepois = null
 */
function proximaEDepois(array $ordemFeita): array {
  $seq = ['estoque','embalagem','conferencia'];
  if (empty($ordemFeita)) return ['estoque','embalagem'];
  $last = end($ordemFeita);
  $idx  = array_search($last, $seq, true);
  if ($idx === false) return ['estoque','embalagem'];

  // se já concluiu a última etapa conhecida
  if ($last === 'conferencia') {
    return ['coleta', null]; // “Disponível para coleta”
  }

  $next  = $seq[$idx+1] ?? 'coleta';
  $after = $seq[$idx+2] ?? null;
  return [$next, $after];
}

$payload   = json_decode(file_get_contents('php://input'), true);
$codigo    = isset($payload['codigo']) ? trim((string)$payload['codigo']) : '';
$workDate  = isset($payload['workDate']) ? (string)$payload['workDate'] : ($_SESSION['data_trabalho'] ?? date('Y-m-d'));

$MIN = 10; $MAX = 20;
if ($codigo === '' || strlen($codigo) < $MIN || strlen($codigo) > $MAX) {
  jexit(['ok'=>false,'mensagem'=>"O código deve ter entre {$MIN} e {$MAX} caracteres."]);
}
$dt = DateTime::createFromFormat('Y-m-d', $workDate);
if (!$dt || $dt->format('Y-m-d') !== $workDate) {
  $workDate = date('Y-m-d');
}
$_SESSION['data_trabalho'] = $workDate;

$etapaAtual   = etapaSessao();
$usuarioNome  = (string)$_SESSION['usuario'];

try {
  $uid = usuarioId($conn, $usuarioNome);
  if (!$uid) jexit(['ok'=>false,'mensagem'=>'Usuário não encontrado.']);

  $codigoId = codigoId($conn, $codigo, $workDate);

  [$timeline, $ordemFeita] = etapasFeitas($conn, $codigoId, $workDate);
  [$filaAtual, $proxDepois] = proximaEDepois($ordemFeita);

  // mensagens específicas de pré-requisito
  if ($etapaAtual === 'embalagem' && empty($timeline['estoque']['hora'])) {
    jexit([
      'ok'=>false,
      'mensagem'=>'Não há registro de bip do Estoque para este código.',
      'showModal'=>true,
      'modal'=>[
        'codigo'=>$codigo, 'timeline'=>$timeline,
        'fila_atual'=>'estoque', 'proxima_etapa'=>'embalagem',
        'data'=>$workDate
      ]
    ]);
  }
  if ($etapaAtual === 'conferencia') {
    if (empty($timeline['estoque']['hora'])) {
      jexit([
        'ok'=>false,
        'mensagem'=>'Não há registro de bip do Estoque para este código.',
        'showModal'=>true,
        'modal'=>[
          'codigo'=>$codigo, 'timeline'=>$timeline,
          'fila_atual'=>'estoque', 'proxima_etapa'=>'embalagem',
          'data'=>$workDate
        ]
      ]);
    }
    if (empty($timeline['embalagem']['hora'])) {
      jexit([
        'ok'=>false,
        'mensagem'=>'Não há registro de bip da Embalagem para este código.',
        'showModal'=>true,
        'modal'=>[
          'codigo'=>$codigo, 'timeline'=>$timeline,
          'fila_atual'=>'embalagem', 'proxima_etapa'=>'conferencia',
          'data'=>$workDate
        ]
      ]);
    }
  }

  // se todas as etapas já foram concluídas (fila = coleta)
  if ($filaAtual === 'coleta') {
    jexit([
      'ok'=>false,
      'mensagem'=>'Código concluído — já está “Disponível para coleta”.',
      'showModal'=>true,
      'modal'=>[
        'codigo'=>$codigo, 'timeline'=>$timeline,
        'fila_atual'=>'coleta', 'proxima_etapa'=>'coleta',
        'data'=>$workDate
      ]
    ]);
  }

  // já existe a mesma etapa nesse dia?
  $stDup = $conn->prepare("SELECT COUNT(*) c FROM movimentos WHERE codigo_id = ? AND data_trabalho = ? AND etapa = ?");
  $stDup->execute([$codigoId, $workDate, $etapaAtual]);
  $jaTemEtapa = (int)$stDup->fetchColumn();

  if ($jaTemEtapa > 0) {
    jexit([
      'ok'=>false,
      'mensagem'=>'Esta etapa já foi registrada para este código.',
      'showModal'=>true,
      'modal'=>[
        'codigo'=>$codigo, 'timeline'=>$timeline,
        'fila_atual'=>$filaAtual, 'proxima_etapa'=>$proxDepois ?? $filaAtual,
        'data'=>$workDate
      ]
    ]);
  }

  // fora de ordem (ex: tentando Conferência quando fila é Embalagem)
  if ($filaAtual !== null && $etapaAtual !== $filaAtual) {
    $ultima = $ordemFeita ? end($ordemFeita) : 'início';
    jexit([
      'ok'=>false,
      'mensagem'=>"Fluxo inválido: após ".ucfirst($ultima).", a próxima etapa é ".ucfirst($filaAtual).".",
      'showModal'=>true,
      'modal'=>[
        'codigo'=>$codigo, 'timeline'=>$timeline,
        'fila_atual'=>$filaAtual, 'proxima_etapa'=>$proxDepois ?? $filaAtual,
        'data'=>$workDate
      ]
    ]);
  }

  // registra movimento da etapa válida
  $ins = $conn->prepare("
    INSERT INTO movimentos (codigo_id, etapa, usuario_id, data_trabalho, horario)
    VALUES (?,?,?,?,NOW())
  ");
  $ins->execute([$codigoId, $etapaAtual, $uid, $workDate]);

  jexit(['ok'=>true, 'mensagem'=>'Registrado com sucesso.', 'loteFechado'=>false]);

} catch (Throwable $e) {
  jexit(['ok'=>false,'mensagem'=>'Erro no servidor.']);
}
