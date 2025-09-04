<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json; charset=utf-8');

/**
 * Últimos bipes (sucessos) para a data de trabalho ativa — máx 4 itens.
 */
try {
  if (empty($_SESSION['usuario'])) {
    echo json_encode(['ok'=>false, 'mensagem'=>'Sessão expirada.']); exit;
  }

  $dataTrabalho = $_SESSION['data_trabalho'] ?? date('Y-m-d');
  $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  $sql = "
    SELECT
      DATE_FORMAT(MAX(m.horario), '%H:%i') AS hora,
      c.codigo
    FROM movimentos m
    JOIN codigos c ON c.id = m.codigo_id
    WHERE DATE(m.horario) = :data
    GROUP BY m.codigo_id, c.codigo
    ORDER BY MAX(m.horario) DESC
    LIMIT 4
  ";

  $st = $conn->prepare($sql);
  $st->execute([':data' => $dataTrabalho]);
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode(['ok'=>true,'itens'=>$rows]);

} catch (Throwable $e) {
  echo json_encode(['ok'=>false,'mensagem'=>$e->getMessage()]);
}
