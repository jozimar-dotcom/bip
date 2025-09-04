<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json; charset=utf-8');

/**
 * Últimos bipes (sucessos) da data informada — máx 4 itens.
 * Prioridade:
 *   1) $_GET['date'] (YYYY-MM-DD) válido
 *   2) $_SESSION['data_trabalho']
 *   3) date('Y-m-d')
 */
try {
  if (empty($_SESSION['usuario'])) {
    echo json_encode(['ok'=>false, 'mensagem'=>'Sessão expirada.']); exit;
  }

  $dataTrabalho = $_GET['date'] ?? ($_SESSION['data_trabalho'] ?? date('Y-m-d'));

  $dt = DateTime::createFromFormat('Y-m-d', (string)$dataTrabalho);
  if (!$dt || $dt->format('Y-m-d') !== $dataTrabalho) {
    $dataTrabalho = date('Y-m-d');
  }

  $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  $sql = "
    SELECT
      c.codigo,
      DATE_FORMAT(MAX(m.horario), '%H:%i') AS hora
    FROM movimentos m
    JOIN codigos c ON c.id = m.codigo_id
    WHERE m.data_trabalho = :data
    GROUP BY m.codigo_id, c.codigo
    ORDER BY MAX(m.horario) DESC
    LIMIT 4
  ";

  $st = $conn->prepare($sql);
  $st->execute([':data' => $dataTrabalho]);
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode(['ok'=>true,'itens'=>$rows], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  echo json_encode(['ok'=>false,'mensagem'=>'Erro ao carregar recentes.']);
}
