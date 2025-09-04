<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['usuario'])) {
  echo json_encode(['ok'=>false,'mensagem'=>'Sessão expirada']); exit;
}

try {
  // aceita POST (mais confiável em alguns hosts)
  $q = isset($_POST['q']) ? trim((string)$_POST['q']) : '';
  if (strlen($q) < 3) {
    echo json_encode(['ok'=>true, 'itens'=>[]]); exit;
  }

  $dataTrabalho = $_SESSION['data_trabalho'] ?? date('Y-m-d');

  // Busca por trecho (LIKE %q%) apenas na data ativa — últimos 20
  $stmt = $conn->prepare("
    SELECT codigo, TIME(horario) AS hora
      FROM bipagens
     WHERE DATE(horario) = :d
       AND codigo LIKE :q
     ORDER BY horario DESC
     LIMIT 20
  ");
  $stmt->execute([
    ':d' => $dataTrabalho,
    ':q' => '%'.$q.'%'
  ]);

  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  echo json_encode(['ok'=>true, 'itens'=>$rows, 'data'=>$dataTrabalho]);
} catch (Throwable $e) {
  echo json_encode(['ok'=>false, 'mensagem'=>'Falha na busca']);
}
