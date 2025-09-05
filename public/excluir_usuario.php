<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../config/config.php';

if (empty($_SESSION['usuario']) || ($_SESSION['perfil'] ?? '') !== 'admin') { header('HTTP/1.1 403'); echo 'Acesso negado.'; exit; }

$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { echo 'ID inválido.'; exit; }

if ((int)($_SESSION['id'] ?? 0) === $id) { echo 'Você não pode excluir a si mesmo.'; exit; }

$st = $conn->prepare("SELECT usuario FROM usuarios WHERE id=?");
$st->execute([$id]);
if (!$st->fetch()) { echo 'Usuário não encontrado.'; exit; }

$del = $conn->prepare("DELETE FROM usuarios WHERE id=?");
$del->execute([$id]);

header('Location: usuarios.php');
