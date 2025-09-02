<?php
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}
$perfil = $_SESSION['perfil'] ?? 'user';
if ($perfil !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

require_once '../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $usuario = $_POST['usuario'] ?? '';
    $perfil = $_POST['perfil'] ?? '';
    $senha = $_POST['senha'] ?? '';
    $confirmar = $_POST['confirmar_senha'] ?? '';

    if ($senha !== $confirmar) {
        header("Location: editar_usuario.php?id=$id&erro=1");
        exit();
    }

    try {
        if (!empty($senha)) {
            $hash = password_hash($senha, PASSWORD_DEFAULT);
            $sql = "UPDATE usuarios SET usuario = :usuario, perfil = :perfil, senha = :senha WHERE usuario = :id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':senha', $hash);
        } else {
            $sql = "UPDATE usuarios SET usuario = :usuario, perfil = :perfil WHERE usuario = :id";
            $stmt = $conn->prepare($sql);
        }

        $stmt->bindParam(':usuario', $usuario);
        $stmt->bindParam(':perfil', $perfil);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        header("Location: editar_usuario.php?id=$usuario&sucesso=1");
        exit();
    } catch (PDOException $e) {
        die("Erro ao atualizar: " . $e->getMessage());
    }
} else {
    header("Location: usuarios.php");
    exit();
}
