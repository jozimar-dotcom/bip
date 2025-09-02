<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['usuario']) || $_SESSION['perfil'] !== 'admin') {
    header("Location: relatorio.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);

    $stmt = $conn->prepare("DELETE FROM bipagens WHERE id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        header("Location: relatorio.php?msg=ok");
    } else {
        header("Location: relatorio.php?msg=erro");
    }
    exit();
}

header("Location: relatorio.php");
exit();
