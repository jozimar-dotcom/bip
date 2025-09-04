<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['usuario'])) {
  echo json_encode(['ok'=>false,'mensagem'=>'Sessão expirada.']); exit;
}

$usuarioLogin   = $_SESSION['usuario'];
$usuarioId      = (int)($_SESSION['id'] ?? 0);
$etapaPermitida = strtolower($_SESSION['etapa_permitida'] ?? $_SESSION['perfil'] ?? 'estoque');
$dataTrabalho   = $_SESSION['data_trabalho'] ?? date('Y-m-d');

$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);
$codigo = trim($payload['codigo'] ?? '');

if ($codigo === '' || strlen($codigo) < 12 || strlen($codigo) > 20) {
  echo json_encode(['ok'=>false,'mensagem'=>'O código deve ter entre 12 e 20 caracteres.']); exit;
}

// Helpers
function etapaOrdem(string $et): int {
  $map = ['estoque'=>1,'embalagem'=>2,'conferencia'=>3];
  return $map[$et] ?? 0;
}
function proximaEtapaFila(string $fila): string {
  switch ($fila) {
    case 'estoque':     return 'embalagem';
    case 'embalagem':   return 'conferencia';
    case 'conferencia': return 'conferencia';
    default:            return 'embalagem';
  }
}

try {
  /** @var PDO $conn */
  $conn->beginTransaction();

  // 1) Obtém (ou cria) id do código
  $stmt = $conn->prepare("SELECT id FROM codigos WHERE codigo = ?");
  $stmt->execute([$codigo]);
  $codigoId = $stmt->fetchColumn();
  if (!$codigoId) {
    $stmt = $conn->prepare("INSERT INTO codigos (codigo) VALUES (?)");
    $stmt->execute([$codigo]);
    $codigoId = (int)$conn->lastInsertId();
  }

  // 2) Busca movimentos do DIA ATIVO
  $sqlMov = "
    SELECT m.id, m.etapa, m.horario, u.usuario AS usuario
    FROM movimentos m
    JOIN usuarios u ON u.id = m.usuario_id
    WHERE m.codigo_id = ? AND DATE(m.horario) = ?
    ORDER BY m.horario ASC
  ";
  $stmt = $conn->prepare($sqlMov);
  $stmt->execute([$codigoId, $dataTrabalho]);
  $movs = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // Monta estado atual (fila) e timeline
  $temEstoque = false; $temEmbalagem = false; $temConferencia = false;
  $infoEstoque = null; $infoEmbalagem = null; $infoConferencia = null;

  foreach ($movs as $m) {
    if ($m['etapa']==='estoque')     { $temEstoque=true;     $infoEstoque=$m; }
    if ($m['etapa']==='embalagem')   { $temEmbalagem=true;   $infoEmbalagem=$m; }
    if ($m['etapa']==='conferencia') { $temConferencia=true; $infoConferencia=$m; }
  }

  // Fila atual = última etapa realizada
  $filaAtual = 'estoque';
  if     ($temConferencia) $filaAtual = 'conferencia';
  elseif ($temEmbalagem)   $filaAtual = 'embalagem';
  elseif ($temEstoque)     $filaAtual = 'estoque';
  else                     $filaAtual = 'estoque';

  // Próxima etapa VALIDADA
  $proxEsperada = proximaEtapaFila($filaAtual);

  // 3) Verifica se a tentativa do usuário está de acordo com a etapa permitida + fluxo
  $tentativa = $etapaPermitida; // etapa do usuário (perfil)

  // Regras: não pode pular etapas; tentativa tem que ser a próxima esperada
  if ($tentativa !== $proxEsperada) {
    // montar modal
    $timeline = [
      'estoque'     => $infoEstoque     ? ['usuario'=>$infoEstoque['usuario'],'hora'=>substr($infoEstoque['horario'], 11, 5)] : null,
      'embalagem'   => $infoEmbalagem   ? ['usuario'=>$infoEmbalagem['usuario'],'hora'=>substr($infoEmbalagem['horario'], 11, 5)] : null,
      'conferencia' => $infoConferencia ? ['usuario'=>$infoConferencia['usuario'],'hora'=>substr($infoConferencia['horario'], 11, 5)] : null,
    ];
    $msg = "Fluxo inválido: após " . ucfirst($filaAtual) . ", a próxima etapa é " . ucfirst($proxEsperada) . ".";
    $conn->rollBack();
    echo json_encode([
      'ok' => false,
      'mensagem' => $msg,
      'modal' => [
        'codigo'   => $codigo,
        'erro'     => $msg,
        'timeline' => $timeline,
        'status'   => [
          'fila'    => ucfirst($filaAtual),
          'proxima' => ucfirst($proxEsperada),
        ],
      ],
    ]);
    exit;
  }

  // 4) Caso esteja correto, insere movimento
  $stmt = $conn->prepare("INSERT INTO movimentos (codigo_id, etapa, usuario_id) VALUES (?,?,?)");
  $stmt->execute([$codigoId, $tentativa, $usuarioId]);

  $conn->commit();

  // Você pode determinar fechamento de lote aqui (se tiver lógica), por enquanto false
  echo json_encode([
    'ok' => true,
    'mensagem' => 'Registrado com sucesso.',
    'loteFechado' => false
  ]);
  exit;

}catch(Throwable $e){
  if ($conn->inTransaction()) $conn->rollBack();
  echo json_encode(['ok'=>false,'mensagem'=>'Erro no servidor.']); exit;
}
